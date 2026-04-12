<?php
/**
 * VPN Service - Business logic for VPN account management
 * Integrates with 3x-ui backend panels
 */
namespace Cybte\VPN;

use PDO;

require_once __DIR__ . '/ThreeXUIDatabaseManager.php';

class VPNService {
    private $db;
    private $servers = [];
    
    /**
     * Constructor
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
        $this->loadServers();
    }
    
    /**
     * Load VPN servers from database
     */
    private function loadServers() {
        try {
            $stmt = $this->db->query("SELECT * FROM vpn_servers WHERE status = 'active' ORDER BY id ASC");
            $this->servers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('VPNService: Failed to load servers: ' . $e->getMessage());
            $this->servers = [];
        }
    }
    
    /**
     * Get available VPN servers
     * @return array List of servers
     */
    public function getServers() {
        return $this->servers;
    }
    
    /**
     * Get server by ID
     * @param int $serverId Server ID
     * @return array|null Server config
     */
    public function getServer($serverId) {
        foreach ($this->servers as $server) {
            if ($server['id'] == $serverId) {
                return $server;
            }
        }
        return null;
    }
    
    /**
     * Get default server (first active one)
     * @return array|null Server config
     */
    public function getDefaultServer() {
        return $this->servers[0] ?? null;
    }
    
    /**
     * Create VPN account for user on specified server
     * @param int $userId User ID
     * @param int $serverId Server ID (uses default if null)
     * @param string $plan Plan type (trial, starter, pro, team)
     * @return array|false Account info or false on failure
     */
    public function createAccount($userId, $serverId = null, $plan = 'trial') {
        // Get user info
        $stmt = $this->db->prepare("SELECT email, name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            error_log('VPNService: User not found: ' . $userId);
            return false;
        }
        
        // Get server
        $server = $serverId ? $this->getServer($serverId) : $this->getDefaultServer();
        
        if (!$server) {
            error_log('VPNService: No active VPN servers available');
            return false;
        }
        
        // Determine traffic limit based on plan
        $trafficGb = $this->getPlanTrafficLimit($plan);
        $expiryDays = $this->getPlanExpiryDays($plan);
        
        // Create client identifier
        $clientEmail = $this->generateClientEmail($userId, $user['email']);
        
        // Initialize 3x-ui Database Manager with HTTPS support
        $sshKeyPath = $server['ssh_key_path'] ?? 'C:\\Users\\' . getenv('USERNAME') . '\\.ssh\\id_rsa';
        $manager = new ThreeXUIDatabaseManager(
            $server['ip_address'],
            $sshKeyPath,
            $server['inbound_id'] ?? 1,
            $server['domain'] ?? null,        // Domain name (e.g., cybte.com)
            $server['use_https'] ?? false,    // Enable HTTPS
            $server['panel_port'] ?? '54321',  // Panel port
            $server['web_base_path'] ?? '/JE2fu7rGygZsRGQwEW/'  // Web base path
        );
        
        // Create client on panel
        $result = $manager->createClient($clientEmail, $expiryDays, $trafficGb);
        
        if (!$result) {
            error_log('VPNService: Failed to create client on server ' . $server['id'] . ': ' . $manager->getLastError());
            return false;
        }
        
        // Build subscription link from result
        $subscriptionLink = $result['subscription_link'];
        
        // Store in database
        try {
            $stmt = $this->db->prepare("
                INSERT INTO vpn_accounts 
                (user_id, server_id, subscription_link, uuid, client_email, status, created_at, expires_at) 
                VALUES (?, ?, ?, ?, ?, 'active', NOW(), DATE_ADD(NOW(), INTERVAL ? DAY))
            ");
            
            $stmt->execute([
                $userId,
                $server['id'],
                $subscriptionLink,
                $result['uuid'],
                $clientEmail,
                $expiryDays
            ]);
            
            $accountId = $this->db->lastInsertId();
            
            return [
                'id' => $accountId,
                'server_id' => $server['id'],
                'server_name' => $server['name'],
                'server_country' => $server['country'],
                'subscription_link' => $subscriptionLink,
                'uuid' => $result['uuid'],
                'client_email' => $clientEmail,
                'traffic_limit_gb' => $trafficGb,
                'expiry_days' => $expiryDays,
                'expires_at' => date('Y-m-d H:i:s', strtotime("+$expiryDays days"))
            ];
            
        } catch (PDOException $e) {
            error_log('VPNService: Database error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's VPN account
     * @param int $userId User ID
     * @return array|false Account info or false if not found
     */
    public function getUserAccount($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT va.*, vs.name as server_name, vs.country as server_country, vs.flag as server_flag
                FROM vpn_accounts va
                LEFT JOIN vpn_servers vs ON va.server_id = vs.id
                WHERE va.user_id = ? AND va.status = 'active'
                ORDER BY va.created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('VPNService: Failed to get user account: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reset subscription link (revoke old, create new)
     * @param int $userId User ID
     * @return array|false New account info or false on failure
     */
    public function resetSubscriptionLink($userId) {
        // Get current account
        $account = $this->getUserAccount($userId);
        
        if (!$account) {
            error_log('VPNService: No active account found for user ' . $userId);
            return false;
        }
        
        // Get server
        $server = $this->getServer($account['server_id']);
        
        if (!$server) {
            error_log('VPNService: Server not found: ' . $account['server_id']);
            return false;
        }
        
        // Initialize API
        $api = new ThreeXUIAPI(
            $server['api_url'],
            $server['api_username'] ?? '',
            $server['api_password'] ?? '',
            $server['inbound_id'] ?? 1,
            false
        );
        
        // Delete old client
        if ($account['client_email']) {
            $api->deleteClient($account['client_email']);
        }
        
        // Create new account
        return $this->createAccount($userId, $server['id'], 'trial');
    }
    
    /**
     * Update account traffic limit
     * @param int $accountId Account ID
     * @param int $newTrafficGb New traffic limit in GB
     * @return bool Success status
     */
    public function updateTrafficLimit($accountId, $newTrafficGb) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM vpn_accounts WHERE id = ?");
            $stmt->execute([$accountId]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$account) {
                return false;
            }
            
            $server = $this->getServer($account['server_id']);
            
            if (!$server) {
                return false;
            }
            
            $api = new ThreeXUIAPI(
                $server['api_url'],
                $server['api_username'] ?? '',
                $server['api_password'] ?? '',
                $server['inbound_id'] ?? 1,
                false
            );
            
            $success = $api->updateClientTraffic($account['client_email'], $newTrafficGb);
            
            if ($success) {
                $stmt = $this->db->prepare("UPDATE vpn_accounts SET traffic_limit_gb = ? WHERE id = ?");
                $stmt->execute([$newTrafficGb, $accountId]);
            }
            
            return $success;
            
        } catch (PDOException $e) {
            error_log('VPNService: Update traffic error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get traffic limit for plan
     * @param string $plan Plan name
     * @return int Traffic limit in GB
     */
    private function getPlanTrafficLimit($plan) {
        $limits = [
            'trial' => 10,      // 10GB for trial
            'starter' => 50,    // 50GB for starter
            'pro' => 200,      // 200GB for pro
            'team' => 1000     // 1TB for team
        ];
        
        return $limits[$plan] ?? 10;
    }
    
    /**
     * Get expiry days for plan
     * @param string $plan Plan name
     * @return int Days until expiry
     */
    private function getPlanExpiryDays($plan) {
        $days = [
            'trial' => 7,       // 7 days trial
            'starter' => 30,    // 30 days
            'pro' => 30,        // 30 days
            'team' => 30        // 30 days
        ];
        
        return $days[$plan] ?? 7;
    }
    
    /**
     * Generate unique client email for panel
     * @param int $userId User ID
     * @param string $userEmail User's email
     * @return string Client identifier
     */
    private function generateClientEmail($userId, $userEmail) {
        $prefix = 'ts' . $userId;
        $domain = substr(strrchr($userEmail, "@"), 1) ?: 'trustshield.ai';
        return $prefix . '@' . $domain;
    }
}
