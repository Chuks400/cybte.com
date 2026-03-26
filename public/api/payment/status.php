<?php
/**
 * Payment Status API
 * Check the status of a payment order
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
$orderId = $_GET['order_id'] ?? '';

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing order_id parameter']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->connect();

    // Get payment record
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

    // If status is pending and we have a transaction ID, check with provider
    if ($payment['status'] === 'pending' && $payment['transaction_id']) {
        try {
            $provider = PaymentFactory::create($payment['method']);
            $providerStatus = $provider->getStatus($payment['transaction_id']);
            
            // If provider says paid, update our database
            if ($providerStatus === 'paid' && $payment['status'] !== 'paid') {
                $updateStmt = $conn->prepare("
                    UPDATE payments 
                    SET status = 'paid', paid_at = NOW() 
                    WHERE order_id = :order_id
                ");
                $updateStmt->execute([':order_id' => $orderId]);
                
                // Activate VPN subscription
                activateVPNSubscription($conn, $payment);
                
                $payment['status'] = 'paid';
            }
        } catch (Exception $e) {
            // Log error but return current status
            error_log("Payment status check failed: " . $e->getMessage());
        }
    }

    // For fake mode, allow simulation via query parameter (for testing only)
    if (($payment['method'] === 'alipay' || $payment['method'] === 'wechat') 
        && isset($_GET['simulate_paid']) 
        && $_GET['simulate_paid'] === '1') {
        
        $provider = PaymentFactory::create($payment['method']);
        if ($provider->verify(['simulate_paid' => true])) {
            $updateStmt = $conn->prepare("
                UPDATE payments 
                SET status = 'paid', paid_at = NOW() 
                WHERE order_id = :order_id
            ");
            $updateStmt->execute([':order_id' => $orderId]);
            
            // Activate VPN subscription
            activateVPNSubscription($conn, $payment);
            
            $payment['status'] = 'paid';
        }
    }

    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'status' => $payment['status'],
        'amount' => floatval($payment['amount']),
        'currency' => $payment['currency'],
        'method' => $payment['method'],
        'plan' => $payment['plan_name'],
        'created_at' => $payment['created_at'],
        'paid_at' => $payment['paid_at']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Status check failed: ' . $e->getMessage()
    ]);
}

/**
 * Activate VPN subscription after successful payment
 */
function activateVPNSubscription($conn, $payment)
{
    try {
        // Check if subscription already exists
        $checkStmt = $conn->prepare("
            SELECT id FROM subscriptions 
            WHERE user_id = :user_id AND plan = :plan AND service_type = 'vpn'
            ORDER BY id DESC LIMIT 1
        ");
        $checkStmt->execute([
            ':user_id' => $payment['user_id'],
            ':plan' => $payment['plan_name']
        ]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

        $startDate = date('Y-m-d H:i:s');
        $expiryDate = date('Y-m-d H:i:s', strtotime('+30 days'));

        if ($existing) {
            // Update existing subscription
            $updateStmt = $conn->prepare("
                UPDATE subscriptions 
                SET status = 'active', expiry_date = :expiry_date, start_date = :start_date
                WHERE id = :id
            ");
            $updateStmt->execute([
                ':expiry_date' => $expiryDate,
                ':start_date' => $startDate,
                ':id' => $existing['id']
            ]);
        } else {
            // Create new subscription
            $insertStmt = $conn->prepare("
                INSERT INTO subscriptions (user_id, plan, service_type, start_date, expiry_date, status)
                VALUES (:user_id, :plan, 'vpn', :start_date, :expiry_date, 'active')
            ");
            $insertStmt->execute([
                ':user_id' => $payment['user_id'],
                ':plan' => $payment['plan_name'],
                ':start_date' => $startDate,
                ':expiry_date' => $expiryDate
            ]);
        }

        // Create VPN account via VPN service if needed
        require_once __DIR__ . '/../../../src/VPN/VPNService.php';
        use TrustShield\VPN\VPNService;
        
        $vpnService = new VPNService($conn);
        $account = $vpnService->getUserAccount($payment['user_id']);
        
        if (!$account) {
            $vpnService->createAccount($payment['user_id'], null, $payment['plan_name']);
        }

    } catch (Exception $e) {
        error_log("VPN activation failed: " . $e->getMessage());
    }
}
