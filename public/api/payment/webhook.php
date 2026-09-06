<?php

declare(strict_types=1);

header('Content-Type: application/json');

// Webhooks must never change payment state until the provider-specific
// cryptographic signature is verified. The previous handler trusted decoded
// payload fields and therefore was not safe for production use.
//
// Cybte currently confirms payments by querying the configured provider from
// the authenticated payment-status flow. Re-enable this endpoint only after
// implementing and testing the official signature verification procedure for
// each provider.
http_response_code(501);
echo json_encode([
    'success' => false,
    'error' => 'Webhook processing is disabled until provider signature verification is configured.'
]);
