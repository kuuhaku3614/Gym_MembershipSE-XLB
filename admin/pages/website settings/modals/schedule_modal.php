<?php
// Include database connection
require_once '../config.php';

// Sanitize input function
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Days and hours arrays for dropdowns
$days = [
    'Monday', 'Tuesday', 'Wednesday', 'Thursday', 
    'Friday', 'Saturday', 'Sunday'
];

$hours = [];
for ($hour = 0; $hour < 24; $hour++) {
    foreach (['00', '30'] as $minute) {
        $time = sprintf('%02d:%s', $hour, $minute);
        $ampm = $hour < 12 ? 'AM' : 'PM';
        $displayTime = date('h:i A', strtotime($time));
        $hours[] = $displayTime;
    }
}

// Fetch current schedule content
function getScheduleContent() {
    global $pdo; 
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
    $daysFrom = trim($_POST['days_from'] ?? '');
    $daysTo = trim($_POST['days_to'] ?? '');
    $hoursFrom = trim($_POST['hours_from'] ?? '');
    $hoursTo = trim($_POST['hours_to'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    $errors = [];
    
    // Validate inputs with more robust checks
    if (empty($daysFrom) || empty($daysTo)) {
        $errors[] = "Both start and end days are required.";
    }
    
    if (empty($hoursFrom) || empty($hoursTo)) {
        $errors[] = "Both start and end hours are required.";
    }
    
    // Create combined strings for database storage
    $daysCombined = $daysFrom . ' - ' . $daysTo;
    $hoursCombined = $hoursFrom . ' - ' . $hoursTo;
    
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
                    'days' => $daysCombined,
                    'hours' => $hoursCombined,
                    'notes' => $notes
                ]);
            } else {
                // Insert new record
                $query = "INSERT INTO website_content (section, days, hours, description) VALUES ('schedule', :days, :hours, :notes)";
                $stmt = $pdo->prepare($query);
                $result = $stmt->execute([
                    'days' => $daysCombined,
                    'hours' => $hoursCombined,
                    'notes' => $notes
                ]);
            }
            
            if ($result) {
                $response = [
                    'success' => true
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

// Parse existing content if exists
$existingDays = isset($scheduleContent['days']) ? explode(' - ', $scheduleContent['days']) : ['', ''];
$existingHours = isset($scheduleContent['hours']) ? explode(' - ', $scheduleContent['hours']) : ['', ''];
?>

<!-- Schedule Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scheduleModalLabel">Update Operating Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="scheduleForm" method="post">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Days of Operation:</label>
                            <div class="d-flex align-items-center">
                                <select class="form-select me-2" id="days_from" name="days_from" required>
                                    <option value="">From</option>
                                    <?php foreach ($days as $day): ?>
                                        <option value="<?php echo sanitizeInput($day); ?>" 
                                                <?php echo ($existingDays[0] === $day) ? 'selected' : ''; ?>>
                                            <?php echo sanitizeInput($day); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="mx-2">to</span>
                                <select class="form-select" id="days_to" name="days_to" required>
                                    <option value="">To</option>
                                    <?php foreach ($days as $day): ?>
                                        <option value="<?php echo sanitizeInput($day); ?>" 
                                                <?php echo ($existingDays[1] === $day) ? 'selected' : ''; ?>>
                                            <?php echo sanitizeInput($day); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Hours of Operation:</label>
                            <div class="d-flex align-items-center">
                                <select class="form-select me-2" id="hours_from" name="hours_from" required>
                                    <option value="">From</option>
                                    <?php foreach ($hours as $hour): ?>
                                        <option value="<?php echo sanitizeInput($hour); ?>" 
                                                <?php echo ($existingHours[0] === $hour) ? 'selected' : ''; ?>>
                                            <?php echo sanitizeInput($hour); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="mx-2">to</span>
                                <select class="form-select" id="hours_to" name="hours_to" required>
                                    <option value="">To</option>
                                    <?php foreach ($hours as $hour): ?>
                                        <option value="<?php echo sanitizeInput($hour); ?>" 
                                                <?php echo ($existingHours[1] === $hour) ? 'selected' : ''; ?>>
                                            <?php echo sanitizeInput($hour); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Special Notes (Optional):</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="e.g., Closed on public holidays"><?php echo sanitizeInput($scheduleContent['description'] ?? ''); ?></textarea>
                    </div>
                    <div id="errorContainer" class="alert alert-danger" style="display: none;"></div>
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
        var $errorContainer = $('#errorContainer');
        
        // Reset error container
        $errorContainer.hide().empty();
        
        $.ajax({
            type: "POST",
            url: "../admin/pages/website settings/modals/schedule_modal.php",
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
    $('#scheduleModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
});
</script>