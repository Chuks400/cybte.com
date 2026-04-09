<?php
/**
 * SMTP Mailer - Sends emails using SMTP (Gmail, etc.)
 * Uses fsockopen for compatibility without external libraries
 */
class SmtpMailer {
    
    private $host;
    private $port;
    private $username;
    private $password;
    private $encryption;
    private $fromEmail;
    private $fromName;
    private $socket;
    private $debug = false;
    
    public function __construct() {
        $this->host = getenv('MAIL_HOST') ?: 'smtp.gmail.com';
        $this->port = getenv('MAIL_PORT') ?: 587;
        $this->username = getenv('MAIL_USERNAME') ?: '';
        $this->password = getenv('MAIL_PASSWORD') ?: '';
        $this->encryption = getenv('MAIL_ENCRYPTION') ?: 'tls';
        $this->fromEmail = getenv('MAIL_FROM_ADDRESS') ?: 'noreply@cybte.com';
        $this->fromName = getenv('MAIL_FROM_NAME') ?: 'Cybte AI';
    }
    
    /**
     * Send email via SMTP
     */
    public function send($to, $subject, $body, $isHtml = true) {
        // Validate credentials
        if (empty($this->username) || empty($this->password) || 
            $this->username === 'your_email@gmail.com' || $this->password === 'your_app_password') {
            error_log('SMTP Mailer: SMTP credentials not configured properly');
            return false;
        }
        
        try {
            $this->connect();
            $this->authenticate();
            
            // Send email data
            $this->sendCommand("MAIL FROM:<{$this->username}>");
            $this->sendCommand("RCPT TO:<{$to}>");
            $this->sendCommand("DATA");
            
            // Build message
            $message = $this->buildMessage($to, $subject, $body, $isHtml);
            $this->sendCommand($message . "\r\n.");
            $this->sendCommand("QUIT");
            
            $this->disconnect();
            return true;
            
        } catch (Exception $e) {
            error_log('SMTP Mailer Error: ' . $e->getMessage());
            $this->disconnect();
            return false;
        }
    }
    
    /**
     * Connect to SMTP server
     */
    private function connect() {
        $timeout = 30;
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, $timeout);
        
        if (!$this->socket) {
            throw new Exception("Failed to connect to SMTP server: {$errstr} ({$errno})");
        }
        
        $this->getResponse();
        $this->sendCommand("EHLO " . gethostname());
        
        // Start TLS if required
        if ($this->encryption === 'tls') {
            $this->sendCommand("STARTTLS");
            
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("Failed to enable TLS encryption");
            }
            
            $this->sendCommand("EHLO " . gethostname());
        }
    }
    
    /**
     * Authenticate with SMTP server
     */
    private function authenticate() {
        $this->sendCommand("AUTH LOGIN");
        $this->sendCommand(base64_encode($this->username));
        $this->sendCommand(base64_encode($this->password));
    }
    
    /**
     * Send SMTP command
     */
    private function sendCommand($command) {
        if ($this->debug) {
            error_log("SMTP > {$command}");
        }
        
        fwrite($this->socket, $command . "\r\n");
        $response = $this->getResponse();
        
        // Check for error (codes starting with 4 or 5 indicate errors)
        if (preg_match('/^[45]/', $response)) {
            throw new Exception("SMTP Error: {$response}");
        }
        
        return $response;
    }
    
    /**
     * Get server response
     */
    private function getResponse() {
        $response = '';
        while ($line = fgets($this->socket, 512)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        
        if ($this->debug) {
            error_log("SMTP < " . trim($response));
        }
        
        return $response;
    }
    
    /**
     * Build email message
     */
    private function buildMessage($to, $subject, $body, $isHtml) {
        $boundary = md5(time());
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "To: {$to}\r\n";
        $headers .= "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "Subject: {$subject}\r\n";
        
        if ($isHtml) {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        } else {
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        }
        
        $headers .= "Content-Transfer-Encoding: base64\r\n";
        
        return $headers . "\r\n" . chunk_split(base64_encode($body));
    }
    
    /**
     * Disconnect from server
     */
    private function disconnect() {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
    }
    
    /**
     * Check if SMTP is properly configured
     */
    public function isConfigured() {
        return !empty($this->username) && 
               !empty($this->password) && 
               $this->username !== 'your_email@gmail.com' &&
               $this->password !== 'your_app_password';
    }
    
    /**
     * Get configuration status for debugging
     */
    public function getConfigStatus() {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'username_set' => !empty($this->username),
            'password_set' => !empty($this->password),
            'from_email' => $this->fromEmail,
            'is_configured' => $this->isConfigured()
        ];
    }
}
