<?php
/**
 * Email Configuration Test
 * Run this to check your SMTP settings
 */

require_once __DIR__ . '/src/utils/SmtpMailer.php';

// Load environment
require_once __DIR__ . '/.env';

$smtp = new SmtpMailer();
$config = $smtp->getConfigStatus();

echo "=== Email Configuration Test ===\n\n";

// Check configuration
echo "SMTP Host: {$config['host']}\n";
echo "SMTP Port: {$config['port']}\n";
echo "Username set: " . ($config['username_set'] ? 'YES' : 'NO') . "\n";
echo "Password set: " . ($config['password_set'] ? 'YES' : 'NO') . "\n";
echo "From Email: {$config['from_email']}\n";
echo "\n";

if (!$config['is_configured']) {
    echo "❌ SMTP NOT CONFIGURED PROPERLY\n";
    echo "\nPROBLEM: Your .env file has placeholder credentials.\n";
    echo "Current MAIL_USERNAME: " . getenv('MAIL_USERNAME') . "\n";
    echo "Current MAIL_PASSWORD: " . (getenv('MAIL_PASSWORD') ? '[SET]' : '[NOT SET]') . "\n";
    echo "\nFIX: Update your .env file with real Gmail credentials:\n";
    echo "1. Use a Gmail address\n";
    echo "2. Generate an App Password at: https://myaccount.google.com/apppasswords\n";
    echo "3. Update MAIL_USERNAME and MAIL_PASSWORD in .env\n";
    exit(1);
}

echo "✓ SMTP appears configured\n\n";

// Test sending
echo "Sending test email to: " . getenv('MAIL_USERNAME') . "\n";

$result = $smtp->send(
    getenv('MAIL_USERNAME'),
    'Test Email from TrustShield',
    '<h2>Test Email</h2><p>If you received this, your SMTP configuration is working!</p>',
    true
);

if ($result) {
    echo "✓ Test email sent successfully!\n";
    echo "Check your inbox (and spam folder) at " . getenv('MAIL_USERNAME') . "\n";
} else {
    echo "❌ Failed to send test email\n";
    echo "Check the error logs for details.\n";
}
