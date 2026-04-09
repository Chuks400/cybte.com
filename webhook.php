<?php
// Your webhook secret from GitHub
$secret = 'Cjohn22@';

// Get the raw POST payload
$payload = file_get_contents('php://input');

// Get the signatures sent by GitHub
$signature_sha1   = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';
$signature_sha256 = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

// Calculate hashes using your secret
$hash_sha1   = 'sha1=' . hash_hmac('sha1', $payload, $secret);
$hash_sha256 = 'sha256=' . hash_hmac('sha256', $payload, $secret);

// Verify signatures
if (!hash_equals($hash_sha1, $signature_sha1) && !hash_equals($hash_sha256, $signature_sha256)) {
    // Signatures don't match → reject
    http_response_code(403);
    exit('Invalid signature');
}

// Decode JSON payload
$data = json_decode($payload, true);

// --- Process the webhook payload ---
file_put_contents('webhook_log.txt', print_r($data, true), FILE_APPEND);

// Respond to GitHub with 200 OK
http_response_code(200);
echo 'Webhook received successfully';
?>