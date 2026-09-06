<?php

declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);
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

function payment_json(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    payment_json(405, ['success' => false, 'error' => 'Method not allowed']);
}

if (empty($_SESSION['user_id'])) {
    payment_json(401, ['success' => false, 'error' => 'Authentication required']);
}

$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verify_csrf(is_string($csrf) ? $csrf : null)) {
    payment_json(403, ['success' => false, 'error' => 'Invalid request token']);
}

if (!security_rate_limit('payment_create', 8, 900)) {
    payment_json(429, ['success' => false, 'error' => 'Too many payment attempts. Please wait and try again.']);
}

$input = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($input)) {
    payment_json(400, ['success' => false, 'error' => 'Invalid JSON request']);
}

$method = strtolower(trim((string)($input['method'] ?? '')));
$planKey = strtolower(trim((string)($input['plan'] ?? '')));

if (!preg_match('/^[a-z0-9_-]{1,50}$/', $planKey) || !PaymentFactory::isSupported($method)) {
    payment_json(400, ['success' => false, 'error' => 'Invalid payment method or plan']);
}

try {
    $database = new Database();
    $conn = $database->connect();

    // Never trust a price supplied by the browser. The active plan in the database is authoritative.
    $planStmt = $conn->prepare('SELECT plan_key, name, price_cny FROM payment_plans WHERE plan_key = :plan_key AND is_active = 1 LIMIT 1');
    $planStmt->execute([':plan_key' => $planKey]);
    $plan = $planStmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        payment_json(404, ['success' => false, 'error' => 'Selected plan is not available']);
    }

    $amount = (float)$plan['price_cny'];
    if ($amount <= 0) {
        throw new RuntimeException('Invalid configured plan price.');
    }

    $orderId = 'CYB_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));
    $provider = PaymentFactory::create($method);
    $paymentData = $provider->create($orderId, $amount, 'Cybte VPN - ' . $plan['name']);

    $qrUrl = (string)($paymentData['qr_url'] ?? '');
    $transactionId = isset($paymentData['transaction_id']) ? (string)$paymentData['transaction_id'] : null;

    $stmt = $conn->prepare("INSERT INTO payments (order_id, user_id, plan_name, amount, currency, method, status, qr_code, transaction_id) VALUES (:order_id, :user_id, :plan_name, :amount, 'CNY', :method, 'pending', :qr_code, :transaction_id)");
    $stmt->execute([
        ':order_id' => $orderId,
        ':user_id' => (int)$_SESSION['user_id'],
        ':plan_name' => $planKey,
        ':amount' => $amount,
        ':method' => $method,
        ':qr_code' => $qrUrl,
        ':transaction_id' => $transactionId,
    ]);

    payment_json(200, [
        'success' => true,
        'order_id' => $orderId,
        'plan' => $planKey,
        'amount' => $amount,
        'currency' => 'CNY',
        'qr_url' => $qrUrl,
        'transaction_id' => $transactionId,
        'mode' => (string)($paymentData['mode'] ?? 'provider'),
        'expires_at' => $paymentData['expires_at'] ?? (time() + 900),
    ]);
} catch (Throwable $e) {
    error_log('Payment create error: ' . $e->getMessage());
    payment_json(500, ['success' => false, 'error' => 'Payment could not be created. Please try again later.']);
}
