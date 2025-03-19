<?php
// Include database connection
require_once '../config.php';

// Function to fetch product by ID
function getProductById($id) {
    global $pdo;
    try {
        $query = "SELECT * FROM products WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

// Process deletion request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $productId = intval($_POST['id']);
    
    // Get product details first (to access image path)
    $product = getProductById($productId);
    
    if ($product) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Delete product from database
            $query = "DELETE FROM products WHERE id = :id";
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute(['id' => $productId]);
            
            if ($result) {
                // Delete associated image file
                if (!empty($product['image_path'])) {
                    $imagePath = '../../../' . $product['image_path'];
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
                
                // Commit transaction
                $pdo->commit();
                
                $response = [
                    'success' => true,
                    'message' => 'Product deleted successfully!'
                ];
            } else {
                // Rollback transaction
                $pdo->rollBack();
                
                $response = [
                    'success' => false,
                    'message' => 'Failed to delete product from database.'
                ];
            }
        } catch (PDOException $e) {
            // Rollback transaction
            $pdo->rollBack();
            
            $response = [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    } else {
        $response = [
            'success' => false,
            'message' => 'Product not found.'
        ];
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
} else {
    // Invalid request
    $response = [
        'success' => false,
        'message' => 'Invalid request method or missing product ID.'
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>