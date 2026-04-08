<?php
class EmailVerification {
    
    private $conn;
    private $fromEmail;
    private $fromName;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->fromEmail = 'noreply@cybte.com';
        $this->fromName = 'TrustShield AI';
    }
    
    public function generateToken() {
        return bin2hex(random_bytes(32));
    }
    
    public function saveToken($userId, $email, $token) {
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $stmt = $this->conn->prepare("DELETE FROM email_verifications WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $stmt = $this->conn->prepare("INSERT INTO email_verifications (user_id, email, token, expires_at, created_at) VALUES (:user_id, :email, :token, :expires_at, NOW())");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires_at', $expiresAt);
        
        return $stmt->execute();
    }
    
    public function verifyToken($token) {
        $stmt = $this->conn->prepare("SELECT * FROM email_verifications WHERE token = :token AND expires_at > NOW() LIMIT 1");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        $verification = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$verification) {
            return ['success' => false, 'error' => 'Invalid or expired verification link'];
        }
        
        $stmt = $this->conn->prepare("UPDATE users SET email_verified = 1, email_verified_at = NOW() WHERE id = :user_id");
        $stmt->bindParam(':user_id', $verification['user_id'], PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $stmt = $this->conn->prepare("DELETE FROM email_verifications WHERE id = :id");
            $stmt->bindParam(':id', $verification['id'], PDO::PARAM_INT);
            $stmt->execute();
            
            return ['success' => true, 'user_id' => $verification['user_id']];
        }
        
        return ['success' => false, 'error' => 'Failed to verify email'];
    }
    
    public function sendVerificationEmail($email, $token, $name = '') {
        $verificationUrl = 'https://www.cybte.com/verify_email.php?token=' . $token;
        
        $subject = 'Verify Your TrustShield VPN Account';
        
        $message = "Hello {$name},\n\nPlease verify your email by clicking this link:\n{$verificationUrl}\n\nThis link expires in 24 hours.\n\nTrustShield AI";
        
        $headers = "From: {$this->fromName} <{$this->fromEmail}>\\r\\n";
        $headers .= "Reply-To: {$this->fromEmail}\\r\\n";
        
        return mail($email, $subject, $message, $headers);
    }
    
    public function isEmailVerified($userId) {
        $stmt = $this->conn->prepare("SELECT email_verified FROM users WHERE id = :user_id LIMIT 1");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['email_verified'] == 1;
    }
}
?>
