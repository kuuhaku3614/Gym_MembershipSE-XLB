<?php
// Include your database connection file
require_once '../../../../config.php';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the new registration fee from the POST data
    $newRegistrationFee = isset($_POST['newRegistrationFee']) ? $_POST['newRegistrationFee'] : '';

    // Validate the new registration fee
    if (empty($newRegistrationFee) || !is_numeric($newRegistrationFee) || $newRegistrationFee <= 0) {
        echo "Invalid registration fee.";
        exit;
    }

    try {
        // Prepare the SQL query to update the registration fee
        $query = "UPDATE registration SET membership_fee = :newFee WHERE id = 1"; // Adjust the table and condition as necessary
        $stmt = $pdo->prepare($query);

        // Bind the parameter and execute the query
        $stmt->bindParam(':newFee', $newRegistrationFee, PDO::PARAM_STR);
        $stmt->execute();

        // Check if the update was successful
        if ($stmt->rowCount() > 0) {
            echo "success";
        } else {
            echo "No changes were made.";
        }
    } catch (PDOException $e) {
        // Handle database errors
        echo "Database error: " . $e->getMessage();
    }
} else {
    echo "Invalid request method.";
}
?>
