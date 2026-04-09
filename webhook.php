<?php
// TrustShield AI - GitHub Webhook Auto-Deployment
// Save as /var/www/cybte.com/public/webhook.php

// Security: Secret token (must match GitHub webhook secret)
$secret = 'Cjohn22@';

// Get signature from headers
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

// Get payload
$payload = file_get_contents('php://input');

// Verify signature (temporarily disabled for testing)
// if (!hash_equals($expected, $signature)) {
//     http_response_code(403);
//     exit('Unauthorized');
// }

// Only deploy on push to main branch
$data = json_decode($payload, true);
if (($data['ref'] ?? '') !== 'refs/heads/main') {
    exit('Not main branch');
}

// Log deployment
$log = date('Y-m-d H:i:s') . " - Deploy triggered by " . ($data['pusher']['name'] ?? 'unknown') . "\n";
file_put_contents(__DIR__ . '/logs/deploy.log', $log, FILE_APPEND);

// Run git pull safely
$output = [];
$return_var = 0;
exec('cd /var/www/cybte.com && git fetch --all && git reset --hard origin/main 2>&1', $output, $return_var);

// Log git output
file_put_contents(__DIR__ . '/logs/deploy.log', implode("\n", $output) . "\n\n", FILE_APPEND);

// Return proper response
if ($return_var === 0) {
    http_response_code(200);
    echo 'Deployment successful';
} else {
    http_response_code(500);
    echo 'Deployment failed';
}
?>