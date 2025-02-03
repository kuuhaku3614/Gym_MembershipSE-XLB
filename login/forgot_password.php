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
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @font-face {
            font-family: myFont;
            src: url(../AC.ttf);
        }
        body {
            background-color: #ecebeb;
            font-family: myFont;
        }
        .forgot-password-container {
            width: 400px;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .logo-placeholder img {
            max-width: 100px;
            margin-bottom: 1rem;
        }
        .btn-submit {
            background-color: #4361ee;
            color: white;
        }
        .btn-submit:hover {
            background-color:rgb(39, 75, 238);
            color: white;
        }
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: none;
        }
        .form-control.is-invalid {
            border-color: #dc3545;
        }
        .form-control{
            font-family: Arial, Helvetica, sans-serif;
            color: #6c757d;
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
                <label for="username" class="text-secondary">Enter your username</label>
                <input type="text" class="form-control" id="username" name="username" required>
                <div id="usernameError" class="error-message"></div>
                </div>
                <button type="submit" class="btn btn-submit btn-block">Continue</button>
            </form>
            </div>

            <!-- Step 2: Enter Verification Code -->
            <div id="step2" class="form-step" style="display: none;">
            <p class="mb-3 text-secondary">We've sent a verification code to your registered phone number.</p>
            <form id="verificationForm">
                <div class="form-group">
                <label for="code" class="text-secondary">Enter verification code</label>
                <input type="text" class="form-control" id="code" name="code" required>
                <div id="codeError" class="error-message"></div>
                </div>
                <button type="submit" class="btn btn-submit btn-block mb-2">Verify Code</button>
                <button type="button" id="resendCode" class="btn btn-link btn-block">Resend Code</button>
            </form>
            </div>

            <!-- Step 3: New Password -->
            <div id="step3" class="form-step" style="display: none;">
            <form id="passwordForm">
                <div class="form-group">
                <label for="newPassword" class="text-secondary">New Password</label>
                <input type="password" class="form-control" id="newPassword" name="newPassword" required>
                <div id="newPasswordError" class="error-message"></div>
                </div>
                <div class="form-group">
                <label for="confirmPassword" class="text-secondary">Confirm Password</label>
                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                <div id="confirmPasswordError" class="error-message"></div>
                </div>
                <button type="submit" class="btn btn-submit btn-block">Reset Password</button>
            </form>
            </div>

            <div class="text-center mt-3 ">
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
    let resendTimer;

    // Error handling functions
    function showError(elementId, message) {
        $(`#${elementId}`).text(message).show();
        $(`#${elementId}`).prev('input').addClass('is-invalid');
    }

    function clearError(elementId) {
        $(`#${elementId}`).hide();
        $(`#${elementId}`).prev('input').removeClass('is-invalid');
    }

    function clearAllErrors() {
        $('.error-message').hide();
        $('.form-control').removeClass('is-invalid');
    }

    // Loading state functions
    function setLoadingState(button) {
        const originalText = button.text();
        button.prop('disabled', true)
              .html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading...')
              .data('original-text', originalText);
    }

    function resetLoadingState(button) {
        const originalText = button.data('original-text');
        button.prop('disabled', false).text(originalText);
    }

    // Function to send verification code
    function sendVerificationCode() {
        $.get('forgot_password_functions.php', {
            action: 'generate_code',
            username: username
        })
        .done(function(response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    let timeLeft = 60;
                    $('#resendCode').prop('disabled', true);
                    
                    resendTimer = setInterval(function() {
                        if (timeLeft <= 0) {
                            clearInterval(resendTimer);
                            $('#resendCode').prop('disabled', false)
                                          .text('Resend Code');
                        } else {
                            $('#resendCode').text(`Resend Code (${timeLeft}s)`);
                            timeLeft--;
                        }
                    }, 1000);
                } else {
                    showError('codeError', result.message);
                }
            } catch (e) {
                showError('codeError', 'An unexpected error occurred');
            }
        })
        .fail(function() {
            showError('codeError', 'Failed to connect to server. Please try again.');
        });
    }

    // Handle username submission
    $('#usernameForm').on('submit', function(e) {
        e.preventDefault();
        const submitButton = $(this).find('button[type="submit"]');
        username = $('#username').val().trim();
        
        clearAllErrors();
        
        if (!username) {
            showError('usernameError', 'Please enter your username');
            return;
        }

        setLoadingState(submitButton);
        
        $.post('forgot_password_functions.php', {
            action: 'verify_username',
            username: username
        })
        .done(function(response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    $('#step1').hide();
                    $('#step2').show();
                    sendVerificationCode();
                } else {
                    showError('usernameError', result.message);
                }
            } catch (e) {
                showError('usernameError', 'An unexpected error occurred');
            }
        })
        .fail(function() {
            showError('usernameError', 'Failed to connect to server. Please try again.');
        })
        .always(function() {
            resetLoadingState(submitButton);
        });
    });

// Handle verification code submission
$('#verificationForm').on('submit', function(e) {
    e.preventDefault();
    const submitButton = $(this).find('button[type="submit"]');
    const code = $('#code').val().trim();
    
    clearAllErrors();
    
    if (!code) {
        showError('codeError', 'Please enter the verification code');
        return;
    }

    // Validate code format - must be 6 digits
    if (!/^\d{6}$/.test(code)) {
        showError('codeError', 'Please enter a valid 6-digit code');
        return;
    }

    setLoadingState(submitButton);
    
    $.post('forgot_password_functions.php', {
        action: 'verify_code',
        username: username, // This is stored from the first step
        code: code
    })
    .done(function(response) {
        try {
            const result = JSON.parse(response);
            if (result.success) {
                // Show success message before moving to next step
                Swal.fire({
                    title: 'Verified!',
                    text: 'Code verification successful',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    $('#step2').hide();
                    $('#step3').show();
                    clearInterval(resendTimer); // Clear the resend timer when moving to next step
                });
            } else {
                showError('codeError', result.message || 'Invalid verification code');
            }
        } catch (e) {
            showError('codeError', 'An unexpected error occurred');
            console.error('Error parsing response:', e);
        }
    })
    .fail(function(xhr, status, error) {
        showError('codeError', 'Failed to connect to server. Please try again.');
        console.error('AJAX Error:', status, error);
    })
    .always(function() {
        resetLoadingState(submitButton);
    });
});

// Handle resend code button with improved error handling
$('#resendCode').on('click', function() {
    const button = $(this);
    clearError('codeError');
    
    if (button.prop('disabled')) {
        return;
    }

    setLoadingState(button);
    
    $.get('forgot_password_functions.php', {
        action: 'generate_code',
        username: username // Using the stored username
    })
    .done(function(response) {
        try {
            const result = JSON.parse(response);
            if (result.success) {
                let timeLeft = 60;
                button.prop('disabled', true);
                
                clearInterval(resendTimer); // Clear any existing timer
                resendTimer = setInterval(function() {
                    if (timeLeft <= 0) {
                        clearInterval(resendTimer);
                        button.prop('disabled', false)
                              .text('Resend Code');
                    } else {
                        button.text(`Resend Code (${timeLeft}s)`);
                        timeLeft--;
                    }
                }, 1000);
                
                Swal.fire({
                    title: 'Code Sent!',
                    text: 'A new verification code has been sent to your phone.',
                    icon: 'success',
                    timer: 3000,
                    showConfirmButton: false
                });
            } else {
                showError('codeError', result.message || 'Failed to send verification code');
                resetLoadingState(button);
            }
        } catch (e) {
            showError('codeError', 'An unexpected error occurred');
            console.error('Error parsing response:', e);
            resetLoadingState(button);
        }
    })
    .fail(function(xhr, status, error) {
        showError('codeError', 'Failed to connect to server. Please try again.');
        console.error('AJAX Error:', status, error);
        resetLoadingState(button);
    });
});

    // Handle new password submission
    $('#passwordForm').on('submit', function(e) {
        e.preventDefault();
        const submitButton = $(this).find('button[type="submit"]');
        const newPassword = $('#newPassword').val();
        const confirmPassword = $('#confirmPassword').val();
        
        clearAllErrors();
        
        if (!newPassword) {
            showError('newPasswordError', 'Please enter a new password');
            return;
        }

        if (newPassword.length < 8) {
            showError('newPasswordError', 'Password must be at least 8 characters long');
            return;
        }

        if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(newPassword)) {
            showError('newPasswordError', 'Password must contain at least one uppercase letter, one lowercase letter, and one number');
            return;
        }

        if (!confirmPassword) {
            showError('confirmPasswordError', 'Please confirm your password');
            return;
        }

        if (newPassword !== confirmPassword) {
            showError('confirmPasswordError', 'Passwords do not match');
            return;
        }

        setLoadingState(submitButton);

        $.post('forgot_password_functions.php', {
            action: 'reset_password',
            username: username,
            password: newPassword
        })
        .done(function(response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Your password has been reset successfully. You will be redirected to the login page.',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        window.location.href = 'login.php';
                    });
                } else {
                    showError('newPasswordError', result.message);
                }
            } catch (e) {
                showError('newPasswordError', 'An unexpected error occurred');
            }
        })
        .fail(function() {
            showError('newPasswordError', 'Failed to connect to server. Please try again.');
        })
        .always(function() {
            resetLoadingState(submitButton);
        });
    });


    // Clear timer when navigating away from verification step
    function clearResendTimer() {
        if (resendTimer) {
            clearInterval(resendTimer);
            $('#resendCode').prop('disabled', false).text('Resend Code');
        }
    }

    // Handle back to login
    $('.back-to-login').on('click', function(e) {
        e.preventDefault();
        clearResendTimer();
        window.location.href = 'login.php';
    });
});
</script>
</body>
</html>