<?php
/**
 * Update VPN Server Configuration
 * Updates the database with actual 3x-ui panel settings
 */

require_once __DIR__ . '/../src/config/database.php';

header('Content-Type: text/plain');

echo "=== Updating VPN Server Configuration ===\n\n";

try {
    $database = new Database();
    $conn = $database->connect();
    echo "✓ Database connected\n\n";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit;
}

// Updated configuration from actual 3x-ui installation
$ip = '178.104.139.94';
$webBasePath = '/2gXR8V4h5IoobH1wW7/';
$useHttps = true;
$panelPort = '54321';

try {
    $stmt = $conn->prepare("
        UPDATE vpn_servers 
        SET web_base_path = :web_base_path,
            use_https = :use_https,
            panel_port = :panel_port,
            api_url = :api_url
        WHERE ip_address = :ip
    ");
    
    $apiUrl = "https://{$ip}:{$panelPort}";
    
    $stmt->bindParam(':web_base_path', $webBasePath);
    $stmt->bindParam(':use_https', $useHttps, PDO::PARAM_BOOL);
    $stmt->bindParam(':panel_port', $panelPort);
    $stmt->bindParam(':api_url', $apiUrl);
    $stmt->bindParam(':ip', $ip);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "✓ VPN server configuration updated successfully!\n\n";
        echo "=== New Configuration ===\n";
        echo "- IP: {$ip}\n";
        echo "- Panel URL: {$apiUrl}{$webBasePath}\n";
        echo "- HTTPS: Enabled\n";
        echo "- Web Base Path: {$webBasePath}\n";
        echo "\n=== Next Step ===\n";
        echo "Go to http://localhost/trustshield-ai/public/vpn_dashboard.php\n";
        echo "Click 'Reset Link' to generate your real VPN subscription link.\n";
    } else {
        echo "⚠ No server found with IP {$ip}. Run setup_vpn_server.php first.\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
