<?php
// Handle different content management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirectUrl = 'website_settings.php';
    $success = false;

    try {
        // Handle welcome section update
        if (isset($_POST['update_welcome'])) {
            $success = updateWebsiteContent('welcome', [
                'company_name' => $_POST['company_name'],
                'welcome_description' => $_POST['welcome_description']
            ]);
        }

        // Handle offers section update
        if (isset($_POST['update_offers'])) {
            $success = updateWebsiteContent('offers', [
                'offers_description' => $_POST['offers_description']
            ]);
        }

        // Handle about us update
        if (isset($_POST['update_about'])) {
            $success = updateWebsiteContent('about_us', [
                'about_description' => $_POST['about_description']
            ]);
        }

        // Handle contact information update
        if (isset($_POST['update_contact'])) {
            $success = updateWebsiteContent('contact', [
                'location' => $_POST['location'],
                'phone' => $_POST['phone'],
                'email' => $_POST['email']
            ]);
        }

        // Gym Offer Upload
        if (isset($_POST['add_offer']) && isset($_FILES['offer_image'])) {
            $imagePath = uploadFile($_FILES['offer_image'], 'offers');
            $success = addItem('gym_offer', [
                'title' => $_POST['offer_title'],
                'description' => $_POST['offer_description']
            ], $imagePath);
        }

        // Product Upload
        if (isset($_POST['add_product']) && isset($_FILES['product_image'])) {
            $imagePath = uploadFile($_FILES['product_image'], 'products');
            $success = addItem('product', [
                'name' => $_POST['product_name'],
                'description' => $_POST['product_description']
            ], $imagePath);
        }

        // Staff Member Upload
        if (isset($_POST['add_staff']) && isset($_FILES['staff_image'])) {
            $imagePath = uploadFile($_FILES['staff_image'], 'staff');
            $success = addItem('staff', [
                'name' => $_POST['staff_name'],
                'status' => $_POST['staff_status']
            ], $imagePath);
        }

        // Gallery Image Upload
        if (isset($_POST['add_gallery_image']) && isset($_FILES['gallery_image'])) {
            $imagePath = uploadFile($_FILES['gallery_image'], 'gallery');
            $success = addItem('gallery', [
                'alt_text' => $_POST['gallery_image_alt'] ?? 'Gallery Image'
            ], $imagePath);
        }

        // Delete actions
        if (isset($_POST['delete_offer'])) {
            $success = deleteItem('gym_offer', $_POST['offer_id']);
        }

        if (isset($_POST['delete_product'])) {
            $success = deleteItem('product', $_POST['product_id']);
        }

        if (isset($_POST['delete_staff'])) {
            $success = deleteItem('staff', $_POST['staff_id']);
        }

        if (isset($_POST['delete_gallery_image'])) {
            $success = deleteItem('gallery', $_POST['gallery_image_id']);
        }

        // Redirect with success status
        if ($success) {
            $redirectUrl .= '?status=success';
            if (isset($_POST['scroll_to'])) {
                $redirectUrl .= '&scrollTo=' . urlencode($_POST['scroll_to']);
            }
        } else {
            $redirectUrl .= '?status=error';
        }

        header("Location: " . $redirectUrl);
        exit();

    } catch (Exception $e) {
        error_log("Website Settings Error: " . $e->getMessage());
        header("Location: website_settings.php?status=error");
        exit();
    }
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