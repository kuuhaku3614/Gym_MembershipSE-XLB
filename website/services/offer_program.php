<?php
session_start();
require_once 'services.class.php';
require_once 'cart.class.php';
require_once '../../functions/sanitize.php';

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
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

<style>
@font-face {
  font-family: myFont;
  src: url(../../AC.ttf);
}

body {
  font-family: myFont;
}

.main-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 90vh;
}
.bg-custom-red {
  background-color: #c92f2f !important;
}
.card-header {
  background: linear-gradient(45deg, #cc0000, #ff3333);
}
.card-body p-4 {
  height: 60vh;
}
.scrollable-section {
  max-height: calc(100vh - 350px);
  padding: 10px;
  overflow-y: auto;
  overflow-x: hidden;
}
.flex-fill {
  height: 48px !important;
}

.return-btn {
  background-color: #6e6e6e;
  color: white;
}
.return-btn:hover {
  background-color: #616161;
  color: white;
}
.d-grid {
  height: 48px;
}
.add-cart {
  background-color: #4361ee;
  color: white;
}
.add-cart:hover {
  background-color: rgb(39, 75, 238);
  color: white;
}

</style>

<div class="avail-program-page">
    <div class="container-fluid p-0">
        <div class="bg-custom-red text-white p-3 d-flex align-items-center">
            <button class="btn text-white me-3" style="color: white!important;" onclick="window.location.href='../services.php'">
                <i class="bi bi-arrow-left fs-4"></i>
            </button>
            <h1 class="mb-0 fs-4 fw-bold">SERVICES</h1>
        </div>

        <div class="container-fluid">
            <div class="row flex-grow-1 overflow-auto">
                <div class="col-12 col-lg-8 mx-auto py-3 main-container">
                    <div class="card main-content" style="width: 100%;">
                        <div class="card-header py-3">
                            <h2 class="h4 fw-bold mb-0 text-white text-center"><?= $program_type ?> Program</h2>
                        </div>
                        <div class="card-body p-3">
                            <h3 class="h5 fw-bold text-center mb-4"><?= $program_name ?></h3>

                            <section class="scrollable-section">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="border rounded p-3">
                                            <p class="mb-0">Validity: <?= $duration . ' ' . $duration_type ?></p>
                                        </div>
                                    </div>

                                    <form method="POST" onsubmit="return confirmAndValidate()">
                                        <input type="hidden" name="program_id" value="<?= htmlspecialchars($program_id) ?>">
                                        <input type="hidden" name="program_name" value="<?= htmlspecialchars($program_name) ?>">
                                        
                                        <div class="col-12">
                                            <div class="border rounded p-3 mb-3">
                                                <div class="form-group">
                                                    <label for="price" class="form-label">Program Price:</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">₱</span>
                                                        <input type="number" 
                                                               class="form-control form-control-lg" 
                                                               id="price" 
                                                               name="price" 
                                                               step="0.01" 
                                                               min="0" 
                                                               required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="border rounded p-3">
                                                <div class="form-group">
                                                    <label for="description" class="form-label">Program Description:</label>
                                                    <textarea class="form-control form-control-lg" 
                                                              id="description" 
                                                              name="description" 
                                                              rows="3" 
                                                              required></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    
                                        <div class="d-grid gap-3 d-md-flex justify-content-md-between mt-4">
                                            <a href="../services.php" class="btn return-btn btn-lg flex-fill">Return</a>
                                            <button type="submit" class="btn btn-lg flex-fill add-cart">Offer Program</button>
                                        </div>
                                    </form>
                                </div>
                            </section>
                        </div>
                    </div>
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