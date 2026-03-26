<?php
/**
 * Test HTTPS VPN Account Creation
 * Tests the updated VPNService with HTTPS support
 */

require_once __DIR__ . '/../config/database.php';

// Database connection
$db = (new Database())->connect();

// Load VPNService
require_once __DIR__ . '/VPNService.php';

// Initialize VPN Service
$vpnService = new TrustShield\VPN\VPNService($db);

echo "=== Testing HTTPS VPN Account Creation ===\n\n";

// Test 1: Check server configuration
echo "1. Checking VPN Server Configuration:\n";
$servers = $vpnService->getServers();
foreach ($servers as $server) {
    echo "   Server: {$server['name']}\n";
    echo "   IP: {$server['ip_address']}\n";
    echo "   Domain: " . ($server['domain'] ?? 'Not set') . "\n";
    echo "   HTTPS: " . (($server['use_https'] ?? 0) ? 'Enabled' : 'Disabled') . "\n";
    echo "   Panel Port: " . ($server['panel_port'] ?? '54321') . "\n";
    echo "   Web Path: " . ($server['web_base_path'] ?? '/JE2fu7rGygZsRGQwEW/') . "\n";
    echo "   Status: {$server['status']}\n\n";
}

// Test 2: Create test VPN account
echo "2. Creating Test VPN Account:\n";
$testUserId = 1; // Use existing user ID
$serverId = 1;  // Use first server
$plan = 'trial';

$result = $vpnService->createAccount($testUserId, $serverId, $plan);

if ($result) {
    echo "   ✅ VPN Account Created Successfully!\n";
    echo "   Account ID: {$result['id']}\n";
    echo "   Server: {$result['server_name']} ({$result['server_country']})\n";
    echo "   Subscription Link: {$result['subscription_link']}\n";
    echo "   UUID: {$result['uuid']}\n";
    echo "   Client Email: {$result['client_email']}\n";
    echo "   Traffic Limit: {$result['traffic_limit_gb']}GB\n";
    echo "   Expires: {$result['expires_at']}\n\n";
    
    // Test 3: Check if subscription link uses HTTPS
    echo "3. Checking HTTPS Subscription Link:\n";
    $subscriptionLink = $result['subscription_link'];
    if (strpos($subscriptionLink, 'https://') !== false) {
        echo "   ✅ HTTPS enabled in subscription link!\n";
        echo "   Link: $subscriptionLink\n";
    } else {
        echo "   ❌ Still using HTTP in subscription link\n";
        echo "   Link: $subscriptionLink\n";
    }
    
} else {
    echo "   ❌ Failed to create VPN account\n";
}

// Test 4: Get user account
echo "\n4. Retrieving User VPN Account:\n";
$userAccount = $vpnService->getUserAccount($testUserId);
if ($userAccount) {
    echo "   ✅ Account retrieved\n";
    echo "   Subscription: {$userAccount['subscription_link']}\n";
} else {
    echo "   ❌ No account found\n";
}

echo "\n=== Test Complete ===\n";
?>
