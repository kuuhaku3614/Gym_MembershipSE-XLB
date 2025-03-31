<?php
// Include database connection
require_once '../config.php';

// Comprehensive input sanitization function
function sanitizeInput($input, $type = 'string', $maxLength = 255) {
    // Trim whitespace
    $input = trim($input);
    
    // Remove HTML tags
    $input = strip_tags($input);
    
    // Convert special characters to HTML entities
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8', true);
    
    // Type-specific sanitization
    switch ($type) {
        case 'string':
            // Limit input length
            $input = mb_substr($input, 0, $maxLength);
            break;
        case 'text':
            // Allow longer text, e.g., for descriptions
            $input = mb_substr($input, 0, 5000);
            break;
    }
    
    return $input;
}

// Validate and sanitize filename
function sanitizeFilename($filename) {
    // Remove any non-alphanumeric characters except periods and hyphens
    $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $filename);
    
    // Limit filename length
    $filename = mb_substr($filename, 0, 200);
    
    return $filename;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_gym_offer'])) {
    // Initialize response array
    $response = array('success' => false, 'message' => '');
    
    // Validate and sanitize input
    $title = sanitizeInput($_POST['title'] ?? '', 'string', 100);
    $description = sanitizeInput($_POST['description'] ?? '', 'text', 5000);
    
    // Validate required fields
    $errors = [];
    
    // Check if image was uploaded
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Please upload an image.';
    }
    
    // If any errors, return immediately
    if (!empty($errors)) {
        $response['message'] = implode('<br>', $errors);
        echo json_encode($response);
        exit;
    }
    
    // Enhanced file validation
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $_FILES['image']['tmp_name']);
    finfo_close($finfo);
    
    // Allowed mime types with their corresponding extensions
    $allowedMimeTypes = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/gif' => ['gif']
    ];
    
    // Validate file type
    $originalFilename = sanitizeFilename(basename($_FILES['image']['name']));
    $imageFileType = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
    
    // Comprehensive file type check
    $isValidFile = false;
    foreach ($allowedMimeTypes as $allowedMime => $allowedExtensions) {
        if ($mimeType === $allowedMime && in_array($imageFileType, $allowedExtensions)) {
            $isValidFile = true;
            break;
        }
    }
    
    if (!$isValidFile) {
        $response['message'] = 'Only JPG, PNG, and GIF images are allowed.';
        echo json_encode($response);
        exit;
    }
    
    // Check file size (limit to 5MB)
    if ($_FILES['image']['size'] > 5000000) {
        $response['message'] = 'Image file is too large. Maximum size is 5MB.';
        echo json_encode($response);
        exit;
    }
    
    // Define upload directory
    $uploadDir = dirname(__DIR__, 4) . '/cms_img/offers/';
    $db_path_prefix = 'cms_img/offers/';

    // Ensure the directory exists with secure permissions
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $filename = uniqid() . '_' . $originalFilename;
    $upload_path = $uploadDir . $filename; // Full server path
    $db_path = $db_path_prefix . $filename; // Database path

    try {
        // Move uploaded file with additional security
        $uploadMask = umask(0022);
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            // Set secure file permissions
            chmod($upload_path, 0644);
            
            // Reset umask
            umask($uploadMask);
            
            // Insert into database
            $query = "INSERT INTO gym_offers (title, description, image_path, created_at, updated_at) 
                      VALUES (:title, :description, :image_path, NOW(), NOW())";
            
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute([
                'title' => $title,
                'description' => $description,
                'image_path' => $db_path
            ]);
            
            $response['success'] = $result;
            $response['message'] = $result ? 'Upload successful' : 'Database update failed';
        } else {
            $response['message'] = 'Failed to upload image.';
        }
    } catch (PDOException $e) {
        // Log error securely without exposing details
        error_log('Database error: ' . $e->getMessage());
        $response['message'] = 'An error occurred while processing the gym offer.';
    }

    echo json_encode($response);
    exit;
}
?>
<!-- Add Gym Offer Modal -->
<div class="modal fade" id="addGymOfferModal" tabindex="-1" aria-labelledby="addGymOfferModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
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
                        <input type="text" class="form-control" id="offer_title" name="title" placeholder="optional">
                    </div>
                    <div class="mb-3">
                        <label for="offer_description" class="form-label">Offer Description:</label>
                        <textarea class="form-control" id="offer_description" name="description" rows="5" placeholder="optional"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="offer_image" class="form-label">Offer Image:</label>
                        <input type="file" class="form-control" id="offer_image" name="image" accept="image/*" required>
                        <small class="form-text text-muted">Recommended size: 800x600 pixels</small>
                    </div>
                    <div class="mb-3">
                        <div id="imagePreview" style="max-width: 200px; max-height: 200px; overflow: hidden; display: none;">
                            <img id="preview" src="#" alt="Preview" style="width: 100%; height: auto;">
                        </div>
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
    // Image preview functionality
    $('#offer_image').on('change', function() {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#preview').attr('src', e.target.result);
                $('#imagePreview').show();
            };
            reader.readAsDataURL(file);
        }
    });

    // Reset form and preview when modal is about to be shown
    $('#addGymOfferModal').on('show.bs.modal', function () {
        $('#addGymOfferForm')[0].reset();
        $('#imagePreview').hide();
        $('#preview').attr('src', '#');
    });
    
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
                    // Simply reload page on success
                    location.reload();
                } else {
                    // Create error message element
                    var errorMessage = $('<div>', {
                        class: 'alert alert-danger',
                        role: 'alert',
                        text: response.message
                    });
                    
                    // Show message in modal body
                    $('#addGymOfferModal .modal-body').prepend(errorMessage);
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
                $('#addGymOfferModal .modal-body').prepend(errorMessage);
            }
        });
    });
});
</script>