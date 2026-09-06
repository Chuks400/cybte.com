<?php

declare(strict_types=1);

class EmailVerification
{
    private PDO $conn;
    private string $fromEmail;
    private string $fromName;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
        $this->fromEmail = getenv('MAIL_FROM_ADDRESS') ?: 'noreply@cybte.com';
        $this->fromName = getenv('MAIL_FROM_NAME') ?: 'Cybte AI';
    }

    public function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function saveToken(int $userId, string $email, string $token): bool
    {
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $this->conn->beginTransaction();
        try {
            $delete = $this->conn->prepare('DELETE FROM email_verifications WHERE user_id = :user_id');
            $delete->execute([':user_id' => $userId]);
            $insert = $this->conn->prepare('INSERT INTO email_verifications (user_id, email, token, expires_at, created_at) VALUES (:user_id, :email, :token, :expires_at, NOW())');
            $ok = $insert->execute([':user_id' => $userId, ':email' => $email, ':token' => $token, ':expires_at' => $expiresAt]);
            $this->conn->commit();
            return $ok;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            error_log('Email verification token persistence failed: ' . $e->getMessage());
            return false;
        }
    }

    public function verifyToken(string $token): array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return ['success' => false, 'error' => 'Invalid or expired verification link'];
        }

        $stmt = $this->conn->prepare('SELECT id, user_id, email FROM email_verifications WHERE token = :token AND expires_at > NOW() LIMIT 1');
        $stmt->execute([':token' => $token]);
        $verification = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$verification) {
            return ['success' => false, 'error' => 'Invalid or expired verification link'];
        }

        $this->conn->beginTransaction();
        try {
            $update = $this->conn->prepare('UPDATE users SET email_verified = 1, email_verified_at = NOW() WHERE id = :user_id');
            $update->execute([':user_id' => (int)$verification['user_id']]);
            $delete = $this->conn->prepare('DELETE FROM email_verifications WHERE id = :id');
            $delete->execute([':id' => (int)$verification['id']]);
            $this->conn->commit();
            return ['success' => true, 'user_id' => (int)$verification['user_id'], 'email' => (string)$verification['email']];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            error_log('Email verification completion failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to verify email'];
        }
    }

    public function sendVerificationEmail(string $email, string $token, string $name = ''): bool
    {
        require_once __DIR__ . '/ResendMailer.php';
        require_once __DIR__ . '/SmtpMailer.php';

        $appUrl = rtrim(getenv('APP_URL') ?: 'https://www.cybte.com', '/');
        $verificationUrl = $appUrl . '/verify_email.php?token=' . rawurlencode($token);
        $safeName = htmlspecialchars($name !== '' ? $name : 'there', ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($verificationUrl, ENT_QUOTES, 'UTF-8');
        $subject = 'Verify your Cybte AI account';
        $message = '<!doctype html><html><body style="margin:0;background:#06101c;font-family:Arial,sans-serif;color:#dce8f5">'
            . '<div style="max-width:600px;margin:auto;padding:36px 22px">'
            . '<div style="background:#0b1b2e;border:1px solid #173651;border-radius:14px;padding:32px">'
            . '<h1 style="margin:0 0 18px;color:#fff">Verify your Cybte AI account</h1>'
            . '<p>Hello ' . $safeName . ',</p><p style="color:#9eb0c3;line-height:1.7">Confirm your email address to complete your Cybte AI account setup.</p>'
            . '<p style="margin:28px 0"><a href="' . $safeUrl . '" style="display:inline-block;padding:13px 20px;border-radius:8px;background:#3be7ff;color:#04121a;text-decoration:none;font-weight:bold">Verify email address</a></p>'
            . '<p style="color:#71869d;font-size:13px;line-height:1.6">This verification link expires in 24 hours. If you did not create this account, you can ignore this message.</p>'
            . '<p style="color:#60778e;font-size:12px;word-break:break-all">' . $safeUrl . '</p>'
            . '</div><p style="text-align:center;color:#5f748b;font-size:12px">Cybte AI · Protect. Verify. Detect. Store. Connect Securely.</p></div></body></html>';

        $resend = new ResendMailer();
        if ($resend->isConfigured() && $resend->send($email, $subject, $message, true)) {
            return true;
        }

        $smtp = new SmtpMailer();
        if ($smtp->isConfigured() && $smtp->send($email, $subject, $message, true)) {
            return true;
        }

        $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
        $sent = @mail($email, $subject, $message, $headers);
        if (!$sent) {
            error_log('Email verification delivery failed for ' . $email);
            $this->queueEmail($email, $subject, $message);
        }
        return $sent;
    }

    private function queueEmail(string $email, string $subject, string $message): void
    {
        try {
            $stmt = $this->conn->prepare("INSERT INTO email_queue (to_email, subject, body, status, created_at) VALUES (:to_email, :subject, :body, 'pending', NOW())");
            $stmt->execute([':to_email' => $email, ':subject' => $subject, ':body' => $message]);
        } catch (Throwable $e) {
            error_log('Email queue unavailable: ' . $e->getMessage());
        }
    }

    public function isEmailVerified(int $userId): bool
    {
        $stmt = $this->conn->prepare('SELECT email_verified FROM users WHERE id = :user_id LIMIT 1');
        $stmt->execute([':user_id' => $userId]);
        return (int)$stmt->fetchColumn() === 1;
    }

    public function resendVerification(int $userId, string $email, string $name = ''): bool
    {
        $token = $this->generateToken();
        return $this->saveToken($userId, $email, $token) && $this->sendVerificationEmail($email, $token, $name);
    }
}
