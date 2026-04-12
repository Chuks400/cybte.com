<?php
require_once __DIR__ . '/../src/config/database.php';
header('Content-Type: text/plain');

try {
    $db = new Database();
    $conn = $db->connect();
    echo "✓ Database connected\n\n";
    
    // Check vpn_servers table
    echo "=== VPN Servers ===\n";
    $stmt = $conn->query("SELECT id, name, ip_address, status, panel_type FROM vpn_servers");
    $servers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($servers)) {
        echo "No servers found.\n";
    } else {
        foreach ($servers as $s) {
            echo "ID: {$s['id']}, Name: {$s['name']}, IP: {$s['ip_address']}, Status: {$s['status']}\n";
        }
    }
    
    // Check current database name
    echo "\n=== Database Info ===\n";
    $stmt = $conn->query("SELECT DATABASE()");
    $dbName = $stmt->fetchColumn();
    echo "Current database: {$dbName}\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
