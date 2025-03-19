<?php
// Include database connection
require_once '../config.php';

// Function to fetch staff member details
function getStaffDetails($id) {
    global $pdo;
    try {
        $query = "SELECT * FROM staff WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

// Get staff ID from GET request
$staffId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$staffDetails = [];

if ($staffId > 0) {
    $staffDetails = getStaffDetails($staffId);
    if (!$staffDetails) {
        echo json_encode([
            'success' => false,
            'message' => 'Staff member not found'
        ]);
        exit;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $id = isset($_POST['staff_id']) ? (int)$_POST['staff_id'] : 0;
    $name = trim($_POST['name'] ?? '');
    $status = trim($_POST['status'] ?? '');
    
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Staff name is required.";
    }
    
    if (empty($status)) {
        $errors[] = "Staff status is required.";
    }
    
    // Handle file upload if a new image is provided
    $uploadDir = dirname(__DIR__, 4) . '/cms_img/staff/';
    $imagePath = $_POST['current_image_path'] ?? '';
    
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
                // Delete old image if it exists
                if (!empty($imagePath) && file_exists($uploadDir . basename($imagePath))) {
                    @unlink($uploadDir . basename($imagePath));
                }
                
                $imagePath = 'cms_img/staff/' . $fileName;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    }
    
    // Update database if no errors
    if (empty($errors) && $id > 0) {
        try {
            $query = "UPDATE staff SET name = :name, status = :status, image_path = :image_path WHERE id = :id";
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute([
                'name' => $name,
                'status' => $status,
                'image_path' => $imagePath,
                'id' => $id
            ]);
            
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => 'Staff member updated successfully!'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Failed to update staff member.'
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

<!-- Edit Staff Modal -->
<div class="modal fade" id="editStaffModal" tabindex="-1" aria-labelledby="editStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStaffModalLabel">Edit Staff Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editStaffForm" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="staff_id" value="<?php echo $staffDetails['id'] ?? 0; ?>">
                    <input type="hidden" name="current_image_path" value="<?php echo $staffDetails['image_path'] ?? ''; ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Name:</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($staffDetails['name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status/Role:</label>
                        <input type="text" class="form-control" id="status" name="status" value="<?php echo htmlspecialchars($staffDetails['status'] ?? ''); ?>" required>
                        <small class="form-text text-muted">E.g., Gym Owner, Staff, Coach, Trainer, etc.</small>
                    </div>
                    <div class="mb-3">
                        <label for="staff_image" class="form-label">Staff Image:</label>
                        <input type="file" class="form-control" id="staff_image" name="staff_image" accept="image/jpeg, image/png, image/jpg">
                        <small class="form-text text-muted">Leave empty to keep current image. Recommended size: Square image (e.g., 500x500px)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Image:</label>
                        <div id="currentImage" style="max-width: 200px; max-height: 200px; overflow: hidden;">
                            <?php if (!empty($staffDetails['image_path'])): ?>
                                <img src="../<?php echo htmlspecialchars($staffDetails['image_path']); ?>" alt="Current Image" style="width: 100%; height: auto;">
                            <?php else: ?>
                                <p>No image available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div id="imagePreview" style="max-width: 200px; max-height: 200px; overflow: hidden; display: none;">
                            <label class="form-label">New Image Preview:</label>
                            <img id="preview" src="#" alt="Preview" style="width: 100%; height: auto;">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="updateStaff">Update Staff Member</button>
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
    $('#updateStaff').on('click', function() {
        var formData = new FormData($('#editStaffForm')[0]);
        
        $.ajax({
            type: "POST",
            url: "../admin/pages/website settings/modals/edit_staff_modal.php",
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
                    $('#editStaffModal .modal-body').prepend(successMessage);
                    
                    // Auto-close modal after delay
                    setTimeout(function() {
                        $('#editStaffModal').modal('hide');
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
                    $('#editStaffModal .modal-body').prepend(errorMessage);
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
                $('#editStaffModal .modal-body').prepend(errorMessage);
            }
        });
    });
    
    // Remove modal from DOM when hidden
    $('#editStaffModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
});
</script>