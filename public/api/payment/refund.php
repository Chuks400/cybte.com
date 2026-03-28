<?php
/**
 * Payment Refund API (Admin Only)
 * Process refunds for paid orders
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../src/config/database.php';
require_once __DIR__ . '/../../../src/auth.php';
require_once __DIR__ . '/../../../src/Services/EmailService.php';

use TrustShield\Services\EmailService;

// Check admin authentication
require_role(['admin'], 'vpn_login.php');

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$orderId = $input['order_id'] ?? '';
$refundAmount = floatval($input['amount'] ?? 0);
$reason = $input['reason'] ?? '';

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing order_id']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->connect();

    // Get the payment
    $stmt = $conn->prepare("
        SELECT p.*, u.email, u.name as user_name 
        FROM payments p
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.order_id = :order_id
        LIMIT 1
    ");
    $stmt->execute([':order_id' => $orderId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Payment not found']);
        exit;
    }

    // Only paid payments can be refunded
    if ($payment['status'] !== 'paid') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Only paid payments can be refunded']);
        exit;
    }

    // Check if already refunded
    if ($payment['status'] === 'refunded') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Payment already refunded']);
        exit;
    }

    // Validate refund amount
    $maxRefund = floatval($payment['amount']);
    if ($refundAmount <= 0 || $refundAmount > $maxRefund) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Refund amount must be between 0.01 and {$maxRefund}"]);
        exit;
    }

    // Determine if full or partial refund
    $isFullRefund = ($refundAmount == $maxRefund);
    $newStatus = $isFullRefund ? 'refunded' : 'partially_refunded';

    // Update payment status
    $updateStmt = $conn->prepare("
        UPDATE payments 
        SET status = :status,
            refunded_amount = :refunded_amount,
            refunded_at = NOW(),
            refund_reason = :reason,
            refund_by = :admin_id
        WHERE order_id = :order_id
    ");

    $updateStmt->execute([
        ':status' => $newStatus,
        ':refunded_amount' => $refundAmount,
        ':reason' => $reason,
        ':admin_id' => $_SESSION['user_id'],
        ':order_id' => $orderId
    ]);

    // If full refund, deactivate subscription
    if ($isFullRefund) {
        $deactivateStmt = $conn->prepare("
            UPDATE subscriptions 
            SET status = 'cancelled', 
                expiry_date = NOW()
            WHERE user_id = :user_id 
            AND plan = :plan 
            AND service_type = 'vpn'
            AND status = 'active'
        ");
        $deactivateStmt->execute([
            ':user_id' => $payment['user_id'],
            ':plan' => $payment['plan_name']
        ]);
    }

    // Send refund email
    try {
        $emailService = new EmailService();
        $emailService->sendRefundConfirmation($payment, [
            'email' => $payment['email'],
            'name' => $payment['user_name']
        ], $refundAmount);
    } catch (Exception $e) {
        error_log("Failed to send refund email: " . $e->getMessage());
    }

    // Log refund
    $logStmt = $conn->prepare("
        INSERT INTO payment_webhook_logs (provider, event_type, payload, order_id, processed)
        VALUES ('admin', 'manual_refund', :payload, :order_id, 1)
    ");
    $logStmt->execute([
        ':payload' => json_encode([
            'refund_amount' => $refundAmount,
            'reason' => $reason,
            'admin_id' => $_SESSION['user_id'],
            'timestamp' => date('Y-m-d H:i:s')
        ]),
        ':order_id' => $orderId
    ]);

    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'refund_amount' => $refundAmount,
        'status' => $newStatus,
        'message' => $isFullRefund ? 'Full refund processed successfully' : 'Partial refund processed successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Refund failed: ' . $e->getMessage()
    ]);
}
