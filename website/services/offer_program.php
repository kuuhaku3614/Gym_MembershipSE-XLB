<?php
session_start();
require_once 'services.class.php';
require_once 'cart.class.php';

if (!isset($_SESSION['user_id'])) {
    header('location: ../../login/login.php');
    exit;
}

// Initialize variables
$program_id = $program_name = $program_type = $duration = $duration_type = $description = '';
$price = '';

$Services = new Services_Class();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['id'])) {
        $program_id = $_GET['id'];
        $record = $Services->fetchProgram($program_id);
        if (!empty($record)) {
            $program_name = $record['program_name'];
            $program_type = $record['program_type'];
            $duration = $record['duration'];
            $duration_type = $record['duration_type'];
            $description = $record['description'];
        } else {
            echo 'No program found';
            exit;
        }
    } else {
        echo 'No program id found';
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $program_id = clean_input($_POST['program_id']);
    $program_name = clean_input($_POST['program_name']);
    $price = clean_input($_POST['price']);
    $description = clean_input($_POST['description']);
    $coach_id = $_SESSION['user_id'];

    if(empty($price) || $price <= 0) {
        $priceErr = 'Please enter a valid price';
    }

    if(empty($description)) {
        $descriptionErr = 'Description is required';
    }

    if(empty($priceErr) && empty($descriptionErr)) {
        try {
            // Database connection
            $conn = new mysqli('localhost', 'root', '', 'gym_managementdb');
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }

            // Check if coach already offers this program
            $check_sql = "SELECT * FROM coach_program_types WHERE coach_id = ? AND program_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ii", $coach_id, $program_id);
            $check_stmt->execute();
            
            if ($check_stmt->get_result()->num_rows > 0) {
                echo "<script>
                    alert('You have already offered this program. Please choose a different program.');
                    window.location.href = '../services.php';
                </script>";
                exit;
            }

            // Insert new program offer
            $sql = "INSERT INTO coach_program_types (coach_id, program_id, price, description) 
                   VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iids", $coach_id, $program_id, $price, $description);
            
            if($stmt->execute()) {
                $stmt->close();
                $conn->close();
                echo "<script>
                    if(confirm('Program offered successfully! Do you want to view your profile?')) {
                        window.location.href = '../profile.php';
                    } else {
                        window.location.href = '../services.php';
                    }
                </script>";
                exit;
            } else {
                throw new Exception("Error saving program offer");
            }
        } catch (Exception $e) {
            if ($e->getMessage() == "You have already offered this program. Please choose a different program.") {
                echo "<script>
                    alert('" . addslashes($e->getMessage()) . "');
                    window.location.href = '../services.php';
                </script>";
            } else {
                echo "<script>
                    alert('Error: " . addslashes($e->getMessage()) . "');
                    window.location.href = '../services.php';
                </script>";
            }
        }
    }
}

function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<style>
    .bg-custom-black {
        background-color: #000000;
    }
    .card-header, .btn-custom-black {
        background-color: #000000;
        color: white;
    }
    .card-header {
        background-color: #000000;
        border-bottom: 2px solid #000000;
        padding: 1rem;
    }
    .card-body {
        border: 2px solid #000000;
    }
    .btn-outline-black {
        color: #000000;
        border-color: #000000;
    }
    .btn-outline-black:hover {
        background-color: #000000;
        color: white;
    }
    .btn-custom-black {
        background-color: #000000;
        color: white;
    }
    .btn-custom-black:hover {
        background-color: #333333;
        color: white;
    }
</style>

<div class="avail-program-page">
    <div class="container-fluid p-0">
        <!-- Header -->
        <div class="bg-custom-black text-white p-3 d-flex align-items-center">
            <button class="btn text-white me-3" onclick="window.location.href='../services.php'">
                <i class="bi bi-arrow-left fs-4"></i>
            </button>
            <h1 class="mb-0 fs-4 fw-bold">OFFER PROGRAM</h1>
        </div>

        <div class="d-flex justify-content-center align-items-center" style="min-height: 80vh;">
            <div class="card shadow" style="width: 90%; max-width: 800px; min-height: 400px;">
                <div class="card-header text-center">
                    <h2 class="fs-4 fw-bold mb-0"><?= $program_type ?> Program</h2>
                </div>
                <div class="card-body text-center d-flex flex-column justify-content-between" style="padding: 2rem;">
                    <h3 class="fs-5 fw-bold mb-4"><?= $program_name ?></h3>
                    <div class="mb-3 p-2 border rounded">
                        <p class="mb-0">Validity: <?= $duration . ' ' . $duration_type ?></p>
                    </div>
                    
                    <form method="POST" onsubmit="return confirmAndValidate()">
                        <input type="hidden" name="program_id" value="<?= htmlspecialchars($program_id) ?>">
                        <input type="hidden" name="program_name" value="<?= htmlspecialchars($program_name) ?>">
                        
                        <!-- Price Input -->
                        <div class="mb-3 p-2 border rounded">
                            <label for="price" class="form-label">Program Price:</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" 
                                       class="form-control" 
                                       id="price" 
                                       name="price" 
                                       step="0.01" 
                                       min="0" 
                                       required>
                            </div>
                        </div>

                        <!-- Description Input -->
                        <div class="mb-3 p-2 border rounded">
                            <label for="description" class="form-label">Program Description:</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="3" 
                                      required></textarea>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="../services.php" class="btn btn-outline-black btn-lg" style="width: 48%;">Return</a>
                            <button type="submit" class="btn btn-custom-black btn-lg" style="width: 48%;">Offer Program</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmAndValidate() {
    // First validate the form
    const price = document.getElementById('price').value;
    const description = document.getElementById('description').value;
    
    if (!price || price <= 0) {
        alert('Please enter a valid price.');
        return false;
    }

    if (!description.trim()) {
        alert('Please enter a program description.');
        return false;
    }
    
    // If validation passes, show confirmation dialog
    return confirm('Are you sure you want to offer this program?');
}
</script>