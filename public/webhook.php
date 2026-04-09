<?php
// TrustShield AI - GitHub Webhook Auto-Deployment
// Production-ready webhook

// Secret token (must match GitHub webhook secret in GitHub settings)
$secret = 'Cjohn22@';

// Log file paths
$debugLog = __DIR__ . '/logs/debug.log';
$deployLog = __DIR__ . '/logs/deploy.log';

// Ensure logs directory exists
if (!file_exists(dirname($debugLog))) {
    mkdir(dirname($debugLog), 0777, true);
}

// Get signature from headers
$signature_sha1   = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';
$signature_sha256 = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

// Get payload
$payload = file_get_contents('php://input');

// Determine which signature header is provided
if (!empty($signature_sha256)) {
    $signature = $signature_sha256;
    $algorithm = 'sha256';
} elseif (!empty($signature_sha1)) {
    $signature = $signature_sha1;
    $algorithm = 'sha1';
} else {
    http_response_code(403);
    file_put_contents($deployLog, date('Y-m-d H:i:s') . " - Missing webhook signature\n", FILE_APPEND);
    exit('Unauthorized');
}

// Verify signature
$expected = $algorithm . '=' . hash_hmac($algorithm, $payload, $secret);

// Debug log
$debug = date('Y-m-d H:i:s') . " - Received: " . substr($signature, 0, 20) . "... Expected: " . substr($expected, 0, 20) . "...\n";
file_put_contents($debugLog, $debug, FILE_APPEND);

if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    file_put_contents($deployLog, date('Y-m-d H:i:s') . " - Unauthorized webhook attempt\n", FILE_APPEND);
    exit('Unauthorized');
}

// Debug log
file_put_contents($debugLog, date('Y-m-d H:i:s') . " - Passed signature\n", FILE_APPEND);

// Only deploy on push to main branch
$data = json_decode($payload, true);
if (!$data) {
    file_put_contents($debugLog, date('Y-m-d H:i:s') . " - Invalid JSON payload\n", FILE_APPEND);
    http_response_code(400);
    exit('Invalid payload');
}
if (($data['ref'] ?? '') !== 'refs/heads/main') {
    file_put_contents($debugLog, date('Y-m-d H:i:s') . " - Not main branch: " . ($data['ref'] ?? 'no ref') . "\n", FILE_APPEND);
    exit('Not main branch');
}

// Log deployment trigger
$triggeredBy = $data['pusher']['name'] ?? 'unknown';
file_put_contents($deployLog, date('Y-m-d H:i:s') . " - Deploy triggered by $triggeredBy\n", FILE_APPEND);

// Run git safely
$output = [];
$return_var = 0;
$repoDir = '/var/www/cybte.com';

if (!is_dir($repoDir)) {
    file_put_contents($deployLog, date('Y-m-d H:i:s') . " - Repository path not found: $repoDir\n", FILE_APPEND);
    http_response_code(500);
    exit('Deployment path not found');
}

file_put_contents($debugLog, date('Y-m-d H:i:s') . " - Starting git operations\n", FILE_APPEND);

// Use git config override to avoid dubious ownership issues
$gitCommand = '/usr/bin/git -C ' . escapeshellarg($repoDir) . ' -c safe.directory=' . escapeshellarg($repoDir);
exec($gitCommand . ' fetch --all 2>&1', $output, $return_var);
file_put_contents($debugLog, date('Y-m-d H:i:s') . " - Fetch return: $return_var\n", FILE_APPEND);
if ($return_var === 0) {
    exec($gitCommand . ' reset --hard origin/main 2>&1', $output, $return_var);
    file_put_contents($debugLog, date('Y-m-d H:i:s') . " - Reset return: $return_var\n", FILE_APPEND);
}

// Log git output
file_put_contents($deployLog, implode("\n", $output) . "\n\n", FILE_APPEND);

// Return response
if ($return_var === 0) {
    http_response_code(200);
    echo 'Deployment successful';
} else {
    http_response_code(500);
    echo 'Deployment failed';
}
?>