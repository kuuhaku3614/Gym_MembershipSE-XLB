<?php
// Include database connection
require_once '../config.php';

// Function to sanitize HTML output
function sanitizeOutput($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

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

// Get product ID from query parameter
$productId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

// Fetch product details
$product = getProductById($productId);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs with additional sanitization
    $name = trim(strip_tags($_POST['product_name'] ?? ''));
    $description = trim(strip_tags($_POST['product_description'] ?? ''));
    $id = filter_var($_POST['product_id'] ?? 0, FILTER_VALIDATE_INT);
    
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Product name is required.";
    }
    
    if (empty($description)) {
        $errors[] = "Product description is required.";
    }
    
    if ($id <= 0) {
        $errors[] = "Invalid product ID.";
    }
    
    // Get current product to access existing image path
    $currentProduct = getProductById($id);
    $imagePath = $currentProduct['image_path'];
    
    // Handle file upload if new image is provided
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['product_image']['type'], $allowedTypes)) {
            $errors[] = "Only JPG, PNG, and GIF images are allowed.";
        } else {
            $uploadDir = dirname(__DIR__, 4) . '/cms_img/products/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Sanitize filename
            $fileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '', basename($_FILES['product_image']['name']));
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $targetFile)) {
                // Delete old image if exists
                if (!empty($currentProduct['image_path'])) {
                    $oldImagePath = dirname(__DIR__, 4) . '/' . $currentProduct['image_path'];
                    if (file_exists($oldImagePath) && $currentProduct['image_path'] !== 'cms_img/products/default.jpg') {
                        unlink($oldImagePath);
                    }
                }
                
                $imagePath = 'cms_img/products/' . $fileName;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    }
    
    // Update product if no errors
    if (empty($errors)) {
        try {
            $query = "UPDATE products SET name = :name, description = :description, image_path = :image_path WHERE id = :id";
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute([
                'name' => $name,
                'description' => $description,
                'image_path' => $imagePath,
                'id' => $id
            ]);
            
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => 'Product updated successfully!'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Failed to update product.'
                ];
            }
            
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        } catch (PDOException $e) {
            $response = [
                'success' => false,
                'message' => 'Database error: ' . sanitizeOutput($e->getMessage())
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

// If product not found, return error
if (!$product) {
    ?>
    <div class="alert alert-danger">Product not found!</div>
    <script>
        setTimeout(function() {
            $('#editProductModal').modal('hide');
        }, 2000);
    </script>
    <?php
    exit;
}
?>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editProductForm" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" value="<?php echo sanitizeOutput($product['id']); ?>">
                    
                    <div class="mb-3">
                        <label for="product_name" class="form-label">Product Name:</label>
                        <input type="text" class="form-control" id="product_name" name="product_name" value="<?php echo sanitizeOutput($product['name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="product_description" class="form-label">Product Description:</label>
                        <textarea class="form-control" id="product_description" name="product_description" rows="4" required><?php echo sanitizeOutput($product['description']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Current Image:</label>
                        <div class="mb-2">
                            <img src="../<?php echo sanitizeOutput($product['image_path']); ?>" alt="<?php echo sanitizeOutput($product['name']); ?>" style="max-width: 150px; max-height: 150px;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <div id="imagePreview" style="max-width: 200px; max-height: 200px; overflow: hidden; display: none;">
                            <label class="form-label">New Image Preview:</label>
                            <img id="preview" src="#" alt="Preview" style="width: 100%; height: auto;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="product_image" class="form-label">New Image (leave empty to keep current):</label>
                        <input type="file" class="form-control" id="product_image" name="product_image" accept="image/jpeg, image/png, image/gif">
                        <div class="form-text">Upload JPG, PNG, or GIF image.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveProductChanges">Save</button>
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
    $('#saveProductChanges').on('click', function() {
        var formData = new FormData($('#editProductForm')[0]);
        
        $.ajax({
            type: "POST",
            url: "../admin/pages/website settings/modals/edit_product_modal.php",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function(response) {
                // Remove any existing alerts
                $('#editProductModal .alert').remove();
                
                if (response.success) {
                    // Create success message element
                    var successMessage = $('<div>', {
                        class: 'alert alert-success',
                        role: 'alert',
                        text: response.message
                    });
                    
                    // Show message in modal body
                    $('#editProductModal .modal-body').prepend(successMessage);
                    
                    // Auto-close modal after delay
                    setTimeout(function() {
                        $('#editProductModal').modal('hide');
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
                    $('#editProductModal .modal-body').prepend(errorMessage);
                }
            },
            error: function(xhr, status, error) {
                // Remove any existing alerts
                $('#editProductModal .alert').remove();
                
                console.error("Error details:", xhr, status, error);
                
                // Create error message element
                var errorMessage = $('<div>', {
                    class: 'alert alert-danger',
                    role: 'alert',
                    text: "An error occurred while processing your request."
                });
                
                // Show message in modal body
                $('#editProductModal .modal-body').prepend(errorMessage);
            }
        });
    });
    
    // Remove modal from DOM when hidden
    $('#editProductModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
});
</script>