<?php
session_start();
require_once 'functions.php';

// Check if user is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && isset($_SESSION['role_id'])) {
    // Special handling for coach/staff
    if ($_SESSION['role'] === 'coach/staff') {
        // Set flag to show modal but don't redirect
        $showRoleModal = true;
    } else {
        redirectBasedOnRole($_SESSION['role']);
    }
}

require_once '../website/includes/loadingScreen.php';

$error = '';
$username = '';
$showRoleModal = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if this is a role selection from the modal
    if (isset($_POST['role_selection'])) {
        $selectedRole = sanitizeInput($_POST['role_selection']);
        
        if ($selectedRole === 'admin') {
            header("Location: ../admin/index.php");
        } else {
            header("Location: ../website/website.php");
        }
        exit();
    }
    
    // Otherwise process normal login
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';  // Don't sanitize password before verification
    
    if (empty($username) || empty($password)) {
        $error = "Don't have an account? <a href='../register/register.php'>Sign up here</a>.";
    } else {
        $result = loginUser($username, $password);
        if ($result['success']) {
            // Store all necessary session variables
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $result['role'];
            $_SESSION['role_id'] = $result['role_id'];
            
            // Initialize notification session data from database
            initializeNotificationSession($database, $result['user_id']);
            
            // Special handling for coach/staff role
            if ($result['role'] === 'coach/staff') {
                $showRoleModal = true;
            } else {
                // Redirect based on role
                redirectBasedOnRole($result['role']);
            }
        } else {
            $error = $result['message'];
        }
    }
}

// Fetch specific content for sections - using prepared statements from executeQuery function
$welcomeContent = executeQuery("SELECT * FROM website_content WHERE section = ?", ['welcome'])[0] ?? [];
$logo = executeQuery("SELECT * FROM website_content WHERE section = ?", ['logo'])[0] ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JC Powerzone Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="container-fluid d-flex flex-column justify-content-center align-items-center vh-100 w-100">
    <div class="login-container">
    <div class="text-center d-flex justify-content-center mb-2 gap-2">
        <div class="logo-placeholder">
            <img src="../<?php 
                echo sanitizeOutput($logo['location'] ?? ''); 
            ?>" alt="Gym Logo" class="logo-image">
        </div>
        <h1 class="d-flex align-items-center justify-content-center m-0"><?php 
                echo sanitizeOutput($welcomeContent['company_name'] ?? 'Company Name'); 
            ?></h1>
    </div>
    <div class="mb-4">
        <h2>Log In</h2>
        <p class="text-muted">Or sign up to create a new account</p>
    </div>
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error; ?> <!-- Not using sanitizeOutput here since we want the HTML links to work -->
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo sanitizeOutput($_SERVER['PHP_SELF']); ?>">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?php echo sanitizeOutput($username); ?>" placeholder=" ">
                    <label for="username">Username</label>
                </div>
                <div class="form-floating mb-3 position-relative">
                    <input type="password" class="form-control" id="password" name="password" placeholder=" ">
                    <label for="password">Password</label>
                    <button type="button" class="eyeToggler btn position-absolute" onclick="togglePassword('password')">
                            <i class="togglePW fas fa-eye"></i>
                    </button>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check remember-me-container">
                        <input type="checkbox" class="form-check-input" id="remember-me" name="remember-me"
                            <?php if (isset($_COOKIE['remember_me'])): ?>
                                checked
                            <?php endif; ?>
                            onclick="saveRememberMe(this)">
                        <label class="form-check-label remember-me-label" for="remember-me">Remember Me</label>
                    </div>
                    <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-login btn-block">Login</button>
                <a href="../register/register.php" class="btn btn-register btn-block">Sign Up</a>
                
                <div class="text-center bottom-links">
                    <a href="../website/website.php" class="home-link">Back to Home</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Role Selection Modal -->
    <div class="modal fade" id="roleSelectionModal" tabindex="-1" role="dialog" aria-labelledby="roleSelectionModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="roleSelectionModalLabel">Select Your Destination</h5>
                </div>
                <div class="modal-body">
                    <p>As a Coach/Staff member, you have access to both areas. Where would you like to go?</p>
                    <form method="POST" action="<?php echo sanitizeOutput($_SERVER['PHP_SELF']); ?>">
                        <div class="d-flex justify-content-around mt-4">
                            <button type="submit" name="role_selection" value="admin" class="btn btn-primary">
                                <i class="fas fa-cogs mr-2"></i> Admin Area
                            </button>
                            <button type="submit" name="role_selection" value="website" class="btn btn-success">
                                <i class="fas fa-dumbbell mr-2"></i> Website
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        function saveRememberMe(element) {
            if (element.checked) {
                document.cookie = "remember_me=1; expires=Fri, 31 Dec 9999 23:59:59 GMT; path=/; SameSite=Strict; Secure";
            } else {
                document.cookie = "remember_me=0; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; SameSite=Strict; Secure";
            }
        }

        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const icon = event.currentTarget.querySelector('.togglePW');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }   
        
        <?php if ($showRoleModal || (isset($_SESSION['role']) && $_SESSION['role'] === 'coach/staff')): ?>
        // Show the modal when page loads if needed
        $(document).ready(function() {
            $('#roleSelectionModal').modal('show');
        });
        <?php endif; ?>
    </script>
</body>
</html>