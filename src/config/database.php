<?php
class Database {

    private $host = "127.0.0.1";
    private $db_name = "cybte";
    private $username = "root";
    private $password = "Cjohn22@";
    private $port = 3306;  // Try 3306 first, fallback to 3308

    private $lastError = null;

    public $conn;

    public function getLastError(){
        return $this->lastError;
    }

    private function requireEnvFile(): void {
        $envFile = __DIR__ . '/../../.env';
        if (!file_exists($envFile)) {
            return;
        }

        try {
            require_once $envFile;
        } catch (Throwable $e) {
            // If .env cannot be executed, fall back to parsing.
        }
    }

    private function parseEnvFile(): array {
        $envFile = __DIR__ . '/../../.env';
        $values = [];

        if (!file_exists($envFile)) {
            return $values;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, '//') || str_starts_with($line, '<?php') || str_starts_with($line, '?>')) {
                continue;
            }

            if (preg_match('/putenv\s*\(\s*["\']([^=]+)=([^"\']*)["\']\s*\)/i', $line, $matches)) {
                $values[$matches[1]] = $matches[2];
                continue;
            }

            if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/', $line, $matches)) {
                $value = trim($matches[2]);
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }
                $values[$matches[1]] = $value;
            }
        }

        return $values;
    }

    private function loadEnvCredentials() {
        $this->requireEnvFile();
        $envValues = $this->parseEnvFile();

        $envHost = getenv('DB_HOST');
        $envName = getenv('DB_NAME');
        $envUser = getenv('DB_USER');
        $envPass = getenv('DB_PASS');
        $envPort = getenv('DB_PORT');

        if (!$envHost && isset($envValues['DB_HOST'])) {
            $envHost = $envValues['DB_HOST'];
        }
        if (!$envName && isset($envValues['DB_NAME'])) {
            $envName = $envValues['DB_NAME'];
        }
        if (!$envUser && isset($envValues['DB_USER'])) {
            $envUser = $envValues['DB_USER'];
        }
        if (!$envPass && isset($envValues['DB_PASS'])) {
            $envPass = $envValues['DB_PASS'];
        }
        if (!$envPort && isset($envValues['DB_PORT'])) {
            $envPort = $envValues['DB_PORT'];
        }

        if($envHost){ $this->host = $envHost; }
        if($envName){ $this->db_name = $envName; }
        if($envUser){ $this->username = $envUser; }
        if($envPass){ $this->password = $envPass; }
        if($envPort){ $this->port = (int)$envPort; }
    }

    private function isUnknownDatabaseError(string $message): bool {
        return stripos($message, 'Unknown database') !== false;
    }

    private function createDatabaseIfMissing(string $host, int $port): bool {
        $sqlFile = __DIR__ . '/../../database/trustshield.sql';
        if (!file_exists($sqlFile)) {
            return false;
        }

        try {
            $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $this->username, $this->password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = file_get_contents($sqlFile);
            $sql = preg_replace('/^\s*(CREATE DATABASE|USE) .*$/mi', '', $sql);
            $statements = array_filter(array_map('trim', explode(';', $sql)));

            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$this->db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$this->db_name}`");

            foreach ($statements as $statement) {
                if ($statement === '' || preg_match('/^\s*(--|\/\*)/', $statement)) {
                    continue;
                }
                $pdo->exec($statement);
            }

            return true;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function connect(){

        $this->conn = null;
        $this->lastError = null;

        $this->loadEnvCredentials();

        $portsToTry = [$this->port];
        if ($this->port !== 3306) {
            $portsToTry[] = 3306;
        }
        if ($this->port !== 3308) {
            $portsToTry[] = 3308;
        }
        $portsToTry = array_unique($portsToTry);

        $hostsToTry = [$this->host];
        if($this->host === '127.0.0.1'){
            $hostsToTry[] = 'localhost';
        }

        foreach($hostsToTry as $host){
            foreach($portsToTry as $port){
                try{
                    $dsn = "mysql:host=$host;port=$port;dbname=".$this->db_name.";charset=utf8mb4";
                    $this->conn = new PDO($dsn, $this->username, $this->password);
                    $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    return $this->conn;
                }catch(PDOException $e){
                    $this->lastError = $e->getMessage();
                    $this->conn = null;

                    if ($this->isUnknownDatabaseError($e->getMessage()) && $this->createDatabaseIfMissing($host, $port)) {
                        try {
                            $dsn = "mysql:host=$host;port=$port;dbname=".$this->db_name.";charset=utf8mb4";
                            $this->conn = new PDO($dsn, $this->username, $this->password);
                            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                            return $this->conn;
                        } catch (PDOException $reconnectException) {
                            $this->lastError = $reconnectException->getMessage();
                            $this->conn = null;
                        }
                    }
                }
            }
        }

        throw new RuntimeException('Database connection failed. Please contact support.');
    }
}
