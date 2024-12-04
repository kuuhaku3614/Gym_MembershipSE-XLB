<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php';
require_once 'file_upload.php'; 
require_once 'content_management.php'; 
require_once 'database_fetching.php';   
require_once 'website_function.php';

?>
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
            height: 400px; /* Increased height to accommodate more content */
            gap: 15px;
            padding: 10px;
            background-color: #f9f9f9;
            white-space: nowrap;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .existing-item {
            flex: 0 0 auto;
            width: 250px;
            height: 380px; /* Fixed height for consistent layout */
            padding: 10px;
            background-color: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between; /* Distribute space evenly */
        }

        .existing-item img {
            width: 200px;
            height: 200px;
            object-fit: contain;
            align-self: center;
            margin: 10px 0;
        }

        .existing-item form {
            width: 100%;
            display: flex;
            justify-content: center;
            margin-top: 10px;
        }

        .existing-item input[type="submit"] {
            width: 100%; /* Make delete button full width */
            padding: 8px;
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .existing-item input[type="submit"]:hover {
            background-color: #d32f2f;
        }
    </style>
<div class="container-fluid">
<h1 class="nav-title">Website Settings</h1>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
            <?php echo htmlspecialchars($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

    <!-- Welcome Section Update -->
    <div class="section" data-section="welcome">
        <h2>Welcome Section</h2>
        <form method="post">
            <label>Company Name:</label>
            <input type="text" name="company_name" value="<?php echo htmlspecialchars($welcomeContent['company_name'] ?? ''); ?>" required>
            
            <label>Welcome Description:</label>
            <textarea name="welcome_description" required><?php echo htmlspecialchars($welcomeContent['description'] ?? ''); ?></textarea>
            
            <input type="submit" name="update_welcome" value="Update Welcome Section">
        </form>
    </div>

    <!-- Offers Section Update -->
    <div class="section" data-section="offers">
        <h2>Offers Section</h2>
        <form method="post">
            <label>Offers Description:</label>
            <textarea name="offers_description" required><?php echo htmlspecialchars($offersContent['description'] ?? ''); ?></textarea>
            
            <input type="submit" name="update_offers" value="Update Offers Section">
        </form>
    </div>

    <!-- Add Gym Offer -->
    <div class="section" data-section="add-gym-offer">
        <h2>Add New Gym Offer</h2>
        <form method="post" enctype="multipart/form-data">
            <label>Offer Title:</label>
            <input type="text" name="offer_title" required>
            
            <label>Offer Description:</label>
            <textarea name="offer_description" required></textarea>
            
            <label>Offer Image:</label>
            <input type="file" name="offer_image" accept="image/*" required>
            
            <input type="submit" name="add_offer" value="Add Gym Offer">
        </form>
    </div>
    <div class="section" data-section="manage-gym-offers">
        <h2>Manage Existing Gym Offers</h2>
        <div class="existing-items-container">
            <?php foreach ($gymOffers as $offer): ?>
                <div class="existing-item">
                    <h3><?php echo htmlspecialchars($offer['name'] ?? $offer['title']); ?></h3>
                    <img src="../<?php echo 'cms_img/offers/' . basename($offer['image_path']); ?>" alt="<?php echo htmlspecialchars($offer['title']); ?>">
                    
                    <form method="post">
                        <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                        <input type="submit" name="delete_offer" value="Delete Offer" onclick="return confirm('Are you sure you want to delete this offer?');">
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- About Us Section -->
    <div class="section" data-section="about-us">
        <h2>About Us Section</h2>
        <form method="post">
            <label>About Us Description:</label>
            <textarea name="about_description" required><?php echo htmlspecialchars($aboutUsContent['description'] ?? ''); ?></textarea>
            
            <input type="submit" name="update_about" value="Update About Us Section">
        </form>
    </div>

    <!-- Contact Information -->
    <div class="section" data-section="contact">
        <h2>Contact Information</h2>
        <form method="post">
            <label>Location:</label>
            <input type="text" name="location" value="<?php echo htmlspecialchars($contactContent['location'] ?? ''); ?>" required>
            
            <label>Phone Number:</label>
            <input type="tel" name="phone" value="<?php echo htmlspecialchars($contactContent['phone'] ?? ''); ?>" required>
            
            <label>Email:</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($contactContent['email'] ?? ''); ?>" required>
            
            <input type="submit" name="update_contact" value="Update Contact Information">
        </form>
    </div>

    <!-- Add New Product -->
    <div class="section" data-section="add-product">
        <h2>Add New Product</h2>
        <form method="post" enctype="multipart/form-data">
            <label>Product Name:</label>
            <input type="text" name="product_name" required>
            
            <label>Product Description:</label>
            <textarea name="product_description" required></textarea>
            
            <label>Product Image:</label>
            <input type="file" name="product_image" accept="image/*" required>
            
            <input type="submit" name="add_product" value="Add Product">
        </form>
    </div>
    <div class="section" data-section="products">
            <h2>Manage Existing Products</h2>
            <div class="existing-items-container">
                <?php foreach ($products as $product): ?>
                    <div class="existing-item">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <img src="../<?php echo 'cms_img/products/' . basename($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        
                <form method="post">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <input type="submit" name="delete_product" value="Delete Product" onclick="return confirm('Are you sure you want to delete this product?');">
                </form>
            </div>
        <?php endforeach; ?>
        </div>
    </div>

    <!-- Add Staff Member -->
        <div class="section" data-section="add-staff">
            <h2>Add Staff Member</h2>
            <form method="post" enctype="multipart/form-data">
                <label>Staff Name:</label>
                <input type="text" name="staff_name" required>
                
                <label>Staff Status (e.g., Trainer, Manager):</label>
                <input type="text" name="staff_status" required>
                
                <label>Staff Image:</label>
                <input type="file" name="staff_image" accept="image/*" required>
                
                <input type="submit" name="add_staff" value="Add Staff Member">
            </form>
        </div>
    <div class="section" data-section="staff">
        <h2>Manage Staff Members</h2>
        <div class="existing-items-container">
            <?php foreach ($staffMembers as $staff): ?>
                <div class="existing-item">
                    <h3><?php echo htmlspecialchars($staff['name']); ?> (<?php echo htmlspecialchars($staff['status']); ?>)</h3>
                    <img src="../<?php echo 'cms_img/staff/' . basename($staff['image_path']); ?>" alt="<?php echo htmlspecialchars($staff['name']); ?>">
            
                    <form method="post">
                        <input type="hidden" name="staff_id" value="<?php echo $staff['id']; ?>">
                        <input type="submit" name="delete_staff" value="Delete Staff Member" onclick="return confirm('Are you sure you want to delete this staff member?');">
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
        <!-- Add Gallery Image -->
    <div class="section" data-section="add-gallery-image">
        <h2>Add Gallery Image</h2>
        <form method="post" enctype="multipart/form-data">
            <label>Gallery Image:</label>
            <input type="file" name="gallery_image" accept="image/*" required>
            
            <label>Alternative Text (Optional):</label>
            <input type="text" name="gallery_image_alt" placeholder="Describe the image">
            
            <input type="submit" name="add_gallery_image" value="Add Gallery Image">
        </form>
    </div>
    <div class="section" data-section="gallery">
        <h2>Manage Gallery Images</h2>
        <div class="existing-items-container">
            <?php foreach ($galleryImages as $image): ?>
                <div class="existing-item">
                    <img src="../<?php echo 'cms_img/gallery/' . basename($image['image_path']); ?>" alt="<?php echo htmlspecialchars($image['alt_text'] ?? 'Gallery Image'); ?>">
            
                    <form method="post">
                        <input type="hidden" name="gallery_image_id" value="<?php echo $image['id']; ?>">
                        <input type="submit" name="delete_gallery_image" value="Delete Gallery Image" onclick="return confirm('Are you sure you want to delete this gallery image?');">
                    </form>
                </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>
<script>
        // Scroll preservation script
        document.addEventListener('DOMContentLoaded', function() {
            // Check if there's a scroll position to restore
            const urlParams = new URLSearchParams(window.location.search);
            const scrollTo = urlParams.get('scrollTo');
            
            if (scrollTo) {
                const element = document.querySelector(`[data-section="${scrollTo}"]`);
                if (element) {
                    element.scrollIntoView({ behavior: 'auto' });
                }
            }

            // Add scroll preservation to forms
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function() {
                    // Store the section's identifier for scroll preservation
                    const sectionElement = this.closest('.section');
                    if (sectionElement) {
                        const scrollInput = document.createElement('input');
                        scrollInput.type = 'hidden';
                        scrollInput.name = 'scroll_to';
                        scrollInput.value = sectionElement.dataset.section || '';
                        this.appendChild(scrollInput);
                    }
                });
            });
        });
        // message
        document.addEventListener('DOMContentLoaded', function() {
        const messageAlerts = document.querySelectorAll('#message-alert');
        
        messageAlerts.forEach(messageAlert => {
            const removeMessage = () => {
                messageAlert.style.transition = 'opacity 0.5s ease-out';
                messageAlert.style.opacity = '0';
                
                setTimeout(() => {
                    messageAlert.remove();
                }, 500);
            };

            // Set timeout to remove message
            setTimeout(removeMessage, 2000); // 5 seconds

            // Optional: Allow manual dismissal by clicking
            messageAlert.addEventListener('click', removeMessage);

            // Debug logging
            console.log('Message alert found:', messageAlert);
        });
    });
    </script>