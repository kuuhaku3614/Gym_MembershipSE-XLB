<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '../functions/config.php';


$username_err = $password_err = $success_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE username = ? AND role_id = (SELECT id FROM roles WHERE role_name = 'admin')";
        
        if ($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(1, $param_username, PDO::PARAM_STR);
            $param_username = trim($_POST["username"]);
            
            if ($stmt->execute()) {
                if ($stmt->rowCount() == 1) {
                    $username_err = "This username is already taken.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
        }
        unset($stmt);
    }
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";     
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    }
    
    // Check input errors before inserting into database
    if (empty($username_err) && empty($password_err)) {
        $sql = "INSERT INTO users (username, password, role_id) 
        VALUES (?, ?, (SELECT id FROM roles WHERE role_name = 'admin'))";
        
        if ($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(1, $param_username, PDO::PARAM_STR);
            $stmt->bindParam(2, $param_password, PDO::PARAM_STR);
            
            $param_username = trim($_POST["username"]);
            $param_password = password_hash(trim($_POST["password"]), PASSWORD_DEFAULT);
            
            if ($stmt->execute()) {
                $success_msg = "Admin user created successfully.";
            } else {
                echo "Something went wrong. Please try again later.";
            }
        }
        unset($stmt);
    }
    
    unset($pdo);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Admin User</title>
    <style>
        body { font: 14px sans-serif; }
        .wrapper { width: 360px; padding: 20px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Add Admin User</h2>
        <p>Please fill this form to create an admin account.</p>
        
        <?php 
        if (!empty($success_msg)) {
            echo '<div class="success">' . $success_msg . '</div><br>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                <span class="error"><?php echo $username_err; ?></span>
            </div>    
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control">
                <span class="error"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Submit">
            </div>
        </form>
    </div>
</body>
</html>