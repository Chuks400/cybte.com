<?php

namespace TrustShield\Payment;

/**
 * Payment Interface - Defines contract for all payment providers
 * 
 * This interface allows us to swap payment providers easily:
 * - Alipay (China)
 * - WeChat Pay (China)
 * - Stripe (International)
 * - Crypto (Future)
 */
interface PaymentInterface
{
    /**
     * Create a payment and return QR code URL
     * 
     * @param string $order_id Unique order identifier
     * @param float $amount Amount in CNY
     * @param string $description Payment description
     * @return array Contains 'qr_url' and 'transaction_id'
     */
    public function create(string $order_id, float $amount, string $description = ''): array;

    /**
     * Verify payment status
     * 
     * @param array $data Payment data to verify
     * @return bool True if payment is confirmed
     */
    public function verify(array $data): bool;

    /**
     * Get payment status from provider
     * 
     * @param string $transaction_id Provider's transaction ID
     * @return string Status: pending, paid, failed
     */
    public function getStatus(string $transaction_id): string;

    /**
     * Handle webhook callback from payment provider
     * 
     * @param array $payload Webhook payload
     * @return array Contains 'order_id', 'status', 'verified'
     */
    public function handleWebhook(array $payload): array;
}
