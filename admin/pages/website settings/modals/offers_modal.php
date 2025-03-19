<?php
// Include database connection
require_once '../config.php';

// Fetch current offers content
function getOffersContent() {
    global $pdo; // Declare $pdo as global to access it
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
            // Removed the updated_at column reference
            $query = "UPDATE website_content SET description = :description WHERE section = 'offers'";
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute([
                'description' => $description
            ]);
            
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => 'Offers section updated successfully!'
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

<!-- Offers Modal -->
<div class="modal fade" id="offersModal" tabindex="-1" aria-labelledby="offersModalLabel" aria-hidden="true">
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
                        <textarea class="form-control" id="description" name="description" rows="6"><?php echo htmlspecialchars($offersContent['description'] ?? ''); ?></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveOffersChanges">Save changes</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Submit form via AJAX
    $('#saveOffersChanges').on('click', function() {
        var formData = $('#offersForm').serialize();
        
        $.ajax({
            type: "POST",
            url: "../admin/pages/website settings/modals/offers_modal.php",
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
                    $('#offersModal .modal-body').prepend(successMessage);
                    
                    // Auto-close modal after delay
                    setTimeout(function() {
                        $('#offersModal').modal('hide');
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
                    $('#offersModal .modal-body').prepend(errorMessage);
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
                $('#offersModal .modal-body').prepend(errorMessage);
            }
        });
    });
    
    // Remove modal from DOM when hidden
    $('#offersModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
});
</script>