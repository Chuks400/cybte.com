<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class ScanResult
{
    private PDO $conn;
    private string $table = 'scan_results';

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function create(int $userId, string $targetUrl, string $findings, string $severity): bool
    {
        $query = "INSERT INTO {$this->table} (user_id, target_url, vulnerabilities, severity)
                  VALUES (:user_id, :target_url, :findings, :severity)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':user_id' => $userId,
            ':target_url' => $targetUrl,
            ':findings' => $findings,
            ':severity' => $severity,
        ]);
    }

    public function listByUser(int $userId, int $limit = 10): array
    {
        $limit = max(1, min($limit, 50));
        $stmt = $this->conn->prepare(
            "SELECT id, target_url, vulnerabilities, severity, created_at
             FROM {$this->table}
             WHERE user_id = :user_id
             ORDER BY created_at DESC, id DESC
             LIMIT {$limit}"
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
