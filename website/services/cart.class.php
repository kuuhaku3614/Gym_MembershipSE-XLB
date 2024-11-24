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
        if ($_SESSION['cart']['membership'] !== null) {
            throw new Exception("You can only select one membership plan. Please remove the existing plan first.");
        }
        
        $_SESSION['cart']['membership'] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'price' => floatval($item['price']),
            'validity' => $item['validity'],
            'type' => 'membership',
            'start_date' => $item['start_date'],
            'end_date' => $item['end_date']
        ];
        $this->updateTotal();
    }

    public function addProgram($item) {
        $_SESSION['cart']['programs'][] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'price' => floatval($item['price']),
            'validity' => $item['validity'],
            'type' => 'program',
            'coach_id' => $item['coach_id'] ?? null,
            'coach_name' => $item['coach_name'] ?? null,
            'start_date' => $item['start_date'],
            'end_date' => $item['end_date']
        ];
        $this->updateTotal();
    }

    public function addRental($item) {
        // Each rental service is treated as a separate instance
        $_SESSION['cart']['rentals'][] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'price' => floatval($item['price']),
            'validity' => $item['validity'],
            'type' => 'rental',
            'start_date' => $item['start_date'],
            'end_date' => $item['end_date']
        ];
        
        // Sort rentals by start date and service name
        usort($_SESSION['cart']['rentals'], function($a, $b) {
            $dateCompare = strtotime($a['start_date']) - strtotime($b['start_date']);
            if ($dateCompare === 0) {
                return strcmp($a['name'], $b['name']);
            }
            return $dateCompare;
        });
        
        $this->updateTotal();
    }

    public function removeItem($type, $id) {
        if ($type === 'membership') {
            $_SESSION['cart']['membership'] = null;
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
            $total += floatval($rental['price']);
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