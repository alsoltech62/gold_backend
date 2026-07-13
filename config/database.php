<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        $this->host     = getenv('DB_HOST')     ?: 'localhost';
        $this->db_name  = getenv('DB_NAME')     ?: 'gold_platform';
        $this->username = getenv('DB_USER')     ?: 'root';
        $this->password = getenv('DB_PASSWORD') ?: '';
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
            
            // Sync live rates with markup
            require_once __DIR__ . '/../api/helpers/rates.php';
            sync_live_rates($this->conn);
        } catch(PDOException $e) {
            http_response_code(500);
            $message = 'Database connection failed';
            if (getenv('APP_ENV') === 'development') {
                $message .= ': ' . $e->getMessage();
            }
            echo json_encode(['success' => false, 'message' => $message]);
            exit();
        }
        return $this->conn;
    }
}
