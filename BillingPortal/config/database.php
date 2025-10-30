<?php
// config/database.php - Database configuration
class Database {
    private $host = 'localhost';
    private $db_name = 'u351135015_billing_portal';
    private $username = 'u351135015_animesh';
    private $password = 'Lbbiswas&$!&#!%%(&@1230';
    private $charset = 'utf8mb4';
    private $pdo;

    public function getConnection() {
        if ($this->pdo === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                
                $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e) {
                error_log("Database connection error: " . $e->getMessage());
                throw new PDOException("Database connection failed", (int)$e->getCode());
            }
        }
        return $this->pdo;
    }
}
?>