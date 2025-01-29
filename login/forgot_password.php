<?php
session_start();
require_once 'functions.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - JC Powerzone</title>
    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;800&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
        }
        .forgot-password-container {
            max-width: 400px;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .logo-placeholder img {
            max-width: 150px;
            margin-bottom: 1rem;
        }
        .btn-submit {
            background-color: #FF0000;
            color: white;
            font-weight: bold;
        }
        .btn-submit:hover {
            background-color: #cc0000;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="forgot-password-container">
            <div class="text-center">
                <div class="logo-placeholder">
                    <img src="../cms_img/jc_logo1.png" alt="JC Powerzone Gym Logo">
                </div>
                <h2 class="mb-4">Reset Password</h2>
            </div>

            <!-- Step 1: Enter Username -->
            <div id="step1" class="form-step">
                <form id="usernameForm">
                    <div class="form-group">
                        <label for="username">Enter your username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <button type="submit" class="btn btn-submit btn-block">Continue</button>
                </form>
            </div>

            <!-- Step 2: Enter Verification Code -->
            <div id="step2" class="form-step" style="display: none;">
                <p class="mb-3">We've sent a verification code to your registered phone number.</p>
                <form id="verificationForm">
                    <div class="form-group">
                        <label for="code">Enter verification code</label>
                        <input type="text" class="form-control" id="code" name="code" required>
                    </div>
                    <button type="submit" class="btn btn-submit btn-block mb-2">Verify Code</button>
                    <button type="button" id="resendCode" class="btn btn-link btn-block">Resend Code</button>
                </form>
            </div>

            <!-- Step 3: New Password -->
            <div id="step3" class="form-step" style="display: none;">
                <form id="passwordForm">
                    <div class="form-group">
                        <label for="newPassword">New Password</label>
                        <input type="password" class="form-control" id="newPassword" name="newPassword" required>
                        <small class="form-text text-muted">Password must be at least 8 characters with uppercase, lowercase, and numbers.</small>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                    </div>
                    <button type="submit" class="btn btn-submit btn-block">Reset Password</button>
                </form>
            </div>

            <div class="text-center mt-3">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        $(document).ready(function() {
            let username = '';

            // Handle username submission
            $('#usernameForm').on('submit', function(e) {
                e.preventDefault();
                username = $('#username').val();
                
                $.post('forgot_password_functions.php', {
                    action: 'verify_username',
                    username: username
                })
                .done(function(response) {
                    const result = JSON.parse(response);
                    if (result.success) {
                        $('#step1').hide();
                        $('#step2').show();
                        // Send verification code automatically
                        sendVerificationCode();
                    } else {
                        alert(result.message);
                    }
                });
            });

            // Handle verification code submission
            $('#verificationForm').on('submit', function(e) {
                e.preventDefault();
                $.post('forgot_password_functions.php', {
                    action: 'verify_code',
                    username: username,
                    code: $('#code').val()
                })
                .done(function(response) {
                    const result = JSON.parse(response);
                    if (result.success) {
                        $('#step2').hide();
                        $('#step3').show();
                    } else {
                        alert(result.message);
                    }
                });
            });

            // Handle new password submission
            $('#passwordForm').on('submit', function(e) {
                e.preventDefault();
                const newPassword = $('#newPassword').val();
                const confirmPassword = $('#confirmPassword').val();

                if (newPassword !== confirmPassword) {
                    alert('Passwords do not match');
                    return;
                }

                $.post('forgot_password_functions.php', {
                    action: 'reset_password',
                    username: username,
                    password: newPassword
                })
                .done(function(response) {
                    const result = JSON.parse(response);
                    if (result.success) {
                        alert('Password reset successful!');
                        window.location.href = 'login.php';
                    } else {
                        alert(result.message);
                    }
                });
            });

            // Handle resend code
            $('#resendCode').on('click', function() {
                sendVerificationCode();
            });

            function sendVerificationCode() {
                $.get('forgot_password_functions.php', {
                    action: 'generate_code',
                    username: username
                })
                .done(function(response) {
                    const result = JSON.parse(response);
                    if (!result.success) {
                        alert(result.message);
                    }
                });
            }
        });
    </script>
</body>
</html>