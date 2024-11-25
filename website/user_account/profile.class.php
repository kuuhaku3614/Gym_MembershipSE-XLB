<?php

require_once __DIR__ . '/../../config.php';

class Profile_class{

    public $id = '';
    public $user_id = '';
    public $first_name = '';
    public $middle_name = '';
    public $last_name = '';
    public $sex = '';
    public $birthdate = '';
    public $phone_number = '';



    protected $db;

    function __construct(){
        $this->db = new Database();
    }

    public function getUserDetails($userId) {
        $conn = $this->db->connect();
        
        $query = "SELECT pd.*, CONCAT(pd.first_name, ' ', pd.middle_name, ' ', pd.last_name) AS name,
                  r.role_name
                  FROM personal_details pd 
                  JOIN users u ON pd.user_id = u.id
                  JOIN roles r ON u.role_id = r.id
                  WHERE pd.user_id = :user_id";
                  
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }



}