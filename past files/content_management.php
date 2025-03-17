<?php
// Start session for message handling
session_start();

// Include database connection
require_once 'config.php';

// Define upload directory
$uploadDir = dirname(__DIR__, 3) . '/cms_img';

// Ensure upload directory exists
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        throw new Exception("Failed to create upload directory");
    }
}

// Function to handle file uploads
function uploadFile($file, $subDirectory, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'svg']) {
    global $uploadDir;

    // Check if file was uploaded
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("No file uploaded or upload error occurred.");
    }

    // Create subdirectory if it doesn't exist
    $fullUploadPath = $uploadDir . '/' . $subDirectory;
    if (!file_exists($fullUploadPath)) {
        if (!mkdir($fullUploadPath, 0755, true)) {
            throw new Exception("Failed to create upload subdirectory");
        }
    }

    $fileName = basename($file['name']);
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $targetFilePath = $fullUploadPath . '/' . uniqid() . '_' . $fileName;

    // Validate file type using mime type for more robust detection
    $mimeType = mime_content_type($file['tmp_name']);
    $validMimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml'
    ];

    if (!in_array($fileType, $allowedTypes) || !array_key_exists($fileType, $validMimeTypes) || $mimeType !== $validMimeTypes[$fileType]) {
        throw new Exception("Invalid file type. Allowed types: " . implode(', ', $allowedTypes));
    }

    // Validate file size (e.g., max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception("File is too large. Maximum size is 5MB.");
    }

    // Additional check to ensure it's a valid image
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new Exception("Invalid image file.");
    }

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        // Return relative path from project root
        return 'cms_img/' . $subDirectory . '/' . basename($targetFilePath);
    }

    throw new Exception("File upload failed due to an unknown error.");
}

// Function to delete an image file
function deleteImageFile($imagePath) {
    global $uploadDir;
    $fullPath = $uploadDir . '/' . $imagePath;
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }
}

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

// Handle different content management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Reset any previous messages
        unset($_SESSION['error_message']);
        unset($_SESSION['success_message']);

        // Handle welcome section update
        if (isset($_POST['update_welcome'])) {
            $stmt = $pdo->prepare("UPDATE website_content SET company_name = :name, description = :desc WHERE section = 'welcome'");
            $stmt->execute([
                ':name' => $_POST['company_name'],
                ':desc' => $_POST['welcome_description']
            ]);
        }

        // Handle offers update
        if (isset($_POST['update_offers'])) {
            $stmt = $pdo->prepare("UPDATE website_content SET description = :desc WHERE section = 'offers'");
            $stmt->execute([':desc' => $_POST['offers_description']]);
        }

        // Handle about us update
        if (isset($_POST['update_about'])) {
            $stmt = $pdo->prepare("UPDATE website_content SET description = :desc WHERE section = 'about_us'");
            $stmt->execute([':desc' => $_POST['about_description']]);
        }

        if (isset($_POST['update_contact'])) {
            $stmt = $pdo->prepare("UPDATE website_content SET 
                location = :location_name,
                phone = :phone, 
                email = :email,
                latitude = :lat,
                longitude = :lon 
                WHERE section = 'contact'");
            $stmt->execute([
                ':location_name' => $_POST['location_name'] ?? null,
                ':phone' => $_POST['phone'],
                ':email' => $_POST['email'],
                ':lat' => $_POST['latitude'] ?? null,
                ':lon' => $_POST['longitude'] ?? null
            ]);
         }

        // Gym Offer Upload
        if (isset($_POST['add_offer'])) {
            if (isset($_FILES['offer_image'])) {
                $imagePath = uploadFile($_FILES['offer_image'], 'offers');
                
                $stmt = $pdo->prepare("INSERT INTO gym_offers (title, description, image_path) VALUES (:title, :desc, :img)");
                $stmt->execute([
                    ':title' => $_POST['offer_title'],
                    ':desc' => $_POST['offer_description'],
                    ':img' => $imagePath,
                ]);
            }
        }

        // Product Upload
        if (isset($_POST['add_product'])) {
            if (isset($_FILES['product_image'])) {
                $imagePath = uploadFile($_FILES['product_image'], 'products');
                
                $stmt = $pdo->prepare("INSERT INTO products (name, description, image_path) VALUES (:name, :desc, :img)");
                $stmt->execute([
                    ':name' => $_POST['product_name'],
                    ':desc' => $_POST['product_description'],
                    ':img' => $imagePath
                ]);
            }
        }

        // Staff Member Upload
        if (isset($_POST['add_staff'])) {
            if (isset($_FILES['staff_image'])) {
                $imagePath = uploadFile($_FILES['staff_image'], 'staff');
                
                $stmt = $pdo->prepare("INSERT INTO staff (name, status, image_path) VALUES (:name, :status, :img)");
                $stmt->execute([
                    ':name' => $_POST['staff_name'],
                    ':status' => $_POST['staff_status'],
                    ':img' => $imagePath
                ]);
            }
        }

        // Gallery Image Upload
        if (isset($_POST['add_gallery_image'])) {
            if (isset($_FILES['gallery_image'])) {
                $imagePath = uploadFile($_FILES['gallery_image'], 'gallery');
                
                $stmt = $pdo->prepare("INSERT INTO gallery_images (image_path, alt_text) VALUES (:img, :alt)");
                $stmt->execute([
                    ':img' => $imagePath,
                    ':alt' => $_POST['gallery_image_alt'] ?? 'Gallery Image'
                ]);
            }
        }

        // Delete Gym Offer
        if (isset($_POST['delete_offer'])) {
            $offerId = $_POST['offer_id'];
            
            $stmt = $pdo->prepare("SELECT image_path FROM gym_offers WHERE id = :id");
            $stmt->execute([':id' => $offerId]);
            $offer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($offer) {
                deleteImageFile($offer['image_path']);
                
                $stmt = $pdo->prepare("DELETE FROM gym_offers WHERE id = :id");
                $stmt->execute([':id' => $offerId]);
            }
        }

        // Delete Product
        if (isset($_POST['delete_product'])) {
            $productId = $_POST['product_id'];
            
            $stmt = $pdo->prepare("SELECT image_path FROM products WHERE id = :id");
            $stmt->execute([':id' => $productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                deleteImageFile($product['image_path']);
                
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
                $stmt->execute([':id' => $productId]);
            }
        }

        // Delete Staff Member
        if (isset($_POST['delete_staff'])) {
            $staffId = $_POST['staff_id'];
            
            $stmt = $pdo->prepare("SELECT image_path FROM staff WHERE id = :id");
            $stmt->execute([':id' => $staffId]);
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($staff) {
                deleteImageFile($staff['image_path']);
                
                $stmt = $pdo->prepare("DELETE FROM staff WHERE id = :id");
                $stmt->execute([':id' => $staffId]);
            }
        }

        // Delete Gallery Image
        if (isset($_POST['delete_gallery_image'])) {
            $imageId = $_POST['gallery_image_id'];
            
            $stmt = $pdo->prepare("SELECT image_path FROM gallery_images WHERE id = :id");
            $stmt->execute([':id' => $imageId]);
            $galleryImage = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($galleryImage) {
                deleteImageFile($galleryImage['image_path']);
                
                $stmt = $pdo->prepare("DELETE FROM gallery_images WHERE id = :id");
                $stmt->execute([':id' => $imageId]);
            }
        }

        // Set success message
        $_SESSION['success_message'] = "Content updated successfully!";
        
        // If scroll_to is set, preserve it
        if (isset($_POST['scroll_to'])) {
            $_SESSION['scroll_to'] = $_POST['scroll_to'];
        }

    } catch (Exception $e) {
        // Store error message in session
        $_SESSION['error_message'] = $e->getMessage();
        
        // Store scroll position if available
        if (isset($_POST['scroll_to'])) {
            $_SESSION['scroll_to'] = $_POST['scroll_to'];
        }
    }

    // Redirect to prevent form resubmission
    header("Location: /Gym_MembershipSE-XLB/admin/content_management");
    exit();
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
// Fetch logo content
$logoContent = getDynamicContent('logo');
$colorContent = getDynamicContent('colors');

// Handle logo upload
if (isset($_POST['update_logo'])) {
    try {
        if (isset($_FILES['logo_image']) && $_FILES['logo_image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = uploadFile($_FILES['logo_image'], 'logo', ['jpg', 'jpeg', 'png', 'svg']);
            
            // Check if logo entry exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM website_content WHERE section = 'logo'");
            $stmt->execute();
            $logoExists = $stmt->fetchColumn() > 0;
            
            if ($logoExists) {
                // Update existing logo
                $stmt = $pdo->prepare("UPDATE website_content SET logo_path = :path WHERE section = 'logo'");
                $stmt->execute([':path' => $imagePath]);
            } else {
                // Insert new logo
                $stmt = $pdo->prepare("INSERT INTO website_content (section, logo_path) VALUES ('logo', :path)");
                $stmt->execute([':path' => $imagePath]);
            }
            
            $_SESSION['success_message'] = "Logo updated successfully!";
        } else {
            if ($_FILES['logo_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                throw new Exception("Error uploading logo: " . $_FILES['logo_image']['error']);
            }
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
    
    // Store scroll position
    $_SESSION['scroll_to'] = 'logo';
    
    // Redirect to prevent form resubmission
    header("Location: /Gym_MembershipSE-XLB/admin/content_management");
    exit();
}

// Handle color palette update
if (isset($_POST['update_colors'])) {
    try {
        $primaryColor = $_POST['primary_color'];
        $secondaryColor = $_POST['secondary_color'];
        $accentColor = $_POST['accent_color'];
        $textColor = $_POST['text_color'];
        
        // Validate color values (optional)
        $hexPattern = '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/';
        if (!preg_match($hexPattern, $primaryColor) || 
            !preg_match($hexPattern, $secondaryColor) || 
            !preg_match($hexPattern, $accentColor) || 
            !preg_match($hexPattern, $textColor)) {
            throw new Exception("Invalid color format. Please use hexadecimal color codes (e.g., #FF5500).");
        }
        
        // Check if colors entry exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM website_content WHERE section = 'colors'");
        $stmt->execute();
        $colorsExist = $stmt->fetchColumn() > 0;
        
        if ($colorsExist) {
            // Update existing colors
            $stmt = $pdo->prepare("UPDATE website_content SET 
                primary_color = :primary,
                secondary_color = :secondary,
                accent_color = :accent,
                text_color = :text
                WHERE section = 'colors'");
        } else {
            // Insert new colors
            $stmt = $pdo->prepare("INSERT INTO website_content 
                (section, primary_color, secondary_color, accent_color, text_color) 
                VALUES ('colors', :primary, :secondary, :accent, :text)");
        }
        
        $stmt->execute([
            ':primary' => $primaryColor,
            ':secondary' => $secondaryColor,
            ':accent' => $accentColor,
            ':text' => $textColor
        ]);
        
        $_SESSION['success_message'] = "Color palette updated successfully!";
        
        // Generate CSS file with the new colors
        generateCustomCss($primaryColor, $secondaryColor, $accentColor, $textColor);
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
    
    // Store scroll position
    $_SESSION['scroll_to'] = 'colors';
    
    // Redirect to prevent form resubmission
    header("Location: /Gym_MembershipSE-XLB/admin/content_management");
    exit();
}

// Function to generate custom CSS file
function generateCustomCss($primary, $secondary, $accent, $text) {
    $cssContent = <<<CSS
/* Auto-generated custom theme CSS */
:root {
    --primary-color: {$primary};
    --secondary-color: {$secondary};
    --accent-color: {$accent};
    --text-color: {$text};
}

/* Primary color elements */
.btn-primary, 
.primary-bg,
.nav-link.active,
.section h2::after,
input[type="submit"] {
    background-color: var(--primary-color);
}

.primary-border {
    border-color: var(--primary-color);
}

.primary-text {
    color: var(--primary-color);
}

/* Secondary color elements */
.secondary-bg,
footer,
.nav-item:hover {
    background-color: var(--secondary-color);
}

.secondary-border {
    border-color: var(--secondary-color);
}

.secondary-text {
    color: var(--secondary-color);
}

/* Accent color elements */
.accent-bg,
.btn-accent,
.badge {
    background-color: var(--accent-color);
}

.accent-border {
    border-color: var(--accent-color);
}

.accent-text {
    color: var(--accent-color);
}

/* Text color elements */
body, h1, h2, h3, h4, h5, h6, p, .text-default {
    color: var(--text-color);
}
CSS;

    $cssFilePath = dirname(__DIR__, 3) . '/css/custom-theme.css';
    file_put_contents($cssFilePath, $cssContent);
}
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
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
        cursor: pointer; /* Indicates it can be clicked to dismiss */
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
        height: 450px; /* Increased height */
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
    background-color: green;
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
/* Logo and Color Palette Styles */
.current-logo {
    margin: 15px 0;
    padding: 15px;
    background-color: #f9f9f9;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    text-align: center;
}

.hint {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.color-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.color-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.color-item input[type="color"] {
    width: 100%;
    height: 40px;
    padding: 2px;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
}

.color-hex {
    font-family: monospace;
    text-align: center;
}

.color-preview {
    margin-top: 30px;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    background-color: #f9f9f9;
}

.preview-container {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 15px;
}

.preview-header {
    height: 60px;
    border-radius: 6px;
    background-color: var(--primary-color, #4CAF50);
}

.preview-body {
    height: 100px;
    border-radius: 6px;
    background-color: var(--secondary-color, #333333);
}

.preview-button {
    width: 120px;
    height: 40px;
    border-radius: 6px;
    background-color: var(--accent-color, #FF9800);
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
                <img src="../<?php echo 'cms_img/offers/' . basename($image['image_path']); ?>" alt="<?php echo htmlspecialchars($offer['title']); ?>">
                
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

    <div class="section" data-section="contact">
        <h2>Contact Information</h2>
        <form method="post" id="contactForm">
            <input type="hidden" name="latitude" id="locationLatitude">
            <input type="hidden" name="longitude" id="locationLongitude">
            <input type="hidden" name="location_name" id="locationName">

            <label>Phone Number:</label>
            <input type="tel" name="phone" required>
            
            <label>Email:</label>
            <input type="email" name="email" required>

            <label>Selected Location:</label>
            <input type="text" id="displayLocation" readonly style="background-color: #f5f5f5; cursor: not-allowed;">

            <div id="mapModalTrigger" class="btn btn-primary">Select Location on Map</div>
            <br>
            <input type="submit" name="update_contact" value="Update Contact Information">
        </form>
    </div>
</div>

<!-- Modal for Map -->
<div id="locationModal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5); overflow:auto;">
    <div style="background:white; margin:5% auto; padding:20px; width:90%; max-width:800px; border-radius:8px; box-shadow:0 4px 6px rgba(0,0,0,0.1); position:relative;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <h2 style="margin:0;">Select Location</h2>
            <button id="closeLocationModal" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
        </div>
        
        <div style="display:flex; gap:15px; margin-bottom:15px;">
            <div style="flex:1;">
                <label style="display:block; margin-bottom:5px;">Selected Location</label>
                <input type="text" id="selectedLocationDisplay" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" readonly>
            </div>
        </div>

        <div id="locationPickerContainer" style="height:450px; width:100%; border:1px solid #ddd; border-radius:4px;">
            <div id="location-picker-map" style="height:100%; width:100%;"></div>
        </div>

        <div style="display:flex; justify-content:flex-end; margin-top:15px;">
            <button id="saveLocationButton" style="background-color:#4CAF50; color:white; border:none; padding:10px 20px; border-radius:4px; cursor:pointer;">Save Location</button>
        </div>
    </div>
</div>
<?php require_once '../../includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalTrigger = document.getElementById('mapModalTrigger');
    const modal = document.getElementById('locationModal');
    const closeModalButton = document.getElementById('closeLocationModal');
    const saveLocationButton = document.getElementById('saveLocationButton');
    const selectedLocationDisplay = document.getElementById('selectedLocationDisplay');
    const latInput = document.getElementById('locationLatitude');
    const lngInput = document.getElementById('locationLongitude');
    const locationNameInput = document.getElementById('locationName');

    // Pre-fill existing contact info
    const existingLocation = "<?php echo htmlspecialchars($contactContent['location'] ?? ''); ?>";
    const existingLat = <?php echo $contactContent['latitude'] ?? 6.913126; ?>;
    const existingLng = <?php echo $contactContent['longitude'] ?? 122.072516; ?>;
    const existingPhone = "<?php echo htmlspecialchars($contactContent['phone'] ?? ''); ?>";
    const existingEmail = "<?php echo htmlspecialchars($contactContent['email'] ?? ''); ?>";

    // Pre-fill phone and email if available
    document.querySelector('input[name="phone"]').value = existingPhone;
    document.querySelector('input[name="email"]').value = existingEmail;

    // Initial coordinates
    let initialLat = existingLat;
    let initialLng = existingLng;

    let map, marker;

    // Define the updateLocationDetails function
    function updateLocationDetails(lat, lng) {
    // Update marker position
    marker.setLatLng([lat, lng]);
    
    // Update hidden inputs with coordinates
    latInput.value = lat;
    lngInput.value = lng;
    
    // Fetch location details using reverse geocoding with detailed address components
    fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&addressdetails=1&accept-language=en`)
        .then(response => response.json())
        .then(data => {
            // Create a more readable location string
            const address = data.address;
            const components = [];
            
            // Add relevant address components if they exist
            if (address.road) components.push(address.road);
            if (address.suburb) components.push(address.suburb);
            if (address.city || address.town || address.village) {
                components.push(address.city || address.town || address.village);
            }
            if (address.state) components.push(address.state);
            if (address.country) components.push(address.country);
            
            // Join components with commas
            const locationName = components.join(', ');
            
            // Update display and hidden inputs
            selectedLocationDisplay.value = locationName;
            locationNameInput.value = locationName;
        })
        .catch(error => {
            console.error('Error fetching location details:', error);
            const fallbackLocation = `Location at ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            selectedLocationDisplay.value = fallbackLocation;
            locationNameInput.value = fallbackLocation;
        });
}
function addSearchControl() {
    const searchContainer = document.createElement('div');
    searchContainer.style.cssText = 'position: absolute; top: 10px; left: 50px; z-index: 1000; width: 300px;';
    
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = 'Search location...';
    searchInput.style.cssText = 'width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);';
    
    searchContainer.appendChild(searchInput);
    document.getElementById('location-picker-map').appendChild(searchContainer);
    
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const query = this.value;
            if (query.length > 2) {
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0) {
                            const location = data[0];
                            map.setView([location.lat, location.lon], 16);
                            updateLocationDetails(location.lat, location.lon);
                        }
                    })
                    .catch(error => console.error('Error searching location:', error));
            }
        }, 500);
    });
}
function initMap() {
    map = L.map('location-picker-map').setView([initialLat, initialLng], 16);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);
    
    // Initialize with existing location details
    updateLocationDetails(initialLat, initialLng);
    
    // Add search control
    addSearchControl();
    
    // Update location details when marker is dragged
    marker.on('dragend', function(e) {
        const { lat, lng } = e.target.getLatLng();
        updateLocationDetails(lat, lng);
    });

    // Update location details when map is clicked
    map.on('click', function(e) {
        const { lat, lng } = e.latlng;
        updateLocationDetails(lat, lng);
    });
}

    // Modal event listeners
    modalTrigger.addEventListener('click', function () {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        if (!map) {
            initMap();
        }
    });

    closeModalButton.addEventListener('click', function () {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    });

    saveLocationButton.addEventListener('click', function() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    });


    function updateLocationInfo(lat, lng) {
        fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&addressdetails=1`)
            .then(response => response.json())
            .then(data => {
                // Create a detailed location string
                let locationName = data.display_name;
                
                // Update input fields
                selectedLocationDisplay.value = locationName;
                latInput.value = lat;
                lngInput.value = lng;
                locationNameInput.value = locationName;
            })
            .catch(error => {
                console.error('Error fetching location details:', error);
                const fallbackLocation = `Location at ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                
                selectedLocationDisplay.value = fallbackLocation;
                latInput.value = lat;
                lngInput.value = lng;
                locationNameInput.value = fallbackLocation;
            });
    }

    modalTrigger.addEventListener('click', function () {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        if (!map) {
            initMap();
        }
    });

    closeModalButton.addEventListener('click', function () {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    });

    saveLocationButton.addEventListener('click', function() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    });
});
document.addEventListener('DOMContentLoaded', function() {
    const displayLocation = document.getElementById('displayLocation');
    const locationNameInput = document.getElementById('locationName');

    // Update display when location is selected
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                displayLocation.value = locationNameInput.value;
            }
        });
    });

    observer.observe(locationNameInput, {
        attributes: true
    });

    // Set initial value if exists
    displayLocation.value = locationNameInput.value || '';
});
</script>
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
                    <img src="../<?php echo 'cms_img/products/' . basename($image['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    
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
                    <img src="../<?php echo 'cms_img/staff/' . basename($image['image_path']); ?>" alt="<?php echo htmlspecialchars($staff['name']); ?>">
            
            <form method="post">
                <input type="hidden" name="staff_id" value="<?php echo $staff['id']; ?>">
                <input type="submit" name="delete_staff" value="Delete Staff Member" onclick="return confirm('Are you sure you want to delete this staff member?');">
            </form>
        </div>
    <?php endforeach; ?>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Always try to scroll to the appropriate section
    function scrollToSection(sectionIdentifier) {
        const element = document.querySelector(`[data-section="${sectionIdentifier}"]`);
        if (element) {
            // Use slight delay to ensure page is fully loaded
            setTimeout(() => {
                element.scrollIntoView({ 
                    behavior: 'auto',  // Changed from 'smooth' to 'auto' for more predictable scrolling
                    block: 'center' 
                });
            }, 100);
        }
    }

    // Check for scroll position from session
    <?php
    if (isset($_SESSION['scroll_to'])) {
        echo "const scrollTo = '" . $_SESSION['scroll_to'] . "';";
        unset($_SESSION['scroll_to']); // Clear the scroll position
    } else {
        echo "const scrollTo = null;";
    }
    ?>

    // Scroll to specified section if needed
    if (scrollTo) {
        scrollToSection(scrollTo);
    }

    // Message handling
    const messageContainer = document.getElementById('message-container');
    
    <?php
    // Handle error messages
    if (isset($_SESSION['error_message'])) {
        echo "
        const errorMessage = '" . addslashes($_SESSION['error_message']) . "';
        if (errorMessage) {
            const errorAlert = document.createElement('div');
            errorAlert.className = 'error';
            errorAlert.id = 'message-alert';
            
            // Create message content div
            const messageContent = document.createElement('div');
            messageContent.className = 'message-content';
            messageContent.textContent = errorMessage;
            
            // Create dismiss button
            const dismissBtn = document.createElement('button');
            dismissBtn.className = 'dismiss-btn';
            dismissBtn.innerHTML = '&times;';
            
            errorAlert.appendChild(messageContent);
            errorAlert.appendChild(dismissBtn);
            messageContainer.appendChild(errorAlert);

            // Dismiss function
            const dismissError = () => {
                errorAlert.classList.add('hide');
                setTimeout(() => errorAlert.remove(), 500);
            };
            
            // Auto-dismiss
            const dismissTimer = setTimeout(dismissError, 5000);
            
            // Click to dismiss
            dismissBtn.addEventListener('click', () => {
                clearTimeout(dismissTimer);
                dismissError();
            });
        }";
        
        // Clear the error message
        unset($_SESSION['error_message']);
    }

    // Handle success messages
    if (isset($_SESSION['success_message'])) {
        echo "
        const successMessage = '" . addslashes($_SESSION['success_message']) . "';
        if (successMessage) {
            const successAlert = document.createElement('div');
            successAlert.className = 'success';
            successAlert.id = 'message-alert';
            
            // Create message content div
            const messageContent = document.createElement('div');
            messageContent.className = 'message-content';
            messageContent.textContent = successMessage;
            
            // Create dismiss button
            const dismissBtn = document.createElement('button');
            dismissBtn.className = 'dismiss-btn';
            dismissBtn.innerHTML = '&times;';
            
            successAlert.appendChild(messageContent);
            successAlert.appendChild(dismissBtn);
            messageContainer.appendChild(successAlert);

            // Dismiss function
            const dismissSuccess = () => {
                successAlert.classList.add('hide');
                setTimeout(() => successAlert.remove(), 500);
            };
            
            // Auto-dismiss
            const dismissTimer = setTimeout(dismissSuccess, 3000);
            
            // Click to dismiss
            dismissBtn.addEventListener('click', () => {
                clearTimeout(dismissTimer);
                dismissSuccess();
            });
        }";
        
        // Clear the success message
        unset($_SESSION['success_message']);
    }
    ?>

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
</script>