<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../functions/sanitize.php';

class Cart_Class {
    protected $db;

    function __construct() {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            if (!isset($_SESSION['cart'])) {
                $this->initializeCart();
            }
            
            $this->db = new Database();
            
        } catch (Exception $e) {
            throw new Exception("Failed to initialize cart: " . $e->getMessage());
        }
    }

    private function initializeCart() {
        $_SESSION['cart'] = [
            'memberships' => [],
            'programs' => [],
            'rentals' => [],
            'walkins' => [],
            'registration_fee' => null,
            'total' => 0
        ];
    }

    public function addMembership($item) {
        try {
            // Validate required fields
            $required_fields = ['id', 'plan_name', 'price', 'validity', 'start_date', 'end_date'];
            foreach ($required_fields as $field) {
                if (!isset($item[$field]) || (is_string($item[$field]) && trim($item[$field]) === '')) {
                    throw new Exception("Missing required field: " . $field);
                }
            }

            // Initialize cart if not set
            if (!isset($_SESSION['cart'])) {
                $this->initializeCart();
            }

            // Add membership to cart
            $_SESSION['cart']['memberships'][] = [
                'id' => clean_input($item['id']),
                'name' => clean_input($item['plan_name']),
                'price' => floatval(clean_input($item['price'])),
                'validity' => clean_input($item['validity']),
                'type' => 'membership',
                'start_date' => clean_input($item['start_date']),
                'end_date' => clean_input($item['end_date'])
            ];

            // Get user role directly from database
            $conn = $this->db->connect();
            $sql = "SELECT role_id FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([clean_input($_SESSION['user_id'])]);
            $userRole = $stmt->fetch();
            
            // Check if user is a member (role_id = 4) or coach (role_id = 3)
            if ($userRole && ($userRole['role_id'] == 3 || $userRole['role_id'] == 4)) {
                $this->removeRegistrationFee();
            } else {
                $services = new Services_Class();
                $hasActiveMembership = $services->checkActiveMembership(clean_input($_SESSION['user_id']));
                
                if (!$hasActiveMembership && empty($_SESSION['cart']['registration_fee'])) {
                    $this->addRegistrationFee();
                }
            }

            $this->updateTotal();
            return true;
        } catch (Exception $e) {
            throw new Exception("Failed to add membership to cart: " . $e->getMessage());
        }
    }

    public function addProgram($item) {
        try {
            $_SESSION['cart']['programs'][] = [
                'id' => clean_input($item['id']),
                'name' => clean_input($item['name']),
                'price' => floatval(clean_input($item['price'])),
                'validity' => clean_input($item['validity']),
                'type' => 'program',
                'coach_id' => clean_input($item['coach_id']),
                'coach_name' => clean_input($item['coach_name']),
                'start_date' => clean_input($item['start_date']),
                'end_date' => clean_input($item['end_date'])
            ];
            $this->updateTotal();
            return true;
        } catch (Exception $e) {
            throw new Exception("Failed to add program to cart");
        }
    }

    public function addRental($item) {
        try {
            // Check available slots
            $sql = "SELECT available_slots FROM rental_services WHERE id = ?";
            $stmt = $this->db->connect()->prepare($sql);
            $stmt->execute([clean_input($item['id'])]);
            $result = $stmt->fetch();
            
            if ($result && $result['available_slots'] < 1) {
                return false;
            }

            $_SESSION['cart']['rentals'][] = [
                'id' => clean_input($item['id']),
                'name' => clean_input($item['name']),
                'price' => floatval(clean_input($item['price'])),
                'validity' => clean_input($item['validity']),
                'type' => 'rental',
                'start_date' => clean_input($item['start_date']),
                'end_date' => clean_input($item['end_date'])
            ];
            
            $this->updateTotal();
            return true;
        } catch (Exception $e) {
            throw new Exception("Failed to add rental to cart");
        }
    }

    public function addRegistrationFee() {
        try {
            $db = new Database();
            $conn = $db->connect();
            
            // Get registration fee from database
            $sql = "SELECT membership_fee FROM registration WHERE id = 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            
            $fee = $result ? floatval($result['membership_fee']) : 150.00; // Default to 150 if not found
            
            $_SESSION['cart']['registration_fee'] = [
                'name' => 'Registration Fee',
                'price' => $fee,
                'type' => 'registration'
            ];
            
            $this->updateTotal();
            return true;
        } catch (Exception $e) {
            throw new Exception("Failed to add registration fee to cart");
        }
    }

    public function removeRegistrationFee() {
        try {
            $_SESSION['cart']['registration_fee'] = null;
            $this->updateTotal();
            return true;
        } catch (Exception $e) {
            throw new Exception("Failed to remove registration fee from cart");
        }
    }

    public function removeItem($type, $id) {
        try {
            switch ($type) {
                case 'membership':
                    if (isset($_SESSION['cart']['memberships'][$id])) {
                        unset($_SESSION['cart']['memberships'][$id]);
                        $_SESSION['cart']['memberships'] = array_values($_SESSION['cart']['memberships']);
                        
                        // If no memberships left, remove registration fee
                        if (empty($_SESSION['cart']['memberships'])) {
                            $this->removeRegistrationFee();
                        }
                    }
                    break;
                    
                case 'program':
                    if (isset($_SESSION['cart']['programs'][$id])) {
                        unset($_SESSION['cart']['programs'][$id]);
                        $_SESSION['cart']['programs'] = array_values($_SESSION['cart']['programs']);
                    }
                    break;
                    
                case 'rental':
                    if (isset($_SESSION['cart']['rentals'][$id])) {
                        unset($_SESSION['cart']['rentals'][$id]);
                        $_SESSION['cart']['rentals'] = array_values($_SESSION['cart']['rentals']);
                    }
                    break;
                    
                case 'walkin':
                    if (isset($_SESSION['cart']['walkins'][$id])) {
                        unset($_SESSION['cart']['walkins'][$id]);
                        $_SESSION['cart']['walkins'] = array_values($_SESSION['cart']['walkins']);
                    }
                    break;
            }
            
            $this->updateTotal();
            return true;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function updateTotal() {
        $total = 0;

        // Add membership prices
        if (!empty($_SESSION['cart']['memberships'])) {
            foreach ($_SESSION['cart']['memberships'] as $membership) {
                $total += $membership['price'];
            }
        }

        // Add program prices
        if (!empty($_SESSION['cart']['programs'])) {
            foreach ($_SESSION['cart']['programs'] as $program) {
                $total += $program['price'];
            }
        }

        // Add rental prices
        if (!empty($_SESSION['cart']['rentals'])) {
            foreach ($_SESSION['cart']['rentals'] as $rental) {
                $total += $rental['price'];
            }
        }

        // Add walk-in prices
        if (!empty($_SESSION['cart']['walkins'])) {
            foreach ($_SESSION['cart']['walkins'] as $walkin) {
                $total += $walkin['price'];
            }
        }

        // Add registration fee if exists
        if (isset($_SESSION['cart']['registration_fee']) && !empty($_SESSION['cart']['registration_fee']['price'])) {
            $total += $_SESSION['cart']['registration_fee']['price'];
        }

        $_SESSION['cart']['total'] = $total;
    }

    public function getCart() {
        if (!isset($_SESSION['cart'])) {
            $this->initializeCart();
        }
        
        // Make sure all arrays exist
        if (!isset($_SESSION['cart']['memberships'])) $_SESSION['cart']['memberships'] = [];
        if (!isset($_SESSION['cart']['programs'])) $_SESSION['cart']['programs'] = [];
        if (!isset($_SESSION['cart']['rentals'])) $_SESSION['cart']['rentals'] = [];
        if (!isset($_SESSION['cart']['walkins'])) $_SESSION['cart']['walkins'] = [];
        if (!isset($_SESSION['cart']['total'])) $_SESSION['cart']['total'] = 0;
        
        return $_SESSION['cart'];
    }

    public function clearCart() {
        try {
            $_SESSION['cart'] = [
                'memberships' => [],
                'programs' => [],
                'rentals' => [],
                'walkins' => [],
                'registration_fee' => null,
                'total' => 0
            ];
            
            return true;
        } catch (Exception $e) {
            throw new Exception("Failed to clear cart: " . $e->getMessage());
        }
    }

    public function validateCart() {
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('User not logged in');
            }

            $services = new Services_Class();
            $hasMembership = $services->checkActiveMembership($_SESSION['user_id']);
            $hasPrograms = !empty($_SESSION['cart']['programs']);
            $hasRentals = !empty($_SESSION['cart']['rentals']);
            $hasMembershipInCart = $this->hasMembershipInCart();

            // If cart has programs or rentals but no active membership and no membership in cart
            if (($hasPrograms || $hasRentals) && !$hasMembership && !$hasMembershipInCart) {
                throw new Exception('You need to have an active membership or include a membership plan in your cart to avail programs or rentals.');
            }

            // Check if cart is empty
            if (empty($_SESSION['cart']['memberships']) && 
                empty($_SESSION['cart']['programs']) && 
                empty($_SESSION['cart']['rentals']) && 
                empty($_SESSION['cart']['walkins'])) {
                throw new Exception('Cart is empty');
            }

            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function hasMembershipInCart() {
        return !empty($_SESSION['cart']['memberships']);
    }

    public function addWalkinToCart($walkin_id, $date) {
        $conn = $this->db->connect();
        try {
            // First, get the walk-in details
            $sql = "SELECT price FROM walk_in WHERE id = :walkin_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':walkin_id', $walkin_id);
            $stmt->execute();
            $walkin = $stmt->fetch();

            if (!$walkin) {
                return false;
            }

            // Initialize cart if not exists
            if (!isset($_SESSION['cart'])) {
                $this->initializeCart();
            }

            // Add to session cart
            $_SESSION['cart']['walkins'][] = array(
                'id' => $walkin_id,
                'price' => floatval($walkin['price']),
                'date' => $date,
                'type' => 'walkin'
            );

            // Update cart total
            $this->updateTotal();

            return true;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function removeWalkin($index) {
        if (isset($_SESSION['cart']['walkins'][$index])) {
            unset($_SESSION['cart']['walkins'][$index]);
            $_SESSION['cart']['walkins'] = array_values($_SESSION['cart']['walkins']);
            return true;
        }
        return false;
    }

    public function getTotalAmount() {
        $total = 0;

        // Add membership prices
        if (!empty($_SESSION['cart']['memberships'])) {
            foreach ($_SESSION['cart']['memberships'] as $membership) {
                $total += $membership['price'];
            }
        }

        // Add program prices
        if (!empty($_SESSION['cart']['programs'])) {
            foreach ($_SESSION['cart']['programs'] as $program) {
                $total += $program['price'];
            }
        }

        // Add rental prices
        if (!empty($_SESSION['cart']['rentals'])) {
            foreach ($_SESSION['cart']['rentals'] as $rental) {
                $total += $rental['price'];
            }
        }

        // Add walk-in prices
        if (!empty($_SESSION['cart']['walkins'])) {
            foreach ($_SESSION['cart']['walkins'] as $walkin) {
                $total += $walkin['price'];
            }
        }

        // Add registration fee if exists
        if (isset($_SESSION['cart']['registration_fee']) && !empty($_SESSION['cart']['registration_fee']['price'])) {
            $total += $_SESSION['cart']['registration_fee']['price'];
        }

        return $total;
    }
} 