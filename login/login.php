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
    <style>
        body {
    background-color: #f8f9fa;
    font-family: 'Inter', sans-serif;
}
.login-container {
    width: 480px; /* Previous: 480px */
    max-width: 90%;
    padding: 1.5rem; /* Reduced from 2.5rem */
    background: white;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
}

.logo-placeholder img {
    max-width: 180px;
    margin-bottom: 1rem; /* Reduced from 1.25rem */
}
.btn-login {
    background-color: #FF0000;
    color: white;
    font-weight: bold;
    margin-bottom: 12px;
    padding: 10px 0;
    font-size: 1rem;
}
.btn-login:hover {
    background-color: #cc0000;
    color: white;
}
.btn-register {
    background-color: #ffffff;
    color: #FF0000;
    border: 2px solid #FF0000;
    font-weight: bold;
    padding: 10px 0;
    font-size: 1rem;
}
.btn-register:hover {
    background-color: #ff0000;
    color: white;
}
h1 {
    font-size: 1.5rem; /* Reduced from 1.75rem */
    font-weight: bold;
    margin-bottom: 1.5rem; /* Reduced from 1.75rem */
    line-height: 1.4;
}
.form-floating {
    position: relative;
    margin-bottom: 1rem; /* Reduced from 1.25rem */
}


.form-floating input {
    height: calc(3.5rem + 2px);
    padding: 1rem 0.75rem;
    font-size: 1rem;
}
.form-floating label {
    position: absolute;
    top: 0;
    left: 0;
    padding: 1rem 0.75rem;
    pointer-events: none;
    transition: all 0.2s ease-in-out;
    font-size: 1rem;
}
.form-floating input:focus ~ label,
.form-floating input:not(:placeholder-shown) ~ label {
    opacity: 0.65;
    transform: scale(0.85) translateY(-0.75rem) translateX(0.15rem);
}
.forgot-password {
    color: #6c757d;
    text-decoration: none;
    font-size: 0.9rem;
}
.forgot-password:hover {
    color: #FF0000;
    text-decoration: none;
}
.home-link {
    color: #6c757d;
    text-decoration: none;
    font-size: 0.9rem;
    margin-top: 0.75rem;
    display: inline-block;
}
.home-link:hover {
    color: #FF0000;
    text-decoration: none;
}
.remember-me-label {
    font-size: 0.9rem;
    color: #6c757d;
}
.form-check-input {
    transform: scale(1.1);
    margin-top: 0.25rem;
}
.alert {
    padding: 0.5rem 1rem; /* Reduced from 0.75rem 1.25rem */
    font-size: 0.85rem; /* Reduced from 0.95rem */
    margin-bottom: 1rem; /* Reduced from 1.25rem */
}


.remember-me-container {
    margin-left: 0.25rem;
}
.bottom-links {
    margin-top: 1rem; /* Reduced from 1.5rem */
    padding-top: 0.5rem; /* Reduced from 0.75rem */
}
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="login-container">
            <div class="text-center">
                <div class="logo-placeholder">
                    <img src="../cms_img/jc_logo1.png" alt="JC Powerzone Gym Logo EST 2022">
                </div>
                <h1>WELCOME TO <br><span style="color: #FF0000;">JC POWERZONE</span></h1>
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
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder=" ">
                    <label for="password">Password</label>
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
                <a href="../register/register.php" class="btn btn-register btn-block">Register</a>
                
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
    </script>
</body>
</html>