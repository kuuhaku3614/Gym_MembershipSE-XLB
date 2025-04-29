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
    $primaryColor = trim($_POST['cp_primary_color'] ?? '');
    $secondaryColor = trim($_POST['cp_secondary_color'] ?? '');
    
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
$primaryHex = $colorContent ? '#'.str_pad(dechex(abs(floor($colorContent['latitude'] * 16777215))), 6, '0', STR_PAD_LEFT) : '#4CAF50';
$secondaryHex = $colorContent ? '#'.str_pad(dechex(abs(floor($colorContent['longitude'] * 16777215))), 6, '0', STR_PAD_LEFT) : '#2196F3';

// Ensure hex values start with #
if (!str_starts_with($primaryHex, '#')) {
    $primaryHex = '#' . $primaryHex;
}

if (!str_starts_with($secondaryHex, '#')) {
    $secondaryHex = '#' . $secondaryHex;
}

// Ensure hex values are properly formatted with length of 7 (#RRGGBB)
if (strlen($primaryHex) !== 7) {
    $primaryHex = '#' . str_pad(substr($primaryHex, 1), 6, '0', STR_PAD_LEFT);
}

if (strlen($secondaryHex) !== 7) {
    $secondaryHex = '#' . str_pad(substr($secondaryHex, 1), 6, '0', STR_PAD_LEFT);
}
?>
<style>
    #cp_saveColorChanges {
        background-color: #0d6efd!important;
    }
    
    .cp-color-picker {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        width: 75px;
        height: 40px;
        background-color: transparent;
        border: none;
        cursor: pointer;
    }
    
    .cp-color-picker::-webkit-color-swatch {
        border-radius: 5px;
        border: 1px solid #ddd;
    }
    
    .cp-color-picker::-moz-color-swatch {
        border-radius: 5px;
        border: 1px solid #ddd;
    }
    
    .cp-color-preview {
        width: 100%;
        height: 40px;
        border: 1px solid #e0e0e0;
        border-radius: 5px;
        margin-top: 10px;
        transition: background-color 0.3s ease;
    }
    
    .cp-modal-btn {
        transition: background-color 0.3s ease;
    }
    
    .cp-input-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .cp-hex-input {
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        padding: 0.375rem 0.75rem;
        flex-grow: 1;
        text-transform: uppercase;
    }
</style>

<!-- Color Palette Modal -->
<div class="modal fade" id="colorPaletteModal" tabindex="-1" aria-labelledby="cp_modalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cp_modalLabel">Update Color Palette</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="cp_messageContainer"></div>
                <form id="cp_colorPaletteForm" method="post">
                    <div class="mb-4">
                        <label for="cp_primaryColorPicker" class="form-label">Primary Color:</label>
                        <div class="cp-input-group">
                            <input type="color" class="cp-color-picker" id="cp_primaryColorPicker" name="cp_primary_color" value="<?php echo $primaryHex; ?>" title="Choose primary color">
                            <input type="text" class="cp-hex-input" id="cp_primaryColorHex" value="<?php echo strtoupper($primaryHex); ?>" pattern="^#[0-9A-Fa-f]{6}$" title="Please enter a valid hex color (e.g., #FF0000)">
                        </div>
                        <div class="cp-color-preview" id="cp_primaryColorPreview" style="background-color: <?php echo $primaryHex; ?>;"></div>
                    </div>
                    <div class="mb-4">
                        <label for="cp_secondaryColorPicker" class="form-label">Secondary Color:</label>
                        <div class="cp-input-group">
                            <input type="color" class="cp-color-picker" id="cp_secondaryColorPicker" name="cp_secondary_color" value="<?php echo $secondaryHex; ?>" title="Choose secondary color">
                            <input type="text" class="cp-hex-input" id="cp_secondaryColorHex" value="<?php echo strtoupper($secondaryHex); ?>" pattern="^#[0-9A-Fa-f]{6}$" title="Please enter a valid hex color (e.g., #FF0000)">
                        </div>
                        <div class="cp-color-preview" id="cp_secondaryColorPreview" style="background-color: <?php echo $secondaryHex; ?>;"></div>
                    </div>
                    
                    <div class="mt-4">
                        <h6>Preview:</h6>
                        <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn cp-modal-btn cp-preview-primary" id="cp_previewPrimaryBtn" style="background-color: <?php echo $primaryHex; ?>; color: white;">Primary Button</button>
                            <button type="button" class="btn cp-modal-btn cp-preview-secondary" id="cp_previewSecondaryBtn" style="background-color: <?php echo $secondaryHex; ?>; color: white;">Secondary Button</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cp_closeBtn" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="cp_saveColorChanges">Save Changes</button>
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
        // Add # if missing
        if (!hex.startsWith('#')) {
            hex = '#' + hex;
        }
        
        // Ensure it's exactly 7 characters (#RRGGBB)
        if (hex.length < 7) {
            hex = '#' + hex.substring(1).padStart(6, '0');
        } else if (hex.length > 7) {
            hex = '#' + hex.substring(1, 7);
        }
        
        return hex.toUpperCase();
    }

    // Show message in modal
    function showModalMessage(message, type) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        $('#cp_messageContainer').html(alertHtml);
        
        // Auto dismiss success messages
        if (type === 'success') {
            setTimeout(function() {
                $('.alert').alert('close');
            }, 3000);
        }
    }

    // Update hex input and preview when primary color picker changes
    $('#cp_primaryColorPicker').on('input', function() {
        const color = $(this).val().toUpperCase();
        $('#cp_primaryColorHex').val(color);
        $('#cp_primaryColorPreview').css('background-color', color);
        $('#cp_previewPrimaryBtn').css('background-color', color);
    });
    
    // Update hex input and preview when secondary color picker changes
    $('#cp_secondaryColorPicker').on('input', function() {
        const color = $(this).val().toUpperCase();
        $('#cp_secondaryColorHex').val(color);
        $('#cp_secondaryColorPreview').css('background-color', color);
        $('#cp_previewSecondaryBtn').css('background-color', color);
    });

    // Update color picker and preview when primary hex input changes
    $('#cp_primaryColorHex').on('input', function() {
        let hex = $(this).val();
        
        // Format hex value when it's valid
        if (hex.length >= 4) { // At least #RGB
            hex = formatHex(hex);
            if (isValidHex(hex)) {
                $('#cp_primaryColorPicker').val(hex);
                $('#cp_primaryColorPreview').css('background-color', hex);
                $('#cp_previewPrimaryBtn').css('background-color', hex);
            }
        }
    });
    
    // Update color picker and preview when secondary hex input changes
    $('#cp_secondaryColorHex').on('input', function() {
        let hex = $(this).val();
        
        // Format hex value when it's valid
        if (hex.length >= 4) { // At least #RGB
            hex = formatHex(hex);
            if (isValidHex(hex)) {
                $('#cp_secondaryColorPicker').val(hex);
                $('#cp_secondaryColorPreview').css('background-color', hex);
                $('#cp_previewSecondaryBtn').css('background-color', hex);
            }
        }
    });
    
    // Submit form via AJAX
    $('#cp_saveColorChanges').on('click', function() {
        // Validate hex values before submission
        const primaryHex = formatHex($('#cp_primaryColorHex').val());
        const secondaryHex = formatHex($('#cp_secondaryColorHex').val());
        
        // Update form values with formatted values
        $('#cp_primaryColorPicker').val(primaryHex);
        $('#cp_primaryColorHex').val(primaryHex);
        $('#cp_secondaryColorPicker').val(secondaryHex);
        $('#cp_secondaryColorHex').val(secondaryHex);
        
        const formData = $('#cp_colorPaletteForm').serialize();
        
        $.ajax({
            type: "POST",
            url: "../admin/pages/website settings/modals/color_palette_modal.php",
            data: formData,
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    showModalMessage(response.message, 'success');
                    
                    // Auto-close modal after delay
                    setTimeout(function() {
                        $('#colorPaletteModal').modal('hide');
                        // Reload page to reflect changes
                        location.reload();
                    }, 1500);
                } else {
                    showModalMessage(response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.log("Error details:", xhr, status, error);
                showModalMessage("An error occurred while processing your request.", 'error');
            }
        });
    });
    
    // Add blur event handlers to format hex inputs when focus is lost
    $('#cp_primaryColorHex, #cp_secondaryColorHex').on('blur', function() {
        const hex = formatHex($(this).val());
        if (isValidHex(hex)) {
            $(this).val(hex);
        }
    });
    
    // Remove modal from DOM when hidden
    $('#colorPaletteModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
});
</script>