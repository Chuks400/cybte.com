<?php
class Database {

    private $host = "127.0.0.1";
    private $db_name = "cybte";
    private $username = "root";
    private $password = "Cjohn22@";
    private $port = 3308;

    private $lastError = null;

    public $conn;

    public function getLastError(){
        return $this->lastError;
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
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, '//')) {
                continue;
            }

            if (preg_match('/putenv\s*\(\s*["\']([^=]+)=([^"\']*)["\']\s*\)/', $line, $matches)) {
                $values[$matches[1]] = $matches[2];
            }
        }

        return $values;
    }

    private function loadEnvCredentials() {
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

    public function connect(){

        $this->conn = null;
        $this->lastError = null;

        $this->loadEnvCredentials();

        $portsToTry = [$this->port];
        if($this->port === 3308){
            $portsToTry[] = 3306;
        }

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
                }
            }
        }

        throw new RuntimeException('Database connection failed. Please contact support.');
    }
}
