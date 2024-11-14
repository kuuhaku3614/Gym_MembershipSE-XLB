<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JC Powerzone Registration</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="register.css">
</head>
<body>
    <div class="container d-flex justify-content-center align-items-start">
        <div class="registration-container">
            <div class="logo-placeholder">
                <img src="../img/jc_logo1.png" alt="JC Powerzone Gym Logo EST 2022">
            </div>
            <h1>WELCOME TO <br><span style="color: #FF0000;">JC POWERZONE</span></h1>
            
            <form id="registrationForm" method="POST" action="register_handler.php">
                <div class="form-row">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="firstName" name="first_name" placeholder=" " required>
                            <label for="firstName">First Name</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="lastName" name="last_name" placeholder=" " required>
                            <label for="lastName">Last Name</label>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="middleName" name="middle_name" placeholder="Optional">
                            <label for="middleName">Middle Name</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <select class="form-select form-control" id="sex" name="sex" required>
                                <option value="" selected disabled></option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                            <label for="sex">Sex</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <input type="date" class="form-control" id="birthday" name="birthday" placeholder=" " required>
                            <label for="birthday">Birthday</label>
                        </div>
                    </div>
                </div>

                <div class="form-floating">
                    <input type="tel" class="form-control" id="phone" name="phone" placeholder=" " required>
                    <label for="phone">Phone No.</label>
                </div>

                <div class="form-floating">
                    <input type="text" class="form-control" id="username" name="username" placeholder=" " required>
                    <label for="username">Username</label>
                </div>

                <div class="form-floating">
                    <input type="password" class="form-control" id="password" name="password" placeholder=" " required>
                    <label for="password">Password</label>
                </div>

                <div class="form-floating">
                    <input type="password" class="form-control" id="confirmPassword" name="confirm_password" placeholder=" " required>
                    <label for="confirmPassword">Confirm Password</label>
                </div>

                <button type="submit" class="btn btn-confirm btn-block">Confirm</button>
                <a href="../login/login.php" class="btn btn-exit btn-block">Exit</a>
            </form>
        </div>
    </div>

    <!-- Modal Overlay -->
    <div class="modal-overlay"></div>

    <!-- Verification Modal -->
    <div class="verification-modal">
        <img src="../img/jc_logo_2.png" alt="JC Powerzone Logo" class="modal-logo">
        <h1 class="welcome-text">WELCOME TO<br>JC POWERZONE</h1>
        <p class="verification-text">
            Enter verification code to verify phone number. (123456)<br>
            <span id="phoneDisplay"></span>
        </p>
        <input type="text" class="verification-input" maxlength="6">
        <button class="resend-code">Resend code</button>
        <div class="modal-buttons">
            <button class="btn-back">Go Back</button>
            <button class="btn-verify">Verify</button>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="success-modal">
        <img src="../img/jc_logo1.png" alt="JC Powerzone Logo" class="modal-logo">
        <h1 class="welcome-text">WELCOME TO<br>JC POWERZONE</h1>
        <p class="success-message">Account Created Successfully</p>
        <button class="btn-login">Login</button>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        $(document).ready(function() {
            // Form submission handler
            $('#registrationForm').on('submit', function(e) {
                e.preventDefault();
                
                // Validate password match
                if ($('#password').val() !== $('#confirmPassword').val()) {
                    alert('Passwords do not match!');
                    return;
                }
                
                // First, validate the form data
                $.ajax({
                    url: 'register_handler.php',
                    type: 'POST',
                    data: $(this).serialize() + '&action=validate',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Generate verification code
                            $.get('register_handler.php', { action: 'generate_code' }, function() {
                                // Show verification modal
                                const phoneNumber = $('#phone').val();
                                const maskedPhone = maskPhoneNumber(phoneNumber);
                                $('#phoneDisplay').text(`Verification code sent to ${maskedPhone}`);
                                $('.modal-overlay').fadeIn();
                                $('.verification-modal').css('display', 'block');
                            });
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred during validation. Please try again.');
                    }
                });
            });

            // Verify button click handler
            $('.btn-verify').click(function() {
                const code = $('.verification-input').val();
                
                $.ajax({
                    url: 'register_handler.php',
                    type: 'POST',
                    data: {
                        action: 'verify',
                        code: code
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('.verification-modal').css('display', 'none');
                            $('.success-modal').css('display', 'block');
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred during verification. Please try again.');
                    }
                });
            });

            // Existing event handlers remain the same
            $('.btn-back').click(function() {
                $('.verification-modal').css('display', 'none');
                $('.modal-overlay').fadeOut();
            });

            $('.btn-login').click(function() {
                window.location.href = '../login/login.php';
            });

            $('.resend-code').click(function() {
                $.get('register_handler.php', { action: 'generate_code' }, function(response) {
                    if (response.success) {
                        alert('New verification code sent!');
                    }
                });
            });

            // Helper function
            function maskPhoneNumber(phone) {
                return phone.substring(0, 2) + '*'.repeat(phone.length - 4) + phone.slice(-2);
            }
        });
    </script>
</body>
</html>