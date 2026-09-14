<?php

// backend/config/database.php

class Database {
    private $host = "localhost";
    private $db_name = "land_records_db";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            // Read from environment variables in production, fallback to defaults
            $this->host = getenv('DB_HOST') ?: "sql202.infinityfree.com";
            $this->db_name = getenv('DB_NAME') ?: "if0_42902886_land";
            $this->username = getenv('DB_USER') ?: "if0_42902886";
            $this->password = getenv('DB_PASSWORD') ?: "Rocky7272";

            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
            exit();
        }

        return $this->conn;
    }
}
?>
