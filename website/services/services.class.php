<?php

require_once __DIR__ . '/../../config.php';

class Services_class{

    protected $db;

    function __construct(){
        $this->db = new Database();
    }

    public function displayGymRates(){
        $conn = $this->db->connect();
        $sql = "SELECT p.id as program_id, p.program_name, p.price, 
                CONCAT(p.duration, ' ', dt.type_name) as validity 
                FROM programs p
                LEFT JOIN duration_types dt ON p.duration_type_id = dt.id
                WHERE p.status_id = 1";
        $result = $conn->query($sql);
        return $result;
    }



}