<?php
/**
 * Resend.com API Mailer
 * Simple HTTP API - no SMTP needed
 * Free tier: 3000 emails/month
 */
class ResendMailer {

    private $apiKey;
    private $apiUrl = 'https://api.resend.com/emails';
    private $fromEmail;
    private $fromName;

    public function __construct() {
        // Load .env file if not already loaded
        $this->loadEnvFile();

        $this->apiKey = getenv('RESEND_API_KEY') ?: '';
        $this->fromEmail = getenv('MAIL_FROM_ADDRESS') ?: 'onboarding@resend.dev';
        $this->fromName = getenv('MAIL_FROM_NAME') ?: 'Cybte VPN';
    }

    /**
     * Load environment variables from .env file
     */
    private function loadEnvFile() {
        $envFile = __DIR__ . '/../../.env';
        if (!file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, '//') || str_starts_with($line, '<?php') || str_starts_with($line, '?>')) {
                continue;
            }

            if (preg_match('/putenv\s*\(\s*["\']([^=]+)=([^"\']*)["\']\s*\)/i', $line, $matches)) {
                putenv("{$matches[1]}={$matches[2]}");
            }
        }
    }
    
    /**
     * Send email via Resend API
     */
    public function send($to, $subject, $body, $isHtml = true) {
        if (empty($this->apiKey)) {
            error_log('ResendMailer: No API key configured');
            return false;
        }
        
        // Build from field
        $from = $this->fromName ? "{$this->fromName} <{$this->fromEmail}>" : $this->fromEmail;
        
        // Build payload
        $payload = [
            'from' => $from,
            'to' => is_array($to) ? $to : [$to],
            'subject' => $subject,
        ];
        
        if ($isHtml) {
            $payload['html'] = $body;
        } else {
            $payload['text'] = $body;
        }
        
        // Send via cURL
        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log('ResendMailer cURL error: ' . $error);
            return false;
        }
        
        if ($httpCode !== 200) {
            error_log('ResendMailer API error (' . $httpCode . '): ' . $response);
            return false;
        }
        
        $data = json_decode($response, true);
        return isset($data['id']);
    }
    
    /**
     * Check if Resend is configured
     */
    public function isConfigured() {
        return !empty($this->apiKey) && strpos($this->apiKey, 're_') === 0;
    }
    
    /**
     * Get config status for debugging
     */
    public function getConfigStatus() {
        return [
            'api_key_set' => !empty($this->apiKey),
            'api_key_valid_format' => strpos($this->apiKey, 're_bm2eAkm7_xVjW44a8RHrLB8pSfQMz36hn') === 0,
            'from_email' => $this->fromEmail,
            'from_name' => $this->fromName,
            'is_configured' => $this->isConfigured()
        ];
    }
}
