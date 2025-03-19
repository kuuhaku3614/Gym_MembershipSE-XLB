<?php
// Include database connection
require_once '../config.php';

// Fetch current contact content
function getContactContent() {
    global $pdo;
    try {
        $query = "SELECT * FROM website_content WHERE section = 'contact'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

// Get current content
$contactContent = getContactContent();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $location = trim($_POST['location'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
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
            
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => 'Contact information updated successfully!'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Failed to update contact information.'
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

<!-- Contact Modal -->
<div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contactModalLabel">Update Contact Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="contactForm" method="post">
                    <div class="mb-3">
                        <label for="location" class="form-label">Location:</label>
                        <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($contactContent['location'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number:</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($contactContent['phone'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email:</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($contactContent['email'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select Location on Map:</label>
                        <div id="map" style="height: 400px; width: 100%;"></div>
                        <p class="mt-2 small text-muted">Click on the map to select your location.</p>
                        <input type="hidden" id="latitude" name="latitude" value="<?php echo htmlspecialchars($contactContent['latitude'] ?? ''); ?>">
                        <input type="hidden" id="longitude" name="longitude" value="<?php echo htmlspecialchars($contactContent['longitude'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <p id="selected-location" class="fw-bold">
                            <?php 
                            if (!empty($contactContent['latitude']) && !empty($contactContent['longitude'])) {
                                echo "Selected Location: " . htmlspecialchars($contactContent['location'] ?? '');
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
                <button type="button" class="btn btn-primary" id="saveContactChanges">Save changes</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize the map
    var map = L.map('map').setView([
        <?php echo !empty($contactContent['latitude']) ? $contactContent['latitude'] : '6.913'; ?>, 
        <?php echo !empty($contactContent['longitude']) ? $contactContent['longitude'] : '122.073'; ?>
    ], 15);

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // Add marker if coordinates exist
    var marker;
    if (<?php echo !empty($contactContent['latitude']) && !empty($contactContent['longitude']) ? 'true' : 'false'; ?>) {
        marker = L.marker([
            <?php echo $contactContent['latitude'] ?? '6.913'; ?>,
            <?php echo $contactContent['longitude'] ?? '122.073'; ?>
        ]).addTo(map);
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

    // Add click event to the map
    map.on('click', onMapClick);

    // Submit form via AJAX
    $('#saveContactChanges').on('click', function() {
        var formData = $('#contactForm').serialize();
        
        $.ajax({
            type: "POST",
            url: "../admin/pages/website settings/modals/contact_modal.php",
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
                    $('#contactModal .modal-body').prepend(successMessage);
                    
                    // Auto-close modal after delay
                    setTimeout(function() {
                        $('#contactModal').modal('hide');
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
                    $('#contactModal .modal-body').prepend(errorMessage);
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
                $('#contactModal .modal-body').prepend(errorMessage);
            }
        });
    });
    
    // Remove modal from DOM when hidden
    $('#contactModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
});
</script>