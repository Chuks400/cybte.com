<?php

declare(strict_types=1);

class Database
{
    private ?PDO $conn = null;
    private ?string $lastError = null;

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    private function loadEnvFile(): void
    {
        $envFile = __DIR__ . '/../../.env';
        if (!is_file($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, '//') || str_starts_with($line, '<?php') || str_starts_with($line, '?>')) {
                continue;
            }

            if (preg_match('/putenv\s*\(\s*["\']([^=]+)=([^"\']*)["\']\s*\)/i', $line, $matches)) {
                if (getenv($matches[1]) === false) {
                    putenv($matches[1] . '=' . $matches[2]);
                }
                continue;
            }

            if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/', $line, $matches)) {
                $value = trim($matches[2]);
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }
                if (getenv($matches[1]) === false) {
                    putenv($matches[1] . '=' . $value);
                }
            }
        }
    }

    public function connect(): PDO
    {
        if ($this->conn instanceof PDO) {
            return $this->conn;
        }

        $this->loadEnvFile();

        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $name = getenv('DB_NAME') ?: 'cybte';
        $user = getenv('DB_USER') ?: '';
        $pass = getenv('DB_PASS');
        $port = (int)(getenv('DB_PORT') ?: 3306);

        if ($user === '' || $pass === false) {
            throw new RuntimeException('Database credentials are not configured. Set DB_USER and DB_PASS in the server environment.');
        }

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
            $this->conn = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return $this->conn;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log('Database connection failed: ' . $e->getMessage());
            throw new RuntimeException('Database connection failed. Please contact support.');
        }
    }
}
