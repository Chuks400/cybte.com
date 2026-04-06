<?php
/**
 * TrustShield AI - Email Verification Utility
 * 
 * Handles email verification token generation and sending
 */

class EmailVerification {
    
    private $conn;
    private $fromEmail;
    private $fromName;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->fromEmail = getenv('MAIL_FROM_ADDRESS') ?: 'noreply@cybte.com';
        $this->fromName = getenv('MAIL_FROM_NAME') ?: 'TrustShield AI';
    }
    
    /**
     * Generate a secure verification token
     */
    public function generateToken() {
        return bin2hex(random_bytes(32)); // 64 character hex string
    }
    
    /**
     * Save verification token to database
     */
    public function saveToken($userId, $email, $token) {
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours')); // Token expires in 24 hours
        
        // First, invalidate any existing tokens for this user
        $stmt = $this->conn->prepare("DELETE FROM email_verifications WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        // Insert new token
        $stmt = $this->conn->prepare("INSERT INTO email_verifications (user_id, email, token, expires_at, created_at) VALUES (:user_id, :email, :token, :expires_at, NOW())");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires_at', $expiresAt);
        
        return $stmt->execute();
    }
    
    /**
     * Verify a token and activate the user account
     */
    public function verifyToken($token) {
        $stmt = $this->conn->prepare("SELECT * FROM email_verifications WHERE token = :token AND expires_at > NOW() LIMIT 1");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        $verification = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$verification) {
            return ['success' => false, 'error' => 'Invalid or expired verification link'];
        }
        
        // Mark user as verified
        $stmt = $this->conn->prepare("UPDATE users SET email_verified = 1, email_verified_at = NOW() WHERE id = :user_id");
        $stmt->bindParam(':user_id', $verification['user_id'], PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            // Delete the used token
            $stmt = $this->conn->prepare("DELETE FROM email_verifications WHERE id = :id");
            $stmt->bindParam(':id', $verification['id'], PDO::PARAM_INT);
            $stmt->execute();
            
            return ['success' => true, 'user_id' => $verification['user_id'], 'email' => $verification['email']];
        }
        
        return ['success' => false, 'error' => 'Failed to verify email'];
    }
    
    /**
     * Send verification email
     */
    public function sendVerificationEmail($email, $token, $name = '') {
        $verificationUrl = 'https://www.cybte.com/verify_email.php?token=' . $token;
        
        $subject = 'Verify Your TrustShield VPN Account';
        
        $message = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #00d4ff, #0099cc); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .button { display: inline-block; background: #00d4ff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to TrustShield VPN!</h1>
        </div>
        <div class="content">
            <h2>Hello {$name},</h2>
            <p>Thank you for creating a TrustShield VPN account. Please verify your email address to complete your registration and start using our secure VPN service.</p>
            
            <center>
                <a href="{$verificationUrl}" class="button">Verify Email Address</a>
            </center>
            
            <p>Or copy and paste this link into your browser:</p>
            <p style="word-break: break-all; color: #666;">{$verificationUrl}</p>
            
            <p>This link will expire in 24 hours.</p>
            
            <p>If you didn't create this account, please ignore this email.</p>
        </div>
        <div class="footer">
            <p>TrustShield AI - Secure VPN Service</p>
            <p>www.cybte.com</p>
        </div>
    </div>
</body>
</html>
HTML;
        
        // Headers for HTML email
        $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        return mail($email, $subject, $message, $headers);
    }
    
    /**
     * Check if user email is verified
     */
    public function isEmailVerified($userId) {
        $stmt = $this->conn->prepare("SELECT email_verified FROM users WHERE id = :user_id LIMIT 1");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['email_verified'] == 1;
    }
    
    /**
     * Resend verification email
     */
    public function resendVerification($userId, $email, $name = '') {
        // Generate new token
        $token = $this->generateToken();
        
        // Save token
        if ($this->saveToken($userId, $email, $token)) {
            // Send email
            return $this->sendVerificationEmail($email, $token, $name);
        }
        
        return false;
    }
}
?>
