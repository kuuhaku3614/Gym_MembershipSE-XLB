<?php
// Include database connection
require_once '../config.php';

// Sanitize input function
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Fetch current contact content
function getContactContent() {
    global $pdo;
    try {
        $query = "SELECT * FROM website_content WHERE section = 'contact'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        return false;
    }
}

// Get current content
$contactContent = getContactContent();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $location = sanitizeInput($_POST['location'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : null;
    $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : null;
    
    $errors = [];
    
    if (empty($location)) {
        $errors[] = "Location is required.";
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required.";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    
    if ($latitude === null || $longitude === null) {
        $errors[] = "Please select a location on the map.";
    }
    
    // Update content if no errors
    if (empty($errors)) {
        try {
            $query = "UPDATE website_content SET location = :location, phone = :phone, email = :email, latitude = :latitude, longitude = :longitude WHERE section = 'contact'";
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute([
                'location' => $location,
                'phone' => $phone,
                'email' => $email,
                'latitude' => $latitude,
                'longitude' => $longitude
            ]);
            
            $response = [
                'success' => $result,
                'message' => $result ? 'Contact information updated successfully!' : 'Failed to update contact information.'
            ];
            
            // Return JSON response for AJAX
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

<!-- Contact Modal -->
<div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contactModalLabel">Update Contact Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="errorContainer" class="alert alert-danger" style="display:none;"></div>
                <form id="contactForm" method="post">
                    <div class="mb-3">
                        <label for="location" class="form-label">Location:</label>
                        <input type="text" class="form-control" id="location" name="location" value="<?php echo sanitizeInput($contactContent['location'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number:</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo sanitizeInput($contactContent['phone'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email:</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo sanitizeInput($contactContent['email'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select Location on Map:</label>
                        <div id="map" style="height: 400px; width: 100%;"></div>
                        <p class="mt-2 small text-muted">Click on the map to select your location.</p>
                        <input type="hidden" id="latitude" name="latitude" value="<?php echo sanitizeInput($contactContent['latitude'] ?? ''); ?>">
                        <input type="hidden" id="longitude" name="longitude" value="<?php echo sanitizeInput($contactContent['longitude'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <p id="selected-location" class="fw-bold">
                            <?php 
                            if (!empty($contactContent['latitude']) && !empty($contactContent['longitude'])) {
                                echo "Selected Location: " . sanitizeInput($contactContent['location'] ?? '');
                            } else {
                                echo "No location selected";
                            }
                            ?>
                        </p>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveContactChanges">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
var map, marker;

// Initialize the map function
function initMap() {
    if (map) {
        // If map already exists, invalidate size to refresh it properly
        map.invalidateSize();
        return;
    }
    
    // Get default coordinates
    var defaultLat = <?php echo !empty($contactContent['latitude']) ? $contactContent['latitude'] : '6.913'; ?>;
    var defaultLng = <?php echo !empty($contactContent['longitude']) ? $contactContent['longitude'] : '122.073'; ?>;
    
    // Create map
    map = L.map('map').setView([defaultLat, defaultLng], 15);

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // Add marker if coordinates exist
    if (<?php echo !empty($contactContent['latitude']) && !empty($contactContent['longitude']) ? 'true' : 'false'; ?>) {
        marker = L.marker([defaultLat, defaultLng]).addTo(map);
    }

    // Add click event to the map
    map.on('click', onMapClick);
}

// Function to handle map clicks
function onMapClick(e) {
    // Remove existing marker if there is one
    if (marker) {
        map.removeLayer(marker);
    }
    
    // Add a new marker at the clicked location
    marker = L.marker(e.latlng).addTo(map);
    
    // Update the hidden inputs with the new coordinates
    $('#latitude').val(e.latlng.lat);
    $('#longitude').val(e.latlng.lng);
    
    // Use reverse geocoding to get address
    reverseGeocode(e.latlng.lat, e.latlng.lng);
}

// Function to perform reverse geocoding
function reverseGeocode(lat, lng) {
    // Using Nominatim API for reverse geocoding
    $.ajax({
        url: `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data && data.display_name) {
                $('#location').val(data.display_name);
                $('#selected-location').text('Selected Location: ' + data.display_name);
            }
        },
        error: function() {
            alert('Error performing reverse geocoding');
        }
    });
}

$(document).ready(function() {
    // Initialize map when modal is fully shown
    $('#contactModal').on('shown.bs.modal', function() {
        setTimeout(initMap, 100); // Short delay to ensure DOM is ready
    });
    
    // Submit form via AJAX
    $('#saveContactChanges').on('click', function() {
        var formData = $('#contactForm').serialize();
        
        // Clear previous error messages
        $('#errorContainer').hide().empty();
        
        $.ajax({
            type: "POST",
            url: "../admin/pages/website settings/modals/contact_modal.php",
            data: formData,
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    // Reload page to reflect changes
                    location.reload();
                } else {
                    // Show error messages
                    $('#errorContainer').html(response.message).show();
                }
            },
            error: function(xhr, status, error) {
                console.log("Error details:", xhr, status, error);
                
                // Show generic error message
                $('#errorContainer').html("An error occurred while processing your request.").show();
            }
        });
    });
    
    // Handle modal closing
    $('#contactModal').on('hidden.bs.modal', function () {
        // Clean up map instance when modal is closed
        if (map) {
            map.remove();
            map = null;
        }
    });
});
</script>