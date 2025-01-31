<?php
class Database {
   
    private $host = 'localhost';      
    private $username = 'root';       
    private $password = '';           
    private $dbname = 'gym_managementdb';

    public $connection; 
    
    function connect() {
        try {
            if($this->connection === null) {
                error_log("Attempting database connection to {$this->host}...");
                $this->connection = new PDO(
                    "mysql:host=$this->host;dbname=$this->dbname", 
                    $this->username, 
                    $this->password,
                    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
                );
                error_log("Database connection successful");
            }
            return $this->connection;
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
}

// Initialize error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error.log');

// Initialize database
$database = new Database();

try {
    // Test database connection
    $pdo = $database->connect();
    error_log("Initial database connection test successful");
} catch (Exception $e) {
    error_log("Initial database connection test failed: " . $e->getMessage());
    die("Connection failed: " . $e->getMessage());
}