<?php
/**
 * Test API - Check if payment system is properly configured
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$errors = [];
$warnings = [];
$info = [];

// Test 1: Check if required files exist
$requiredFiles = [
    __DIR__ . '/../../../src/config/database.php',
    __DIR__ . '/../../../src/Payment/PaymentFactory.php',
    __DIR__ . '/../../../src/Payment/Alipay.php',
    __DIR__ . '/../../../src/Payment/WeChatPay.php',
    __DIR__ . '/../../../src/Payment/PaymentInterface.php',
];

foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        $errors[] = "File not found: " . basename($file);
    } else {
        $info[] = "File exists: " . basename($file);
    }
}

// Test 2: Check if classes can be loaded
try {
    require_once __DIR__ . '/../../../src/config/database.php';
    $info[] = "Database class loaded";
} catch (Exception $e) {
    $errors[] = "Database class failed: " . $e->getMessage();
}

try {
    require_once __DIR__ . '/../../../src/Payment/PaymentFactory.php';
    $info[] = "PaymentFactory loaded";
    
    // Check if class exists
    if (!class_exists('Cybte\Payment\PaymentFactory')) {
        $errors[] = "Class Cybte\Payment\PaymentFactory not found";
    } else {
        $info[] = "Class Cybte\Payment\PaymentFactory exists";
        $methods = Cybte\Payment\PaymentFactory::getAvailableMethods();
        $info[] = "Available methods: " . implode(', ', $methods);
    }
} catch (Exception $e) {
    $errors[] = "PaymentFactory failed: " . $e->getMessage();
}

// Test 3: Check database connection
try {
    $db = new Database();
    $conn = $db->connect();
    $info[] = "Database connection successful";
    
    // Check if payments table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'payments'");
    if ($stmt->rowCount() > 0) {
        $info[] = "Payments table exists";
    } else {
        $warnings[] = "Payments table does NOT exist - run migration";
    }
} catch (Exception $e) {
    $errors[] = "Database connection failed: " . $e->getMessage();
}

// Test 4: Check session
try {
    session_start();
    if (isset($_SESSION['user_id'])) {
        $info[] = "Session active - user_id: " . $_SESSION['user_id'];
    } else {
        $warnings[] = "No user session - user not logged in";
    }
} catch (Exception $e) {
    $errors[] = "Session error: " . $e->getMessage();
}

// Return results
echo json_encode([
    'success' => empty($errors),
    'errors' => $errors,
    'warnings' => $warnings,
    'info' => $info,
    'php_version' => PHP_VERSION,
    'timestamp' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT);
