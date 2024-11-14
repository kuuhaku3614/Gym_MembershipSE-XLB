<?php
// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'gym_managementdb';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Data for the new coach (In a real application, this would come from a form submission)
$username = 'jamal';
$password = password_hash('jamal', PASSWORD_DEFAULT); // Hash the password for security
$role = 'coach';
$first_name = 'jamal';
$middle_name = 'al';
$last_name = 'badi';
$sex = 'Male';
$birthdate = '1985-06-15';
$phone_number = '09562307665';

// Insert into users table
$user_sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("sss", $username, $password, $role);

if ($user_stmt->execute()) {
    // Get the last inserted user ID
    $user_id = $conn->insert_id;

    // Insert into personal_details table
    $details_sql = "INSERT INTO personal_details (user_id, first_name, middle_name, last_name, sex, birthdate, phone_number) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $details_stmt = $conn->prepare($details_sql);
    $details_stmt->bind_param("issssss", $user_id, $first_name, $middle_name, $last_name, $sex, $birthdate, $phone_number);

    if ($details_stmt->execute()) {
        echo "Coach added successfully!";
    } else {
        echo "Error adding personal details: " . $details_stmt->error;
    }
} else {
    echo "Error adding user: " . $user_stmt->error;
}

// Close the statement and connection
$user_stmt->close();
$details_stmt->close();
$conn->close();
?>
