<?php
class Cart {
    protected $db;

    function __construct() {
        if (!isset($_SESSION['cart'])) {
            $this->initializeCart();
        }
        $this->db = new Database();
    }

    private function initializeCart() {
        $_SESSION['cart'] = [
            'memberships' => [],
            'programs' => [],
            'rentals' => [],
            'total' => 0
        ];
    }

    public function addMembership($item) {
        $_SESSION['cart']['memberships'][] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'price' => floatval($item['price']),
            'validity' => $item['validity'],
            'type' => 'membership',
            'start_date' => $item['start_date'],
            'end_date' => $item['end_date']
        ];
        $this->updateTotal();
        return true;
    }

    public function addProgram($item) {
        $_SESSION['cart']['programs'][] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'price' => floatval($item['price']),
            'validity' => $item['validity'],
            'type' => 'program',
            'coach_id' => $item['coach_id'],
            'coach_name' => $item['coach_name'],
            'start_date' => $item['start_date'],
            'end_date' => $item['end_date']
        ];
        $this->updateTotal();
        return true;
    }

    public function addRental($item) {
        // Check available slots
        $sql = "SELECT available_slots FROM rental_services WHERE id = ?";
        $stmt = $this->db->connect()->prepare($sql);
        $stmt->execute([$item['id']]);
        $result = $stmt->fetch();
        
        if ($result && $result['available_slots'] < 1) {
            return false;
        }

        $_SESSION['cart']['rentals'][] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'price' => floatval($item['price']),
            'validity' => $item['validity'],
            'type' => 'rental',
            'start_date' => $item['start_date'],
            'end_date' => $item['end_date']
        ];
        
        $this->updateTotal();
        return true;
    }

    public function removeItem($type, $id) {
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
    }

    private function updateTotal() {
        $total = 0;
        
        foreach ($_SESSION['cart']['memberships'] as $membership) {
            $total += floatval($membership['price']);
        }
        
        foreach ($_SESSION['cart']['programs'] as $program) {
            $total += floatval($program['price']);
        }
        
        foreach ($_SESSION['cart']['rentals'] as $rental) {
            $total += floatval($rental['price']);
        }
        
        $_SESSION['cart']['total'] = $total;
    }

    public function getCart() {
        return $_SESSION['cart'];
    }

    public function clearCart() {
        $this->initializeCart();
        return true;
    }

    public function validateCart() {
        $errors = [];
        
        // Check if at least one membership plan is selected
        if (empty($_SESSION['cart']['memberships'])) {
            $errors[] = "Please select at least one membership plan.";
        }
        
        // Check if programs have valid coaches
        if (!empty($_SESSION['cart']['programs'])) {
            foreach ($_SESSION['cart']['programs'] as $program) {
                if (empty($program['coach_id'])) {
                    $errors[] = "Please select a coach for program: " . $program['name'];
                }
            }
        }
        
        // Check if rental services have available slots
        if (!empty($_SESSION['cart']['rentals'])) {
            $sql = "SELECT available_slots FROM rental_services WHERE id = ?";
            $stmt = $this->db->connect()->prepare($sql);
            
            foreach ($_SESSION['cart']['rentals'] as $rental) {
                $stmt->execute([$rental['id']]);
                $result = $stmt->fetch();
                
                if ($result && $result['available_slots'] < 1) {
                    $errors[] = "No available slots for rental service: " . $rental['name'];
                }
            }
        }
        
        return $errors;
    }
} 