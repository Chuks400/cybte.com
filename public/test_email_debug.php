<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

echo "=== Email Debug Test ===\n\n";

// Load .env file
$envFile = __DIR__ . '/../.env';
echo "Env file exists: " . (file_exists($envFile) ? 'YES' : 'NO') . "\n";

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (preg_match('/putenv\s*\(\s*["\']([^=]+)=([^"\']*)["\']\s*\)/i', $line, $matches)) {
            putenv("{$matches[1]}={$matches[2]}");
        }
    }
}

$apiKey = getenv('RESEND_API_KEY');
echo "RESEND_API_KEY: " . ($apiKey ? substr($apiKey, 0, 15) . '...' : 'NOT SET') . "\n";
echo "MAIL_FROM_ADDRESS: " . getenv('MAIL_FROM_ADDRESS') . "\n";
echo "MAIL_FROM_NAME: " . getenv('MAIL_FROM_NAME') . "\n\n";

// Test Resend API directly
if ($apiKey) {
    echo "Testing Resend API...\n";
    
    $payload = [
        'from' => 'Cybte VPN <noreply@cybte.com>',
        'to' => ['test@example.com'],
        'subject' => 'Test Email',
        'html' => '<p>This is a test email</p>'
    ];
    
    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    echo "cURL Error: " . ($error ?: 'None') . "\n";
    echo "Response: $response\n";
}
