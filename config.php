<?php
class Database {
   
    private $host = 'localhost';      
    private $username = 'root';       
    private $password = '';           
    private $dbname = 'gym_managementdb';

    public $connection; 
    
    function connect() {
        if($this->connection === null) {
            $this->connection = new PDO(
                "mysql:host=$this->host;dbname=$this->dbname", 
                $this->username, 
                $this->password
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return $this->connection;
    }
}

// Initialize error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize database
$database = new Database();

try {
    // Test database connection
    $pdo = $database->connect();
    
} catch (Exception $e) {
    die("Connection failed");
}