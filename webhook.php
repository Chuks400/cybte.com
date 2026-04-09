<?php
// Temporary test webhook

// Get payload
$payload = file_get_contents('php://input');
if (!$payload) {
    file_put_contents(__DIR__ . '/logs/deploy.log', "No payload received\n", FILE_APPEND);
    http_response_code(400);
    exit('No payload');
}

// Only deploy on push to main branch
$data = json_decode($payload, true);
if (($data['ref'] ?? '') !== 'refs/heads/main') {
    file_put_contents(__DIR__ . '/logs/deploy.log', "Not main branch\n", FILE_APPEND);
    exit('Not main branch');
}

// Log deployment trigger
file_put_contents(__DIR__ . '/logs/deploy.log', date('Y-m-d H:i:s') . " - Deployment triggered\n", FILE_APPEND);

// Run git safely
$output = [];
$return_var = 0;
exec('cd /var/www/cybte.com && git fetch --all && git reset --hard origin/main 2>&1', $output, $return_var);

// Log git output
file_put_contents(__DIR__ . '/logs/deploy.log', implode("\n", $output) . "\n\n", FILE_APPEND);

// Respond
if ($return_var === 0) {
    http_response_code(200);
    echo 'Deployment successful';
} else {
    http_response_code(500);
    echo 'Deployment failed';
}
?>