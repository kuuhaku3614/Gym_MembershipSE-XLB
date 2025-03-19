<?php
// Include database connection
require_once '../config.php';

// Process deletion request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    
    try {
        // First, get the image path
        $query = "SELECT image_path FROM gallery_images WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $id]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($image) {
            // Delete from database
            $deleteQuery = "DELETE FROM gallery_images WHERE id = :id";
            $deleteStmt = $pdo->prepare($deleteQuery);
            $result = $deleteStmt->execute(['id' => $id]);
            
            if ($result) {
                // Delete the file if it exists
                $filePath = dirname(__DIR__, 4) . '/' . $image['image_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                
                $response = [
                    'success' => true,
                    'message' => 'Gallery image deleted successfully!'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Failed to delete gallery image from database.'
                ];
            }
        } else {
            $response = [
                'success' => false,
                'message' => 'Gallery image not found.'
            ];
        }
    } catch (PDOException $e) {
        $response = [
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
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
        'message' => 'Invalid request.'
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>