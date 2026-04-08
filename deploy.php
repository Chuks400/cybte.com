<?php
// GitHub Webhook Auto-Deploy Script
$secret = 'Cjohn22@'; // Change this!

// Get GitHub payload
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

// Verify signature
$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    die('Unauthorized');
}

// Pull latest code
$output = [];
$return_var = 0;
exec('cd /var/www/cybte.com && git pull origin main 2>&1', $output, $return_var);

// Log result
$log = date('Y-m-d H:i:s') . " - Deploy result: " . implode("\n", $output);
file_put_contents('/var/www/cybte.com/logs/deploy.log', $log . "\n", FILE_APPEND);

echo $return_var === 0 ? 'Deploy successful' : 'Deploy failed';
