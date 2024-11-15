<?php

class Database{
   
    private $host = 'localhost';      
    private $username = 'root';       
    private $password = '';           
    private $dbname = 'gym_managementdb';

    protected $connection; 
    
    function connect(){
        try {
            if($this->connection === null){
                $this->connection = new PDO("mysql:host=$this->host;dbname=$this->dbname", $this->username, $this->password);
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
            return $this->connection;
        } catch(PDOException $e) {
            echo "Connection error: " . $e->getMessage();
            exit();
        }
    }
}

// Create database instance and get connection
$database = new Database();
$pdo = $database->connect();
