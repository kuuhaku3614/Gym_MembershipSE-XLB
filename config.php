<?php
// Define base URL for pretty URLs
define('BASE_URL', 'http://localhost/Gym_MembershipSE-XLB');

class Database {
   
    private $host = 'localhost';      
    private $username = 'root';       
    private $password = '';           
    private $dbname = 'gym_managementdb';

    public $connection; 
    
    function connect() {
        try {
            if($this->connection === null) {
                $this->connection = new PDO(
                    "mysql:host=$this->host;dbname=$this->dbname", 
                    $this->username, 
                    $this->password,
                    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
                );
            }
            return $this->connection;
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
}

// Initialize database
$database = new Database();

try {
    // Test database connection
    $pdo = $database->connect();
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}