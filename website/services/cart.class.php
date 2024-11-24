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
            'price' => floatval($item['price']),
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
            'price' => floatval($item['price']),
            'validity' => $item['validity'],
            'type' => 'program',
            'coach_id' => $item['coach_id'] ?? null,
            'coach_name' => $item['coach_name'] ?? null
        ];
        $this->updateTotal();
    }

    public function addRental($item) {
        // Add rental service with quantity
        $rental = [
            'id' => $item['id'],
            'name' => $item['name'],
            'price' => floatval($item['price']),
            'validity' => $item['validity'],
            'type' => 'rental',
            'quantity' => 1 // Default quantity
        ];

        // Check if this rental already exists in cart
        $exists = false;
        foreach ($_SESSION['cart']['rentals'] as &$existingRental) {
            if ($existingRental['id'] == $item['id']) {
                // Increment quantity instead of adding new item
                $existingRental['quantity']++;
                $exists = true;
                break;
            }
        }

        // If rental doesn't exist in cart, add it
        if (!$exists) {
            $_SESSION['cart']['rentals'][] = $rental;
        }

        $this->updateTotal();
    }

    public function updateRentalQuantity($rentalId, $quantity) {
        foreach ($_SESSION['cart']['rentals'] as &$rental) {
            if ($rental['id'] == $rentalId) {
                $rental['quantity'] = max(1, intval($quantity)); // Ensure quantity is at least 1
                break;
            }
        }
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
            $_SESSION['cart']['rentals'] = array_filter($_SESSION['cart']['rentals'], function($item) use ($id) {
                return $item['id'] != $id;
            });
            $_SESSION['cart']['rentals'] = array_values($_SESSION['cart']['rentals']); // Re-index array
        }
        $this->updateTotal();
    }

    private function updateTotal() {
        $total = 0;
        
        if ($_SESSION['cart']['membership']) {
            $total += floatval($_SESSION['cart']['membership']['price']);
        }
        
        foreach ($_SESSION['cart']['programs'] as $program) {
            $total += floatval($program['price']);
        }
        
        foreach ($_SESSION['cart']['rentals'] as $rental) {
            $total += floatval($rental['price']) * $rental['quantity'];
        }
        
        $_SESSION['cart']['total'] = $total;
    }

    public function getCart() {
        return $_SESSION['cart'];
    }

    public function clearCart() {
        $this->initializeCart();
    }

    public function validateCart() {
        $errors = [];
        
        // Check if a membership plan is selected
        if ($_SESSION['cart']['membership'] === null) {
            $errors[] = "Please select a membership plan.";
        }
        
        // Validate rental quantities
        foreach ($_SESSION['cart']['rentals'] as $rental) {
            if ($rental['quantity'] < 1) {
                $errors[] = "Invalid quantity for rental item: " . $rental['name'];
            }
        }
        
        return $errors;
    }
} 