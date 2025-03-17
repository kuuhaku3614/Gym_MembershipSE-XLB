<?php

// Include process content file with all functions
require_once(__DIR__ . '/process_content.php');

// Fetch current content for pre-filling forms
$welcomeContent = getDynamicContent('welcome');
$contactContent = getDynamicContent('contact');
?>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <style>
        .section {
            background-color: #f4f4f4;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        input, textarea {
            margin-bottom: 10px;
            padding: 5px;
        }
        .message-container {
            position: sticky;
            top: 10;
            z-index: 1000;
            width: 100%;
            margin-bottom: 20px;
        }

        .message-container .error, 
        .message-container .success {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #f44336;
            color: white;
            position: relative;
            transition: all 0.5s ease;
            margin: 0;
            padding: 10px;
        }

        .message-container .success {
            background-color: #4CAF50;
        }

        .message-container .dismiss-btn {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0 10px;
            transition: color 0.3s ease;
        }

        .message-container .dismiss-btn:hover {
            color: rgba(255,255,255,0.7);
        }

        .message-container .error.hide,
        .message-container .success.hide {
            opacity: 0;
            transform: translateY(-100%);
            max-height: 0;
            padding: 0;
            overflow: hidden;
        }
        .error, .success {
            width: 100%;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            animation: slideIn 0.3s ease-out;
            transition: opacity 0.5s ease-out;
            cursor: pointer;
            opacity: 1;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        .btn {
            background-color: green;
            color: white;
            border: none;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            cursor: pointer;
        }

        .button-link {
            color: white;
            text-decoration: none;
        }

        .button-link:hover {
            color: white;
            text-decoration: none;
        }
    </style>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Update Contents</h2>
        <div class="mt-2">
            <div class="message-container" id="message-container">
                <?php if (isset($error)): ?>
                    <div class="error" id="message-alert"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="success" id="message-alert">Content updated successfully!</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Welcome Section Update -->
    <div class="section" data-section="welcome">
        <h2>Welcome Section</h2>
        <form method="post" action="/admin/pages/website settings/process_content.php">
            <label>Company Name:</label>
            <input type="text" name="company_name" value="<?php echo htmlspecialchars($welcomeContent['company_name'] ?? ''); ?>" required>
            
            <label>Welcome Description:</label>
            <textarea name="welcome_description" required><?php echo htmlspecialchars($welcomeContent['description'] ?? ''); ?></textarea>
            
            <input type="submit" name="update_welcome" value="Update Welcome Section">
        </form>
    </div>

    <!-- Contact Information Section -->
    <div class="section" data-section="contact">
        <h2>Contact Information</h2>
        <form method="post" action="/admin/pages/website settings/process_content.php" id="contactForm">
            <input type="hidden" name="latitude" id="locationLatitude" value="<?php echo $contactContent['latitude'] ?? '6.913126'; ?>">
            <input type="hidden" name="longitude" id="locationLongitude" value="<?php echo $contactContent['longitude'] ?? '122.072516'; ?>">
            <input type="hidden" name="location_name" id="locationName" value="<?php echo htmlspecialchars($contactContent['location'] ?? ''); ?>">

            <label>Phone Number:</label>
            <input type="tel" name="phone" value="<?php echo htmlspecialchars($contactContent['phone'] ?? ''); ?>" required>
            
            <label>Email:</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($contactContent['email'] ?? ''); ?>" required>

            <label>Selected Location:</label>
            <input type="text" id="displayLocation" value="<?php echo htmlspecialchars($contactContent['location'] ?? ''); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;">

            <div id="mapModalTrigger" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#locationModal">Select Location on Map</div>
            <br>
            <input type="submit" name="update_contact" value="Update Contact Information">
        </form>
    </div>
</div>

<!-- Modal for Map -->
<div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="locationModalLabel">Select Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="map" style="height: 400px; width: 100%; margin-bottom: 20px;"></div>
                <div>
                    <input type="text" id="searchLocation" placeholder="Search for a location..." style="width: 70%; padding: 8px;">
                    <button id="searchButton" class="btn btn-primary">Search</button>
                </div>
                <div style="margin-top: 20px;">
                    <input id="selectedLocationName" type="text" placeholder="Location Name" style="width: 70%; padding: 8px; margin-right: 10px;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirmLocation" class="btn btn-success">Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Map functionality
    let map, marker;
    let currentLatitude = document.getElementById('locationLatitude').value || 6.913126;
    let currentLongitude = document.getElementById('locationLongitude').value || 122.072516;
    
    // Handle message dismissal
    const messageContainer = document.getElementById('message-container');
    if (messageContainer) {
        const messages = messageContainer.querySelectorAll('.error, .success');
        messages.forEach(message => {
            message.addEventListener('click', function() {
                message.classList.add('hide');
                setTimeout(() => {
                    message.remove();
                }, 500);
            });
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                message.classList.add('hide');
                setTimeout(() => {
                    message.remove();
                }, 500);
            }, 5000);
        });
    }
    
    // Modal handling with Bootstrap
    const locationModal = new bootstrap.Modal(document.getElementById('locationModal'));
    
    // Initialize map when modal is shown
    document.getElementById('locationModal').addEventListener('shown.bs.modal', function () {
        initMap();
    });
    
    // Initialize map
    function initMap() {
        if (map) {
            map.remove();
        }
        
        map = L.map('map').setView([currentLatitude, currentLongitude], 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        marker = L.marker([currentLatitude, currentLongitude], {
            draggable: true
        }).addTo(map);
        
        marker.on('dragend', function(event) {
            const position = marker.getLatLng();
            currentLatitude = position.lat;
            currentLongitude = position.lng;
            updateLocationInput();
        });
        
        // Update location when clicking on map
        map.on('click', function(e) {
            currentLatitude = e.latlng.lat;
            currentLongitude = e.latlng.lng;
            marker.setLatLng(e.latlng);
            updateLocationInput();
        });
        
        // Initialize search functionality
        const searchButton = document.getElementById('searchButton');
        const searchInput = document.getElementById('searchLocation');
        
        searchButton.addEventListener('click', function() {
            const query = searchInput.value;
            if (query) {
                searchLocation(query);
            }
        });
        
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const query = this.value;
                if (query) {
                    searchLocation(query);
                }
            }
        });
    }
    
    // Confirm location button
    document.getElementById('confirmLocation').addEventListener('click', function() {
        const locationName = document.getElementById('selectedLocationName').value.trim();
        
        if (!locationName) {
            alert('Please enter a name for this location.');
            return;
        }
        
        document.getElementById('locationLatitude').value = currentLatitude;
        document.getElementById('locationLongitude').value = currentLongitude;
        document.getElementById('locationName').value = locationName;
        document.getElementById('displayLocation').value = locationName;
        
        // Hide modal using Bootstrap's method
        locationModal.hide();
    });
    
    function searchLocation(query) {
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    const location = data[0];
                    currentLatitude = parseFloat(location.lat);
                    currentLongitude = parseFloat(location.lon);
                    
                    map.setView([currentLatitude, currentLongitude], 15);
                    marker.setLatLng([currentLatitude, currentLongitude]);
                    
                    // Update displayed location name
                    document.getElementById('selectedLocationName').value = location.display_name || query;
                    updateLocationInput();
                } else {
                    alert('Location not found. Please try a different search.');
                }
            })
            .catch(error => {
                console.error('Error searching for location:', error);
                alert('Error searching for location. Please try again.');
            });
    }
    
    function updateLocationInput() {
        // This function would typically do a reverse geocode lookup
        // For simplicity, we'll just update with coordinates if no name is set
        const selectedLocationField = document.getElementById('selectedLocationName');
        if (!selectedLocationField.value) {
            selectedLocationField.value = `Lat: ${currentLatitude.toFixed(6)}, Long: ${currentLongitude.toFixed(6)}`;
        }
    }
    
    // Add scroll restoration functionality
    <?php if (isset($_SESSION['scroll_to'])): ?>
    const scrollToSection = document.querySelector('[data-section="<?php echo $_SESSION['scroll_to']; ?>"]');
    if (scrollToSection) {
        scrollToSection.scrollIntoView();
    }
    <?php 
    // Clear the scroll position from session
    unset($_SESSION['scroll_to']);
    endif; ?>
    
    // Store current scroll position when submitting forms
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const section = this.closest('.section');
            if (section) {
                const scrollToInput = document.createElement('input');
                scrollToInput.type = 'hidden';
                scrollToInput.name = 'scroll_to';
                scrollToInput.value = section.dataset.section;
                this.appendChild(scrollToInput);
            }
        });
    });
    
});
</script>