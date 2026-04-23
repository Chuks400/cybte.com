<?php
/**
 * 3x-ui Database Direct Manager
 * Manages VPN clients by directly manipulating x-ui's SQLite database via SSH
 */
namespace Cybte\VPN;

use PDO;

class ThreeXUIDatabaseManager {
    private $vpsIp;
    private $domain;
    private $useHttps;
    private $sshKeyPath;
    private $dbPath = '/etc/x-ui/x-ui.db';
    private $localDbPath;
    private $inboundId;
    private $panelPort;
    private $webBasePath;
    
    // Debug state
    private $lastError = null;
    private $lastOutput = null;
    
    public function __construct($vpsIp, $sshKeyPath, $inboundId = 1, $domain = null, $useHttps = false, $panelPort = '54321', $webBasePath = '/JE2fu7rGygZsRGQwEW/') {
        $this->vpsIp = $vpsIp;
        $this->domain = $domain;
        $this->useHttps = $useHttps;
        $this->sshKeyPath = $sshKeyPath;
        $this->inboundId = $inboundId;
        $this->panelPort = $panelPort;
        $this->webBasePath = $webBasePath;
        $this->localDbPath = sys_get_temp_dir() . '/x-ui-' . time() . '.db';
    }
    
    public function getLastError() {
        return $this->lastError;
    }
    
    public function getLastOutput() {
        return $this->lastOutput;
    }
    
    /**
     * Execute command with proper Windows support
     */
    private function execCommand($cmd) {
        $descriptors = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w']   // stderr
        ];
        
        $process = proc_open($cmd, $descriptors, $pipes);
        
        if (!is_resource($process)) {
            return ['output' => [], 'code' => -1, 'error' => 'Failed to start process'];
        }
        
        fclose($pipes[0]); // Close stdin
        
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        $exitCode = proc_close($process);
        
        $output = array_filter(array_merge(
            explode("\n", $stdout),
            explode("\n", $stderr)
        ));
        
        return ['output' => $output, 'code' => $exitCode];
    }
    
    /**
     * Download database from VPS
     */
    private function downloadDatabase() {
        $this->localDbPath = sys_get_temp_dir() . '/xui-' . time() . '.db';
        
        $cmd = sprintf(
            'scp -o StrictHostKeyChecking=no -i %s root@%s:%s %s',
            escapeshellarg($this->sshKeyPath),
            escapeshellarg($this->vpsIp),
            escapeshellarg($this->dbPath),
            escapeshellarg($this->localDbPath)
        );
        
        $result = $this->execCommand($cmd);
        
        if ($result['code'] !== 0) {
            $this->lastError = "Failed to download DB (code " . $result['code'] . "): " . implode("\n", $result['output']);
            return false;
        }
        
        // Verify file was created and has content
        if (!file_exists($this->localDbPath)) {
            $this->lastError = "DB file not created at: " . $this->localDbPath;
            return false;
        }
        
        $size = filesize($this->localDbPath);
        if ($size === 0) {
            $this->lastError = "DB file is empty";
            return false;
        }
        
        return true;
    }
    
    /**
     * Upload database back to VPS
     */
    private function uploadDatabase() {
        // Stop x-ui before uploading
        $this->execRemote('systemctl stop x-ui');
        
        $cmd = sprintf(
            'scp -o StrictHostKeyChecking=no -i %s %s root@%s:%s',
            escapeshellarg($this->sshKeyPath),
            escapeshellarg($this->localDbPath),
            escapeshellarg($this->vpsIp),
            escapeshellarg($this->dbPath)
        );
        
        $result = $this->execCommand($cmd);
        
        if ($result['code'] !== 0) {
            $this->lastError = "Failed to upload DB (code " . $result['code'] . "): " . implode("\n", $result['output']);
            return false;
        }
        
        // Restart x-ui
        $this->execRemote('systemctl start x-ui');
        
        return true;
    }
    
    /**
     * Execute remote command via SSH
     */
    private function execRemote($command) {
        $cmd = sprintf(
            'ssh -o StrictHostKeyChecking=no -i %s root@%s %s',
            escapeshellarg($this->sshKeyPath),
            escapeshellarg($this->vpsIp),
            escapeshellarg($command)
        );
        
        return $this->execCommand($cmd);
    }
    
    /**
     * Get local PDO connection
     */
    private function getDb() {
        if (!file_exists($this->localDbPath)) {
            $this->lastError = "Local DB file not found";
            return null;
        }
        
        try {
            $pdo = new PDO('sqlite:' . $this->localDbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            $this->lastError = "DB connection failed: " . $e->getMessage();
            return null;
        }
    }
    
    /**
     * Generate UUID v4
     */
    private function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    /**
     * Get inbound configuration
     */
    public function getInbound() {
        if (!$this->downloadDatabase()) {
            return false;
        }
        
        $db = $this->getDb();
        if (!$db) {
            return false;
        }
        
        try {
            $stmt = $db->prepare("SELECT * FROM inbounds WHERE id = ?");
            $stmt->execute([$this->inboundId]);
            $inbound = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$inbound) {
                $this->lastError = "Inbound ID " . $this->inboundId . " not found in database";
                return false;
            }
            
            $inbound['settings'] = json_decode($inbound['settings'], true);
            $inbound['stream_settings'] = json_decode($inbound['stream_settings'], true);
            
            return $inbound;
        } catch (PDOException $e) {
            $this->lastError = "DB Error: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Create new VPN client
     */
    public function createClient($email, $expiryDays = 30, $trafficLimitGB = 100) {
        // Download fresh DB
        if (!$this->downloadDatabase()) {
            return false;
        }
        
        $db = $this->getDb();
        if (!$db) {
            return false;
        }
        
        try {
            // Get current inbound
            $inbound = $this->getInbound();
            if (!$inbound) {
                $this->lastError = "Inbound not found";
                return false;
            }
            
            $settings = $inbound['settings'];
            $clients = $settings['clients'] ?? [];
            
            // Check if email already exists
            foreach ($clients as $client) {
                if ($client['email'] === $email) {
                    $this->lastError = "Client with email {$email} already exists";
                    return false;
                }
            }
            
            // Generate new client
            $uuid = $this->generateUUID();
            $expiryTime = (time() + ($expiryDays * 86400)) * 1000; // x-ui uses milliseconds
            $trafficLimitBytes = $trafficLimitGB * 1024 * 1024 * 1024;
            
            $newClient = [
                'id' => $uuid,
                'email' => $email,
                'limitIp' => 0,
                'totalGB' => $trafficLimitBytes,
                'expiryTime' => $expiryTime,
                'enable' => true,
                'tgId' => '',
                'subId' => bin2hex(random_bytes(8))
            ];
            
            $clients[] = $newClient;
            $settings['clients'] = $clients;
            
            // Update inbound
            $stmt = $db->prepare("UPDATE inbounds SET settings = ? WHERE id = ?");
            $stmt->execute([json_encode($settings), $this->inboundId]);
            
            // Upload DB back
            if (!$this->uploadDatabase()) {
                return false;
            }
            
            return [
                'uuid' => $uuid,
                'email' => $email,
                'subscription_link' => $this->generateSubscriptionLink($uuid, $email, $inbound)
            ];
            
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
    
    /**
     * Get panel URL
     */
    public function getPanelUrl() {
        if ($this->domain && $this->useHttps) {
            return "https://{$this->domain}{$this->webBasePath}";
        }
        return "http://{$this->vpsIp}:{$this->panelPort}{$this->webBasePath}";
    }
    
    /**
     * Get subscription URL
     */
    public function getSubscriptionUrl($uuid) {
        if ($this->domain && $this->useHttps) {
            return "https://{$this->domain}{$this->webBasePath}panel/inbound/{$this->inboundId}/client/{$uuid}";
        }
        return "http://{$this->vpsIp}:{$this->panelPort}{$this->webBasePath}panel/inbound/{$this->inboundId}/client/{$uuid}";
    }
    private function generateSubscriptionLink($uuid, $email, $inbound) {
        $protocol = $inbound['protocol'] ?? 'vless';
        $port = $inbound['port'] ?? 443;
        
        // Use domain for connection if available and HTTPS enabled
        $connectionHost = ($this->domain && $this->useHttps) ? $this->domain : $this->vpsIp;
        
        // Build VLESS/VMESS URL
        $streamSettings = $inbound['stream_settings'] ?? [];
        $network = $streamSettings['network'] ?? 'tcp';
        $security = $streamSettings['security'] ?? 'none';
        
        // Base64 encode for subscription
        $config = [
            'v' => '2',
            'ps' => $email,
            'add' => $connectionHost,
            'port' => (string)$port,
            'id' => $uuid,
            'aid' => '0',
            'net' => $network,
            'type' => 'none',
            'host' => '',
            'path' => $streamSettings['wsSettings']['path'] ?? '',
            'tls' => $security === 'tls' ? 'tls' : 'none'
        ];
        
        $vmess = base64_encode(json_encode($config));
        return "vmess://{$vmess}";
    }
    
    /**
     * Cleanup temporary files
     */
    public function cleanup() {
        if (file_exists($this->localDbPath)) {
            unlink($this->localDbPath);
        }
    }
    
    public function __destruct() {
        $this->cleanup();
    }
}
