<?php
// Updated editor.php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/functions/config.php';
require_once 'ContentManager.php';

$contentManager = new ContentManager();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle text content updates
    if (isset($_POST['update_section'])) {
        $section = $_POST['section'];
        foreach ($_POST as $key => $value) {
            if ($key !== 'update_section' && $key !== 'section') {
                $contentManager->updateContent($section, $key, $value);
            }
        }
    }

    // Handle image uploads
    if (isset($_FILES['image'])) {
        $section = $_POST['section'];
        $image_key = $_POST['image_key'];
        
        // Get current number of images for section
        $count = $contentManager->getImageCount($section);

        // Check image limits
        $limits = [
            'offers' => 3,
            'products' => 8,
            'about_us' => 4,
            'contact' => 1
        ];

        if ($count < $limits[$section] || !empty($_POST['replace'])) {
            $target_dir = "uploads/" . $section . "/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $file_name = uniqid() . '_' . basename($_FILES["image"]["name"]);
            $target_file = $target_dir . $file_name;
            
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $contentManager->updateImage($section, $image_key, $target_file);
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS Editor</title>
    <style>
        /* Previous CSS remains the same */
    </style>
</head>
<body>
    <h1>Website Content Management System</h1>

    <?php
    // Welcome Section
    $welcome = $contentManager->getSectionContent('welcome');
    ?>
    
    <div class="editor-section">
        <h2>Welcome Section</h2>
        <form method="POST">
            <input type="hidden" name="update_section" value="1">
            <input type="hidden" name="section" value="welcome">
            
            <div class="form-group">
                <label>Company Name:</label>
                <input type="text" name="company_name" value="<?php echo htmlspecialchars($welcome['company_name']); ?>">
            </div>
            
            <div class="form-group">
                <label>Welcome Message:</label>
                <textarea name="message"><?php echo htmlspecialchars($welcome['message']); ?></textarea>
            </div>
            
            <button type="submit">Update Welcome Section</button>
        </form>
    </div>

    <?php
    // Offers Section
    $offers = $contentManager->getSectionContent('offers');
    ?>
    <div class="editor-section">
        <!-- Rest of the HTML remains the same, just update the PHP parts to use $contentManager -->
    </div>
</body>
</html>