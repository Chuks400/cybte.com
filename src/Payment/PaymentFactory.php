<?php

namespace Cybte\Payment;

/**
 * Payment Factory
 * 
 * Factory pattern to create payment provider instances
 * Makes it easy to add new payment methods in the future
 */
class PaymentFactory
{
    /**
     * Available payment providers
     */
    private static array $providers = [
        'alipay' => Alipay::class,
        'wechat' => WeChatPay::class,
        'wechatpay' => WeChatPay::class,
        'paypal' => PayPal::class,
    ];

    /**
     * Create payment provider instance
     * 
     * @param string $method Payment method (alipay, wechat, etc.)
     * @param array $config Optional configuration override
     * @return PaymentInterface
     * @throws \InvalidArgumentException If provider not found
     */
    public static function create(string $method, array $config = []): PaymentInterface
    {
        $method = strtolower($method);

        if (!isset(self::$providers[$method])) {
            throw new \InvalidArgumentException(
                "Payment method '{$method}' not supported. " .
                "Available: " . implode(', ', array_keys(self::$providers))
            );
        }

        $className = self::$providers[$method];
        return new $className($config);
    }

    /**
     * Register a new payment provider
     * 
     * @param string $name Provider name
     * @param string $className Fully qualified class name
     */
    public static function register(string $name, string $className): void
    {
        // Verify class implements PaymentInterface
        if (!in_array(PaymentInterface::class, class_implements($className))) {
            throw new \InvalidArgumentException(
                "Class {$className} must implement PaymentInterface"
            );
        }

        self::$providers[strtolower($name)] = $className;
    }

    /**
     * Get list of available payment methods
     * 
     * @return array
     */
    public static function getAvailableMethods(): array
    {
        return array_keys(self::$providers);
    }

    /**
     * Check if payment method is supported
     * 
     * @param string $method
     * @return bool
     */
    public static function isSupported(string $method): bool
    {
        return isset(self::$providers[strtolower($method)]);
    }
}
