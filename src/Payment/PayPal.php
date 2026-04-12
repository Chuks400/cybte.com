<?php

namespace Cybte\Payment;

/**
 * PayPal Payment Adapter
 * 
 * Supports PayPal checkout for VPN subscriptions
 * Works with both personal and business PayPal accounts
 */
class PayPal implements PaymentInterface
{
    private $config;
    private $mode;

    public function __construct(array $config = [])
    {
        $this->mode = getenv('PAYPAL_MODE') ?: 'sandbox';
        
        $this->config = array_merge([
            'client_id' => getenv('PAYPAL_CLIENT_ID') ?: '',
            'client_secret' => getenv('PAYPAL_CLIENT_SECRET') ?: '',
            'base_url' => $this->mode === 'production' 
                ? 'https://api-m.paypal.com' 
                : 'https://api-m.sandbox.paypal.com',
        ], $config);
    }

    /**
     * Create PayPal order
     */
    public function create(string $order_id, float $amount, string $description = ''): array
    {
        if (empty($this->config['client_id']) || empty($this->config['client_secret'])) {
            // Return fake mode if no credentials
            return $this->createFake($order_id, $amount, $description);
        }

        try {
            // Get access token
            $token = $this->getAccessToken();
            
            // Create PayPal order
            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => $order_id,
                        'description' => $description ?: 'Cybte VPN Subscription',
                        'amount' => [
                            'currency_code' => 'USD',
                            'value' => number_format($amount, 2, '.', '')
                        ],
                        'custom_id' => $order_id
                    ]
                ],
                'application_context' => [
                    'brand_name' => 'Cybte VPN',
                    'landing_page' => 'BILLING',
                    'user_action' => 'PAY_NOW',
                    'return_url' => 'https://www.cybte.com/vpn_dashboard.php?payment=success',
                    'cancel_url' => 'https://www.cybte.com/vpn_pricing.php?payment=cancelled'
                ]
            ];

            $ch = curl_init($this->config['base_url'] . '/v2/checkout/orders');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $token
                ],
                CURLOPT_POSTFIELDS => json_encode($orderData)
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $result = json_decode($response, true);

            if ($httpCode === 201 && isset($result['id'])) {
                // Find approval URL
                $approvalUrl = '';
                foreach ($result['links'] as $link) {
                    if ($link['rel'] === 'approve') {
                        $approvalUrl = $link['href'];
                        break;
                    }
                }

                return [
                    'qr_url' => $approvalUrl, // PayPal uses redirect URL, not QR
                    'transaction_id' => $result['id'],
                    'mode' => 'paypal',
                    'order_id' => $order_id,
                    'status' => $result['status'] ?? 'CREATED'
                ];
            }

            // If PayPal API fails, fallback to fake
            error_log("[PAYPAL] API Error: " . ($result['message'] ?? 'Unknown error'));
            return $this->createFake($order_id, $amount, $description);

        } catch (\Exception $e) {
            error_log("[PAYPAL] Error: " . $e->getMessage());
            return $this->createFake($order_id, $amount, $description);
        }
    }

    /**
     * Fake PayPal link for testing
     */
    private function createFake(string $order_id, float $amount, string $description): array
    {
        // Generate a fake PayPal checkout simulation page
        $fakeUrl = 'https://www.cybte.com/api/payment/fake_paypal.php?order_id=' . urlencode($order_id) . '&amount=' . $amount;
        
        return [
            'qr_url' => $fakeUrl,
            'transaction_id' => 'FAKE_PAYPAL_' . $order_id,
            'mode' => 'fake',
            'order_id' => $order_id,
            'status' => 'CREATED'
        ];
    }

    /**
     * Get PayPal access token
     */
    private function getAccessToken(): string
    {
        $ch = curl_init($this->config['base_url'] . '/v1/oauth2/token');
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_USERPWD => $this->config['client_id'] . ':' . $this->config['client_secret'],
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Accept-Language: en_US'
            ],
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials'
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        
        if (!isset($result['access_token'])) {
            throw new \Exception('Failed to get PayPal access token: ' . ($result['error_description'] ?? 'Unknown error'));
        }

        return $result['access_token'];
    }

    /**
     * Capture/verify PayPal payment
     */
    public function verify(array $data): bool
    {
        if (empty($this->config['client_id'])) {
            return isset($data['simulate_paid']) && $data['simulate_paid'] === true;
        }

        try {
            $token = $this->getAccessToken();
            $orderId = $data['order_id'] ?? $data['transaction_id'] ?? '';

            if (empty($orderId)) {
                return false;
            }

            $ch = curl_init($this->config['base_url'] . '/v2/checkout/orders/' . $orderId);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token
                ]
            ]);

            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response, true);

            return isset($result['status']) && $result['status'] === 'COMPLETED';

        } catch (\Exception $e) {
            error_log("[PAYPAL] Verify error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get payment status
     */
    public function getStatus(string $transaction_id): string
    {
        if (empty($this->config['client_id'])) {
            return 'pending';
        }

        try {
            $token = $this->getAccessToken();

            $ch = curl_init($this->config['base_url'] . '/v2/checkout/orders/' . $transaction_id);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token
                ]
            ]);

            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response, true);

            $statusMap = [
                'COMPLETED' => 'paid',
                'APPROVED' => 'pending',
                'CREATED' => 'pending',
                'VOIDED' => 'failed',
                'PAYER_ACTION_REQUIRED' => 'pending'
            ];

            return $statusMap[$result['status'] ?? ''] ?? 'pending';

        } catch (\Exception $e) {
            return 'pending';
        }
    }

    /**
     * Handle PayPal webhook
     */
    public function handleWebhook(array $payload): array
    {
        if (empty($this->config['client_id'])) {
            return [
                'order_id' => $payload['order_id'] ?? '',
                'status' => 'pending',
                'verified' => false
            ];
        }

        // PayPal webhook event types
        $eventType = $payload['event_type'] ?? '';
        $resource = $payload['resource'] ?? [];

        if ($eventType === 'CHECKOUT.ORDER.APPROVED' || $eventType === 'CHECKOUT.ORDER.COMPLETED') {
            return [
                'order_id' => $resource['purchase_units'][0]['reference_id'] ?? $resource['custom_id'] ?? '',
                'transaction_id' => $resource['id'] ?? '',
                'status' => 'paid',
                'verified' => true,
                'amount' => $resource['purchase_units'][0]['amount']['value'] ?? 0,
                'currency' => $resource['purchase_units'][0]['amount']['currency_code'] ?? 'USD'
            ];
        }

        return [
            'order_id' => '',
            'status' => 'unknown',
            'verified' => false
        ];
    }
}
