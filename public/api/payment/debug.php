<?php
/**
 * Debug API - Check payment system status
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$errors = [];
$info = [];

// Check PHP version
$info[] = "PHP Version: " . PHP_VERSION;

// Check required files
$files = [
    '../../../src/config/database.php',
    '../../../src/Payment/PaymentFactory.php',
    '../../../src/Payment/Alipay.php',
    '../../../src/Payment/WeChatPay.php',
    '../../../src/Payment/PaymentInterface.php',
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $info[] = "✓ Found: " . basename($file);
    } else {
        $errors[] = "✗ Missing: " . basename($file);
    }
}

// Try to load classes
try {
    require_once __DIR__ . '/../../../src/config/database.php';
    $info[] = "✓ Database class loaded";
} catch (Throwable $e) {
    $errors[] = "Database load error: " . $e->getMessage();
}

try {
    require_once __DIR__ . '/../../../src/Payment/PaymentFactory.php';
    $info[] = "✓ PaymentFactory loaded";
} catch (Throwable $e) {
    $errors[] = "PaymentFactory load error: " . $e->getMessage();
}

// Check class exists
if (class_exists('Cybte\Payment\PaymentFactory')) {
    $info[] = "✓ Class Cybte\Payment\PaymentFactory exists";
    try {
        $methods = Cybte\Payment\PaymentFactory::getAvailableMethods();
        $info[] = "Available methods: " . implode(', ', $methods);
    } catch (Throwable $e) {
        $errors[] = "getAvailableMethods error: " . $e->getMessage();
    }
} else {
    $errors[] = "✗ Class Cybte\Payment\PaymentFactory NOT FOUND";
    // List declared classes
    $declared = get_declared_classes();
    $paymentClasses = array_filter($declared, function($c) {
        return stripos($c, 'payment') !== false;
    });
    if ($paymentClasses) {
        $info[] = "Found payment-related classes: " . implode(', ', $paymentClasses);
    }
}

// Check database
try {
    $db = new Database();
    $conn = $db->connect();
    $info[] = "✓ Database connected";
    
    // Check tables
    $tables = ['payments', 'payment_plans', 'payment_webhook_logs'];
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            $info[] = "✓ Table exists: {$table}";
        } else {
            $errors[] = "✗ Table missing: {$table}";
        }
    }
} catch (Throwable $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

// Session check
session_start();
if (isset($_SESSION['user_id'])) {
    $info[] = "✓ Session active, user_id: " . $_SESSION['user_id'];
} else {
    $info[] = "ℹ No session (user not logged in)";
}

echo json_encode([
    'success' => empty($errors),
    'errors' => $errors,
    'info' => $info,
    'timestamp' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT);
