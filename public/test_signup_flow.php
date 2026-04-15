<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

echo "=== Testing Signup Flow ===\n\n";

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/utils/EmailVerification.php';
require_once __DIR__ . '/../src/utils/ResendMailer.php';

// Test 1: Database connection
echo "1. Testing database connection...\n";
try {
    $db = new Database();
    $conn = $db->connect();
    echo "   ✓ Database connected\n\n";
} catch (Exception $e) {
    echo "   ✗ Failed: " . $e->getMessage() . "\n\n";
    exit;
}

// Test 2: EmailVerification loads .env
echo "2. Testing EmailVerification loads .env...\n";
$emailVerify = new EmailVerification($conn);
$apiKey = getenv('RESEND_API_KEY');
echo "   RESEND_API_KEY: " . ($apiKey ? substr($apiKey, 0, 10) . '...' : 'NOT SET') . "\n";
echo "   MAIL_FROM: " . getenv('MAIL_FROM_ADDRESS') . "\n\n";

// Test 3: ResendMailer configuration
echo "3. Testing ResendMailer...\n";
$resend = new ResendMailer();
echo "   isConfigured: " . ($resend->isConfigured() ? 'YES' : 'NO') . "\n\n";

// Test 4: Simulate sending verification email
echo "4. Testing sendVerificationEmail...\n";
$testEmail = 'test_' . time() . '@example.com';
$token = $emailVerify->generateToken();
echo "   Generated token: " . substr($token, 0, 10) . "...\n";

$saved = $emailVerify->saveToken(999, $testEmail, $token);
echo "   Token saved: " . ($saved ? 'YES' : 'NO') . "\n";

if ($saved) {
    echo "   Sending email to: $testEmail\n";
    $sent = $emailVerify->sendVerificationEmail($testEmail, $token, 'Test User');
    echo "   Email sent: " . ($sent ? 'YES' : 'NO') . "\n";
}

echo "\n=== Test Complete ===";
