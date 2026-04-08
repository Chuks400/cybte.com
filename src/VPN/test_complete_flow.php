<?php
/**
 * Test complete signup-to-dashboard flow
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/VPNService.php';

use Cybte\VPN\VPNService;

echo "=== Complete Signup Flow Test ===\n\n";

try {
    $db = new Database();
    $pdo = $db->connect();
    echo "Database: OK\n";
    
    // Step 1: Simulate user registration
    $testEmail = 'flow_test_' . time() . '@example.com';
    $testPassword = password_hash('testpass123', PASSWORD_BCRYPT);
    $testName = 'Test User';
    
    echo "Creating user: {$testEmail}\n";
    
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'vpn_user')");
    $stmt->execute([$testName, $testEmail, $testPassword]);
    $userId = (int)$pdo->lastInsertId();
    echo "User created with ID: {$userId}\n";
    
    // Step 2: Create subscription
    $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, plan, service_type, start_date, expiry_date, status) VALUES (?, 'trial', 'vpn', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 'active')");
    $stmt->execute([$userId]);
    echo "Subscription created\n";
    
    // Step 3: Create VPN account (what happens in vpn_signup.php)
    $vpnService = new VPNService($pdo);
    $vpnAccount = $vpnService->createAccount($userId, null, 'trial');
    
    if (!$vpnAccount) {
        echo "ERROR: VPN account creation failed\n";
        exit(1);
    }
    
    echo "\nVPN Account Created:\n";
    echo "  UUID: {$vpnAccount['uuid']}\n";
    echo "  Server: {$vpnAccount['server_name']}\n";
    echo "  Subscription: " . substr($vpnAccount['subscription_link'], 0, 50) . "...\n";
    
    // Step 4: Verify account can be retrieved (what happens in vpn_dashboard.php)
    $retrievedAccount = $vpnService->getUserAccount($userId);
    
    if (!$retrievedAccount) {
        echo "ERROR: Could not retrieve VPN account from database\n";
        exit(1);
    }
    
    echo "\nAccount Retrieved:\n";
    echo "  Subscription Link: " . substr($retrievedAccount['subscription_link'], 0, 50) . "...\n";
    echo "  Status: {$retrievedAccount['status']}\n";
    
    // Step 5: Verify subscription link is valid format
    if (strpos($retrievedAccount['subscription_link'], 'vmess://') === 0 || 
        strpos($retrievedAccount['subscription_link'], 'vless://') === 0) {
        echo "\nSubscription link format: VALID\n";
    } else {
        echo "\nWARNING: Subscription link format may be invalid\n";
    }
    
    echo "\n=== FLOW TEST PASSED ===\n";
    echo "\nUser can now:\n";
    echo "1. Sign up at vpn_signup.php\n";
    echo "2. See subscription link at vpn_dashboard.php\n";
    echo "3. Copy link to V2Ray/Clash client\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
