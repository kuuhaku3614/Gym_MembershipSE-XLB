<?php
session_start();
require_once 'functions.php';

// Check if user is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && isset($_SESSION['role_id'])) {
    redirectBasedOnRole($_SESSION['role']);
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
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
            
            // Redirect based on role
            redirectBasedOnRole($result['role']);
        } else {
            $error = $result['message'];
        }
    }
}
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
            <img src="../cms_img/jc_logo1.png" alt="JC Powerzone Gym Logo EST 2022">
        </div>
        <h1 class="d-flex align-items-center justify-content-center m-0">JC POWERZONE</h1>
    </div>
    <div class=" mb-4">
        <h2>Log In</h2>
        <p class="text-muted">Or sign up to create a new account</p>
    </div>
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?php echo htmlspecialchars($username); ?>" placeholder=" ">
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

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        function saveRememberMe(element) {
            if (element.checked) {
                document.cookie = "remember_me=1; expires=Fri, 31 Dec 9999 23:59:59 GMT; path=/";
            } else {
                document.cookie = "remember_me=0; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/";
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
    </script>
</body>
</html>