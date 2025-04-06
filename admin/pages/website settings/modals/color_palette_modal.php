<?php
// Include database connection
require_once '../config.php';

// Fetch current color content
function getColorContent() {
    global $pdo;
    try {
        $query = "SELECT * FROM website_content WHERE section = 'color'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

// Get current content
$colorContent = getColorContent();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the color values
    $primaryColor = trim($_POST['primary_color'] ?? '');
    $secondaryColor = trim($_POST['secondary_color'] ?? '');
    
    $errors = [];
    
    if (empty($primaryColor)) {
        $errors[] = "Primary color is required.";
    }
    
    if (empty($secondaryColor)) {
        $errors[] = "Secondary color is required.";
    }
    
    // Convert hex colors to decimal values for storage
    $primaryHex = ltrim($primaryColor, '#');
    $secondaryHex = ltrim($secondaryColor, '#');
    
    // Convert hex to decimal values suitable for storage in decimal columns
    $primaryValue = hexdec($primaryHex) / 16777215; // Normalize to a decimal between 0-1
    $secondaryValue = hexdec($secondaryHex) / 16777215; // Normalize to a decimal between 0-1
    
    // Update content if no errors
    if (empty($errors)) {
        try {
            // Check if color section already exists
            $checkQuery = "SELECT COUNT(*) FROM website_content WHERE section = 'color'";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->execute();
            $exists = (int)$checkStmt->fetchColumn();
            
            if ($exists) {
                // Update existing record
                $query = "UPDATE website_content SET latitude = :primary_color, longitude = :secondary_color WHERE section = 'color'";
                $stmt = $pdo->prepare($query);
                $result = $stmt->execute([
                    'primary_color' => $primaryValue,
                    'secondary_color' => $secondaryValue
                ]);
            } else {
                // Insert new record
                $query = "INSERT INTO website_content (section, latitude, longitude) VALUES ('color', :primary_color, :secondary_color)";
                $stmt = $pdo->prepare($query);
                $result = $stmt->execute([
                    'primary_color' => $primaryValue,
                    'secondary_color' => $secondaryValue
                ]);
            }
            
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => 'Color palette updated successfully!'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Failed to update color palette.'
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


// Convert stored decimal values back to hex colors for display
$primaryHex = $colorContent ? '#'.dechex(abs(floor($colorContent['latitude'] * 16777215))) : '#4CAF50';
$secondaryHex = $colorContent ? '#'.dechex(abs(floor($colorContent['longitude'] * 16777215))) : '#2196F3';

// Ensure hex values are properly formatted with leading zeros
$primaryHex = strlen($primaryHex) < 7 ? str_pad(substr($primaryHex, 1), 6, '0', STR_PAD_LEFT) : $primaryHex;
$secondaryHex = strlen($secondaryHex) < 7 ? str_pad(substr($secondaryHex, 1), 6, '0', STR_PAD_LEFT) : $secondaryHex;
?>
<style>
    #saveColorChanges {
        background-color: #0d6efd!important;
    }
</style>
<!-- Color Palette Modal -->
<div class="modal fade" id="colorPaletteModal" tabindex="-1" aria-labelledby="colorPaletteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="colorPaletteModalLabel">Update Color Palette</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="colorPaletteForm" method="post">
                    <div class="mb-4">
                        <label for="primary_color" class="form-label">Primary Color:</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="primary_color" name="primary_color" value="<?php echo $primaryHex; ?>" title="Choose primary color">
                            <input type="text" class="form-control" id="primary_color_hex" value="<?php echo strtoupper($primaryHex); ?>" pattern="^#[0-9A-Fa-f]{6}$" title="Please enter a valid hex color (e.g., #FF0000)">
                        </div>
                        <div class="mt-2">
                            <div id="primary_color_preview" style="width: 100%; border:1px solid light grey; height: 40px; background-color: <?php echo $primaryHex; ?>; border-radius: 5px;"></div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="secondary_color" class="form-label">Secondary Color:</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="secondary_color" name="secondary_color" value="<?php echo $secondaryHex; ?>" title="Choose secondary color">
                            <input type="text" class="form-control" id="secondary_color_hex" value="<?php echo strtoupper($secondaryHex); ?>" pattern="^#[0-9A-Fa-f]{6}$" title="Please enter a valid hex color (e.g., #FF0000)">
                        </div>
                        <div class="mt-2">
                            <div id="secondary_color_preview" style="width: 100%; border:1px solid light grey; height: 40px; background-color: <?php echo $secondaryHex; ?>; border-radius: 5px;"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveColorChanges">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Function to validate and format hex color
    function isValidHex(hex) {
        return /^#[0-9A-Fa-f]{6}$/.test(hex);
    }

    function formatHex(hex) {
        hex = hex.toUpperCase();
        if (!hex.startsWith('#')) {
            hex = '#' + hex;
        }
        return hex;
    }

    // Update hex input and preview when color picker changes
    $('#primary_color').on('input', function() {
        var color = $(this).val().toUpperCase();
        $('#primary_color_hex').val(color);
        $('#primary_color_preview').css('background-color', color);
        updatePreview();
    });
    
    $('#secondary_color').on('input', function() {
        var color = $(this).val().toUpperCase();
        $('#secondary_color_hex').val(color);
        $('#secondary_color_preview').css('background-color', color);
        updatePreview();
    });

    // Update color picker and preview when hex input changes
    $('#primary_color_hex').on('input', function() {
        var hex = formatHex($(this).val());
        if (isValidHex(hex)) {
            $('#primary_color').val(hex);
            $('#primary_color_preview').css('background-color', hex);
            updatePreview();
        }
    });
    
    $('#secondary_color_hex').on('input', function() {
        var hex = formatHex($(this).val());
        if (isValidHex(hex)) {
            $('#secondary_color').val(hex);
            $('#secondary_color_preview').css('background-color', hex);
            updatePreview();
        }
    });

    // Function to update the preview buttons
    function updatePreview() {
        var primaryColor = $('#primary_color').val();
        var secondaryColor = $('#secondary_color').val();
        
        $('.btn:first').css('background-color', primaryColor);
        $('.btn:last').css('background-color', secondaryColor);
    }
    
    // Submit form via AJAX
    $('#saveColorChanges').on('click', function() {
        var formData = $('#colorPaletteForm').serialize();
        
        $.ajax({
            type: "POST",
            url: "../admin/pages/website settings/modals/color_palette_modal.php",
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
                    $('#colorPaletteModal .modal-body').prepend(successMessage);
                    
                    // Auto-close modal after delay
                    setTimeout(function() {
                        $('#colorPaletteModal').modal('hide');
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
                    $('#colorPaletteModal .modal-body').prepend(errorMessage);
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
                $('#colorPaletteModal .modal-body').prepend(errorMessage);
            }
        });
    });
    
    // Remove modal from DOM when hidden
    $('#colorPaletteModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
});
</script>