<?php
/**
 * Test 3x-ui Database Direct Integration
 */

require_once __DIR__ . '/ThreeXUIDatabaseManager.php';

use Cybte\VPN\ThreeXUIDatabaseManager;

// Database configuration
require_once __DIR__ . '/../config/database.php';

echo "=== 3x-ui Database Direct Test ===\n\n";

try {
    $db = new Database();
    $pdo = $db->connect();
    echo "Database connection: OK\n";
    
    // Get VPN server config
    $stmt = $pdo->query("SELECT * FROM vpn_servers WHERE status = 'active' LIMIT 1");
    $server = $stmt->fetch();
    
    if (!$server) {
        echo "No VPN server configured\n";
        exit(1);
    }
    
    echo "Server: {$server['name']} ({$server['ip_address']})\n";
    
    // SSH key path - adjust if needed
    $sshKeyPath = 'C:\\Users\\John\\.ssh\\id_rsa';
    
    echo "SSH Key: {$sshKeyPath}\n";
    echo "Testing connectivity...\n\n";
    
    // Test SSH connection
    $cmd = sprintf(
        'ssh -i %s -o ConnectTimeout=5 -o StrictHostKeyChecking=no root@%s "echo OK" 2>&1',
        escapeshellarg($sshKeyPath),
        escapeshellarg($server['ip_address'])
    );
    
    exec($cmd, $output, $exitCode);
    
    if ($exitCode !== 0) {
        echo "SSH Connection: FAILED\n";
        echo "Error: " . implode("\n", $output) . "\n";
        exit(1);
    }
    
    echo "SSH Connection: OK\n";
    
    // Test database download
    $manager = new ThreeXUIDatabaseManager(
        $server['ip_address'],
        $sshKeyPath,
        1
    );
    
    echo "\nTesting inbound retrieval...\n";
    echo "Debug: Temp file will be at: " . sys_get_temp_dir() . "\n";
    
    // Test scp directly through PHP first
    $testCmd = 'scp -o StrictHostKeyChecking=no -i ' . escapeshellarg($sshKeyPath) . ' root@' . $server['ip_address'] . ':/etc/x-ui/x-ui.db ' . sys_get_temp_dir() . '\test-php.db 2>&1';
    echo "Debug: Test command: {$testCmd}\n";
    
    exec($testCmd, $testOutput, $testCode);
    echo "Debug: Direct exec test - Exit code: {$testCode}, Output: " . implode(" | ", $testOutput) . "\n";
    
    $inbound = $manager->getInbound();
    
    if (!$inbound) {
        echo "Get Inbound: FAILED\n";
        echo "Error: " . ($manager->getLastError() ?: "No error message (file may not exist or is empty)") . "\n";
        echo "Last Output: " . ($manager->getLastOutput() ?: "N/A") . "\n";
        exit(1);
    }
    
    echo "Get Inbound: OK\n";
    echo "  Protocol: {$inbound['protocol']}\n";
    echo "  Port: {$inbound['port']}\n";
    echo "  Clients: " . count($inbound['settings']['clients'] ?? []) . "\n";
    
    // Test client creation
    $testEmail = 'test_' . time() . '@trustshield.local';
    echo "\nTesting client creation ({$testEmail})...\n";
    
    $result = $manager->createClient($testEmail, 30, 100);
    
    if (!$result) {
        echo "Create Client: FAILED\n";
        echo "Error: " . $manager->getLastError() . "\n";
        exit(1);
    }
    
    echo "Create Client: OK\n";
    echo "  UUID: {$result['uuid']}\n";
    echo "  Subscription: {$result['subscription_link']}\n";
    
    echo "\n=== ALL TESTS PASSED ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
