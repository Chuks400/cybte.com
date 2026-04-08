<?php
/**
 * Payment Create API
 * Creates a new payment order and generates QR code
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../src/config/database.php';
require_once __DIR__ . '/../../../src/Payment/PaymentFactory.php';

use Cybte\Payment\PaymentFactory;

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

$method = $input['method'] ?? '';
$plan = $input['plan'] ?? '';
$amount = floatval($input['amount'] ?? 0);

// Validate input
if (!$method || !$plan || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields: method, plan, amount']);
    exit;
}

// Validate payment method
if (!PaymentFactory::isSupported($method)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => 'Unsupported payment method. Available: ' . implode(', ', PaymentFactory::getAvailableMethods())
    ]);
    exit;
}

try {
    // Connect to database
    $database = new Database();
    $conn = $database->connect();

    // Generate order ID
    $orderId = 'TS_' . time() . '_' . rand(1000, 9999);

    // Create payment using the factory
    $paymentProvider = PaymentFactory::create($method);
    $paymentData = $paymentProvider->create($orderId, $amount, "Cybte VPN - {$plan}");

    // Save to database
    $stmt = $conn->prepare("
        INSERT INTO payments (order_id, user_id, plan_name, amount, currency, method, status, qr_code)
        VALUES (:order_id, :user_id, :plan_name, :amount, 'CNY', :method, 'pending', :qr_code)
    ");

    $stmt->execute([
        ':order_id' => $orderId,
        ':user_id' => $userId,
        ':plan_name' => $plan,
        ':amount' => $amount,
        ':method' => $method,
        ':qr_code' => $paymentData['qr_url'] ?? ''
    ]);

    // Return success response
    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'qr_url' => $paymentData['qr_url'],
        'transaction_id' => $paymentData['transaction_id'] ?? null,
        'mode' => $paymentData['mode'] ?? 'fake',
        'expires_at' => $paymentData['expires_at'] ?? (time() + 3600)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Payment creation failed: ' . $e->getMessage()
    ]);
}
