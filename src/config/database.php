<?php
// Load .env file directly
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    require_once $envFile;
}

class Database {

    private $host = "127.0.0.1";
    private $db_name = "cybte";
    private $username = "root";
    private $password = "";  // XAMPP default: no password
    private $port = 3308;   // XAMPP MySQL port

    private $lastError = null;

    public $conn;

    public function getLastError(){
        return $this->lastError;
    }

    private function loadEnvCredentials() {
        $envHost = getenv('DB_HOST');
        $envName = getenv('DB_NAME');
        $envUser = getenv('DB_USER');
        $envPass = getenv('DB_PASS');
        $envPort = getenv('DB_PORT');

        if($envHost){ $this->host = $envHost; }
        if($envName){ $this->db_name = $envName; }
        if($envUser){ $this->username = $envUser; }
        // Explicitly check for false/null but allow empty string
        if($envPass !== false && $envPass !== null){ $this->password = $envPass; }
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
