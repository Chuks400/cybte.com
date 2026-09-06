<?php

namespace Cybte\Services;

use PDO;
use RuntimeException;

class VaultService
{
    private PDO $conn;
    private string $storagePath;
    private string $key;

    private const MAX_FILE_SIZE = 10485760; // 10 MB
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'text/plain',
        'text/csv',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
        $this->storagePath = getenv('VAULT_STORAGE_PATH') ?: dirname(__DIR__, 2) . '/storage/vault';
        $this->key = $this->resolveKey();

        if (!is_dir($this->storagePath) && !mkdir($this->storagePath, 0700, true) && !is_dir($this->storagePath)) {
            throw new RuntimeException('Secure Vault storage is not writable.');
        }
    }

    private function resolveKey(): string
    {
        $configured = getenv('APP_KEY') ?: '';
        if (str_starts_with($configured, 'base64:')) {
            $decoded = base64_decode(substr($configured, 7), true);
            if ($decoded !== false && strlen($decoded) >= 32) {
                return substr($decoded, 0, 32);
            }
        }

        if ($configured !== '' && strlen($configured) >= 32) {
            return substr(hash('sha256', $configured, true), 0, 32);
        }

        throw new RuntimeException('APP_KEY is required for Secure Vault encryption.');
    }

    public function listDocuments(int $userId): array
    {
        $stmt = $this->conn->prepare('SELECT id, original_name, mime_type, size_bytes, sha256, created_at FROM vault_documents WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function upload(int $userId, array $file): array
    {
        $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('The upload did not complete successfully.');
        }

        $tmpPath = (string)($file['tmp_name'] ?? '');
        $originalName = trim((string)($file['name'] ?? 'document'));
        $size = (int)($file['size'] ?? 0);

        if ($size < 1 || $size > self::MAX_FILE_SIZE) {
            throw new RuntimeException('Files must be between 1 byte and 10 MB.');
        }

        if (!is_uploaded_file($tmpPath)) {
            throw new RuntimeException('Invalid upload source.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = (string)$finfo->file($tmpPath);
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new RuntimeException('This file type is not allowed in Secure Vault.');
        }

        $plain = file_get_contents($tmpPath);
        if ($plain === false) {
            throw new RuntimeException('Unable to read the uploaded document.');
        }

        $storedName = bin2hex(random_bytes(24)) . '.vault';
        $encrypted = $this->encrypt($plain);
        $targetPath = $this->storagePath . DIRECTORY_SEPARATOR . $storedName;

        if (file_put_contents($targetPath, $encrypted, LOCK_EX) === false) {
            throw new RuntimeException('Unable to store the encrypted document.');
        }
        @chmod($targetPath, 0600);

        $sha256 = hash('sha256', $plain);

        try {
            $stmt = $this->conn->prepare('INSERT INTO vault_documents (user_id, original_name, stored_name, mime_type, size_bytes, sha256, encryption_method) VALUES (:user_id, :original_name, :stored_name, :mime_type, :size_bytes, :sha256, :encryption_method)');
            $stmt->execute([
                ':user_id' => $userId,
                ':original_name' => mb_substr(basename($originalName), 0, 255),
                ':stored_name' => $storedName,
                ':mime_type' => $mimeType,
                ':size_bytes' => $size,
                ':sha256' => $sha256,
                ':encryption_method' => 'AES-256-GCM',
            ]);
            $documentId = (int)$this->conn->lastInsertId();
            $this->audit($userId, $documentId, 'upload', 'Document encrypted and stored');
            return ['id' => $documentId, 'name' => $originalName];
        } catch (\Throwable $e) {
            @unlink($targetPath);
            throw $e;
        }
    }

    public function download(int $userId, int $documentId): array
    {
        $stmt = $this->conn->prepare('SELECT * FROM vault_documents WHERE id = :id AND user_id = :user_id LIMIT 1');
        $stmt->execute([':id' => $documentId, ':user_id' => $userId]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$document) {
            throw new RuntimeException('Document not found.');
        }

        $path = $this->storagePath . DIRECTORY_SEPARATOR . $document['stored_name'];
        $payload = is_file($path) ? file_get_contents($path) : false;
        if ($payload === false) {
            throw new RuntimeException('Encrypted document is unavailable.');
        }

        $plain = $this->decrypt($payload);
        if (!hash_equals((string)$document['sha256'], hash('sha256', $plain))) {
            throw new RuntimeException('Document integrity verification failed.');
        }

        $this->audit($userId, $documentId, 'download', 'Document decrypted for authorized retrieval');

        return [
            'name' => (string)$document['original_name'],
            'mime_type' => (string)$document['mime_type'],
            'content' => $plain,
        ];
    }

    public function delete(int $userId, int $documentId): void
    {
        $stmt = $this->conn->prepare('SELECT stored_name FROM vault_documents WHERE id = :id AND user_id = :user_id LIMIT 1');
        $stmt->execute([':id' => $documentId, ':user_id' => $userId]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$document) {
            throw new RuntimeException('Document not found.');
        }

        $this->conn->beginTransaction();
        try {
            $this->audit($userId, $documentId, 'delete', 'Document removed by owner');
            $delete = $this->conn->prepare('DELETE FROM vault_documents WHERE id = :id AND user_id = :user_id');
            $delete->execute([':id' => $documentId, ':user_id' => $userId]);
            $this->conn->commit();
        } catch (\Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }

        $path = $this->storagePath . DIRECTORY_SEPARATOR . $document['stored_name'];
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function encrypt(string $plain): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($plain, 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        if ($ciphertext === false || strlen($tag) !== 16) {
            throw new RuntimeException('Document encryption failed.');
        }
        return 'CYBTEV1' . $iv . $tag . $ciphertext;
    }

    private function decrypt(string $payload): string
    {
        if (!str_starts_with($payload, 'CYBTEV1') || strlen($payload) < 35) {
            throw new RuntimeException('Invalid encrypted document format.');
        }
        $iv = substr($payload, 7, 12);
        $tag = substr($payload, 19, 16);
        $ciphertext = substr($payload, 35);
        $plain = openssl_decrypt($ciphertext, 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag, '');
        if ($plain === false) {
            throw new RuntimeException('Document decryption failed.');
        }
        return $plain;
    }

    private function audit(int $userId, ?int $documentId, string $action, string $detail): void
    {
        $stmt = $this->conn->prepare('INSERT INTO vault_audit_logs (user_id, document_id, action, detail) VALUES (:user_id, :document_id, :action, :detail)');
        $stmt->execute([
            ':user_id' => $userId,
            ':document_id' => $documentId,
            ':action' => $action,
            ':detail' => mb_substr($detail, 0, 255),
        ]);
    }
}
