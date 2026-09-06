<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/RiskScore.php';

class FraudController
{
    private RiskScore $riskModel;

    public function __construct(?RiskScore $riskModel = null)
    {
        $this->riskModel = $riskModel ?? new RiskScore();
    }

    public function analyzeTransaction(int $userId, string $transactionId, float $amount, string $location, string $device): array
    {
        $riskScore = 0;
        $signals = [];

        if ($amount > 1000) {
            $riskScore += 40;
            $signals[] = 'High transaction amount';
        }

        if ($location === 'unknown') {
            $riskScore += 30;
            $signals[] = 'Unknown location';
        }

        if ($device === 'new') {
            $riskScore += 30;
            $signals[] = 'New device';
        }

        if ($riskScore >= 70) {
            $status = 'High Risk';
        } elseif ($riskScore >= 40) {
            $status = 'Medium Risk';
        } else {
            $status = 'Low Risk';
        }

        if (!$this->riskModel->create($userId, $transactionId, $riskScore, $status)) {
            throw new RuntimeException('Unable to store fraud assessment.');
        }

        return [
            'risk_score' => $riskScore,
            'status' => $status,
            'signals' => $signals,
        ];
    }
}
