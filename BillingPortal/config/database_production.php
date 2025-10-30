<?php
// config/database_production.php - Production database configuration template
class Database {
    // UPDATE THESE WITH YOUR HOSTINGER DATABASE CREDENTIALS
    private $host = 'localhost';                    // Usually 'localhost' on Hostinger
    private $db_name = 'u351135015_billing_portal';       // Replace with your actual database name
    private $username = 'u351135015_animesh';         // Replace with your actual database username
    private $password = 'Lbbiswas&$!&#!%%(&@1230';    // Replace with your actual database password
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
                    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false, // For shared hosting
                ];
                
                $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e) {
                // Log error instead of displaying it in production
                error_log("Database connection error: " . $e->getMessage());
                throw new PDOException("Database connection failed", (int)$e->getCode());
            }
        }
        return $this->pdo;
    }
}

/*
HOSTINGER DATABASE SETUP INSTRUCTIONS:

1. Login to your Hostinger hPanel
2. Go to "Databases" → "MySQL Databases"  
3. Create a new database
4. Note down these credentials:
   - Database Name: u[account_id]_[db_name]
   - Username: u[account_id]_[username]  
   - Password: [your_chosen_password]
   - Host: localhost

5. Replace the values above with your actual credentials
6. Rename this file to database.php (overwrite the existing one)
7. Upload to your config/ directory on Hostinger

Example Hostinger credentials format:
- Host: localhost
- Database: u123456789_billing_portal
- Username: u123456789_billing_user
- Password: SecurePass123!
*/
?>