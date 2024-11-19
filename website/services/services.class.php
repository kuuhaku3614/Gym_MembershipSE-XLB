<?php

require_once __DIR__ . '/../../functions/config.php';

class Profile_class{

    protected $db;

    function __construct(){
        $this->db = new Database();
    }

}