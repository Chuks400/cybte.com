<?php
/**
 * Payment Create API
 * Creates a new payment order and generates QR code
 */

// Log errors to file for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../../logs/payment_errors.log');

// Disable error display to prevent breaking JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

// Capture any output buffers
ob_start();

// Log start of request
error_log("[PAYMENT] Create request started - User: " . ($_SESSION['user_id'] ?? 'not logged in'));

try {
    require_once __DIR__ . '/../../../src/config/database.php';
    require_once __DIR__ . '/../../../src/Payment/PaymentInterface.php';
    require_once __DIR__ . '/../../../src/Payment/Alipay.php';
    require_once __DIR__ . '/../../../src/Payment/WeChatPay.php';
    require_once __DIR__ . '/../../../src/Payment/PaymentFactory.php';
    error_log("[PAYMENT] Files loaded successfully");
} catch (Throwable $e) {
    error_log("[PAYMENT] File load error: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load required files: ' . $e->getMessage()]);
    exit;
}

use Cybte\Payment\PaymentFactory;

// Check authentication
if (!isset($_SESSION['user_id'])) {
    error_log("[PAYMENT] Auth failed - no user_id in session");
    http_response_code(401);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}
error_log("[PAYMENT] Auth passed - user_id: " . $_SESSION['user_id']);

$userId = (int)$_SESSION['user_id'];

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
error_log("[PAYMENT] Raw input: " . file_get_contents('php://input'));

if (!$input) {
    error_log("[PAYMENT] Invalid JSON input");
    http_response_code(400);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

$method = $input['method'] ?? '';
$plan = $input['plan'] ?? '';
$amount = floatval($input['amount'] ?? 0);
error_log("[PAYMENT] Parsed input - method: {$method}, plan: {$plan}, amount: {$amount}");

// Validate input
if (!$method || !$plan || $amount <= 0) {
    error_log("[PAYMENT] Validation failed - missing fields");
    http_response_code(400);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Missing required fields: method, plan, amount']);
    exit;
}

// Validate payment method
try {
    $availableMethods = PaymentFactory::getAvailableMethods();
    error_log("[PAYMENT] Available methods: " . implode(', ', $availableMethods));
} catch (Throwable $e) {
    error_log("[PAYMENT] Error getting methods: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Payment system error: ' . $e->getMessage()]);
    exit;
}

if (!PaymentFactory::isSupported($method)) {
    error_log("[PAYMENT] Unsupported method: {$method}");
    http_response_code(400);
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Unsupported payment method. Available: ' . implode(', ', $availableMethods)
    ]);
    exit;
}

error_log("[PAYMENT] Method validated: {$method}");

try {
    // Connect to database
    error_log("[PAYMENT] Connecting to database...");
    $database = new Database();
    $conn = $database->connect();
    error_log("[PAYMENT] Database connected");

    // Generate order ID
    $orderId = 'TS_' . time() . '_' . rand(1000, 9999);
    error_log("[PAYMENT] Generated order ID: {$orderId}");

    // Create payment using the factory
    error_log("[PAYMENT] Creating payment provider for method: {$method}");
    $paymentProvider = PaymentFactory::create($method);
    error_log("[PAYMENT] Payment provider created");

    error_log("[PAYMENT] Calling provider->create()");
    $paymentData = $paymentProvider->create($orderId, $amount, "Cybte VPN - {$plan}");
    error_log("[PAYMENT] Payment data received: " . json_encode($paymentData));

    // Save to database
    error_log("[PAYMENT] Saving to database...");
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
    error_log("[PAYMENT] Saved to database, ID: " . $conn->lastInsertId());

    // Return success response
    ob_clean();
    $response = [
        'success' => true,
        'order_id' => $orderId,
        'qr_url' => $paymentData['qr_url'],
        'transaction_id' => $paymentData['transaction_id'] ?? null,
        'mode' => $paymentData['mode'] ?? 'fake',
        'expires_at' => $paymentData['expires_at'] ?? (time() + 3600)
    ];
    error_log("[PAYMENT] Success response: " . json_encode($response));
    echo json_encode($response);

} catch (Throwable $e) {
    error_log("[PAYMENT] ERROR: " . $e->getMessage());
    error_log("[PAYMENT] Stack trace: " . $e->getTraceAsString());
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Payment creation failed: ' . $e->getMessage()
    ]);
}
