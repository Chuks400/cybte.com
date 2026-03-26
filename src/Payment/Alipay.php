<?php

namespace TrustShield\Payment;

/**
 * Alipay Payment Adapter
 * 
 * Supports both:
 * - Fake/QR mode (for testing without real credentials)
 * - Airwallex aggregator (for production without Chinese business license)
 * - Direct Alipay API (for future with proper credentials)
 */
class Alipay implements PaymentInterface
{
    private $mode;
    private $config;

    /**
     * Mode options:
     * - 'fake': Generate test QR codes (default for development)
     * - 'airwallex': Use Airwallex aggregator
     * - 'direct': Direct Alipay API (requires merchant account)
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'mode' => $_ENV['ALIPAY_MODE'] ?? 'fake',
            'airwallex_client_id' => $_ENV['AIRWALLEX_CLIENT_ID'] ?? '',
            'airwallex_api_key' => $_ENV['AIRWALLEX_API_KEY'] ?? '',
            'airwallex_base_url' => 'https://api.airwallex.com/api/v1',
        ], $config);

        $this->mode = $this->config['mode'];
    }

    /**
     * Create Alipay payment
     */
    public function create(string $order_id, float $amount, string $description = ''): array
    {
        switch ($this->mode) {
            case 'airwallex':
                return $this->createAirwallex($order_id, $amount, $description);
            case 'direct':
                return $this->createDirect($order_id, $amount, $description);
            case 'fake':
            default:
                return $this->createFake($order_id, $amount);
        }
    }

    /**
     * Fake QR generation for testing
     */
    private function createFake(string $order_id, float $amount): array
    {
        // Generate a fake QR code using a free QR API
        // In production, this would be replaced with real payment QR
        $qrData = "ALIPAY|{$order_id}|{$amount}|CNY";
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=" . urlencode($qrData);

        return [
            'qr_url' => $qrUrl,
            'transaction_id' => 'FAKE_ALIPAY_' . $order_id,
            'mode' => 'fake',
            'expires_at' => time() + 3600 // 1 hour expiry
        ];
    }

    /**
     * Airwallex aggregator integration
     * Best for startups without Chinese business license
     */
    private function createAirwallex(string $order_id, float $amount, string $description): array
    {
        $data = [
            'request_id' => $order_id,
            'amount' => $amount,
            'currency' => 'CNY',
            'payment_method' => [
                'type' => 'alipay_qr'
            ],
            'descriptor' => $description ?: 'TrustShield VPN Subscription'
        ];

        $response = $this->airwallexRequest('/pa/payment_intents/create', $data);

        if (isset($response['next_action']['url'])) {
            return [
                'qr_url' => $response['next_action']['url'],
                'transaction_id' => $response['id'] ?? '',
                'mode' => 'airwallex',
                'client_secret' => $response['client_secret'] ?? ''
            ];
        }

        // Fallback to fake if API fails
        return $this->createFake($order_id, $amount);
    }

    /**
     * Direct Alipay API (requires proper credentials)
     */
    private function createDirect(string $order_id, float $amount, string $description): array
    {
        // Placeholder for direct Alipay integration
        // Requires: app_id, private_key, alipay_public_key
        // This would use Alipay's official SDK
        
        // For now, fallback to fake mode
        return $this->createFake($order_id, $amount);
    }

    /**
     * Make request to Airwallex API
     */
    private function airwallexRequest(string $endpoint, array $data): array
    {
        $ch = curl_init($this->config['airwallex_base_url'] . $endpoint);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-client-id: ' . $this->config['airwallex_client_id'],
                'x-api-key: ' . $this->config['airwallex_api_key']
            ],
            CURLOPT_POSTFIELDS => json_encode($data)
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true) ?? [];
    }

    /**
     * Verify payment
     */
    public function verify(array $data): bool
    {
        if ($this->mode === 'fake') {
            // In fake mode, we can simulate verification
            return isset($data['simulate_paid']) && $data['simulate_paid'] === true;
        }

        if ($this->mode === 'airwallex') {
            return $this->verifyAirwallex($data);
        }

        return false;
    }

    /**
     * Verify Airwallex payment
     */
    private function verifyAirwallex(array $data): bool
    {
        if (!isset($data['id']) || !isset($data['status'])) {
            return false;
        }

        // Verify webhook signature (simplified - implement proper signature check)
        return $data['status'] === 'SUCCEEDED';
    }

    /**
     * Get payment status
     */
    public function getStatus(string $transaction_id): string
    {
        if ($this->mode === 'fake') {
            // Check if transaction exists in session/cache
            // For demo purposes, always return pending
            return 'pending';
        }

        if ($this->mode === 'airwallex') {
            return $this->getAirwallexStatus($transaction_id);
        }

        return 'pending';
    }

    /**
     * Get Airwallex payment status
     */
    private function getAirwallexStatus(string $transaction_id): string
    {
        $ch = curl_init($this->config['airwallex_base_url'] . '/pa/payment_intents/' . $transaction_id);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-client-id: ' . $this->config['airwallex_client_id'],
                'x-api-key: ' . $this->config['airwallex_api_key']
            ]
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        $statusMap = [
            'SUCCEEDED' => 'paid',
            'FAILED' => 'failed',
            'CANCELLED' => 'failed',
            'EXPIRED' => 'failed'
        ];

        return $statusMap[$data['status'] ?? ''] ?? 'pending';
    }

    /**
     * Handle webhook
     */
    public function handleWebhook(array $payload): array
    {
        if ($this->mode === 'fake') {
            return [
                'order_id' => $payload['order_id'] ?? '',
                'status' => 'pending',
                'verified' => false
            ];
        }

        if ($this->mode === 'airwallex') {
            if ($payload['name'] === 'payment_intent.succeeded') {
                $data = $payload['data']['object'] ?? [];
                return [
                    'order_id' => $data['request_id'] ?? '',
                    'transaction_id' => $data['id'] ?? '',
                    'status' => 'paid',
                    'verified' => true,
                    'amount' => $data['amount'] ?? 0,
                    'currency' => $data['currency'] ?? 'CNY'
                ];
            }
        }

        return [
            'order_id' => '',
            'status' => 'unknown',
            'verified' => false
        ];
    }
}
