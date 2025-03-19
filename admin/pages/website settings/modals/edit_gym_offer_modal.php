<?php
// Include database connection
require_once '../config.php';

// Check if form was submitted for updating
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_gym_offer'])) {
    // Initialize response array
    $response = array('success' => false, 'message' => '');

    // Validate and sanitize input
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';

    // Validate required fields
    if ($id <= 0 || empty($title) || empty($description)) {
        $response['message'] = 'All fields are required.';
        echo json_encode($response);
        exit;
    }

    try {
        // Fetch existing image path from database
        $query = "SELECT image_path FROM gym_offers WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $id]);
        $offer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$offer) {
            $response['message'] = 'Offer not found.';
            echo json_encode($response);
            exit;
        }

        $db_path = $offer['image_path']; // Existing image path

        // Check if a new image was uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Allowed file types
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['image']['type'], $allowed_types)) {
                $response['message'] = 'Only JPG, PNG, and GIF images are allowed.';
                echo json_encode($response);
                exit;
            }

            // Define upload directory
            $uploadDir = dirname(__DIR__, 4) . '/cms_img/offers/';
            $db_path_prefix = 'cms_img/offers/';

            // Ensure directory exists
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Generate unique filename
            $filename = uniqid() . '_' . basename($_FILES['image']['name']);
            $upload_path = $uploadDir . $filename; // Full server path
            $db_path = $db_path_prefix . $filename; // Database path

            // Move uploaded file
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $response['message'] = 'Failed to upload image.';
                echo json_encode($response);
                exit;
            }

            // Delete old image if it exists and is not the default
            $old_image_path = dirname(__DIR__, 3) . '/' . $offer['image_path'];
            if (file_exists($old_image_path) && $offer['image_path'] !== 'cms_img/offers/default.jpg') {
                unlink($old_image_path);
            }
        }

        // Update database
        $query = "UPDATE gym_offers SET 
                  title = :title, 
                  description = :description, 
                  image_path = :image_path, 
                  updated_at = NOW() 
                  WHERE id = :id";

        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'id' => $id,
            'title' => $title,
            'description' => $description,
            'image_path' => $db_path
        ]);

        $response['success'] = true;
        $response['message'] = 'Gym offer updated successfully.';
        echo json_encode($response);
        exit;
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        echo json_encode($response);
        exit;
    }
}

// Check if ID is provided for loading the form
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $offerId = $_GET['id'];

    // Fetch offer details
    $query = "SELECT * FROM gym_offers WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $offerId]);
    $offer = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if offer exists
    if (!$offer) {
        echo json_encode(['success' => false, 'message' => 'Offer not found.']);
        exit;
    }
}
?>

<!-- Edit Gym Offer Modal -->
<div class="modal fade" id="editGymOfferModal" tabindex="-1" aria-labelledby="editGymOfferModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editGymOfferModalLabel">Edit Gym Offer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editGymOfferForm" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $offer['id']; ?>">
                    <div class="mb-3">
                        <label for="edit_offer_title" class="form-label">Offer Title:</label>
                        <input type="text" class="form-control" id="edit_offer_title" name="title" value="<?php echo htmlspecialchars($offer['title']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_offer_description" class="form-label">Offer Description:</label>
                        <textarea class="form-control" id="edit_offer_description" name="description" rows="5" required><?php echo htmlspecialchars($offer['description']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Image:</label>
                        <div class="mb-2">
                            <img src="../<?php echo htmlspecialchars($offer['image_path']); ?>" alt="<?php echo htmlspecialchars($offer['title']); ?>" class="img-thumbnail" style="max-height: 200px;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_offer_image" class="form-label">New Image (leave blank to keep current):</label>
                        <input type="file" class="form-control" id="edit_offer_image" name="image" accept="image/*">
                        <small class="form-text text-muted">Recommended size: 800x600 pixels</small>
                    </div>
                    <input type="hidden" name="update_gym_offer" value="1">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle form submission
    $('#editGymOfferForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        
        $.ajax({
            type: "POST",
            url: "../admin/pages/website settings/modals/edit_gym_offer_modal.php",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    alert("Gym offer updated successfully!");
                    $('#editGymOfferModal').modal('hide');
                    location.reload();
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.log("Error details:", xhr, status, error);
                alert("Error updating gym offer.");
            }
        });
    });
});
</script>
