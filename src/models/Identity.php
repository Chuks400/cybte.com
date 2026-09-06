<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class Identity
{
    private PDO $conn;
    private string $table = 'identity_verifications';

    private const ALLOWED_TYPES = ['passport', 'national_id', 'drivers_license'];

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function create(int $userId, string $documentType, string $documentNumber): bool
    {
        $documentType = strtolower(trim($documentType));
        $documentNumber = trim($documentNumber);

        if (!in_array($documentType, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException('Unsupported document type.');
        }

        if (mb_strlen($documentNumber) < 4 || mb_strlen($documentNumber) > 100) {
            throw new InvalidArgumentException('Enter a valid document number.');
        }

        $protectedNumber = $this->protectDocumentNumber($documentNumber);

        $query = "INSERT INTO {$this->table} (user_id, document_type, document_number, status)\n"
            . "VALUES (:user_id, :document_type, :document_number, 'pending')";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':user_id' => $userId,
            ':document_type' => $documentType,
            ':document_number' => $protectedNumber,
        ]);
    }

    public function listByUser(int $userId, int $limit = 10): array
    {
        $limit = max(1, min($limit, 50));
        $stmt = $this->conn->prepare(
            "SELECT id, document_type, status, created_at FROM {$this->table} "
            . "WHERE user_id = :user_id ORDER BY created_at DESC LIMIT {$limit}"
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function protectDocumentNumber(string $documentNumber): string
    {
        $configured = (string)(getenv('APP_KEY') ?: '');
        $key = '';

        if (str_starts_with($configured, 'base64:')) {
            $decoded = base64_decode(substr($configured, 7), true);
            if ($decoded !== false && strlen($decoded) >= 32) {
                $key = substr($decoded, 0, 32);
            }
        } elseif (strlen($configured) >= 32) {
            $key = hash('sha256', $configured, true);
        }

        if ($key === '') {
            throw new RuntimeException('APP_KEY is required to protect identity references.');
        }

        $normalized = strtoupper(preg_replace('/\s+/', '', $documentNumber) ?? $documentNumber);
        return 'hmac-sha256:' . hash_hmac('sha256', $normalized, $key);
    }
}
