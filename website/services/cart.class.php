<?php
class Cart {
    public function __construct() {
        if (!isset($_SESSION['cart'])) {
            $this->initializeCart();
        }
    }

    private function initializeCart() {
        $_SESSION['cart'] = [
            'membership' => null,
            'programs' => [],
            'rentals' => [],
            'total' => 0
        ];
    }

    public function addMembership($item) {
        // Check if a membership plan already exists
        if ($_SESSION['cart']['membership'] !== null) {
            throw new Exception("You can only select one membership plan. Please remove the existing plan first.");
        }
        
        $_SESSION['cart']['membership'] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'price' => $item['price'],
            'validity' => $item['validity'],
            'type' => 'membership'
        ];
        $this->updateTotal();
    }

    public function addProgram($item) {
        // Check if this program is already in cart
        foreach ($_SESSION['cart']['programs'] as $program) {
            if ($program['id'] == $item['id']) {
                throw new Exception("This program is already in your cart.");
            }
        }
        
        $_SESSION['cart']['programs'][] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'price' => $item['price'],
            'validity' => $item['validity'],
            'type' => 'program',
            'coach_id' => $item['coach_id'],
            'coach_name' => $item['coach_name']
        ];
        $this->updateTotal();
    }

    public function addRental($item) {
        // Rental services can be repeated, so no validation needed
        $_SESSION['cart']['rentals'][] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'price' => $item['price'],
            'validity' => $item['validity'],
            'type' => 'rental'
        ];
        $this->updateTotal();
    }

    public function removeItem($type, $id) {
        if ($type === 'membership') {
            $_SESSION['cart']['membership'] = null;
        } else if ($type === 'program') {
            $_SESSION['cart']['programs'] = array_filter($_SESSION['cart']['programs'], function($item) use ($id) {
                return $item['id'] != $id;
            });
            $_SESSION['cart']['programs'] = array_values($_SESSION['cart']['programs']); // Re-index array
        } else if ($type === 'rental') {
            // Remove only the first occurrence of the rental item
            $found = false;
            foreach ($_SESSION['cart']['rentals'] as $key => $item) {
                if ($item['id'] == $id && !$found) {
                    unset($_SESSION['cart']['rentals'][$key]);
                    $found = true;
                }
            }
            $_SESSION['cart']['rentals'] = array_values($_SESSION['cart']['rentals']); // Re-index array
        }
        $this->updateTotal();
    }

    public function validateCart() {
        $errors = [];
        
        // Check if a membership plan is selected
        if ($_SESSION['cart']['membership'] === null) {
            $errors[] = "Please select a membership plan.";
        }
        
        // Additional validations can be added here
        
        return $errors;
    }

    private function updateTotal() {
        $total = 0;
        
        if ($_SESSION['cart']['membership']) {
            $total += $_SESSION['cart']['membership']['price'];
        }
        
        foreach ($_SESSION['cart']['programs'] as $program) {
            $total += $program['price'];
        }
        
        foreach ($_SESSION['cart']['rentals'] as $rental) {
            $total += $rental['price'];
        }
        
        $_SESSION['cart']['total'] = $total;
    }

    public function getCart() {
        return $_SESSION['cart'];
    }

    public function clearCart() {
        $this->initializeCart();
    }
} 