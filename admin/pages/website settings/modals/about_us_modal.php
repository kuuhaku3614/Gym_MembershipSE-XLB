<?php
// Include database connection
require_once '../config.php';

// Fetch current about us content
function getAboutUsContent() {
    global $pdo; // Declare $pdo as global to access it
    try {
        $query = "SELECT * FROM website_content WHERE section = 'about_us'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

// Get current content
$aboutUsContent = getAboutUsContent();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $description = trim($_POST['about_description'] ?? '');
    
    $errors = [];
    
    if (empty($description)) {
        $errors[] = "About us description is required.";
    }
    
    // Update content if no errors
    if (empty($errors)) {
        try {
            $query = "UPDATE website_content SET description = :description WHERE section = 'about_us'";
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute([
                'description' => $description
            ]);
            
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => 'About Us section updated successfully!'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Failed to update About Us section.'
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

<!-- About Us Modal -->
<div class="modal fade" id="aboutUsModal" tabindex="-1" aria-labelledby="aboutUsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="aboutUsModalLabel">Update About Us Section</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="aboutUsForm" method="post">
                    <div class="mb-3">
                        <label for="about_description" class="form-label">About Us Description:</label>
                        <textarea class="form-control" id="about_description" name="about_description" rows="10"><?php echo htmlspecialchars($aboutUsContent['description'] ?? ''); ?></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveAboutUsChanges">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Submit form via AJAX
    $('#saveAboutUsChanges').on('click', function() {
        var formData = $('#aboutUsForm').serialize();
        
        $.ajax({
            type: "POST",
            url: "../admin/pages/website settings/modals/about_us_modal.php",
            data: formData,
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
                    $('#aboutUsModal .modal-body').prepend(successMessage);
                    
                    // Auto-close modal after delay
                    setTimeout(function() {
                        $('#aboutUsModal').modal('hide');
                        // Reload page to reflect changes
                        location.reload();
                    }, 500);
                } else {
                    // Create error message element
                    var errorMessage = $('<div>', {
                        class: 'alert alert-danger',
                        role: 'alert',
                        html: response.message
                    });
                    
                    // Show message in modal body
                    $('#aboutUsModal .modal-body').prepend(errorMessage);
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
                $('#aboutUsModal .modal-body').prepend(errorMessage);
            }
        });
    });
    
    // Remove modal from DOM when hidden
    $('#aboutUsModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
});
</script>