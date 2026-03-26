<?php
require_once 'src/config/database.php';

echo "Testing trustshield_app user...\n";

// Test with environment variable
putenv('DB_USER=trustshield_app');
putenv('DB_PASS=SecurePass123!');

try {
    $db = new Database();
    $pdo = $db->connect();
    echo "SUCCESS: Connected with trustshield_app user\n";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Users in database: " . $result['count'] . "\n";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
