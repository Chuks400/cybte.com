<?php
/**
 * Payment Retry API
 * Allows users to retry a failed or expired payment
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../src/config/database.php';
require_once __DIR__ . '/../../../src/Payment/PaymentFactory.php';

use TrustShield\Payment\PaymentFactory;

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$orderId = $input['order_id'] ?? '';

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing order_id']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->connect();

    // Get the existing payment
    $stmt = $conn->prepare("
        SELECT * FROM payments 
        WHERE order_id = :order_id AND user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute([
        ':order_id' => $orderId,
        ':user_id' => $userId
    ]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Payment not found']);
        exit;
    }

    // Only allow retry for failed or expired payments
    if ($payment['status'] === 'paid') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Payment already completed']);
        exit;
    }

    // Check if payment is too old (older than 24 hours)
    $createdTime = strtotime($payment['created_at']);
    if (time() - $createdTime > 86400 && $payment['status'] === 'pending') {
        // Mark old payment as expired
        $updateStmt = $conn->prepare("
            UPDATE payments SET status = 'expired' WHERE order_id = :order_id
        ");
        $updateStmt->execute([':order_id' => $orderId]);
    }

    // Generate new order ID for retry
    $newOrderId = 'TS_' . time() . '_' . rand(1000, 9999);

    // Create new payment with the same details
    $paymentProvider = PaymentFactory::create($payment['method']);
    $paymentData = $paymentProvider->create(
        $newOrderId, 
        floatval($payment['amount']), 
        "TrustShield VPN - {$payment['plan_name']} (Retry)"
    );

    // Save new payment to database
    $insertStmt = $conn->prepare("
        INSERT INTO payments (order_id, user_id, plan_name, amount, currency, method, status, qr_code, retry_of)
        VALUES (:order_id, :user_id, :plan_name, :amount, 'CNY', :method, 'pending', :qr_code, :retry_of)
    ");

    $insertStmt->execute([
        ':order_id' => $newOrderId,
        ':user_id' => $userId,
        ':plan_name' => $payment['plan_name'],
        ':amount' => $payment['amount'],
        ':method' => $payment['method'],
        ':qr_code' => $paymentData['qr_url'] ?? '',
        ':retry_of' => $orderId
    ]);

    // Update original payment status to retried
    $updateStmt = $conn->prepare("
        UPDATE payments SET status = 'retried' WHERE order_id = :order_id
    ");
    $updateStmt->execute([':order_id' => $orderId]);

    echo json_encode([
        'success' => true,
        'order_id' => $newOrderId,
        'previous_order' => $orderId,
        'qr_url' => $paymentData['qr_url'],
        'transaction_id' => $paymentData['transaction_id'] ?? null,
        'mode' => $paymentData['mode'] ?? 'fake',
        'message' => 'Payment retry created successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Retry failed: ' . $e->getMessage()
    ]);
}
