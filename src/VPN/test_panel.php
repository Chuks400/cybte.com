<?php
/**
 * 3x-ui Panel Connection Test Script
 * Run this to verify your 3x-ui panel API is accessible
 */

require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/ThreeXUIAPI.php';

header('Content-Type: text/plain');

echo "========================================\n";
echo "TRUSTSHIELD VPN - 3x-ui Panel Test\n";
echo "========================================\n\n";

// Get database connection
$database = new Database();
try {
    $conn = $database->connect();
    echo "✓ Database connection: SUCCESS\n\n";
} catch (Throwable $e) {
    echo "✗ Database connection: FAILED\n";
    echo "  Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Load VPN servers from database
$stmt = $conn->query("SELECT * FROM vpn_servers WHERE status = 'active' AND panel_type = '3x-ui'");
$servers = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($servers)) {
    echo "⚠ No active 3x-ui servers found in database.\n";
    echo "\nPlease run the migration first:\n";
    echo "  database/migrations/add_3xui_support.sql\n";
    echo "\nThen add your server with:\n";
    echo "INSERT INTO vpn_servers (name, location, country, flag, panel_type, api_url, api_username, api_password, inbound_id, status)\n";
    echo "VALUES ('USA-1', 'New York', 'US', '🇺🇸', '3x-ui', 'http://your-server:54321/RANDOM_PATH/', 'admin', 'your-password', 1, 'active');\n";
    exit(1);
}

echo "Found " . count($servers) . " server(s):\n";
echo str_repeat("-", 50) . "\n\n";

foreach ($servers as $server) {
    echo "Testing: {$server['name']} ({$server['location']})\n";
    echo "  URL: {$server['api_url']}\n";

    // Validate credentials
    $username = $server['api_username'] ?? null;
    $password = $server['api_password'] ?? null;

    if (!$username || !$password) {
        echo "  ✗ Missing API credentials in database\n";
        echo "  Please ensure api_username and api_password are set.\n\n";
        continue;
    }

    // Initialize API client
    $api = new ThreeXUIAPI(
        $server['api_url'],
        $username,
        $password,
        $server['inbound_id'] ?? 1
    );

    // Test connection
    echo "  Testing connection... ";
    $connected = $api->testConnection();
    
    // Debug: show what was attempted
    echo "\n  [Debug] Base URL: {$server['api_url']}\n";
    echo "  [Debug] Root URL: " . $api->getRootUrlForDebug() . "\n";
    echo "  [Debug] Last HTTP: " . ($api->getLastHttpCode() ?? 'N/A') . "\n";
    if ($api->getLastError()) {
        echo "  [Debug] Error: " . $api->getLastError() . "\n";
    }
    
    if ($connected) {
        echo "✓ SUCCESS\n";

        // Get inbound info
        $inbound = $api->getInbound($server['inbound_id'] ?? 1);
        if ($inbound) {
            echo "  Inbound: {$inbound['protocol']}://{$inbound['port']}\n";
            echo "  Protocol: {$inbound['protocol']}\n";
        } else {
            echo "  ⚠ Inbound not found\n";
        }
    } else {
        echo "✗ FAILED\n";
        $lastErr = $api->getLastError();
        $lastCode = $api->getLastHttpCode();
        $lastResp = $api->getLastResponse();
        if ($lastErr) {
            echo "  Last error: {$lastErr}\n";
        }
        if ($lastCode !== null) {
            echo "  Last HTTP code: {$lastCode}\n";
        }
        if ($lastResp) {
            echo "  Last response (first 800 chars):\n";
            echo $lastResp . "\n";
        }
        echo "  Check:\n";
        echo "    - Is the panel running?\n";
        echo "    - Is the API URL correct?\n";
        echo "    - Are username/password correct?\n";
        echo "    - Is firewall blocking the port?\n";
    }

    echo "\n";
}

echo str_repeat("=", 50) . "\n";
echo "Test complete.\n";
echo "\nNext steps:\n";
echo "1. If all tests passed, signup at: public/vpn_signup.php\n";
echo "2. Real VPN accounts will be created automatically\n";
echo "3. Check dashboard to see subscription links\n";