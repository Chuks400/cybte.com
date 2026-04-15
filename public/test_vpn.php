<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/VPN/VPNService.php';

use Cybte\VPN\VPNService;

echo "=== VPN Connection Test ===\n\n";

$db = new Database();
$conn = $db->connect();
echo "✓ Database connected\n\n";

$vpnService = new VPNService($conn);

// Test 1: Get servers
echo "1. Getting VPN servers...\n";
$servers = $vpnService->getServers();
echo "   Found " . count($servers) . " server(s)\n";
if ($servers) {
    foreach ($servers as $srv) {
        echo "   - {$srv['name']} ({$srv['ip_address']}) - {$srv['status']}\n";
    }
}
echo "\n";

// Test 2: Check if can create account for user 8 (owner)
$userId = 8; // owner@cybte.com
echo "2. Testing VPN account creation for user $userId...\n";

// First check if account exists
$existing = $vpnService->getUserAccount($userId);
if ($existing) {
    echo "   Account exists: " . print_r($existing, true) . "\n";
} else {
    echo "   No existing account - creating new one...\n";
    $newAccount = $vpnService->createAccount($userId, null, 'trial');
    if ($newAccount) {
        echo "   ✓ Account created!\n";
        echo "   Link: " . $newAccount['subscription_link'] . "\n";
    } else {
        echo "   ✗ Failed to create account\n";
    }
}

echo "\n=== Test Complete ===";
