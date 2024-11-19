<?php
session_start();

require_once 'functions.php';

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
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
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $result['role'];
            
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
    <!-- Custom CSS -->
    <link href="login.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="login-container text-center">
            <!-- dynamic logo -->
            <div class="logo-placeholder">
                <img src="../img/jc_logo1.png" alt="JC Powerzone Gym Logo EST 2022">
            </div>
            <!-- dynamic welcome message -->
            <h1>WELCOME TO <br><span style="color: #FF0000;">JC POWERZONE</span></h1>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" 
                         value="<?php echo htmlspecialchars($username); ?>">
                    <label for="username">Username</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" >
                    <label for="password">Password</label>
                </div>
                <div class="form-row justify-content-between align-items-center mb-3">
                    <div class="form-check d-flex align-items-center">
                        <input type="checkbox" class="form-check-input" id="remember-me" name="remember-me">
                        <label class="form-check-label ml-2" for="remember-me">Remember Me</label>
                    </div>
                    <a href="#" class="forgot-password">Forgot Password</a>
                </div>
                <button type="submit" class="btn btn-block btn-login">Login</button>
                <a href="../register/register.php" class="btn btn-block btn-register">Register</a>
            </form>
            <br>
            <small><a href="../website/website.php">Home</a></small>
        </div>
    </div>
    <!-- Bootstrap JS, Popper.js, and jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>