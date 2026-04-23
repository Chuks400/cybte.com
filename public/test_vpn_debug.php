<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/VPN/VPNService.php';

use Cybte\VPN\VPNService;

echo "=== VPN Debug Test ===\n\n";

$db = new Database();
$conn = $db->connect();
echo "Database: OK\n";

$vpnService = new VPNService($conn);
$servers = $vpnService->getServers();
echo "Servers found: " . count($servers) . "\n";

if (empty($servers)) {
    echo "ERROR: No VPN servers configured!\n";
    exit;
}

foreach ($servers as $server) {
    echo "\nServer: " . $server['name'] . "\n";
    echo "  IP: " . $server['ip_address'] . "\n";
    echo "  Protocol: " . $server['protocol'] . "\n";
    echo "  Inbound ID: " . $server['inbound_id'] . "\n";
    echo "  Web Base Path: " . ($server['web_base_path'] ?? 'NOT SET') . "\n";
    echo "  Panel Port: " . ($server['panel_port'] ?? 'NOT SET') . "\n";
    echo "  SSH Key: " . ($server['ssh_key_path'] ?? 'NOT SET') . "\n";
}

// Test SSH connection
echo "\n=== Testing SSH ===\n";
$server = $servers[0];
$sshKey = $server['ssh_key_path'] ?? '/root/.ssh/id_vpn';
$cmd = "ssh -i " . escapeshellarg($sshKey) . " -o StrictHostKeyChecking=no -o ConnectTimeout=5 root@" . escapeshellarg($server['ip_address']) . " 'echo SSH_OK' 2>&1";
echo "Command: " . substr($cmd, 0, 80) . "...\n";
$output = shell_exec($cmd);
echo "Output: " . trim($output) . "\n";

// Check x-ui db exists
echo "\n=== Checking x-ui DB ===\n";
$cmd2 = "ssh -i " . escapeshellarg($sshKey) . " -o StrictHostKeyChecking=no root@" . escapeshellarg($server['ip_address']) . " 'ls -la /etc/x-ui/x-ui.db 2>&1'";
$output2 = shell_exec($cmd2);
echo "DB check: " . trim($output2) . "\n";
