<?php
// Include database connection
require_once '../config.php';

// Function to get current logo
function getLogoContent() {
    global $pdo;
    try {
        $query = "SELECT * FROM website_content WHERE section = 'logo'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

// Get current logo content
$logoContent = getLogoContent();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Check if file is uploaded
    if (!isset($_FILES['logo_file']) || $_FILES['logo_file']['error'] != UPLOAD_ERR_OK) {
        $errors[] = "Please select a logo image to upload.";
    } else {
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
        if (!in_array($_FILES['logo_file']['type'], $allowedTypes)) {
            $errors[] = "Only JPG, PNG, GIF, and SVG files are allowed.";
        }
        
        // Validate file size (max 2MB)
        if ($_FILES['logo_file']['size'] > 2 * 1024 * 1024) {
            $errors[] = "File size must be less than 2MB.";
        }
    }
    
    // Process upload if no errors
    if (empty($errors)) {
        try {
            // Create upload directory if it doesn't exist
            $uploadDir = dirname(__DIR__, 4) . '/cms_img/logo/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Generate unique filename
            $fileName = uniqid() . '_' . basename($_FILES['logo_file']['name']);
            $targetPath = $uploadDir . $fileName;
            $dbPath = 'cms_img/logo/' . $fileName;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $targetPath)) {
                // Update database - check if logo entry exists
                $checkQuery = "SELECT COUNT(*) FROM website_content WHERE section = 'logo'";
                $checkStmt = $pdo->prepare($checkQuery);
                $checkStmt->execute();
                $exists = $checkStmt->fetchColumn() > 0;
                
                if ($exists) {
                    // Update existing logo entry
                    $query = "UPDATE website_content SET location = :image_path WHERE section = 'logo'";
                } else {
                    // Insert new logo entry
                    $query = "INSERT INTO website_content (section, location) VALUES ('logo', :image_path)";
                }
                
                $stmt = $pdo->prepare($query);
                $result = $stmt->execute(['image_path' => $dbPath]);
                
                if ($result) {
                    // Delete old logo file if it exists
                    if (!empty($logoContent) && !empty($logoContent['image_path'])) {
                        $oldFilePath = dirname(__DIR__, 4) . '/' . $logoContent['image_path'];
                        if (file_exists($oldFilePath)) {
                            unlink($oldFilePath);
                        }
                    }
                    
                    $response = [
                        'success' => true,
                        'message' => 'Logo updated successfully!'
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Failed to update logo in database.'
                    ];
                }
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Failed to upload logo file.'
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

<!-- Logo Modal -->
<div class="modal fade" id="logoModal" tabindex="-1" aria-labelledby="logoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoModalLabel">Update Logo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="logoForm" method="post" enctype="multipart/form-data">
                    <?php if (!empty($logoContent) && !empty($logoContent['image_path'])): ?>
                        <div class="mb-3 text-center">
                            <p>Current Logo:</p>
                            <img src="../../<?php echo htmlspecialchars($logoContent['image_path']); ?>" alt="Current Logo" class="img-fluid" style="max-height: 100px;">
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="logo_file" class="form-label">Upload New Logo:</label>
                        <input type="file" class="form-control" id="logo_file" name="logo_file" accept="image/*" required>
                        <div class="form-text">Recommended size: 200x80 pixels. PNG with transparent background preferred.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveLogoChanges">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Submit form via AJAX
    $('#saveLogoChanges').on('click', function() {
        var formData = new FormData($('#logoForm')[0]);
        
        $.ajax({
            type: "POST",
            url: "../admin/pages/website settings/modals/logo_modal.php",
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
                    $('#logoModal .modal-body').prepend(successMessage);
                    
                    // Auto-close modal after delay
                    setTimeout(function() {
                        $('#logoModal').modal('hide');
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
                    $('#logoModal .modal-body').prepend(errorMessage);
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
                $('#logoModal .modal-body').prepend(errorMessage);
            }
        });
    });
    
    // Remove modal from DOM when hidden
    $('#logoModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
});
</script>