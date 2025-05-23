<?php
// Define base URL for pretty URLs
$current_url = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
$protocol = $is_https ? 'https://' : 'http://';
// Define the mapping of hosts to their respective base paths
$url_config = [
    'localhost' => '/Gym_MembershipSE-XLB',
    // Add your production domain here when ready
    // 'yourdomain.com' => '',
];
// Get the base path for the current host
$base_path = isset($url_config[$current_url]) ? $url_config[$current_url] : '';
// Construct the full BASE_URL
define('BASE_URL', $protocol . $current_url . $base_path);
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
                
                // Set timezone to Asia/Manila (+08:00)
                $this->connection->exec("SET time_zone = '+08:00'");
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
    
    // Additionally set timezone at the global level
    $pdo->exec("SET time_zone = '+08:00'");
    
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}