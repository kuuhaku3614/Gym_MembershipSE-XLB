<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../functions/sanitize.php';

class Cart {
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
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [
                'memberships' => [],
                'programs' => [],
                'rentals' => [],
                'registration_fee' => null,
                'total' => 0
            ];
        }
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
            $db = new Database();
            $conn = $db->connect();
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
            if ($type === 'membership') {
                $index = intval($id);
                if (isset($_SESSION['cart']['memberships'][$index])) {
                    array_splice($_SESSION['cart']['memberships'], $index, 1);
                    $_SESSION['cart']['memberships'] = array_values($_SESSION['cart']['memberships']);
                }
            } else if ($type === 'program') {
                $index = intval($id);
                if (isset($_SESSION['cart']['programs'][$index])) {
                    array_splice($_SESSION['cart']['programs'], $index, 1);
                    $_SESSION['cart']['programs'] = array_values($_SESSION['cart']['programs']);
                }
            } else if ($type === 'rental') {
                $index = intval($id);
                if (isset($_SESSION['cart']['rentals'][$index])) {
                    array_splice($_SESSION['cart']['rentals'], $index, 1);
                    $_SESSION['cart']['rentals'] = array_values($_SESSION['cart']['rentals']);
                }
            }
            $this->updateTotal();
            return true;
        } catch (Exception $e) {
            throw new Exception("Failed to remove item from cart");
        }
    }

    private function updateTotal() {
        try {
            $total = 0;
            
            // Add membership totals
            foreach ($_SESSION['cart']['memberships'] as $item) {
                $total += $item['price'];
            }
            
            // Add program totals
            foreach ($_SESSION['cart']['programs'] as $item) {
                $total += $item['price'];
            }
            
            // Add rental totals
            foreach ($_SESSION['cart']['rentals'] as $item) {
                $total += $item['price'];
            }

            // Add registration fee if present
            if (isset($_SESSION['cart']['registration_fee']) && $_SESSION['cart']['registration_fee'] !== null) {
                $total += $_SESSION['cart']['registration_fee']['price'];
            }
            
            $_SESSION['cart']['total'] = $total;
        } catch (Exception $e) {
            throw new Exception("Failed to update cart total");
        }
    }

    public function getCart() {
        try {
            if (!isset($_SESSION['cart'])) {
                $this->initializeCart();
            }
            
            $cart = $_SESSION['cart'];
            return $cart;
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve cart: " . $e->getMessage());
        }
    }

    public function clearCart() {
        try {
            $_SESSION['cart'] = [
                'memberships' => [],
                'programs' => [],
                'rentals' => [],
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
                empty($_SESSION['cart']['rentals'])) {
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
} 