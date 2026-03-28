<?php

namespace TrustShield\Services;

/**
 * Email Service
 * Handles sending email notifications for payments
 */
class EmailService
{
    private string $fromEmail;
    private string $fromName;
    
    public function __construct()
    {
        $this->fromEmail = $_ENV['MAIL_FROM'] ?? 'noreply@trustshield.ai';
        $this->fromName = $_ENV['MAIL_FROM_NAME'] ?? 'TrustShield VPN';
    }
    
    /**
     * Send payment success email
     */
    public function sendPaymentSuccess(array $payment, array $user): bool
    {
        $subject = "Payment Successful - TrustShield VPN Subscription";
        
        $body = $this->getPaymentSuccessTemplate([
            'user_name' => $user['name'] ?? $user['email'] ?? 'Valued Customer',
            'plan_name' => $payment['plan_name'],
            'amount' => $payment['amount'],
            'currency' => $payment['currency'],
            'order_id' => $payment['order_id'],
            'paid_at' => $payment['paid_at'] ?? date('Y-m-d H:i:s'),
            'expiry_date' => date('Y-m-d', strtotime('+30 days'))
        ]);
        
        return $this->send($user['email'], $subject, $body);
    }
    
    /**
     * Send subscription expiry reminder
     */
    public function sendExpiryReminder(array $user, string $plan, string $expiryDate): bool
    {
        $subject = "Your VPN Subscription Expires Soon";
        
        $body = $this->getExpiryReminderTemplate([
            'user_name' => $user['name'] ?? $user['email'] ?? 'Valued Customer',
            'plan_name' => $plan,
            'expiry_date' => $expiryDate,
            'days_left' => max(0, (strtotime($expiryDate) - time()) / 86400)
        ]);
        
        return $this->send($user['email'], $subject, $body);
    }
    
    /**
     * Send low traffic warning
     */
    public function sendTrafficWarning(array $user, float $usedPercent, float $remainingGb): bool
    {
        $subject = "Low Traffic Alert - TrustShield VPN";
        
        $body = $this->getTrafficWarningTemplate([
            'user_name' => $user['name'] ?? $user['email'] ?? 'Valued Customer',
            'used_percent' => round($usedPercent, 1),
            'remaining_gb' => round($remainingGb, 2)
        ]);
        
        return $this->send($user['email'], $subject, $body);
    }
    
    /**
     * Send refund confirmation email
     */
    public function sendRefundConfirmation(array $payment, array $user, float $refundAmount): bool
    {
        $subject = "Refund Processed - TrustShield VPN";
        
        $body = $this->getRefundTemplate([
            'user_name' => $user['name'] ?? $user['email'] ?? 'Valued Customer',
            'order_id' => $payment['order_id'],
            'plan_name' => $payment['plan_name'],
            'refund_amount' => $refundAmount,
            'currency' => $payment['currency'],
            'refund_date' => date('Y-m-d H:i:s')
        ]);
        
        return $this->send($user['email'], $subject, $body);
    }
    
    /**
     * Generic send method
     */
    private function send(string $to, string $subject, string $body): bool
    {
        $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        // Log email for debugging
        error_log("Sending email to {$to}: {$subject}");
        
        // Try to send email
        $result = mail($to, $subject, $body, $headers);
        
        // If mail() fails, log for retry via queue
        if (!$result) {
            $this->queueEmail($to, $subject, $body);
        }
        
        return $result;
    }
    
    /**
     * Queue email for later sending
     */
    private function queueEmail(string $to, string $subject, string $body): void
    {
        try {
            $db = new \Database();
            $conn = $db->connect();
            
            $stmt = $conn->prepare("
                INSERT INTO email_queue (to_email, subject, body, status, created_at)
                VALUES (:to_email, :subject, :body, 'pending', NOW())
            ");
            
            $stmt->execute([
                ':to_email' => $to,
                ':subject' => $subject,
                ':body' => $body
            ]);
        } catch (\Exception $e) {
            error_log("Failed to queue email: " . $e->getMessage());
        }
    }
    
    /**
     * Payment success email template
     */
    private function getPaymentSuccessTemplate(array $data): string
    {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #00E5FF;'>Payment Successful! 🎉</h2>
                <p>Hi {$data['user_name']},</p>
                <p>Thank you for your payment. Your VPN subscription has been activated.</p>
                
                <div style='background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='margin-top: 0;'>Order Details</h3>
                    <p><strong>Order ID:</strong> {$data['order_id']}</p>
                    <p><strong>Plan:</strong> {$data['plan_name']}</p>
                    <p><strong>Amount:</strong> {$data['currency']}{$data['amount']}</p>
                    <p><strong>Paid at:</strong> {$data['paid_at']}</p>
                    <p><strong>Valid until:</strong> {$data['expiry_date']}</p>
                </div>
                
                <p>You can now access your VPN dashboard to get your subscription link.</p>
                <a href='http://localhost/trustshield-ai/public/vpn_dashboard.php' 
                   style='display: inline-block; background: #00E5FF; color: #0a1929; padding: 12px 24px; 
                          text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 10px;'>
                    Go to Dashboard
                </a>
                
                <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
                <p style='font-size: 12px; color: #999;'>
                    This is an automated message. Please do not reply to this email.<br>
                    TrustShield VPN - Secure Your Connection
                </p>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Expiry reminder email template
     */
    private function getExpiryReminderTemplate(array $data): string
    {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #ffc107;'>Subscription Expiring Soon ⏰</h2>
                <p>Hi {$data['user_name']},</p>
                <p>Your VPN subscription expires in <strong>{$data['days_left']} days</strong>.</p>
                
                <div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #ffc107;'>
                    <h3 style='margin-top: 0;'>Subscription Details</h3>
                    <p><strong>Plan:</strong> {$data['plan_name']}</p>
                    <p><strong>Expiry Date:</strong> {$data['expiry_date']}</p>
                </div>
                
                <p>Don't lose your secure connection! Renew now to continue enjoying uninterrupted VPN service.</p>
                <a href='http://localhost/trustshield-ai/public/vpn_pricing.php' 
                   style='display: inline-block; background: #00E5FF; color: #0a1929; padding: 12px 24px; 
                          text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 10px;'>
                    Renew Now
                </a>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Traffic warning email template
     */
    private function getTrafficWarningTemplate(array $data): string
    {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #ff9800;'>Low Traffic Alert ⚠️</h2>
                <p>Hi {$data['user_name']},</p>
                <p>You've used <strong>{$data['used_percent']}%</strong> of your monthly traffic allowance.</p>
                
                <div style='background: #fff3e0; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #ff9800;'>
                    <p><strong>Remaining Traffic:</strong> {$data['remaining_gb']} GB</p>
                </div>
                
                <p>Consider upgrading to a higher plan for more traffic.</p>
                <a href='http://localhost/trustshield-ai/public/vpn_pricing.php' 
                   style='display: inline-block; background: #00E5FF; color: #0a1929; padding: 12px 24px; 
                          text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 10px;'>
                    View Plans
                </a>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Refund email template
     */
    private function getRefundTemplate(array $data): string
    {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #00E5FF;'>Refund Processed ✅</h2>
                <p>Hi {$data['user_name']},</p>
                <p>Your refund has been processed successfully.</p>
                
                <div style='background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='margin-top: 0;'>Refund Details</h3>
                    <p><strong>Order ID:</strong> {$data['order_id']}</p>
                    <p><strong>Plan:</strong> {$data['plan_name']}</p>
                    <p><strong>Refund Amount:</strong> {$data['currency']}{$data['refund_amount']}</p>
                    <p><strong>Processed on:</strong> {$data['refund_date']}</p>
                </div>
                
                <p>The refund will appear in your account within 5-10 business days depending on your payment method.</p>
                
                <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
                <p style='font-size: 12px; color: #999;'>
                    This is an automated message. Please do not reply to this email.
                </p>
            </div>
        </body>
        </html>
        ";
    }
}
