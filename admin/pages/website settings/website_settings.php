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
<div class="container-fluid px-4 py-4">
<div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Website Settings</h2>
        <div class="mt-2">
        <a href="/Gym_MembershipSE-XLB/admin/content_management" class="btn btn-primary mb-3">Update website</a>
        </div>
        </div>

<!-- Welcome Section Update -->
<div class="card mb-3" data-section="welcome">
    <div class="card-header">
        <h2>Welcome Section</h2>
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
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-3">
            <?php foreach ($gymOffers as $offer): ?>
                <div class="card" style="width: 18rem;">
                    <img src="../<?php echo 'cms_img/offers/' . basename($offer['image_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($offer['title']); ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($offer['name'] ?? $offer['title']); ?></h5>
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
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-3">
            <?php foreach ($products as $product): ?>
                <div class="card" style="width: 18rem;">
                    <img src="../<?php echo 'cms_img/products/' . basename($product['image_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
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
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-3">
            <?php foreach ($staffMembers as $staff): ?>
                <div class="card" style="width: 18rem;">
                    <img src="../<?php echo 'cms_img/staff/' . basename($staff['image_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($staff['name']); ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($staff['name']); ?> (<?php echo htmlspecialchars($staff['status']); ?>)</h5>
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
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-3">
            <?php foreach ($galleryImages as $image): ?>
                <div class="card" style="width: 18rem;">
                    <img src="../<?php echo 'cms_img/gallery/' . basename($image['image_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($image['alt_text'] ?? 'Gallery Image'); ?>">
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
</div>