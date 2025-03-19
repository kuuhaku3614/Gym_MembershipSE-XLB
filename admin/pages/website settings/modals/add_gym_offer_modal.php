<?php
// Include database connection
require_once '../config.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_gym_offer'])) {
    // Initialize response array
    $response = array('success' => false, 'message' => '');
    
    // Validate and sanitize input
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    
    // Validate required fields
    if (empty($title) || empty($description)) {
        $response['message'] = 'All fields are required.';
        echo json_encode($response);
        exit;
    }
    
    // Check if image was uploaded
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'Please upload an image.';
        echo json_encode($response);
        exit;
    }
    
    // Check file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($_FILES['image']['type'], $allowed_types)) {
        $response['message'] = 'Only JPG, PNG, and GIF images are allowed.';
        echo json_encode($response);
        exit;
    }
    
    // Define upload directory
    $uploadDir = dirname(__DIR__, 4) . '/cms_img/offers/';
    $db_path_prefix = 'cms_img/offers/';

    // Ensure the directory exists
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Generate unique filename
    $filename = uniqid() . '_' . basename($_FILES['image']['name']);
    $upload_path = $uploadDir . $filename; // Full server path
    $db_path = $db_path_prefix . $filename; // Database path

    try {
        // Move uploaded file
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            // Insert into database
            $query = "INSERT INTO gym_offers (title, description, image_path, created_at, updated_at) 
                      VALUES (:title, :description, :image_path, NOW(), NOW())";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'title' => $title,
                'description' => $description,
                'image_path' => $db_path
            ]);
            
            $response['success'] = true;
            $response['message'] = 'Gym offer added successfully.';
        } else {
            $response['message'] = 'Failed to upload image.';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }

    echo json_encode($response);
    exit;
}
?>

<!-- Add Gym Offer Modal -->
<div class="modal fade" id="addGymOfferModal" tabindex="-1" aria-labelledby="addGymOfferModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addGymOfferModalLabel">Add New Gym Offer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addGymOfferForm" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="offer_title" class="form-label">Offer Title:</label>
                        <input type="text" class="form-control" id="offer_title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="offer_description" class="form-label">Offer Description:</label>
                        <textarea class="form-control" id="offer_description" name="description" rows="5" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="offer_image" class="form-label">Offer Image:</label>
                        <input type="file" class="form-control" id="offer_image" name="image" accept="image/*" required>
                        <small class="form-text text-muted">Recommended size: 800x600 pixels</small>
                    </div>
                    <input type="hidden" name="add_gym_offer" value="1">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle form submission
    $('#addGymOfferForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        
        $.ajax({
            type: "POST",
            url: "../admin/pages/website settings/modals/add_gym_offer_modal.php",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    alert("Gym offer added successfully!");
                    $('#addGymOfferModal').modal('hide');
                    location.reload();
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.log("Error details:", xhr, status, error);
                alert("Error adding gym offer.");
            }
        });
    });
});
</script>