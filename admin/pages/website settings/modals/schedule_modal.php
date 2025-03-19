<?php
// Include database connection
require_once '../config.php';

// Fetch current schedule content
function getScheduleContent() {
    global $pdo; // Declare $pdo as global to access it
    try {
        $query = "SELECT * FROM website_content WHERE section = 'schedule'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

// Get current content
$scheduleContent = getScheduleContent();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $days = trim($_POST['days'] ?? '');
    $hours = trim($_POST['hours'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    $errors = [];
    
    if (empty($days)) {
        $errors[] = "Days of operation are required.";
    }
    
    if (empty($hours)) {
        $errors[] = "Hours of operation are required.";
    }
    
    // Update content if no errors
    if (empty($errors)) {
        try {
            // Check if schedule entry already exists
            $checkQuery = "SELECT id FROM website_content WHERE section = 'schedule'";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->execute();
            $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($exists) {
                // Update existing record
                $query = "UPDATE website_content SET days = :days, hours = :hours, description = :notes WHERE section = 'schedule'";
                $stmt = $pdo->prepare($query);
                $result = $stmt->execute([
                    'days' => $days,
                    'hours' => $hours,
                    'notes' => $notes
                ]);
            } else {
                // Insert new record
                $query = "INSERT INTO website_content (section, days, hours, description) VALUES ('schedule', :days, :hours, :notes)";
                $stmt = $pdo->prepare($query);
                $result = $stmt->execute([
                    'days' => $days,
                    'hours' => $hours,
                    'notes' => $notes
                ]);
            }
            
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => 'Operating schedule updated successfully!'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Failed to update operating schedule.'
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

<!-- Schedule Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scheduleModalLabel">Update Operating Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="scheduleForm" method="post">
                    <div class="mb-3">
                        <label for="days" class="form-label">Days of Operation:</label>
                        <input type="text" class="form-control" id="days" name="days" placeholder="e.g., Monday-Friday" value="<?php echo htmlspecialchars($scheduleContent['days'] ?? ''); ?>">
                        <small class="form-text text-muted">Enter the days your gym is open (e.g., Monday-Friday)</small>
                    </div>
                    <div class="mb-3">
                        <label for="hours" class="form-label">Hours of Operation:</label>
                        <input type="text" class="form-control" id="hours" name="hours" placeholder="e.g., 9:00 AM - 9:00 PM" value="<?php echo htmlspecialchars($scheduleContent['hours'] ?? ''); ?>">
                        <small class="form-text text-muted">Enter the operating hours (e.g., 9:00 AM - 9:00 PM)</small>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Special Notes (Optional):</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="e.g., Closed on public holidays"><?php echo htmlspecialchars($scheduleContent['description'] ?? ''); ?></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveScheduleChanges">Save changes</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Submit form via AJAX
    $('#saveScheduleChanges').on('click', function() {
        var formData = $('#scheduleForm').serialize();
        
        $.ajax({
            type: "POST",
            url: "../admin/pages/website settings/modals/schedule_modal.php",
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
                    $('#scheduleModal .modal-body').prepend(successMessage);
                    
                    // Auto-close modal after delay
                    setTimeout(function() {
                        $('#scheduleModal').modal('hide');
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
                    $('#scheduleModal .modal-body').prepend(errorMessage);
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
                $('#scheduleModal .modal-body').prepend(errorMessage);
            }
        });
    });
    
    // Remove modal from DOM when hidden
    $('#scheduleModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
});
</script>