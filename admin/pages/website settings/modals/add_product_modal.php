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
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    
    // Type-specific sanitization
    switch ($type) {
        case 'string':
            // Limit input length for short strings
            $input = mb_substr($input, 0, $maxLength);
            break;
        case 'text':
            // Allow longer text for descriptions
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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $name = sanitizeInput($_POST['product_name'] ?? '', 'string', 100);
    $description = sanitizeInput($_POST['product_description'] ?? '', 'text', 5000);
    
    $errors = [];
    
    // Validate name
    if (empty($name)) {
        $errors[] = "Product name is required.";
    }
    
    // Validate description
    if (empty($description)) {
        $errors[] = "Product description is required.";
    }
    
    // Handle file upload
    $imagePath = '';
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == UPLOAD_ERR_OK) {
        // Enhanced file validation using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['product_image']['tmp_name']);
        finfo_close($finfo);
        
        // Allowed mime types with their corresponding extensions
        $allowedMimeTypes = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/gif' => ['gif']
        ];
        
        // Sanitize original filename
        $originalFilename = sanitizeFilename(basename($_FILES['product_image']['name']));
        $imageFileType = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        
        // Comprehensive file type check
        $isValidFile = false;
        foreach ($allowedMimeTypes as $allowedMime => $allowedExtensions) {
            if ($mimeType === $allowedMime && in_array($imageFileType, $allowedExtensions)) {
                $isValidFile = true;
                break;
            }
        }
        
        // Validate file type
        if (!$isValidFile) {
            $errors[] = "Only JPG, PNG, and GIF images are allowed.";
        }
        
        // Check file size (limit to 5MB)
        if ($_FILES['product_image']['size'] > 5000000) {
            $errors[] = "Image file is too large. Maximum size is 5MB.";
        }
        
        // If no file validation errors, process upload
        if (empty($errors)) {
            $uploadDir = dirname(__DIR__, 4) . '/cms_img/products/';
            
            // Ensure the directory exists with secure permissions
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $fileName = uniqid() . '_' . $originalFilename;
            $targetFile = $uploadDir . $fileName;
            
            // Set secure upload mask
            $uploadMask = umask(0022);
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $targetFile)) {
                // Set secure file permissions
                chmod($targetFile, 0644);
                
                // Reset umask
                umask($uploadMask);
                
                $imagePath = 'cms_img/products/' . $fileName;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    } else {
        $errors[] = "Product image is required.";
    }
    
    // Insert product if no errors
    if (empty($errors)) {
        try {
            $query = "INSERT INTO products (name, description, image_path) VALUES (:name, :description, :image_path)";
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute([
                'name' => $name,
                'description' => $description,
                'image_path' => $imagePath
            ]);
            
            $response = [
                'success' => true,
                'message' => 'Product added successfully.'
            ];
            
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        } catch (PDOException $e) {
            // Log error securely without exposing details
            error_log('Database error: ' . $e->getMessage());
            $response = [
                'success' => false,
                'message' => 'An error occurred while processing the product.'
            ];
            
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    } else {
        // Return errors as JSON
        $response = [
            'success' => false,
            'message' => implode('<br>', $errors)
        ];
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}
?>

<!-- Add Product Modal (Static but Closable) -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addProductForm" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="product_name" class="form-label">Product Name:</label>
                        <input type="text" class="form-control" id="product_name" name="product_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="product_description" class="form-label">Product Description:</label>
                        <textarea class="form-control" id="product_description" name="product_description" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="product_image" class="form-label">Product Image:</label>
                        <input type="file" class="form-control" id="product_image" name="product_image" accept="image/jpeg, image/png, image/gif" required>
                        <div class="form-text">Upload JPG, PNG, or GIF image.</div>
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
                <button type="button" class="btn btn-primary" id="saveNewProduct">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Image preview functionality
    $('#product_image').on('change', function() {
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
    $('#saveNewProduct').on('click', function() {
        var formData = new FormData($('#addProductForm')[0]);
        
        $.ajax({
            type: "POST",
            url: "../admin/pages/website settings/modals/add_product_modal.php",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    // Redirect or reload without displaying success message
                    location.reload();
                } else {
                    // Create error message element with HTML entity encoding
                    var errorMessage = $('<div>', {
                        class: 'alert alert-danger',
                        role: 'alert',
                        text: response.message
                    });
                    
                    // Show message in modal body
                    $('#addProductModal .modal-body').prepend(errorMessage);
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
                $('#addProductModal .modal-body').prepend(errorMessage);
            }
        });
    });
    
    // Remove modal from DOM when hidden
    $('#addProductModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
});
</script>