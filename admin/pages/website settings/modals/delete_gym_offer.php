<?php
// Include database connection
require_once '../config.php';

// Initialize response array
$response = array('success' => false, 'message' => '');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    // Validate ID
    if ($id <= 0) {
        $response['message'] = 'Invalid offer ID.';
        echo json_encode($response);
        exit;
    }
    
    try {
        // First, get the image path
        $query = "SELECT image_path FROM gym_offers WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $id]);
        $offer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$offer) {
            $response['message'] = 'Offer not found.';
            echo json_encode($response);
            exit;
        }
        
        // Delete the offer from the database
        $query = "DELETE FROM gym_offers WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $id]);
        
        // Delete the image file if it exists and is not the default
        $image_path = '../../' . $offer['image_path'];
        if (file_exists($image_path) && $offer['image_path'] !== 'cms_img/offers/default.jpg') {
            unlink($image_path);
        }
        
        $response['success'] = true;
        $response['message'] = 'Gym offer deleted successfully.';
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>