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

function retry_json(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') retry_json(405, ['success' => false, 'error' => 'Method not allowed']);
if (empty($_SESSION['user_id'])) retry_json(401, ['success' => false, 'error' => 'Authentication required']);
$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verify_csrf(is_string($csrf) ? $csrf : null)) retry_json(403, ['success' => false, 'error' => 'Invalid request token']);
if (!security_rate_limit('payment_retry', 6, 900)) retry_json(429, ['success' => false, 'error' => 'Too many retry attempts.']);

$input = json_decode((string)file_get_contents('php://input'), true);
$orderId = is_array($input) ? trim((string)($input['order_id'] ?? '')) : '';
if ($orderId === '' || strlen($orderId) > 80) retry_json(400, ['success' => false, 'error' => 'Invalid order ID']);

try {
    $db = new Database();
    $conn = $db->connect();
    $stmt = $conn->prepare('SELECT * FROM payments WHERE order_id = :order_id AND user_id = :user_id LIMIT 1');
    $stmt->execute([':order_id' => $orderId, ':user_id' => (int)$_SESSION['user_id']]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$payment) retry_json(404, ['success' => false, 'error' => 'Payment not found']);
    if ($payment['status'] === 'paid') retry_json(409, ['success' => false, 'error' => 'Payment is already complete']);

    $planStmt = $conn->prepare('SELECT plan_key, name, price_cny FROM payment_plans WHERE plan_key = :plan_key AND is_active = 1 LIMIT 1');
    $planStmt->execute([':plan_key' => (string)$payment['plan_name']]);
    $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
    if (!$plan) retry_json(409, ['success' => false, 'error' => 'This plan is no longer available. Start a new purchase.']);

    $method = (string)$payment['method'];
    if (!PaymentFactory::isSupported($method)) retry_json(409, ['success' => false, 'error' => 'This payment method is no longer available.']);

    $newOrderId = 'CYB_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));
    $amount = (float)$plan['price_cny'];
    $provider = PaymentFactory::create($method);
    $paymentData = $provider->create($newOrderId, $amount, 'Cybte VPN - ' . $plan['name'] . ' (Retry)');

    $insert = $conn->prepare("INSERT INTO payments (order_id, user_id, plan_name, amount, currency, method, status, qr_code, transaction_id) VALUES (:order_id, :user_id, :plan_name, :amount, 'CNY', :method, 'pending', :qr_code, :transaction_id)");
    $insert->execute([
        ':order_id' => $newOrderId,
        ':user_id' => (int)$_SESSION['user_id'],
        ':plan_name' => (string)$plan['plan_key'],
        ':amount' => $amount,
        ':method' => $method,
        ':qr_code' => (string)($paymentData['qr_url'] ?? ''),
        ':transaction_id' => $paymentData['transaction_id'] ?? null,
    ]);

    $update = $conn->prepare("UPDATE payments SET status = 'retried' WHERE order_id = :order_id AND user_id = :user_id AND status <> 'paid'");
    $update->execute([':order_id' => $orderId, ':user_id' => (int)$_SESSION['user_id']]);

    retry_json(200, ['success' => true, 'order_id' => $newOrderId, 'qr_url' => (string)($paymentData['qr_url'] ?? ''), 'amount' => $amount, 'currency' => 'CNY', 'mode' => (string)($paymentData['mode'] ?? 'provider')]);
} catch (Throwable $e) {
    error_log('Payment retry error: ' . $e->getMessage());
    retry_json(500, ['success' => false, 'error' => 'Payment retry is temporarily unavailable.']);
}
