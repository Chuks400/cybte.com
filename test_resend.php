<?php
/**
 * Resend.com Configuration Test
 * Run this after getting your API key from resend.com
 */

require_once __DIR__ . '/src/utils/ResendMailer.php';

// Load environment
require_once __DIR__ . '/.env';

$resend = new ResendMailer();
$config = $resend->getConfigStatus();

echo "=== Resend.com Configuration Test ===\n\n";

echo "API Key set: " . ($config['api_key_set'] ? 'YES' : 'NO') . "\n";
echo "API Key format valid (starts with re_): " . ($config['api_key_valid_format'] ? 'YES' : 'NO') . "\n";
echo "From Email: {$config['from_email']}\n";
echo "From Name: {$config['from_name']}\n";
echo "\n";

if (!$config['is_configured']) {
    echo "❌ RESEND NOT CONFIGURED\n\n";
    echo "To set up Resend:\n";
    echo "1. Go to https://resend.com/signup\n";
    echo "2. Verify your email\n";
    echo "3. Get your API key (starts with 're_')\n";
    echo "4. Add to your .env file:\n";
    echo "   putenv('RESEND_API_KEY=re_your_api_key_here');\n";
    echo "   putenv('MAIL_FROM_ADDRESS=onboarding@resend.dev');  // For testing\n";
    echo "   putenv('MAIL_FROM_NAME=Cybte VPN');\n";
    echo "\nFor production, verify your domain at resend.com/domains\n";
    exit(1);
}

echo "✓ Resend appears configured\n\n";

// Test sending
$testEmail = getenv('MAIL_USERNAME') ?: 'dennismonday072@gmail.com';
echo "Sending test email to: {$testEmail}\n";

$result = $resend->send(
    $testEmail,
    'Test Email from TrustShield via Resend',
    '<h2>Test Email</h2><p>If you received this, your Resend configuration is working!</p><p>Sent at: ' . date('Y-m-d H:i:s') . '</p>',
    true
);

if ($result) {
    echo "✓ Test email sent successfully!\n";
    echo "Check your inbox (and spam folder) at {$testEmail}\n";
} else {
    echo "❌ Failed to send test email\n";
    echo "Check the error logs for details.\n";
}
