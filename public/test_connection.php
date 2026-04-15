<?php
header('Content-Type: text/plain');

echo "=== Database Connection Test ===\n\n";

require_once __DIR__ . '/../src/config/database.php';

$database = new Database();

try {
    $conn = $database->connect();
    echo "✓ Database connected successfully!\n";
    
    // Test VPN servers
    $stmt = $conn->query("SELECT COUNT(*) FROM vpn_servers");
    $count = $stmt->fetchColumn();
    echo "✓ VPN servers in database: $count\n";
    
    // Show server details
    if ($count > 0) {
        $stmt = $conn->query("SELECT id, name, ip_address, status FROM vpn_servers LIMIT 1");
        $server = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✓ Server: {$server['name']} ({$server['ip_address']}) - {$server['status']}\n";
    }
    
    echo "\n=== Connection Details ===\n";
    echo "Host: 127.0.0.1\n";
    echo "Port: 3306/3308\n";
    echo "Database: cybte\n";
    echo "User: root\n";
    
} catch (Throwable $e) {
    echo "✗ Connection failed: " . $e->getMessage() . "\n";
    echo "Last error: " . $database->getLastError() . "\n";
}
