<?php
// Include database connection
require_once '../config.php';

// Sanitize input function
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Fetch current offers content
function getOffersContent() {
    global $pdo; 
    try {
        $query = "SELECT * FROM website_content WHERE section = 'offers'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

// Get current content
$offersContent = getOffersContent();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $description = trim($_POST['description'] ?? '');
    
    $errors = [];
    
    if (empty($description)) {
        $errors[] = "Offers description is required.";
    }
    
    // Update content if no errors
    if (empty($errors)) {
        try {
            $query = "UPDATE website_content SET description = :description WHERE section = 'offers'";
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute([
                'description' => $description
            ]);
            
            if ($result) {
                $response = [
                    'success' => true
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Failed to update offers section.'
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
    } else {
        // Return errors as JSON
        $response = [
            'success' => false,
            'message' => implode('<br>', array_map('sanitizeInput', $errors))
        ];
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}
?>

<!-- Offers Modal -->
<div class="modal fade" id="offersModal" tabindex="-1" aria-labelledby="offersModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="offersModalLabel">Update Offers Section</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="offersForm" method="post">
                    <div class="mb-3">
                        <label for="description" class="form-label">Offers Description:</label>
                        <textarea class="form-control" id="description" name="description" rows="6"><?php echo sanitizeInput($offersContent['description'] ?? ''); ?></textarea>
                    </div>
                    <div id="errorContainer" class="alert alert-danger" style="display: none;"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveOffersChanges">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Submit form via AJAX
    $('#saveOffersChanges').on('click', function() {
        var formData = $('#offersForm').serialize();
        var $errorContainer = $('#errorContainer');
        
        // Reset error container
        $errorContainer.hide().empty();
        
        $.ajax({
            type: "POST",
            url: "../admin/pages/website settings/modals/offers_modal.php",
            data: formData,
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    // No success message, just reload page
                    location.reload();
                } else {
                    // Show error message
                    $errorContainer.html(response.message).show();
                }
            },
            error: function(xhr, status, error) {
                // Show generic error message
                $errorContainer.html("An error occurred while processing your request.").show();
                console.log("Error details:", xhr, status, error);
            }
        });
    });
    
    // Remove modal from DOM when hidden
    $('#offersModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
});
</script>