<?php
// Include database connection
require_once '../config.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $name = trim($_POST['name'] ?? '');
    $status = trim($_POST['status'] ?? '');
    
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Staff name is required.";
    }
    
    if (empty($status)) {
        $errors[] = "Staff status is required.";
    }
    
    // Handle file upload
    $uploadDir = dirname(__DIR__, 4) . '/cms_img/staff/';
    $imagePath = '';
    
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    if (isset($_FILES['staff_image']) && $_FILES['staff_image']['error'] == 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $fileType = $_FILES['staff_image']['type'];
        
        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = "Only JPG, JPEG, and PNG files are allowed.";
        } else {
            // Generate unique filename
            $fileName = uniqid() . '_' . basename($_FILES['staff_image']['name']);
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['staff_image']['tmp_name'], $targetFile)) {
                $imagePath = 'cms_img/staff/' . $fileName;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    } else {
        $errors[] = "Staff image is required.";
    }
    
    // Insert into database if no errors
    if (empty($errors)) {
        try {
            $query = "INSERT INTO staff (name, status, image_path) VALUES (:name, :status, :image_path)";
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute([
                'name' => $name,
                'status' => $status,
                'image_path' => $imagePath
            ]);
            
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => 'Staff member added successfully!'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Failed to add staff member.'
                ];
            }
            
            // Return JSON response for AJAX
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

<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1" aria-labelledby="addStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addStaffModalLabel">Add New Staff Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addStaffForm" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name:</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status/Role:</label>
                        <input type="text" class="form-control" id="status" name="status" required>
                        <small class="form-text text-muted">E.g., Gym Owner, Staff, Coach, Trainer, etc.</small>
                    </div>
                    <div class="mb-3">
                        <label for="staff_image" class="form-label">Staff Image:</label>
                        <input type="file" class="form-control" id="staff_image" name="staff_image" accept="image/jpeg, image/png, image/jpg" required>
                        <small class="form-text text-muted">Recommended size: Square image (e.g., 500x500px)</small>
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
                <button type="button" class="btn btn-primary" id="saveStaff">Save Staff Member</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Image preview functionality
    $('#staff_image').on('change', function() {
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
    $('#saveStaff').on('click', function() {
        var formData = new FormData($('#addStaffForm')[0]);
        
        $.ajax({
            type: "POST",
            url: "../admin/pages/website settings/modals/add_staff_modal.php",
            data: formData,
            dataType: "json",
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    // Create success message element
                    var successMessage = $('<div>', {
                        class: 'alert alert-success',
                        role: 'alert',
                        text: response.message
                    });
                    
                    // Show message in modal body
                    $('#addStaffModal .modal-body').prepend(successMessage);
                    
                    // Auto-close modal after delay
                    setTimeout(function() {
                        $('#addStaffModal').modal('hide');
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
                    $('#addStaffModal .modal-body').prepend(errorMessage);
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
                $('#addStaffModal .modal-body').prepend(errorMessage);
            }
        });
    });
    
    // Remove modal from DOM when hidden
    $('#addStaffModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
});
</script>