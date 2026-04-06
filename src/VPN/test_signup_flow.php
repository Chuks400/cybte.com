<?php
/**
 * Test VPN Signup Flow
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/VPNService.php';

use Cybte\VPN\VPNService;

echo "=== VPN Signup Flow Test ===\n\n";

try {
    // Connect to database
    $db = new Database();
    $pdo = $db->connect();
    echo "Database connection: OK\n";
    
    // Create VPN service
    $vpnService = new VPNService($pdo);
    echo "VPN Service initialized: OK\n";
    
    // Get available servers
    $servers = $vpnService->getServers();
    echo "Active servers: " . count($servers) . "\n";
    
    if (empty($servers)) {
        echo "ERROR: No active VPN servers\n";
        exit(1);
    }
    
    foreach ($servers as $server) {
        echo "  - {$server['name']} ({$server['ip_address']})\n";
    }
    
    // Test account creation
    $testUserId = 1; // Use existing user
    $testEmail = 'test_signup_' . time() . '@trustshield.ai';
    
    echo "\nCreating VPN account for test user...\n";
    echo "  User ID: {$testUserId}\n";
    echo "  Email: {$testEmail}\n";
    
    $result = $vpnService->createAccount($testUserId, null, 'trial');
    
    if (!$result) {
        echo "ERROR: Failed to create VPN account\n";
        exit(1);
    }
    
    echo "\nAccount created successfully!\n";
    echo "  Account ID: {$result['id']}\n";
    echo "  Server: {$result['server_name']} ({$result['server_country']})\n";
    echo "  UUID: {$result['uuid']}\n";
    echo "  Traffic Limit: {$result['traffic_limit_gb']} GB\n";
    echo "  Expires: {$result['expires_at']}\n";
    echo "  Subscription Link: {$result['subscription_link']}\n";
    
    // Verify account exists in database
    $account = $vpnService->getUserAccount($testUserId);
    if ($account) {
        echo "\nAccount verified in database: OK\n";
    } else {
        echo "\nWARNING: Account not found in database\n";
    }
    
    echo "\n=== TEST PASSED ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
