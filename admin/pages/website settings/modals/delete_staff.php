<?php
// Include database connection
require_once '../config.php';

// Process deletion request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $staffId = (int)$_POST['id'];
    
    try {
        // First, get the image path to delete the file
        $query = "SELECT image_path FROM staff WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $staffId]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete from database
        $deleteQuery = "DELETE FROM staff WHERE id = :id";
        $deleteStmt = $pdo->prepare($deleteQuery);
        $result = $deleteStmt->execute(['id' => $staffId]);
        
        if ($result) {
            // Delete associated image file if exists
            if ($staff && !empty($staff['image_path'])) {
                $uploadDir = dirname(__DIR__, 4) . '/';
                $imagePath = $uploadDir . $staff['image_path'];
                
                if (file_exists($imagePath)) {
                    @unlink($imagePath);
                }
            }
            
            $response = [
                'success' => true,
                'message' => 'Staff member deleted successfully!'
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Failed to delete staff member.'
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