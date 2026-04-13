<?php
// Parse .env file directly and set credentials
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

    private function loadEnvCredentials() {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            require $envFile;
        }
        
        // Try to get from getenv() first (works in CLI and some web servers)
        $envHost = getenv('DB_HOST');
        $envName = getenv('DB_NAME');
        $envUser = getenv('DB_USER');
        $envPass = getenv('DB_PASS');
        $envPort = getenv('DB_PORT');
        
        // If getenv() didn't work, parse the .env file directly
        if (!$envPass) {
            $content = file_get_contents($envFile);
            if (preg_match("/putenv\\('DB_PASS=([^']*)'\\)/", $content, $m)) {
                $envPass = $m[1];
            }
            if (preg_match("/putenv\\('DB_HOST=([^']*)'\\)/", $content, $m)) {
                $envHost = $m[1];
            }
            if (preg_match("/putenv\\('DB_PORT=([^']*)'\\)/", $content, $m)) {
                $envPort = $m[1];
            }
            if (preg_match("/putenv\\('DB_USER=([^']*)'\\)/", $content, $m)) {
                $envUser = $m[1];
            }
            if (preg_match("/putenv\\('DB_NAME=([^']*)'\\)/", $content, $m)) {
                $envName = $m[1];
            }
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

        // Load credentials from .env
        $this->loadEnvCredentials();

        $portsToTry = [$this->port];
        if($this->port === 3308){
            $portsToTry[] = 3306;
        }

        $hostsToTry = [$this->host];
        // Add localhost fallback for 127.0.0.1 TCP issues
        if($this->host === '127.0.0.1'){
            $hostsToTry[] = 'localhost';
        }

        foreach($hostsToTry as $host){
            foreach($portsToTry as $port){
                try{
                    // For localhost, try socket connection first (avoids TCP SSL issues)
                    if($host === '127.0.0.1' || $host === 'localhost'){
                        $socket = 'C:/xampp/mysql/mysql.sock';
                        if(file_exists($socket)){
                            $dsn = "mysql:unix_socket=$socket;dbname=".$this->db_name.";charset=utf8mb4";
                        } else {
                            $dsn = "mysql:host=$host;port=$port;dbname=".$this->db_name.";charset=utf8mb4";
                        }
                    } else {
                        $dsn = "mysql:host=$host;port=$port;dbname=".$this->db_name.";charset=utf8mb4";
                    }
                    
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
