<?php
/**
 * 3x-UI VPN Panel API Client
 * Handles communication with 3x-ui (Xray-based) control panels
 */
class ThreeXUIAPI {
    private $baseUrl;
    private $username;
    private $password;
    private $cookie;
    private $inboundId;
    private $verifySsl;
    private $isLoggedIn = false;
    private $rootUrl;
    private $cookieHeader = null;
    private $lastError = null;
    private $lastHttpCode = null;
    private $lastResponse = null;

    public function getLastError() {
        return $this->lastError;
    }

    public function getLastHttpCode() {
        return $this->lastHttpCode;
    }

    public function getLastResponse() {
        return $this->lastResponse;
    }

    public function getRootUrlForDebug() {
        return $this->rootUrl;
    }

    /**
     * Constructor
     */
    public function __construct($baseUrl, $username, $password, $inboundId = 1, $verifySsl = false) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $parsed = parse_url($this->baseUrl);
        $scheme = $parsed['scheme'] ?? 'http';
        $host = $parsed['host'] ?? '';
        $port = isset($parsed['port']) ? (':' . $parsed['port']) : '';
        $this->rootUrl = rtrim($scheme . '://' . $host . $port, '/');
        $this->username = $username;
        $this->password = $password;
        $this->inboundId = $inboundId;
        $this->verifySsl = $verifySsl;
        $this->cookie = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'trustshield_3xui_cookie_' . uniqid('', true) . '.txt';
    }

    /**
     * LOGIN (session-based authentication)
     */
    private function login() {
        if ($this->isLoggedIn) {
            return true;
        }

        $this->lastError = null;
        $this->lastHttpCode = null;
        $this->lastResponse = null;

        $ch = curl_init();

        $loginUrl = $this->baseUrl . '/login';
        curl_setopt($ch, CURLOPT_URL, $loginUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'username' => $this->username,
            'password' => $this->password,
            'twoFactorCode' => ''
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'X-Requested-With: XMLHttpRequest'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifySsl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->verifySsl ? 2 : 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error = curl_error($ch);
        curl_close($ch);

        // If the random webBasePath is only for UI, some builds serve /login at the root.
        if ($httpCode === 404 && $this->rootUrl && $this->rootUrl !== $this->baseUrl) {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $this->rootUrl . '/login');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'username' => $this->username,
                'password' => $this->password,
                'twoFactorCode' => ''
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'X-Requested-With: XMLHttpRequest'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifySsl);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->verifySsl ? 2 : 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $error = curl_error($ch);
            curl_close($ch);
        }

        $this->lastHttpCode = $httpCode;
        $raw = is_string($response) ? $response : '';
        $headersRaw = $headerSize ? substr($raw, 0, $headerSize) : '';
        $bodyRaw = $headerSize ? substr($raw, $headerSize) : $raw;
        $this->lastResponse = substr($bodyRaw, 0, 800);

        if ($error) {
            $this->lastError = 'Login Error: ' . $error;
            error_log('3x-ui ' . $this->lastError);
            return false;
        }

        if (!in_array($httpCode, [200, 302], true)) {
            $this->lastError = 'Login HTTP Error: ' . $httpCode;
            error_log('3x-ui ' . $this->lastError);
            return false;
        }

        if (!is_string($response)) {
            $response = '';
        }

        // Heuristic: if response indicates invalid login, fail early.
        if (stripos($bodyRaw, 'invalid username') !== false || stripos($bodyRaw, 'two-factor') !== false) {
            $this->lastError = 'Login Failed: Invalid credentials or 2FA required';
            error_log('3x-ui ' . $this->lastError);
            return false;
        }

        // Try to capture Set-Cookie for environments where cookie jar file isn't persisted.
        $cookies = [];
        foreach (preg_split("/\r\n|\n|\r/", (string)$headersRaw) as $line) {
            if (stripos($line, 'Set-Cookie:') === 0) {
                $cookiePair = trim(substr($line, strlen('Set-Cookie:')));
                $cookiePair = explode(';', $cookiePair, 2)[0];
                if ($cookiePair !== '') {
                    $cookies[] = $cookiePair;
                }
            }
        }
        if (!empty($cookies)) {
            $this->cookieHeader = implode('; ', $cookies);
        }

        // Some 3x-ui builds return JSON success without setting a cookie file.
        // We'll consider a successful login response sufficient and let the first API call verify auth.

        $this->isLoggedIn = true;
        return true;
    }

    /**
     * Create a new VPN client/user
     */
    public function createClient($email, $uuid = null, $totalGb = 0, $expiryDays = 30) {
        if (!$uuid) {
            $uuid = $this->generateUUID();
        }

        $totalBytes = $totalGb > 0 ? $totalGb * 1024 * 1024 * 1024 : 0;
        $expiryTime = $expiryDays > 0 ? (time() + ($expiryDays * 86400)) * 1000 : 0;

        $payload = [
            'id' => $this->inboundId,
            'settings' => json_encode([
                'clients' => [
                    [
                        'id' => $uuid,
                        'email' => $email,
                        'totalGB' => $totalBytes,
                        'expiryTime' => $expiryTime,
                        'enable' => true
                    ]
                ]
            ])
        ];

        $response = $this->apiRequest('/api/inbounds/addClient', $payload);

        if ($response && isset($response['success']) && $response['success']) {
            return [
                'uuid' => $uuid,
                'email' => $email,
                'subscription_link' => $this->getSubscriptionLink($email),
                'total_gb' => $totalGb,
                'expiry_days' => $expiryDays
            ];
        }

        return false;
    }

    public function getSubscriptionLink($email) {
        return $this->baseUrl . '/sub/' . urlencode($email);
    }

    public function getClientConfig($email) {
        $inbound = $this->getInbound($this->inboundId);

        if (!$inbound) return false;

        $settings = json_decode($inbound['settings'], true);
        $streamSettings = json_decode($inbound['streamSettings'], true);

        $client = null;
        foreach ($settings['clients'] as $c) {
            if ($c['email'] === $email) {
                $client = $c;
                break;
            }
        }

        if (!$client) return false;

        return [
            'protocol' => $inbound['protocol'],
            'id' => $client['id'],
            'email' => $client['email'],
            'address' => $this->extractServerAddress(),
            'port' => $inbound['port'],
            'network' => $streamSettings['network'] ?? 'tcp',
            'security' => $streamSettings['security'] ?? 'none',
            'tls_settings' => $streamSettings['tlsSettings'] ?? null,
            'ws_settings' => $streamSettings['wsSettings'] ?? null
        ];
    }

    public function buildVlessLink($config) {
        $params = [];

        if ($config['security'] === 'tls' && $config['tls_settings']) {
            $params['security'] = 'tls';
            $params['sni'] = $config['tls_settings']['serverName'] ?? '';
        }

        if ($config['network'] === 'ws' && $config['ws_settings']) {
            $params['type'] = 'ws';
            $params['path'] = $config['ws_settings']['path'] ?? '/';
            $params['host'] = $config['ws_settings']['headers']['Host'] ?? $config['address'];
        }

        $query = http_build_query($params);
        $query = str_replace(['%2F', '%3A'], ['/', ':'], $query);

        return sprintf(
            'vless://%s@%s:%s?%s#%s',
            $config['id'],
            $config['address'],
            $config['port'],
            $query,
            urlencode($config['email'] . '-TRUSTSHIELD')
        );
    }

    public function updateClientTraffic($email, $newTotalGb) {
        $totalBytes = $newTotalGb * 1024 * 1024 * 1024;

        $payload = [
            'id' => $this->inboundId,
            'email' => $email,
            'totalGB' => $totalBytes
        ];

        $response = $this->apiRequest('/api/inbounds/updateClient', $payload);
        return $response && isset($response['success']) && $response['success'];
    }

    public function resetClientTraffic($email) {
        $payload = [
            'id' => $this->inboundId,
            'email' => $email
        ];

        $response = $this->apiRequest('/api/inbounds/resetClientTraffic', $payload);
        return $response && isset($response['success']) && $response['success'];
    }

    public function deleteClient($email) {
        $payload = [
            'id' => $this->inboundId,
            'email' => $email
        ];

        $response = $this->apiRequest('/api/inbounds/delClient', $payload);
        return $response && isset($response['success']) && $response['success'];
    }

    public function getInbound($inboundId) {
        $response = $this->apiRequest('/api/inbounds/get/' . $inboundId);

        if ($response && isset($response['success']) && $response['success'] && isset($response['obj'])) {
            return $response['obj'];
        }

        return false;
    }

    public function testConnection() {
        $response = $this->apiRequest('/api/status');
        return $response !== false;
    }

    private function generateUUID() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * CORE API REQUEST (with login + cookies)
     */
    private function apiRequest($endpoint, $postData = null) {
        $this->lastError = null;
        $this->lastHttpCode = null;
        $this->lastResponse = null;

        if (!$this->login()) {
            return false;
        }

        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifySsl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->verifySsl ? 2 : 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // use saved session cookie
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie);
        if ($this->cookieHeader) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->cookieHeader);
        }

        $headers = [
            'Accept: application/json',
            'X-Requested-With: XMLHttpRequest'
        ];

        if ($postData !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // If API endpoints are hosted at the root (without webBasePath), retry on rootUrl when 404.
        if ($httpCode === 404 && $this->rootUrl && $this->rootUrl !== $this->baseUrl) {
            $url = $this->rootUrl . $endpoint;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifySsl);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->verifySsl ? 2 : 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie);
            if ($this->cookieHeader) {
                curl_setopt($ch, CURLOPT_COOKIE, $this->cookieHeader);
            }

            $headers = [
                'Accept: application/json',
                'X-Requested-With: XMLHttpRequest'
            ];

            if ($postData !== null) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
        }

        // Some builds expose endpoints under /panel/api instead of /api. Try the alternative variant on 404.
        if ($httpCode === 404 && is_string($endpoint)) {
            $altEndpoint = null;
            if (substr($endpoint, 0, 5) === '/api/') {
                $altEndpoint = '/panel' . $endpoint;
            } elseif (substr($endpoint, 0, 11) === '/panel/api/') {
                $altEndpoint = substr($endpoint, strlen('/panel'));
            }

            if ($altEndpoint) {
                $tryBases = [$this->baseUrl];
                if ($this->rootUrl && $this->rootUrl !== $this->baseUrl) {
                    $tryBases[] = $this->rootUrl;
                }

                foreach ($tryBases as $base) {
                    $url = $base . $altEndpoint;
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifySsl);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->verifySsl ? 2 : 0);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie);
                    if ($this->cookieHeader) {
                        curl_setopt($ch, CURLOPT_COOKIE, $this->cookieHeader);
                    }

                    $headers = [
                        'Accept: application/json',
                        'X-Requested-With: XMLHttpRequest'
                    ];
                    if ($postData !== null) {
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
                        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                    }
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $error = curl_error($ch);
                    curl_close($ch);

                    if ($httpCode !== 404) {
                        break;
                    }
                }
            }
        }

        $this->lastHttpCode = $httpCode;
        $this->lastResponse = is_string($response) ? substr($response, 0, 800) : '';

        if ($error || $httpCode !== 200) {
            $this->lastError = 'API Error: ' . $error . ' HTTP: ' . $httpCode;
            error_log('3x-ui ' . $this->lastError);
            return false;
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->lastError = 'JSON Error: ' . json_last_error_msg();
            error_log('3x-ui ' . $this->lastError);
            return false;
        }

        return $data;
    }

    private function extractServerAddress() {
        $parsed = parse_url($this->baseUrl);
        return $parsed['host'] ?? 'localhost';
    }
}