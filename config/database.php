<?php

class Database {

    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function __construct() {
        $this->host     = getenv('DB_HOST');
        $this->db_name  = getenv('DB_NAME');
        $this->username = getenv('DB_USER');
        $this->password = getenv('DB_PASS');
        $this->port     = getenv('DB_PORT');
    }

    public function getConnection() {

        $this->conn = null;

        try {

            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";

            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);

        } catch(PDOException $exception) {
            die("Database Connection Failed: " . $exception->getMessage());
        }

        return $this->conn;
    }
}
