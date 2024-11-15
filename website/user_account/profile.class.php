<?php

require_once '../config.php';

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
        
        $query = "SELECT pd.*, CONCAT(pd.first_name, ' ', pd.middle_name, ' ', pd.last_name) AS name
                  FROM personal_details pd 
                  WHERE pd.user_id = :user_id";
                  
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }



}