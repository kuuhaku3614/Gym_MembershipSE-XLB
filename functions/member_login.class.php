<?php

require_once 'database.php';

class Customer {
    public $customer_id = '';
    public $first_name = '';
    public $last_name = '';
    public $contact_no = '';
    public $email = '';
    public $password = '';
    public $role = '';
    public $isAdmin = false;
    public $isCustomer = true;

    protected $db;

    function __construct() {
        $this->db = new Database();
    }

    function addCustomer() {
        $this->role = 'customer'; // Set role for customer
        $this->isAdmin = false; // Ensure isAdmin is false for customers
        $this->isCustomer = true; // Ensure isCustomer is true for customers

        return $this->insertUser();
    }

    function addAdmin() {
        $this->role = 'admin'; // Set role for admin
        $this->isAdmin = true; // Set isAdmin to true
        $this->isCustomer = false; // Set isCustomer to false

        return $this->insertUser();
    }

    private function insertUser() {
        $sql = "INSERT INTO customer (first_name, last_name, contact_no, email, password, role, isAdmin, isCustomer) VALUES (:first_name, :last_name, :contact_no, :email, :password, :role, :isAdmin, :isCustomer);";
        $query = $this->db->connect()->prepare($sql);

        $query->bindParam(':first_name', $this->first_name);
        $query->bindParam(':last_name', $this->last_name);
        $query->bindParam(':contact_no', $this->contact_no);
        $query->bindParam(':email', $this->email);
        $hashpassword = password_hash($this->password, PASSWORD_DEFAULT);
        $query->bindParam(':password', $hashpassword);
        $query->bindParam(':role', $this->role);
        $query->bindParam(':isAdmin', $this->isAdmin);
        $query->bindParam(':isCustomer', $this->isCustomer);

        return $query->execute();
    }

    function emailExist($email, $excludeID = null) {
        $sql = "SELECT COUNT(*) FROM customer WHERE email = :email";
        if ($excludeID) {
            $sql .= " and customer_id != :excludeID"; // Adjusted to use customer_id
        }

        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':email', $email);

        if ($excludeID) {
            $query->bindParam(':excludeID', $excludeID);
        }

        $count = $query->execute() ? $query->fetchColumn() : 0;

        return $count > 0;
    }

    function login($email, $password) {
        $sql = "SELECT * FROM customer WHERE email = :email LIMIT 1;";
        $query = $this->db->connect()->prepare($sql);

        $query->bindParam('email', $email);

        if ($query->execute()) {
            $data = $query->fetch();
            if ($data && password_verify($password, $data['password'])) {
                return true;
            }
        }

        return false;
    }

    function fetch($email) {
        $sql = "SELECT * FROM customer WHERE email = :email LIMIT 1;";
        $query = $this->db->connect()->prepare($sql);

        $query->bindParam('email', $email);
        $data = null;
        if ($query->execute()) {
            $data = $query->fetch();
        }

        return $data;
    }
}