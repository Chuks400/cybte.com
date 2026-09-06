<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/ScanResult.php';

class ScanController
{
    private ScanResult $scanModel;

    public function __construct(?ScanResult $scanModel = null)
    {
        $this->scanModel = $scanModel ?? new ScanResult();
    }

    public function assessTarget(int $userId, string $url): array
    {
        $url = trim($url);
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Enter a valid website URL including http:// or https://.');
        }

        $parts = parse_url($url);
        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        $host = strtolower((string)($parts['host'] ?? ''));

        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new InvalidArgumentException('Only HTTP and HTTPS website URLs are supported.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException('Do not include credentials in the target URL.');
        }

        if ($host === 'localhost' || str_ends_with($host, '.local')) {
            throw new InvalidArgumentException('Local or internal hostnames are not supported.');
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $isPublic = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
            if ($isPublic === false) {
                throw new InvalidArgumentException('Private or reserved IP addresses are not supported.');
            }
        }

        $findings = [];
        $severity = 'Low';

        if ($scheme !== 'https') {
            $findings[] = 'HTTPS is not used in the submitted URL';
            $severity = 'Medium';
        } else {
            $findings[] = 'HTTPS scheme is present';
        }

        if (isset($parts['port']) && !in_array((int)$parts['port'], [80, 443], true)) {
            $findings[] = 'A non-standard web port is declared';
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $findings[] = 'The target uses a literal public IP address';
        }

        $findings[] = 'No active network scan was performed';

        if (!$this->scanModel->create($userId, $url, implode('; ', $findings), $severity)) {
            throw new RuntimeException('Unable to store cyber protection assessment.');
        }

        return [
            'target_url' => $url,
            'findings' => $findings,
            'severity' => $severity,
        ];
    }
}
