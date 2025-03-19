<?php
// Include database connection
require_once '../config.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $name = trim($_POST['product_name'] ?? '');
    $description = trim($_POST['product_description'] ?? '');
    
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Product name is required.";
    }
    
    if (empty($description)) {
        $errors[] = "Product description is required.";
    }
    
    // Handle file upload
    $imagePath = '';
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['product_image']['type'], $allowedTypes)) {
            $errors[] = "Only JPG, PNG, and GIF images are allowed.";
        } else {
            $uploadDir = dirname(__DIR__, 4) . '/cms_img/products/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = uniqid() . '_' . basename($_FILES['product_image']['name']);
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $targetFile)) {
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
            
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => 'Product added successfully!'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Failed to add product.'
                ];
            }
            
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        } catch (PDOException $e) {
            $response = [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
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


<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
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
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveNewProduct">Save Product</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
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
                    // Create success message element
                    var successMessage = $('<div>', {
                        class: 'alert alert-success',
                        role: 'alert',
                        text: response.message
                    });
                    
                    // Show message in modal body
                    $('#addProductModal .modal-body').prepend(successMessage);
                    
                    // Auto-close modal after delay
                    setTimeout(function() {
                        $('#addProductModal').modal('hide');
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