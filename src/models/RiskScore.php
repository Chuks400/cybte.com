<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class RiskScore
{
    private PDO $conn;
    private string $table = 'fraud_scores';

    public function __construct(?PDO $conn = null)
    {
        if ($conn instanceof PDO) {
            $this->conn = $conn;
            return;
        }

        $database = new Database();
        $this->conn = $database->connect();
    }

    public function create(int $userId, string $transactionId, int $riskScore, string $status): bool
    {
        $query = "INSERT INTO {$this->table} (user_id, transaction_id, risk_score, status)
                  VALUES (:user_id, :transaction_id, :risk_score, :status)";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':user_id' => $userId,
            ':transaction_id' => mb_substr($transactionId, 0, 100),
            ':risk_score' => $riskScore,
            ':status' => mb_substr($status, 0, 50),
        ]);
    }

    public function listByUser(int $userId, int $limit = 10): array
    {
        $limit = max(1, min($limit, 50));
        $stmt = $this->conn->prepare(
            "SELECT id, transaction_id, risk_score, status, created_at
             FROM {$this->table}
             WHERE user_id = :user_id
             ORDER BY created_at DESC, id DESC
             LIMIT {$limit}"
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
