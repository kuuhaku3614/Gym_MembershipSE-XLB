<?php
// Include database connection
require_once '../config.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $altText = trim($_POST['alt_text'] ?? '');
    
    // Handle file upload
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = dirname(__DIR__, 4) . '/cms_img/gallery/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $filename = uniqid() . '_' . basename($_FILES['image_file']['name']);
        $targetPath = $uploadDir . $filename;
        
        // Check if file is an image
        $imageFileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($imageFileType, $allowedTypes)) {
            $errors[] = "Only JPG, JPEG, PNG, and GIF files are allowed.";
        }
        
        // Check file size (limit to 5MB)
        if ($_FILES['image_file']['size'] > 5000000) {
            $errors[] = "File is too large. Maximum size is 5MB.";
        }
        
        // If no errors, proceed with upload and database insertion
        if (empty($errors)) {
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $targetPath)) {
                // Insert into database
                try {
                    $query = "INSERT INTO gallery_images (image_path, alt_text) VALUES (:image_path, :alt_text)";
                    $stmt = $pdo->prepare($query);
                    $result = $stmt->execute([
                        'image_path' => 'cms_img/gallery/' . $filename,
                        'alt_text' => $altText
                    ]);
                    
                    if ($result) {
                        $response = [
                            'success' => true,
                            'message' => 'Gallery image added successfully!'
                        ];
                    } else {
                        $response = [
                            'success' => false,
                            'message' => 'Failed to add gallery image to database.'
                        ];
                    }
                } catch (PDOException $e) {
                    $response = [
                        'success' => false,
                        'message' => 'Database error: ' . $e->getMessage()
                    ];
                }
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Failed to upload image.'
                ];
            }
        } else {
            $response = [
                'success' => false,
                'message' => implode('<br>', $errors)
            ];
        }
    } else {
        $response = [
            'success' => false,
            'message' => 'Please select an image to upload.'
        ];
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>

<!-- Add Gallery Modal -->
<div class="modal fade" id="addGalleryModal" tabindex="-1" aria-labelledby="addGalleryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addGalleryModalLabel">Add New Gallery Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addGalleryForm" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="image_file" class="form-label">Image File:</label>
                        <input type="file" class="form-control" id="image_file" name="image_file" accept="image/*" required>
                        <small class="text-muted">Recommended size: 1280x720 pixels. Max file size: 5MB.</small>
                    </div>
                    <div class="mb-3">
                        <label for="alt_text" class="form-label">Alternative Text:</label>
                        <input type="text" class="form-control" id="alt_text" name="alt_text" placeholder="Describe the image (optional)">
                        <small class="text-muted">Helps with accessibility and SEO.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveGalleryImage">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Submit form via AJAX
    $('#saveGalleryImage').on('click', function() {
        var formData = new FormData($('#addGalleryForm')[0]);
        
        $.ajax({
            type: "POST",
            url: "../admin/pages/website settings/modals/add_gallery_modal.php",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    // Create success message element
                    var successMessage = $('<div>', {
                        class: 'alert alert-success',
                        role: 'alert',
                        text: response.message
                    });
                    
                    // Show message in modal body
                    $('#addGalleryModal .modal-body').prepend(successMessage);
                    
                    // Auto-close modal after delay
                    setTimeout(function() {
                        $('#addGalleryModal').modal('hide');
                        // Reload page to reflect changes
                        location.reload();
                    }, 1000);
                } else {
                    // Create error message element
                    var errorMessage = $('<div>', {
                        class: 'alert alert-danger',
                        role: 'alert',
                        html: response.message
                    });
                    
                    // Show message in modal body
                    $('#addGalleryModal .modal-body').prepend(errorMessage);
                }
            },
            error: function(xhr, status, error) {
                console.log("Error details:", xhr, status, error);
                
                // Create error message element
                var errorMessage = $('<div>', {
                    class: 'alert alert-danger',
                    role: 'alert',
                    text: "An error occurred while processing your request."
                });
                
                // Show message in modal body
                $('#addGalleryModal .modal-body').prepend(errorMessage);
            }
        });
    });
    
    // Remove modal from DOM when hidden
    $('#addGalleryModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
});
</script>