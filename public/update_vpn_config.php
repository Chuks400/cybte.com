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
// Note: SSL certificate failed, using HTTP instead
$ip = '178.104.139.94';
$webBasePath = '/2gXR8V4h5IoobH1wW7/';
$useHttps = false;  // SSL failed, use HTTP
$panelPort = '54321';

try {
    // Use HTTP since SSL certificate failed
    $apiUrl = "http://{$ip}:{$panelPort}";
    
    // Update server config using ID 1 (since we confirmed it exists)
    $stmt = $conn->prepare("
        UPDATE vpn_servers 
        SET web_base_path = :web_base_path,
            use_https = :use_https,
            panel_port = :panel_port,
            api_url = :api_url
        WHERE id = 1
    ");
    
    $stmt->bindParam(':web_base_path', $webBasePath);
    $stmt->bindParam(':use_https', $useHttps, PDO::PARAM_INT);
    $stmt->bindParam(':panel_port', $panelPort);
    $stmt->bindParam(':api_url', $apiUrl);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "✓ VPN server configuration updated successfully!\n\n";
        echo "=== New Configuration ===\n";
        echo "- IP: {$ip}\n";
        echo "- Panel URL: {$apiUrl}{$webBasePath}\n";
        echo "- Protocol: HTTP (SSL certificate failed)\n";
        echo "- Web Base Path: {$webBasePath}\n";
        echo "\n=== Next Step ===\n";
        echo "Go to http://localhost/trustshield-ai/public/vpn_dashboard.php\n";
        echo "Click 'Reset Link' to generate your real VPN subscription link.\n";
    } else {
        echo "⚠ Server config may already be up to date. Check vpn_dashboard.php\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
