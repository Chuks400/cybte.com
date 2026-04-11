<?php
// Simple CLI test for PaymentFactory
require_once __DIR__ . '/../../../src/config/database.php';
require_once __DIR__ . '/../../../src/Payment/PaymentFactory.php';

use Cybte\Payment\PaymentFactory;

echo "Database class: " . (class_exists('Database') ? "EXISTS" : "NOT FOUND") . "\n";
echo "PaymentFactory class: " . (class_exists('Cybte\Payment\PaymentFactory') ? "EXISTS" : "NOT FOUND") . "\n";

if (class_exists('Cybte\Payment\PaymentFactory')) {
    echo "Methods: " . implode(', ', PaymentFactory::getAvailableMethods()) . "\n";
}
