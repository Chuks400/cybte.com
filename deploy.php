<?php
// GitHub Webhook Auto-Deploy Script
 = 'your-webhook-secret-here'; // Change this!

// Get GitHub payload
 = file_get_contents('php://input');
 = ['HTTP_X_HUB_SIGNATURE_256'] ?? '';

// Verify signature
 = 'sha256=' . hash_hmac('sha256', , );
if (!hash_equals(, )) {
    http_response_code(403);
    die('Unauthorized');
}

// Pull latest code
 = [];
 = 0;
exec('cd /var/www/cybte.com && git pull origin main 2>&1', , );

// Log result
 = date('Y-m-d H:i:s') . " - Deploy result: \ . implode(\/\n\, );
file_put_contents('/var/www/cybte.com/logs/deploy.log', . \/\n\, FILE_APPEND);

echo === 0 ? 'Deploy successful' : 'Deploy failed';
