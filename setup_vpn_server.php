<?php
/**
 * Setup VPN Server
 * Adds the VPS to the database for 3x-ui integration
 */

require_once __DIR__ . '/src/config/database.php';

$database = new Database();
$conn = $database->connect();

// VPS Configuration
$username = getenv('USERNAME');
$serverConfig = [
    'name' => 'USA-1',
    'location' => 'New York',
    'country' => 'US',
    'flag' => '🇺🇸',
    'ip_address' => '178.104.139.94',
    'status' => 'active',
    'load_percent' => 45,
    'panel_type' => '3x-ui',
    'api_url' => 'http://178.104.139.94:54321',
    'api_username' => '',
    'api_password' => '',
    'inbound_id' => 1,
    'protocol' => 'vless',
    'domain' => null,
    'use_https' => false,
    'panel_port' => '54321',
    'web_base_path' => '/JE2fu7rGygZsRGQwEW/',
    'ssh_key_path' => "C:\\Users\\$username\\.ssh\\id_rsa"
];

try {
    // Check if server already exists
    $stmt = $conn->prepare("SELECT id FROM vpn_servers WHERE ip_address = :ip");
    $stmt->bindParam(':ip', $serverConfig['ip_address']);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        echo "Server with IP {$serverConfig['ip_address']} already exists.\n";
        
        // Update existing server
        $stmt = $conn->prepare("
            UPDATE vpn_servers SET
                name = :name,
                location = :location,
                country = :country,
                flag = :flag,
                status = :status,
                load_percent = :load_percent,
                panel_type = :panel_type,
                api_url = :api_url,
                inbound_id = :inbound_id,
                protocol = :protocol,
                domain = :domain,
                use_https = :use_https,
                panel_port = :panel_port,
                web_base_path = :web_base_path
            WHERE ip_address = :ip_address
        ");
        
        $stmt->execute($serverConfig);
        echo "Server updated successfully!\n";
    } else {
        // Insert new server
        $stmt = $conn->prepare("
            INSERT INTO vpn_servers 
            (name, location, country, flag, ip_address, status, load_percent, panel_type, api_url, 
             api_username, api_password, inbound_id, protocol, domain, use_https, panel_port, web_base_path)
            VALUES 
            (:name, :location, :country, :flag, :ip_address, :status, :load_percent, :panel_type, :api_url,
             :api_username, :api_password, :inbound_id, :protocol, :domain, :use_https, :panel_port, :web_base_path)
        ");
        
        $stmt->execute($serverConfig);
        echo "Server added successfully with ID: " . $conn->lastInsertId() . "\n";
    }
    
    echo "\nVPS Configuration:\n";
    echo "- IP: {$serverConfig['ip_address']}\n";
    echo "- Panel URL: {$serverConfig['api_url']}\n";
    echo "- SSH Key: {$serverConfig['ssh_key_path']}\n";
    echo "\nNext steps:\n";
    echo "1. Ensure 3x-ui is installed on your VPS\n";
    echo "2. Verify SSH key authentication works: ssh root@{$serverConfig['ip_address']}\n";
    echo "3. Check 3x-ui panel is accessible at {$serverConfig['api_url']}\n";
    echo "4. Go to vpn_dashboard.php and click 'Reset Link'\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
