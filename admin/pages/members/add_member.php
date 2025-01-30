<?php
require_once("../../../config.php");
require_once("functions/add_member.class.php");

session_start();
$addMember = new AddMember();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $memberData = [
            'username' => $_POST['username'],
            'password' => $_POST['password'],
            'first_name' => $_POST['first_name'],
            'middle_name' => $_POST['middle_name'],
            'last_name' => $_POST['last_name'],
            'sex' => $_POST['sex'],
            'birthdate' => $_POST['birthdate'],
            'phone_number' => $_POST['phone_number']
        ];

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            $memberData['photo'] = $_FILES['photo'];
        }

        $result = $addMember->addNewMember($memberData);
        
        if ($result['success']) {
            $_SESSION['success_message'] = "Member added successfully!";
            header('Location: /Gym_MembershipSE-XLB/admin/members_new');
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Member - Gym Management System</title>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    <?php include '../../includes/header.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title mb-0">Add New Member</h2>
                    </div>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>

                    <div class="card-body">
                        <form action="/Gym_MembershipSE-XLB/admin/pages/members/add_member.php" method="POST" enctype="multipart/form-data">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="text-center mb-3">
                                        <img id="profilePreview" src="/Gym_MembershipSE-XLB/uploads/default.jpg" 
                                             class="rounded-circle mb-2" 
                                             alt="Profile Preview" 
                                             style="width: 150px; height: 150px; object-fit: cover;">
                                        <div class="mt-2">
                                            <label for="photo" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-camera"></i> Choose Photo
                                            </label>
                                            <input type="file" id="photo" name="photo" class="d-none" accept="image/*">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <label for="username">Username</label>
                                            <input type="text" class="form-control" id="username" name="username" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="password">Password</label>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="confirm_password">Confirm Password</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="first_name">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="middle_name">Middle Name</label>
                                    <input type="text" class="form-control" id="middle_name" name="middle_name">
                                </div>
                                <div class="col-md-4">
                                    <label for="last_name">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-md-4">
                                    <label for="sex">Sex</label>
                                    <select class="form-select" id="sex" name="sex" required>
                                        <option value="">Choose...</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="birthdate">Birthdate</label>
                                    <input type="date" class="form-control" id="birthdate" name="birthdate" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="phone_number">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone_number" name="phone_number" required>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">Save Member</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('photo').addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profilePreview').src = e.target.result;
            };
            reader.readAsDataURL(e.target.files[0]);
        }
    });
    </script>
</body>
</html>
