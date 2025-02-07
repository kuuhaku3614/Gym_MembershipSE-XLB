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
.btn {
  color: white; /* Set the text color */
  border: none; /* Remove border */
  padding: 10px 20px; /* Add padding */
  text-align: center; /* Center text */
  text-decoration: none; /* Remove text decoration */
  display: inline-block; /* Keep the element inline-block */
  font-size: 16px; /* Set font size */
  cursor: pointer; /* Change cursor to pointer */
}

.button-link {
  color: white; /* Set link color */
  text-decoration: none; /* Remove link underline */
}

.button-link:hover {
  color: white; /* Keep link color on hover */
  text-decoration: none; /* Ensure link underline stays removed */
}

</style>

<h1 class="nav-title">Website</h1>
<!-- HTML -->
<a href="/Gym_MembershipSE-XLB/admin/content_management" class="button-link">
    <button type="button" class="btn btn-primary">Update website</button>
</a>
    <!-- Welcome Section Update -->
    <div class="section" data-section="welcome">
        <h2>Welcome Section</h2>
        <form method="post">
            <label>Company Name:</label>
            <input type="text" name="company_name" value="<?php echo htmlspecialchars($welcomeContent['company_name'] ?? ''); ?>" readonly>
            
            <label>Welcome Description:</label>
            <textarea name="welcome_description" readonly><?php echo htmlspecialchars($welcomeContent['description'] ?? ''); ?></textarea>
        </form>
    </div>

    <!-- Offers Section Update -->
    <div class="section" data-section="offers">
        <h2>Offers Section</h2>
        <form method="post">
            <label>Offers Description:</label>
            <textarea name="offers_description" readonly><?php echo htmlspecialchars($offersContent['description'] ?? ''); ?></textarea>
        </form>
    </div>

    <div class="section" data-section="manage-gym-offers">
    <h2>Manage Existing Gym Offers</h2>
    <div class="existing-items-container">
        <?php foreach ($gymOffers as $offer): ?>
            <div class="existing-item">
                <h3><?php echo htmlspecialchars($offer['name'] ?? $offer['title']); ?></h3>
                <img src="../<?php echo 'cms_img/offers/' . basename($offer['image_path']); ?>" alt="<?php echo htmlspecialchars($offer['title']); ?>">

            </div>
        <?php endforeach; ?>
    </div>
</div>
    <!-- About Us Section -->
    <div class="section" data-section="about-us">
        <h2>About Us Section</h2>
        <form method="post">
            <label>About Us Description:</label>
            <textarea name="about_description" readonly><?php echo htmlspecialchars($aboutUsContent['description'] ?? ''); ?> </textarea>

        </form>
    </div>

    <!-- Contact Information -->
    <div class="section" data-section="contact">
        <h2>Contact Information</h2>
        <form method="post">
            <label>Location:</label>
            <input type="text" name="location" value="<?php echo htmlspecialchars($contactContent['location'] ?? ''); ?>" readonly>
            
            <label>Phone Number:</label>
            <input type="tel" name="phone" value="<?php echo htmlspecialchars($contactContent['phone'] ?? ''); ?>" readonly>
            
            <label>Email:</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($contactContent['email'] ?? ''); ?>" readonly>
            

        </form>
    </div>


    <div class="section" data-section="products">
        <h2>Manage Existing Products</h2>
        <div class="existing-items-container">
            <?php foreach ($products as $product): ?>
                <div class="existing-item">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <img src="../<?php echo 'cms_img/products/' . basename($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">

        </div>
    <?php endforeach; ?>
    </div>
</div>

    <div class="section" data-section="staff">
        <h2>Manage Staff Members</h2>
        <div class="existing-items-container">
            <?php foreach ($staffMembers as $staff): ?>
                <div class="existing-item">
                    <h3><?php echo htmlspecialchars($staff['name']); ?> (<?php echo htmlspecialchars($staff['status']); ?>)</h3>
                    <img src="../<?php echo 'cms_img/staff/' . basename($staff['image_path']); ?>" alt="<?php echo htmlspecialchars($staff['name']); ?>">

        </div>
    <?php endforeach; ?>
</div>

    <div class="section" data-section="gallery">
        <h2>Manage Gallery Images</h2>
        <div class="existing-items-container">
            <?php foreach ($galleryImages as $image): ?>
                <div class="existing-item">
                    <img src="../<?php echo 'cms_img/gallery/' . basename($image['image_path']); ?>" alt="<?php echo htmlspecialchars($image['alt_text'] ?? 'Gallery Image'); ?>">
            
        </div>
    <?php endforeach; ?>
    </div>
</div>