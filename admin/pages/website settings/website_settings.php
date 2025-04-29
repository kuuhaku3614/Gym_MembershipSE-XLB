<?php
// Include database connection
require_once 'config.php';
// Function to fetch dynamic content
function getDynamicContent($section) {
    global $pdo;
    $query = "SELECT * FROM website_content WHERE section = :section";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['section' => $section]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to fetch existing content from a table
function fetchExistingContent($table) {
    global $pdo;
    $query = "SELECT * FROM $table";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// Fetch current content for pre-filling forms
$logo = getDynamicContent('logo');
$welcomeContent = getDynamicContent('welcome');
$offersContent = getDynamicContent('offers');
$aboutUsContent = getDynamicContent('about_us');
$contactContent = getDynamicContent('contact');

// Fetch existing content for management
$gymOffers = fetchExistingContent('gym_offers');
$products = fetchExistingContent('products');
$staffMembers = fetchExistingContent('staff');
$galleryImages = fetchExistingContent('gallery_images');
?>

<style>
    .btn{
        color: white!important;
        font-family: "Inter", sans-serif!important;
    }
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
            top: 0;
            z-index: 1000;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 10px 20px;
        }

        .error, .success {
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            animation: slideIn 0.3s ease-out;
            transition: opacity 0.5s ease-out; /* Added for smooth fade-out */
        }

        .error {
            background-color: #ffebee;
            color: #d32f2f;
            border: 1px solid #d32f2f;
        }

        .success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #2e7d32;
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
    .existing-items-container {
        display: flex;
        overflow-x: auto;
        overflow-y: hidden;
        gap: 15px;
        padding: 10px;
        background-color: #f9f9f9;
        white-space: nowrap;
        border: 1px solid #ddd;
        border-radius: 5px;
        scrollbar-width: thin;
        scrollbar-color: #888 #f1f1f1;
    }

    .existing-items-container::-webkit-scrollbar {
        height: 8px;
    }

    .existing-items-container::-webkit-scrollbar-thumb {
        background-color: #888;
        border-radius: 4px;
    }

    .existing-item {
        flex: 0 0 250px; /* Fixed width for consistent layout */
        height: 300px; /* Increased height */
        padding: 15px;
        background-color: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 5px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        transition: transform 0.2s;
    }

    .existing-item:hover {
        transform: scale(1.02);
    }

    .existing-item img {
        width: 220px;
        height: 220px;
        object-fit: contain;
        align-self: center;
        margin: 10px 0;
        border: 1px solid #eee;
        border-radius: 4px;
    }

    .existing-item .item-actions {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .item-actions input[type="submit"] {
        flex-grow: 1;
        padding: 8px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .item-actions .update-btn {
        background-color: #4CAF50;
        color: white;
    }

    .item-actions .delete-btn {
        background-color: #f44336;
        color: white;
    }

    .item-actions input[type="submit"]:hover {
        opacity: 0.9;
    }
    /* CSS */
.update-logo-btn, .update-color-palette-btn, .update-welcome-btn, .update-offers-btn, .update-gym-offers-btn, 
.update-about-us-btn, .update-contact-btn, .update-products-btn, .update-staff-btn, .update-gallery-btn,
.update-staff-member-btn, .update-product-btn, .update-offer-btn, .update-terms-btn, .update-schedule-btn {
    color: white; /* Set the text color */
    border: none; /* Remove border */
    padding: 10px 20px; /* Add padding */
    text-align: center; /* Center text */
    text-decoration: none; /* Remove text decoration */
    display: inline-block; /* Keep the element inline-block */
    font-size: 16px; /* Set font size */
    background-color: #4CAF50; /* Green background */
    border-radius: 4px; /* Rounded corners */
}

.update-logo-btn:hover, 
.update-color-palette-btn:hover, 
.update-welcome-btn:hover, 
.update-offers-btn:hover, 
.update-gym-offers-btn:hover, 
.update-about-us-btn:hover, 
.update-contact-btn:hover, 
.update-products-btn:hover, 
.update-staff-btn:hover, 
.update-gallery-btn:hover,
.update-staff-member-btn:hover,
.update-product-btn:hover,
.update-offer-btn:hover, 
.update-terms-btn:hover,
.update-schedule-btn:hover {
    background-color: #45a049; /* Slightly darker green */
    cursor: pointer;
    transition: background-color 0.3s ease;
}

/* .btn:hover {
color: #f8f8f8;
} */

.button-link {
  color: white; /* Set link color */
  text-decoration: none; /* Remove link underline */
}

.button-link:hover {
  color: white; /* Keep link color on hover */
  text-decoration: none; /* Ensure link underline stays removed */
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

/* Fixed height cards with consistent button positioning */
.item-card {
  width: 18rem;
  height: 400px; /* Fixed height for all cards */
  margin-bottom: 20px;
  position: relative; /* For absolute positioning of content */
  display: flex;
  flex-direction: column;
}

.item-card .card-img-container {
  height: 220px; /* Fixed height for image container */
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.item-card .card-img-top {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain; /* Maintain aspect ratio */
}

.item-card .card-body {
  padding: 1rem;
  display: flex;
  flex-direction: column;
  flex-grow: 1; /* Take remaining space */
}

.item-card .card-title {
  margin-bottom: 1rem;
  height: 50px; /* Fixed height for title */
  overflow: hidden;
}

.item-card .card-buttons {
  margin-top: auto; /* Push to bottom */
  display: flex;
  gap: 10px;
}

.card-buttons .btn {
  flex: 1;
 }/*
.btn .btn-secondary {
  background-color: #6e6e6e!important;
  color: white;
}
.btn .btn-secondary:hover {
  background-color: #616161!important;
  color: white;
}
.btn .btn-primary {
  background-color: #4361ee!important;
  color: white;
}
.btn .btn-primary:hover {
  background-color: rgb(39, 75, 238)!important;
  color: white;
} */


</style>
<div class="container-fluid px-4 py-4">
<div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Website Settings</h2>
        <a href="../index.php" class="btn btn-primary">Go to Website</a>
    </div>
<!-- Logo Section Update -->
<div class="card mb-3" data-section="logo">
    <div class="card-header">
        <h2>Logo</h2>
        <button class="btn update-logo-btn">Update</button>
    </div>
    <div class="card-body">
        <div class="text-center">
            <?php
            // Fetch logo information
            $logoContent = getDynamicContent('logo');
            if (!empty($logoContent) && !empty($logoContent['location'])):
            ?>
                <img src="../<?php echo htmlspecialchars($logoContent['location']); ?>" alt="Site Logo" class="img-fluid" style="max-height: 100px;">
            <?php else: ?>
                <p>No logo currently set.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Color Palette Section -->
<div class="card mb-3" data-section="color-palette">
    <div class="card-header">
        <h2>Color Palette</h2>
        <button class="btn update-color-palette-btn">Update</button>
    </div>
    <div class="card-body">
        <?php
        // Fetch color palette information
        $colorContent = getDynamicContent('color');
        
        // Convert stored decimal values to hex colors
        $primaryColor = $colorContent && isset($colorContent['latitude']) ? 
            '#'.str_pad(dechex(abs(floor($colorContent['latitude'] * 16777215))), 6, '0', STR_PAD_LEFT) : 
            '#4CAF50';
            
        $secondaryColor = $colorContent && isset($colorContent['longitude']) ? 
            '#'.str_pad(dechex(abs(floor($colorContent['longitude'] * 16777215))), 6, '0', STR_PAD_LEFT) : 
            '#2196F3';
        ?>
        <div class="d-flex justify-content-around mb-3">
            <div class="color-swatch-container text-center">
                <div class="color-swatch" style="background-color: <?php echo $primaryColor; ?>; width: 150px; height: 150px; border-radius: 5px; box-shadow: 0 3px 6px rgba(0,0,0,0.16);"></div>
                <p class="mt-3"><strong>Primary Color</strong><br><?php echo strtoupper($primaryColor); ?></p>
            </div>
            <div class="color-swatch-container text-center">
                <div class="color-swatch" style="background-color: <?php echo $secondaryColor; ?>; width: 150px; height: 150px; border-radius: 5px; box-shadow: 0 3px 6px rgba(0,0,0,0.16);"></div>
                <p class="mt-3"><strong>Secondary Color</strong><br><?php echo strtoupper($secondaryColor); ?></p>
            </div>
        </div>
        </div>
</div>

<!-- Welcome Section Update -->
<div class="card mb-3" data-section="welcome">
    <div class="card-header">
        <h2>Welcome Section</h2>
        <button class="btn update-welcome-btn">Update</button>
    </div>
    <div class="card-body">
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Company Name:</label>
                <input type="text" class="form-control" name="company_name" value="<?php echo htmlspecialchars($welcomeContent['company_name'] ?? ''); ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Welcome Description:</label>
                <textarea class="form-control" name="welcome_description" readonly><?php echo htmlspecialchars($welcomeContent['description'] ?? ''); ?></textarea>
            </div>
        </form>
    </div>
</div>

<!-- Offers Section Update -->
<div class="card mb-3" data-section="offers">
    <div class="card-header">
        <h2>Offers Section</h2>
        <button class="btn update-offers-btn">Update</button>
    </div>
    <div class="card-body">
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Offers Description:</label>
                <textarea class="form-control" name="offers_description" readonly><?php echo htmlspecialchars($offersContent['description'] ?? ''); ?></textarea>
            </div>
        </form>
    </div>
</div>

<!-- Manage Existing Gym Offers -->
<div class="card mb-3" data-section="manage-gym-offers">
    <div class="card-header">
        <h2>Manage Existing Gym Offers</h2>
        <button class="btn update-gym-offers-btn">Add New Offer</button>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-3">
            <?php foreach ($gymOffers as $offer): ?>
                <div class="card item-card">
                    <div class="card-img-container">
                        <img src="../<?php echo 'cms_img/offers/' . basename($offer['image_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($offer['title']); ?>">
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($offer['name'] ?? $offer['title']); ?></h5>
                        <div class="card-buttons">
                            <button class="btn update-offer-btn" data-id="<?php echo $offer['id']; ?>">Edit</button>
                            <button class="btn delete-offer-btn" data-id="<?php echo $offer['id']; ?>" style="background-color: #f44336;">Delete</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- About Us Section -->
<div class="card mb-3" data-section="about-us">
    <div class="card-header">
        <h2>About Us Section</h2>
        <button class="btn update-about-us-btn">Update</button>
    </div>
    <div class="card-body">
        <form method="post">
            <div class="mb-3">
                <label class="form-label">About Us Description:</label>
                <textarea class="form-control" name="about_description" readonly><?php echo htmlspecialchars($aboutUsContent['description'] ?? ''); ?></textarea>
            </div>
        </form>
    </div>
</div>

<!-- Contact Information -->
<div class="card mb-3" data-section="contact">
    <div class="card-header">
        <h2>Contact Information</h2>
        <button class="btn update-contact-btn">Update</button>
    </div>
    <div class="card-body">
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Location:</label>
                <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($contactContent['location'] ?? ''); ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone Number:</label>
                <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($contactContent['phone'] ?? ''); ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Email:</label>
                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($contactContent['email'] ?? ''); ?>" readonly>
            </div>
        </form>
    </div>
</div>

<!-- Manage Existing Products -->
<div class="card mb-3" data-section="products">
    <div class="card-header">
        <h2>Manage Existing Products</h2>
        <button class="btn update-products-btn">Add New Product</button>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-3">
            <?php foreach ($products as $product): ?>
                <div class="card item-card">
                    <div class="card-img-container">
                        <img src="../<?php echo 'cms_img/products/' . basename($product['image_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <div class="card-buttons">
                            <button class="btn update-product-btn" data-id="<?php echo $product['id']; ?>">Edit</button>
                            <button class="btn delete-product-btn" data-id="<?php echo $product['id']; ?>" style="background-color: #f44336;">Delete</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Manage Staff Members -->
<div class="card mb-3" data-section="staff">
    <div class="card-header">
        <h2>Manage Staff Members</h2>
        <button class="btn update-staff-btn">Add New Staff</button>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-3">
            <?php foreach ($staffMembers as $staff): ?>
                <div class="card item-card">
                    <div class="card-img-container">
                        <img src="../<?php echo 'cms_img/staff/' . basename($staff['image_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($staff['name']); ?>">
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($staff['name']); ?> (<?php echo htmlspecialchars($staff['status']); ?>)</h5>
                        <div class="card-buttons">
                            <button class="btn update-staff-member-btn" data-id="<?php echo $staff['id']; ?>">Edit</button>
                            <button class="btn delete-staff-btn" data-id="<?php echo $staff['id']; ?>" style="background-color: #f44336;">Delete</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Manage Gallery Images -->
<div class="card mb-3" data-section="gallery">
    <div class="card-header">
        <h2>Manage Gallery Images</h2>
        <button class="btn update-gallery-btn">Add New Image</button>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-3">
            <?php foreach ($galleryImages as $image): ?>
                <div class="card item-card">
                    <div class="card-img-container">
                        <img src="../<?php echo 'cms_img/gallery/' . basename($image['image_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($image['alt_text'] ?? 'Gallery Image'); ?>">
                    </div>
                    <div class="card-body">
                        <div class="card-buttons">
                            <button class="btn delete-gallery-btn" data-id="<?php echo $image['id']; ?>" style="background-color: #f44336;">Delete</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Operating Schedule Section -->
<div class="card mb-3" data-section="schedule">
    <div class="card-header">
        <h2>Operating Schedule</h2>
        <button class="btn update-schedule-btn" style="background-color: #4CAF50;">Update</button>
    </div>
    <div class="card-body">
        <?php
        // Fetch schedule information
        $scheduleContent = getDynamicContent('schedule');
        ?>
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Days of Operation:</label>
                <input type="text" class="form-control" name="days" value="<?php echo htmlspecialchars($scheduleContent['days'] ?? ''); ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Hours of Operation:</label>
                <input type="text" class="form-control" name="hours" value="<?php echo htmlspecialchars($scheduleContent['hours'] ?? ''); ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Special Notes:</label>
                <textarea class="form-control" name="notes" readonly><?php echo htmlspecialchars($scheduleContent['description'] ?? ''); ?></textarea>
            </div>
        </form>
    </div>
</div>

<!-- Terms and Conditions Section -->
<div class="card mb-3" data-section="terms-and-conditions">
    <div class="card-header">
        <h2>Terms and Conditions</h2>
        <button class="btn update-terms-btn" style="background-color: #4CAF50;">Update</button>
    </div>
    <div class="card-body">
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Terms and Conditions:</label>
                <textarea class="form-control" name="terms_conditions" readonly><?php echo htmlspecialchars(getDynamicContent('terms_conditions')['description'] ?? ''); ?></textarea>
            </div>
        </form>
    </div>
</div>

</div>
<script>
   $(document).ready(function() {
    // Preserve scroll position across page reloads
    function preserveScrollPosition() {
    // Save scroll position before page reload
    $(window).on('beforeunload', function() {
        localStorage.setItem('scrollPosition', window.scrollY);
    });

    // Restore scroll position after page load
    const savedScrollPosition = localStorage.getItem('scrollPosition');
    if (savedScrollPosition) {
        // Use scrollTo with instant behavior for immediate positioning
        window.scrollTo({
            top: parseInt(savedScrollPosition),
            behavior: 'instant'
        });
        localStorage.removeItem('scrollPosition');
    }
}
    preserveScrollPosition();

    function showConfirmModal(message, confirmCallback) {
    // Create confirmation modal HTML without the close (X) button
    const modalHtml = `
    <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Action</h5>
                </div>
                <div class="modal-body">
                    ${message}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmAction">Confirm</button>
                </div>
            </div>
        </div>
    </div>
    `;
    
    // Remove any existing confirmation modal
    $('#confirmModal').remove();
    
    // Append modal to body
    $('body').append(modalHtml);
    
    // Show modal
    $('#confirmModal').modal('show');
    
    // Handle confirm button click
    $('#confirmAction').on('click', function() {
        $('#confirmModal').modal('hide');
        confirmCallback();
    });

    // Handle cancel button click to close modal
    $('.btn-secondary[data-dismiss="modal"]').on('click', function() {
        $('#confirmModal').modal('hide');
    });
}

    // Function to show toast notification
    function showToast(message, type = 'success') {
        // Remove any existing toasts
        $('.toast-notification').remove();
        
        const toastHtml = `
        <div class="toast-notification position-fixed top-0 end-0 p-3" style="z-index: 1050;">
            <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            </div>
        </div>
        `;
        
        // Append toast to body
        $('body').append(toastHtml);
        
        // Show and auto-hide toast
        const toastEl = $('.toast-notification .toast');
        toastEl.toast({ autohide: true, delay: 3000 });
        toastEl.toast('show');
        
        // Remove toast after animation
        toastEl.on('hidden.bs.toast', function() {
            $('.toast-notification').remove();
        });
    }
    
    // Welcome Section
    $('.update-welcome-btn').on('click', function() {
        $.ajax({
            type: "GET",
            url: "pages/website settings/modals/welcome_modal.php",
            dataType: "html",
            success: function(response) {
                $("body").append(response);
                $("#welcomeModal").modal('show');
            },
            error: function(xhr, status, error) {
                console.log("Error details:", xhr, status, error);
                alert("Error loading the welcome modal.");
            }
        });
    });

    // Offers Section
    $('.update-offers-btn').on('click', function() {
        $.ajax({
            type: "GET",
            url: "pages/website settings/modals/offers_modal.php",
            dataType: "html",
            success: function(response) {
                $("body").append(response);
                $("#offersModal").modal('show');
            },
            error: function(xhr, status, error) {
                console.log("Error details:", xhr, status, error);
                alert("Error loading the offers modal.");
            }
        });
    });

    // Gym Offers Management
    $('.update-gym-offers-btn').on('click', function() {
        $.ajax({
            type: "GET",
            url: "pages/website settings/modals/add_gym_offer_modal.php",
            dataType: "html",
            success: function(response) {
                $("body").append(response);
                $("#addGymOfferModal").modal('show');
            },
            error: function(xhr, status, error) {
                console.log("Error details:", xhr, status, error);
                alert("Error loading the add gym offer modal.");
            }
        });
    });

    // Edit Existing Gym Offer
    $('.update-offer-btn').on('click', function() {
        var offerId = $(this).data('id');
        $.ajax({
            type: "GET",
            url: "pages/website settings/modals/edit_gym_offer_modal.php",
            data: { id: offerId },
            dataType: "html",
            success: function(response) {
                $("body").append(response);
                $("#editGymOfferModal").modal('show');
            },
            error: function(xhr, status, error) {
                console.log("Error details:", xhr, status, error);
                alert("Error loading the edit gym offer modal.");
            }
        });
    });

    // Delete Gym Offer
    $('.delete-offer-btn').on('click', function() {
        var offerId = $(this).data('id');
        showConfirmModal("Are you sure you want to delete this offer?", function() {
            $.ajax({
                type: "POST",
                url: "pages/website settings/modals/delete_gym_offer.php",
                data: { id: offerId },
                dataType: "json",
                success: function(response) {
                    if(response.success) {
                        showToast("Offer deleted successfully!");
                        location.reload();
                    } else {
                        showToast("Error: " + response.message, 'danger');
                    }
                },
                error: function(xhr, status, error) {
                    console.log("Error details:", xhr, status, error);
                    showToast("Error deleting the offer.", 'danger');
                }
            });
        });
    });

    // About Us Section
    $('.update-about-us-btn').on('click', function() {
        $.ajax({
            type: "GET",
            url: "pages/website settings/modals/about_us_modal.php",
            dataType: "html",
            success: function(response) {
                $("body").append(response);
                $("#aboutUsModal").modal('show');
            },
            error: function(xhr, status, error) {
                console.log("Error details:", xhr, status, error);
                alert("Error loading the about us modal.");
            }
        });
    });

    // Contact Information
    $('.update-contact-btn').on('click', function() {
        $.ajax({
            type: "GET",
            url: "pages/website settings/modals/contact_modal.php",
            dataType: "html",
            success: function(response) {
                $("body").append(response);
                $("#contactModal").modal('show');
            },
            error: function(xhr, status, error) {
                console.log("Error details:", xhr, status, error);
                alert("Error loading the contact modal.");
            }
        });
    });

    // Products Management
    $('.update-products-btn').on('click', function() {
        $.ajax({
            type: "GET",
            url: "pages/website settings/modals/add_product_modal.php",
            dataType: "html",
            success: function(response) {
                $("body").append(response);
                $("#addProductModal").modal('show');
            },
            error: function(xhr, status, error) {
                console.log("Error details:", xhr, status, error);
                alert("Error loading the add product modal.");
            }
        });
    });

    // Edit Existing Product
    $('.update-product-btn').on('click', function() {
        var productId = $(this).data('id');
        $.ajax({
            type: "GET",
            url: "pages/website settings/modals/edit_product_modal.php",
            data: { id: productId },
            dataType: "html",
            success: function(response) {
                $("body").append(response);
                $("#editProductModal").modal('show');
            },
            error: function(xhr, status, error) {
                console.log("Error details:", xhr, status, error);
                alert("Error loading the edit product modal.");
            }
        });
    });

    // Delete Product
    $('.delete-product-btn').on('click', function() {
        var productId = $(this).data('id');
        showConfirmModal("Are you sure you want to delete this product?", function() {
            $.ajax({
                type: "POST",
                url: "pages/website settings/modals/delete_product.php",
                data: { id: productId },
                dataType: "json",
                success: function(response) {
                    if(response.success) {
                        showToast("Product deleted successfully!");
                        location.reload();
                    } else {
                        showToast("Error: " + response.message, 'danger');
                    }
                },
                error: function(xhr, status, error) {
                    console.log("Error details:", xhr, status, error);
                    showToast("Error deleting the product.", 'danger');
                }
            });
        });
    });

    // Staff Management
    $('.update-staff-btn').on('click', function() {
        $.ajax({
            type: "GET",
            url: "pages/website settings/modals/add_staff_modal.php",
            dataType: "html",
            success: function(response) {
                $("body").append(response);
                $("#addStaffModal").modal('show');
            },
            error: function(xhr, status, error) {
                console.log("Error details:", xhr, status, error);
                alert("Error loading the add staff modal.");
            }
        });
    });

    // Edit Existing Staff Member
    $('.update-staff-member-btn').on('click', function() {
        var staffId = $(this).data('id');
        $.ajax({
            type: "GET",
            url: "pages/website settings/modals/edit_staff_modal.php",
            data: { id: staffId },
            dataType: "html",
            success: function(response) {
                $("body").append(response);
                $("#editStaffModal").modal('show');
            },
            error: function(xhr, status, error) {
                console.log("Error details:", xhr, status, error);
                alert("Error loading the edit staff modal.");
            }
        });
    });

    // Delete Staff Member
    $('.delete-staff-btn').on('click', function() {
        var staffId = $(this).data('id');
        showConfirmModal("Are you sure you want to delete this staff member?", function() {
            $.ajax({
                type: "POST",
                url: "pages/website settings/modals/delete_staff.php",
                data: { id: staffId },
                dataType: "json",
                success: function(response) {
                    if(response.success) {
                        showToast("Staff member deleted successfully!");
                        location.reload();
                    } else {
                        showToast("Error: " + response.message, 'danger');
                    }
                },
                error: function(xhr, status, error) {
                    console.log("Error details:", xhr, status, error);
                    showToast("Error deleting the staff member.", 'danger');
                }
            });
        });
    });

    // Gallery Management
    $('.update-gallery-btn').on('click', function() {
        $.ajax({
            type: "GET",
            url: "pages/website settings/modals/add_gallery_modal.php",
            dataType: "html",
            success: function(response) {
                $("body").append(response);
                $("#addGalleryModal").modal('show');
            },
            error: function(xhr, status, error) {
                console.log("Error details:", xhr, status, error);
                alert("Error loading the add gallery modal.");
            }
        });
    });

    // Delete Gallery Image
    $('.delete-gallery-btn').on('click', function() {
        var imageId = $(this).data('id');
        showConfirmModal("Are you sure you want to delete this image?", function() {
            $.ajax({
                type: "POST",
                url: "pages/website settings/modals/delete_gallery.php",
                data: { id: imageId },
                dataType: "json",
                success: function(response) {
                    if(response.success) {
                        showToast("Image deleted successfully!");
                        location.reload();
                    } else {
                        showToast("Error: " + response.message, 'danger');
                    }
                },
                error: function(xhr, status, error) {
                    console.log("Error details:", xhr, status, error);
                    showToast("Error deleting the image.", 'danger');
                }
            });
        });
    });

    // Logo Management
    $('.update-logo-btn').on('click', function() {
        $.ajax({
            type: "GET",
            url: "pages/website settings/modals/logo_modal.php",
            dataType: "html",
            success: function(response) {
                $("body").append(response);
                $("#logoModal").modal('show');
            },
            error: function(xhr, status, error) {
                console.log("Error details:", xhr, status, error);
                alert("Error loading the logo modal.");
            }
        });
    });

    // Color Palette
    $('.update-color-palette-btn').on('click', function() {
        $.ajax({
            type: "GET",
            url: "pages/website settings/modals/color_palette_modal.php",
            dataType: "html",
            success: function(response) {
                $("body").append(response);
                $("#colorPaletteModal").modal('show');
            },
            error: function(xhr, status, error) {
                console.log("Error details:", xhr, status, error);
                alert("Error loading the color palette modal.");
            }
        });
    });

    // Operating Schedule
    $('.update-schedule-btn').on('click', function() {
        $.ajax({
            type: "GET",
            url: "pages/website settings/modals/schedule_modal.php",
            dataType: "html",
            success: function(response) {
                $("body").append(response);
                $("#scheduleModal").modal('show');
            },
            error: function(xhr, status, error) {
                console.log("Error details:", xhr, status, error);
                alert("Error loading the schedule modal.");
            }
        });
    });

    // Terms and Conditions Section
    $('.update-terms-btn').on('click', function() {
        $.ajax({
            type: "GET",
            url: "pages/website settings/modals/terms_conditions.modal.php",
            dataType: "html",
            success: function(response) {
                $("body").append(response);
                $("#termsConditionsModal").modal('show');
            },
            error: function(xhr, status, error) {
                console.log("Error details:", xhr, status, error);
                alert("Error loading the terms and conditions modal.");
            }
        });
    });

    // Color picker and hex input synchronization
    function syncColorInputs(pickerElement, hexElement, swatchElement) {
        // Update hex when color picker changes
        $(pickerElement).on('input', function() {
            $(hexElement).val($(this).val().toUpperCase());
            $(swatchElement).css('background-color', $(this).val());
        });

        // Update color picker when hex input changes
        $(hexElement).on('input', function() {
            let hex = $(this).val();
            if (/^#[0-9A-Fa-f]{6}$/.test(hex)) {
                $(pickerElement).val(hex);
                $(swatchElement).css('background-color', hex);
            }
        });
    }

    // Initialize color input synchronization
    syncColorInputs('#primaryColorPicker', '#primaryColorHex', '#primaryColorSwatch');
    syncColorInputs('#secondaryColorPicker', '#secondaryColorHex', '#secondaryColorSwatch');

    // Update preview buttons when colors change
    function updatePreviewButtons() {
        $('.preview-primary-btn').css('background-color', $('#primaryColorPicker').val());
        $('.preview-secondary-btn').css('background-color', $('#secondaryColorPicker').val());
    }

    $('#primaryColorPicker, #secondaryColorPicker').on('input', updatePreviewButtons);
    $('#primaryColorHex, #secondaryColorHex').on('change', updatePreviewButtons);
});
</script>