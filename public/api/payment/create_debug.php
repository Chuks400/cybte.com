<?php
/**
 * Debug version of create.php - Shows detailed error info
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain');

echo "=== PAYMENT CREATE DEBUG ===\n\n";

// Step 1: Session
echo "Step 1: Checking session...\n";
session_start();
if (isset($_SESSION['user_id'])) {
    echo "✓ Session active, user_id: " . $_SESSION['user_id'] . "\n";
    $userId = (int)$_SESSION['user_id'];
} else {
    echo "✗ NO SESSION - user not logged in\n";
    echo "Session data: " . print_r($_SESSION, true) . "\n";
    exit;
}

// Step 2: Load files
echo "\nStep 2: Loading required files...\n";
$files = [
    __DIR__ . '/../../../src/config/database.php',
    __DIR__ . '/../../../src/Payment/PaymentFactory.php',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✓ Found: " . basename($file) . "\n";
        try {
            require_once $file;
            echo "  ✓ Loaded successfully\n";
        } catch (Throwable $e) {
            echo "  ✗ Load error: " . $e->getMessage() . "\n";
            exit;
        }
    } else {
        echo "✗ Missing: " . $file . "\n";
        exit;
    }
}

// Step 3: Check classes
echo "\nStep 3: Checking classes...\n";
$classes = ['Database', 'Cybte\Payment\PaymentFactory'];
foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "✓ Class exists: {$class}\n";
    } else {
        echo "✗ Class NOT found: {$class}\n";
        // Show all declared classes containing relevant names
        $all = get_declared_classes();
        $found = array_filter($all, function($c) use ($class) {
            return stripos($c, 'database') !== false || stripos($c, 'payment') !== false;
        });
        if ($found) {
            echo "  Found related: " . implode(', ', $found) . "\n";
        }
    }
}

// Step 4: Get input
echo "\nStep 4: Getting request data...\n";
$raw = file_get_contents('php://input');
echo "Raw input: " . $raw . "\n";

$input = json_decode($raw, true);
if ($input) {
    echo "✓ JSON decoded: " . print_r($input, true) . "\n";
} else {
    echo "✗ JSON decode failed\n";
    exit;
}

// Step 5: Validate
echo "\nStep 5: Validating input...\n";
$method = $input['method'] ?? '';
$plan = $input['plan'] ?? '';
$amount = floatval($input['amount'] ?? 0);

echo "method: {$method}, plan: {$plan}, amount: {$amount}\n";

if (!$method || !$plan || $amount <= 0) {
    echo "✗ Validation failed\n";
    exit;
}
echo "✓ Input valid\n";

// Step 6: Check payment method
echo "\nStep 6: Checking payment method...\n";
try {
    $available = Cybte\Payment\PaymentFactory::getAvailableMethods();
    echo "Available methods: " . implode(', ', $available) . "\n";
    
    if (Cybte\Payment\PaymentFactory::isSupported($method)) {
        echo "✓ Method '{$method}' is supported\n";
    } else {
        echo "✗ Method '{$method}' not supported\n";
        exit;
    }
} catch (Throwable $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit;
}

// Step 7: Database connection
echo "\nStep 7: Connecting to database...\n";
try {
    $database = new Database();
    $conn = $database->connect();
    echo "✓ Database connected\n";
} catch (Throwable $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit;
}

// Step 8: Create payment
echo "\nStep 8: Creating payment...\n";
try {
    $orderId = 'TS_' . time() . '_' . rand(1000, 9999);
    echo "Generated order ID: {$orderId}\n";
    
    $paymentProvider = Cybte\Payment\PaymentFactory::create($method);
    echo "✓ Payment provider created\n";
    
    $paymentData = $paymentProvider->create($orderId, $amount, "Cybte VPN - {$plan}");
    echo "✓ Payment data: " . print_r($paymentData, true) . "\n";
} catch (Throwable $e) {
    echo "✗ Payment creation error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit;
}

// Step 9: Save to database
echo "\nStep 9: Saving to database...\n";
try {
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
    echo "✓ Saved to database, ID: " . $conn->lastInsertId() . "\n";
} catch (Throwable $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit;
}

// Success
echo "\n=== SUCCESS ===\n";
$response = [
    'success' => true,
    'order_id' => $orderId,
    'qr_url' => $paymentData['qr_url'],
    'mode' => $paymentData['mode'] ?? 'fake',
];
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
