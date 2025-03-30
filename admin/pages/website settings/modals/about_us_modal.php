<?php
// Include database connection
require_once '../config.php';

// Enhanced sanitize input function
function sanitizeInput($input) {
    // Remove whitespace from beginning and end
    $input = trim($input);
    
    // Convert special characters to HTML entities to prevent XSS
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8', true);
    
    // Strip out HTML tags completely
    $input = strip_tags($input);
    
    // Limit input length
    $input = mb_substr($input, 0, 5000);
    
    // Additional protection against potential SQL injection
    $input = addslashes($input);
    
    return $input;
}

// Fetch current about us content
function getAboutUsContent() {
    global $pdo;
    try {
        $query = "SELECT * FROM website_content WHERE section = 'about_us'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        return false;
    }
}

// Get current content
$aboutUsContent = getAboutUsContent();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $description = sanitizeInput($_POST['about_description'] ?? '');
    
    $errors = [];
    
    if (empty($description)) {
        $errors[] = "About us description is required.";
    }
    
    // Additional validation
    if (strlen($description) > 5000) {
        $errors[] = "Description cannot exceed 5000 characters.";
    }
    
    // Update content if no errors
    if (empty($errors)) {
        try {
            $query = "UPDATE website_content SET description = :description WHERE section = 'about_us'";
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute([
                'description' => $description
            ]);
            
            $response = $result 
                ? ['success' => true, 'message' => 'Update successful']
                : ['success' => false, 'message' => 'Update failed'];
            
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            $response = [
                'success' => false,
                'message' => 'Database error occurred.'
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

<!-- About Us Modal (Static but Closable) -->
<div class="modal fade" id="aboutUsModal" tabindex="-1" aria-labelledby="aboutUsModalLabel" aria-hidden="true" data-bs-backdrop="static">
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
                        <textarea class="form-control" id="about_description" name="about_description" rows="10"><?php echo htmlspecialchars($aboutUsContent['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
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
                    // Redirect or update page without showing success message
                    location.reload();
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