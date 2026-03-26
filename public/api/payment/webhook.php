<?php
/**
 * Payment Webhook Handler
 * Receives callbacks from payment providers (Airwallex, Alipay, WeChat)
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../src/config/database.php';
require_once __DIR__ . '/../../../src/Payment/PaymentFactory.php';

use TrustShield\Payment\PaymentFactory;

// Get webhook payload
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

// Log webhook for debugging and audit
$provider = detectProvider($data);
logWebhook($provider, $payload, $data);

try {
    $database = new Database();
    $conn = $database->connect();

    // Handle different provider webhooks
    switch ($provider) {
        case 'airwallex':
            handleAirwallexWebhook($conn, $data);
            break;
        
        case 'alipay':
        case 'wechat':
            // For direct integrations, implement provider-specific handling
            // For now, these use the factory pattern
            handleProviderWebhook($conn, $provider, $data);
            break;
        
        default:
            // Unknown provider, still log but return 400
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown provider']);
            exit;
    }

    http_response_code(200);
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("Webhook processing failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Processing failed']);
}

/**
 * Detect payment provider from webhook payload
 */
function detectProvider(array $data): string
{
    // Airwallex webhooks have 'name' field with event type
    if (isset($data['name']) && strpos($data['name'], 'payment_intent') !== false) {
        return 'airwallex';
    }
    
    // Alipay webhooks typically have 'notify_type' or 'trade_status'
    if (isset($data['notify_type']) || isset($data['trade_status'])) {
        return 'alipay';
    }
    
    // WeChat Pay webhooks have 'event_type' or 'resource'
    if (isset($data['event_type']) && strpos($data['event_type'], 'TRANSACTION') !== false) {
        return 'wechat';
    }
    
    return 'unknown';
}

/**
 * Log webhook for audit trail
 */
function logWebhook(string $provider, string $payload, array $data)
{
    try {
        $database = new Database();
        $conn = $database->connect();
        
        $eventType = $data['name'] ?? $data['event_type'] ?? 'unknown';
        $orderId = extractOrderId($provider, $data);
        
        $stmt = $conn->prepare("
            INSERT INTO payment_webhook_logs (provider, event_type, payload, order_id, processed)
            VALUES (:provider, :event_type, :payload, :order_id, 0)
        ");
        
        $stmt->execute([
            ':provider' => $provider,
            ':event_type' => $eventType,
            ':payload' => $payload,
            ':order_id' => $orderId
        ]);
    } catch (Exception $e) {
        error_log("Failed to log webhook: " . $e->getMessage());
    }
}

/**
 * Extract order ID from webhook data
 */
function extractOrderId(string $provider, array $data): string
{
    switch ($provider) {
        case 'airwallex':
            return $data['data']['object']['request_id'] ?? '';
        
        case 'alipay':
            return $data['out_trade_no'] ?? '';
        
        case 'wechat':
            return $data['resource']['out_trade_no'] ?? '';
        
        default:
            return '';
    }
}

/**
 * Handle Airwallex webhook
 */
function handleAirwallexWebhook($conn, array $data)
{
    $provider = PaymentFactory::create('alipay'); // Any method using Airwallex
    $result = $provider->handleWebhook($data);
    
    if ($result['verified'] && $result['status'] === 'paid') {
        processPaymentSuccess($conn, $result['order_id'], $result['transaction_id']);
    }
    
    // Mark webhook as processed
    markWebhookProcessed($conn, $data);
}

/**
 * Handle generic provider webhook
 */
function handleProviderWebhook($conn, string $provider, array $data)
{
    $paymentProvider = PaymentFactory::create($provider);
    $result = $paymentProvider->handleWebhook($data);
    
    if ($result['verified'] && $result['status'] === 'paid') {
        processPaymentSuccess($conn, $result['order_id'], $result['transaction_id']);
    }
}

/**
 * Process successful payment
 */
function processPaymentSuccess($conn, string $orderId, string $transactionId)
{
    // Update payment status
    $stmt = $conn->prepare("
        UPDATE payments 
        SET status = 'paid', 
            paid_at = NOW(),
            transaction_id = :transaction_id
        WHERE order_id = :order_id AND status = 'pending'
    ");
    
    $stmt->execute([
        ':order_id' => $orderId,
        ':transaction_id' => $transactionId
    ]);
    
    if ($stmt->rowCount() > 0) {
        // Get payment details for VPN activation
        $selectStmt = $conn->prepare("
            SELECT * FROM payments WHERE order_id = :order_id
        ");
        $selectStmt->execute([':order_id' => $orderId]);
        $payment = $selectStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($payment) {
            activateVPN($conn, $payment);
        }
    }
}

/**
 * Activate VPN subscription after payment
 */
function activateVPN($conn, array $payment)
{
    try {
        $userId = $payment['user_id'];
        $plan = $payment['plan_name'];
        
        // Check existing subscription
        $checkStmt = $conn->prepare("
            SELECT id, status FROM subscriptions 
            WHERE user_id = :user_id AND service_type = 'vpn'
            ORDER BY id DESC LIMIT 1
        ");
        $checkStmt->execute([':user_id' => $userId]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        $startDate = date('Y-m-d H:i:s');
        $expiryDate = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        if ($existing) {
            // Extend existing subscription
            $updateStmt = $conn->prepare("
                UPDATE subscriptions 
                SET status = 'active', 
                    plan = :plan,
                    expiry_date = :expiry_date,
                    start_date = :start_date
                WHERE id = :id
            ");
            $updateStmt->execute([
                ':plan' => $plan,
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
                ':user_id' => $userId,
                ':plan' => $plan,
                ':start_date' => $startDate,
                ':expiry_date' => $expiryDate
            ]);
        }
        
        // Create VPN account if needed
        require_once __DIR__ . '/../../../src/VPN/VPNService.php';
        
        $vpnService = new \TrustShield\VPN\VPNService($conn);
        $account = $vpnService->getUserAccount($userId);
        
        if (!$account) {
            $vpnService->createAccount($userId, null, $plan);
        }
        
        error_log("VPN activated for user {$userId}, plan: {$plan}");
        
    } catch (Exception $e) {
        error_log("VPN activation failed: " . $e->getMessage());
    }
}

/**
 * Mark webhook as processed
 */
function markWebhookProcessed($conn, array $data)
{
    $orderId = extractOrderId(detectProvider($data), $data);
    
    $stmt = $conn->prepare("
        UPDATE payment_webhook_logs 
        SET processed = 1 
        WHERE order_id = :order_id 
        ORDER BY id DESC 
        LIMIT 1
    ");
    
    $stmt->execute([':order_id' => $orderId]);
}
