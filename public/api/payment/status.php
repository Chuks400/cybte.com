<?php

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../src/security.php';
require_once __DIR__ . '/../../../src/config/database.php';
require_once __DIR__ . '/../../../src/Payment/PaymentInterface.php';
require_once __DIR__ . '/../../../src/Payment/Alipay.php';
require_once __DIR__ . '/../../../src/Payment/WeChatPay.php';
require_once __DIR__ . '/../../../src/Payment/PayPal.php';
require_once __DIR__ . '/../../../src/Payment/PaymentFactory.php';

use Cybte\Payment\PaymentFactory;

security_start_session();

function status_json(int $code, array $payload): never
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit();
}

if (empty($_SESSION['user_id'])) {
    status_json(401, ['success' => false, 'error' => 'Authentication required']);
}

$orderId = trim((string)($_GET['order_id'] ?? ''));
if ($orderId === '' || strlen($orderId) > 80) {
    status_json(400, ['success' => false, 'error' => 'Invalid order ID']);
}

try {
    $database = new Database();
    $conn = $database->connect();
    $stmt = $conn->prepare('SELECT * FROM payments WHERE order_id = :order_id AND user_id = :user_id LIMIT 1');
    $stmt->execute([':order_id' => $orderId, ':user_id' => (int)$_SESSION['user_id']]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        status_json(404, ['success' => false, 'error' => 'Payment not found']);
    }

    if ($payment['status'] === 'pending' && !empty($payment['transaction_id'])) {
        try {
            $provider = PaymentFactory::create((string)$payment['method']);
            $providerStatus = $provider->getStatus((string)$payment['transaction_id']);

            if ($providerStatus === 'paid') {
                $conn->beginTransaction();
                try {
                    $update = $conn->prepare("UPDATE payments SET status = 'paid', paid_at = NOW() WHERE order_id = :order_id AND user_id = :user_id AND status = 'pending'");
                    $update->execute([':order_id' => $orderId, ':user_id' => (int)$_SESSION['user_id']]);
                    if ($update->rowCount() > 0) {
                        activateVpnSubscription($conn, $payment);
                    }
                    $conn->commit();
                    $payment['status'] = 'paid';
                    $payment['paid_at'] = date('Y-m-d H:i:s');
                } catch (Throwable $e) {
                    if ($conn->inTransaction()) {
                        $conn->rollBack();
                    }
                    throw $e;
                }
            } elseif (in_array($providerStatus, ['failed', 'expired'], true)) {
                $update = $conn->prepare('UPDATE payments SET status = :status WHERE order_id = :order_id AND user_id = :user_id AND status = \'pending\'');
                $update->execute([':status' => $providerStatus, ':order_id' => $orderId, ':user_id' => (int)$_SESSION['user_id']]);
                $payment['status'] = $providerStatus;
            }
        } catch (Throwable $e) {
            error_log('Payment provider status check failed: ' . $e->getMessage());
        }
    }

    status_json(200, [
        'success' => true,
        'order_id' => $orderId,
        'status' => (string)$payment['status'],
        'amount' => (float)$payment['amount'],
        'currency' => (string)$payment['currency'],
        'method' => (string)$payment['method'],
        'plan' => (string)$payment['plan_name'],
        'created_at' => $payment['created_at'],
        'paid_at' => $payment['paid_at'],
    ]);
} catch (Throwable $e) {
    error_log('Payment status error: ' . $e->getMessage());
    status_json(500, ['success' => false, 'error' => 'Payment status is temporarily unavailable.']);
}

function activateVpnSubscription(PDO $conn, array $payment): void
{
    $planKey = (string)$payment['plan_name'];
    $planStmt = $conn->prepare('SELECT duration_days FROM payment_plans WHERE plan_key = :plan_key AND is_active = 1 LIMIT 1');
    $planStmt->execute([':plan_key' => $planKey]);
    $duration = (int)($planStmt->fetchColumn() ?: 30);
    $duration = max(1, min($duration, 3660));

    $startDate = date('Y-m-d H:i:s');
    $expiryDate = date('Y-m-d H:i:s', strtotime('+' . $duration . ' days'));

    $check = $conn->prepare("SELECT id FROM subscriptions WHERE user_id = :user_id AND service_type = 'vpn' ORDER BY id DESC LIMIT 1");
    $check->execute([':user_id' => (int)$payment['user_id']]);
    $existingId = $check->fetchColumn();

    if ($existingId) {
        $stmt = $conn->prepare("UPDATE subscriptions SET plan = :plan, status = 'active', start_date = :start_date, expiry_date = :expiry_date WHERE id = :id");
        $stmt->execute([':plan' => $planKey, ':start_date' => $startDate, ':expiry_date' => $expiryDate, ':id' => (int)$existingId]);
    } else {
        $stmt = $conn->prepare("INSERT INTO subscriptions (user_id, plan, service_type, start_date, expiry_date, status) VALUES (:user_id, :plan, 'vpn', :start_date, :expiry_date, 'active')");
        $stmt->execute([':user_id' => (int)$payment['user_id'], ':plan' => $planKey, ':start_date' => $startDate, ':expiry_date' => $expiryDate]);
    }

    // Provisioning happens only after the provider has confirmed payment.
    try {
        require_once __DIR__ . '/../../../src/VPN/VPNService.php';
        $vpnService = new \Cybte\VPN\VPNService($conn);
        if (!$vpnService->getUserAccount((int)$payment['user_id'])) {
            $vpnService->createAccount((int)$payment['user_id'], null, $planKey);
        }
    } catch (Throwable $e) {
        error_log('VPN provisioning after payment failed: ' . $e->getMessage());
    }
}
