<?php
// Include database connection
require_once '../config.php';

// Fetch current welcome content
function getWelcomeContent() {
    global $pdo; // Declare $pdo as global to access it
    try {
        $query = "SELECT * FROM website_content WHERE section = 'welcome'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

// Get current content
$welcomeContent = getWelcomeContent();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $companyName = trim($_POST['company_name'] ?? '');
    $description = trim($_POST['welcome_description'] ?? '');
    
    $errors = [];
    
    if (empty($companyName)) {
        $errors[] = "Company name is required.";
    }
    
    if (empty($description)) {
        $errors[] = "Welcome description is required.";
    }
    
    // Update content if no errors
    if (empty($errors)) {
        try {
            $query = "UPDATE website_content SET company_name = :company_name, description = :description WHERE section = 'welcome'";
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute([
                'company_name' => $companyName,
                'description' => $description
            ]);
            
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => 'Welcome section updated successfully!'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Failed to update welcome section.'
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

<!-- Welcome Modal -->
<div class="modal fade" id="welcomeModal" tabindex="-1" aria-labelledby="welcomeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="welcomeModalLabel">Update Welcome Section</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="welcomeForm" method="post">
                    <div class="mb-3">
                        <label for="company_name" class="form-label">Company Name:</label>
                        <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($welcomeContent['company_name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="welcome_description" class="form-label">Welcome Description:</label>
                        <textarea class="form-control" id="welcome_description" name="welcome_description" rows="6"><?php echo htmlspecialchars($welcomeContent['description'] ?? ''); ?></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveWelcomeChanges">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Submit form via AJAX
    $('#saveWelcomeChanges').on('click', function() {
        var formData = $('#welcomeForm').serialize();
        
        $.ajax({
            type: "POST",
            url: "../admin/pages/website settings/modals/welcome_modal.php",
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
                    $('#welcomeModal .modal-body').prepend(successMessage);
                    
                    // Auto-close modal after delay
                    setTimeout(function() {
                        $('#welcomeModal').modal('hide');
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
                    $('#welcomeModal .modal-body').prepend(errorMessage);
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
                $('#welcomeModal .modal-body').prepend(errorMessage);
            }
        });
    });
    
    // Remove modal from DOM when hidden
    $('#welcomeModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
});
</script>