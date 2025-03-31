<?php
// Include database connection
require_once '../config.php';

// Sanitize and validate input function
function sanitizeInput($input) {
    // Trim whitespace
    $input = trim($input);
    
    // Remove any HTML tags
    $input = strip_tags($input);
    
    // Convert special characters to HTML entities
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8', true);
    
    // Limit input length
    $input = mb_substr($input, 0, 255);
    
    return $input;
}

// Validate and sanitize file name
function sanitizeFilename($filename) {
    // Remove any non-alphanumeric characters except periods and hyphens
    $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $filename);
    
    // Limit filename length
    $filename = mb_substr($filename, 0, 200);
    
    return $filename;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Sanitize alt text
    $altText = sanitizeInput($_POST['alt_text'] ?? '');
    
    // Handle file upload
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = dirname(__DIR__, 4) . '/cms_img/gallery/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Sanitize original filename
        $originalFilename = sanitizeFilename(basename($_FILES['image_file']['name']));
        
        // Generate unique filename
        $filename = uniqid() . '_' . $originalFilename;
        $targetPath = $uploadDir . $filename;
        
        // Validate file type using mime type and extension
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['image_file']['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimeTypes = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/gif' => ['gif']
        ];
        
        $imageFileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
        
        // Check if mime type and extension match
        $isValidFile = false;
        foreach ($allowedMimeTypes as $allowedMime => $allowedExtensions) {
            if ($mimeType === $allowedMime && in_array($imageFileType, $allowedExtensions)) {
                $isValidFile = true;
                break;
            }
        }
        
        if (!$isValidFile) {
            $errors[] = "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.";
        }
        
        // Check file size (limit to 5MB)
        if ($_FILES['image_file']['size'] > 5000000) {
            $errors[] = "File is too large. Maximum size is 5MB.";
        }
        
        // If no errors, proceed with upload and database insertion
        if (empty($errors)) {
            // Additional security: randomize filename and use strict permissions
            $uploadMask = umask(0022);
            
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $targetPath)) {
                // Ensure proper file permissions
                chmod($targetPath, 0644);
                
                // Reset umask
                umask($uploadMask);
                
                // Insert into database
                try {
                    $query = "INSERT INTO gallery_images (image_path, alt_text) VALUES (:image_path, :alt_text)";
                    $stmt = $pdo->prepare($query);
                    $result = $stmt->execute([
                        'image_path' => 'cms_img/gallery/' . $filename,
                        'alt_text' => $altText
                    ]);
                    
                    $response = $result 
                        ? ['success' => true, 'message' => 'Upload successful']
                        : ['success' => false, 'message' => 'Database update failed'];
                } catch (PDOException $e) {
                    // Log error securely without exposing details
                    error_log('Database error: ' . $e->getMessage());
                    $response = [
                        'success' => false,
                        'message' => 'An error occurred while processing the image.'
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

<!-- Add Gallery Modal (Static but Closable) -->
<div class="modal fade" id="addGalleryModal" tabindex="-1" aria-labelledby="addGalleryModalLabel" aria-hidden="true" data-bs-backdrop="static">
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
                    <div class="mb-3">
                        <div id="imagePreview" style="max-width: 200px; max-height: 200px; overflow: hidden; display: none;">
                            <img id="preview" src="#" alt="Preview" style="width: 100%; height: auto;">
                        </div>
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
        // Image preview functionality
    $('#image_file').on('change', function() {
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
                    // Simply reload page on success
                    location.reload();
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