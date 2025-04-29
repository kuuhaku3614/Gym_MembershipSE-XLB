<?php
// Include database connection
require_once '../config.php';

// Sanitize input function
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Fetch current terms and conditions content
function getTermsConditionsContent() {
    global $pdo;
    try {
        $query = "SELECT * FROM website_content WHERE section = 'terms_conditions'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        return false;
    }
}

// Get current content
$termsConditionsContent = getTermsConditionsContent();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and get input
    $description = isset($_POST['terms_description']) ? trim($_POST['terms_description']) : null;
    
    try {
        $query = "UPDATE website_content SET description = :description WHERE section = 'terms_conditions'";
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([
            'description' => $description
        ]);
        
        if ($result) {
            $response = [
                'success' => true,
                'message' => 'Terms and Conditions updated successfully!'
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Failed to update Terms and Conditions.'
            ];
        }
        
        // Return JSON response for AJAX
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } catch (PDOException $e) {
        $response = [
            'success' => false,
            'message' => 'Database error: ' . sanitizeInput($e->getMessage())
        ];
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}
?>

<!-- Terms and Conditions Modal -->
<div class="modal fade" id="termsConditionsModal" tabindex="-1" aria-labelledby="termsConditionsModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsConditionsModalLabel">Terms and Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="errorContainer" class="mb-3"></div>
                <form id="termsConditionsForm" method="post">
                    <div class="mb-3">
                        <label for="terms_description" class="form-label">Terms and Conditions:</label>
                        <textarea class="form-control" id="terms_description" name="terms_description" rows="10"><?php echo sanitizeInput($termsConditionsContent['description'] ?? ''); ?></textarea>
                        <small class="text-muted">You can leave this field empty if needed.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveTermsConditions">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Submit form via AJAX
    $('#saveTermsConditions').on('click', function() {
        var formData = $('#termsConditionsForm').serialize();
        
        // Clear previous error messages
        $('#errorContainer').empty();
        
        $.ajax({
            type: "POST",
            url: "../admin/pages/website settings/modals/terms_conditions.modal.php",
            data: formData,
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    // Reload page to reflect changes
                    location.reload();
                } else {
                    // Show error message
                    var errorMessage = $('<div>', {
                        class: 'alert alert-danger alert-dismissible fade show',
                        role: 'alert',
                        html: response.message + 
                              '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
                    });
                    
                    // Show message in error container
                    $('#errorContainer').append(errorMessage);
                }
            },
            error: function(xhr, status, error) {
                console.log("Error details:", xhr, status, error);
                
                // Create error message element
                var errorMessage = $('<div>', {
                    class: 'alert alert-danger alert-dismissible fade show',
                    role: 'alert',
                    html: "An error occurred while processing your request." +
                          '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
                });
                
                // Show message in error container
                $('#errorContainer').append(errorMessage);
            }
        });
    });
    
    // Remove modal from DOM when hidden
    $('#termsConditionsModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
});
</script>