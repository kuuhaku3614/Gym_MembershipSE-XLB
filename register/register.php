<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JC Powerzone Registration</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="register.css">
</head>
<body>
    <div class="container d-flex justify-content-center align-items-start">
        <div class="registration-container">
            <div class="logo-placeholder">
                <img src="../cms_img/jc_logo1.png" alt="JC Powerzone Gym Logo EST 2022">
            </div>
            <h1>WELCOME TO <br><span style="color: #FF0000;">JC POWERZONE</span></h1>
            
            <form id="registrationForm" method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <!-- Profile Photo Upload Section -->
                    <div class="profile-photo-upload">
                        <div class="profile-photo-container">
                            <div class="profile-photo-placeholder">
                                <i class="fas fa-camera"></i>
                                <span>Upload Photo</span>
                            </div>
                        </div>
                        <div class="file-upload-container">
                            <input 
                                type="file" 
                                id="profile_photo" 
                                name="profile_photo" 
                                accept=".jpg, .jpeg, .png" 
                                class="file-input" 
                                hidden
                            >
                            <div class="file-upload-wrapper">
                                <button type="button" class="btn-upload-trigger">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Upload</span>
                                </button>
                                <span class="file-name-display">No file chosen</span>
                            </div>
                        </div>
                    </div>
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
                            <input type="text" class="form-control" id="middleName" name="middle_name" placeholder=" ">
                            <label for="middleName">Middle Name (Optional)</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <select class="form-select form-control" id="sex" name="sex" required>
                                <option value="" selected disabled>N/A</option>
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
        <img src="../cms_img/jc_logo_2.png" alt="JC Powerzone Logo" class="modal-logo">
        <h1 class="welcome-text">WELCOME TO<br>JC POWERZONE</h1>
        <p class="verification-text">
            Enter verification code to verify phone number.<br>
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
        <img src="../cms_img/jc_logo1.png" alt="JC Powerzone Logo" class="modal-logo">
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
        let isVerificationInProgress = false;

        // Custom file upload trigger
        $('.btn-upload-trigger').on('click', function() {
            $('#profile_photo').click();
        });

        // File input change handler
        $('#profile_photo').on('change', function(event) {
            const file = event.target.files[0];
            const fileNameDisplay = $('.file-name-display');
            
            if (file) {
                const allowedTypes = ['image/jpeg', 'image/png'];
                const maxSize = 5 * 1024 * 1024; // 5MB
                
                if (!allowedTypes.includes(file.type)) {
                    alert('Please upload a valid image (JPEG or PNG)');
                    resetFileInput();
                    return;
                }
                
                if (file.size > maxSize) {
                    alert('File size exceeds 5MB limit');
                    resetFileInput();
                    return;
                }
                
                fileNameDisplay.text(file.name);
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('.profile-photo-container').html(`<img src="${e.target.result}" alt="Profile Photo">`);
                };
                reader.readAsDataURL(file);
            }
        });

        function resetFileInput() {
            $('#profile_photo').val('');
            $('.file-name-display').text('No file chosen');
            $('.profile-photo-container').html(`
                <div class="profile-photo-placeholder">
                    <i class="fas fa-camera"></i>
                    <span>Upload Photo</span>
                </div>
            `);
        }

        // Form submission handler
        $('#registrationForm').on('submit', function(e) {
            e.preventDefault();
            
            if (isVerificationInProgress) {
                return;
            }
            
            // Validate password match
            if ($('#password').val() !== $('#confirmPassword').val()) {
                alert('Passwords do not match!');
                return;
            }
            
            var formData = new FormData(this);
            formData.append('action', 'validate');
            
            $.ajax({
                url: 'register_handler.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        isVerificationInProgress = true;
                        const phoneNumber = $('#phone').val();
                        const maskedPhone = maskPhoneNumber(phoneNumber);
                        $('#phoneDisplay').text(`Verification code sent to ${maskedPhone}`);
                        $('.modal-overlay').fadeIn();
                        $('.verification-modal').fadeIn();
                        
                        // Generate verification code after showing modal
                        generateVerificationCode();
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('An error occurred during validation. Please try again.');
                }
            });
        });

        // Function to generate verification code
        function generateVerificationCode() {
            $.ajax({
                url: 'register_handler.php',
                type: 'GET',
                data: { action: 'generate_code' },
                dataType: 'json',
                success: function(response) {
                    if (!response.success) {
                        alert(response.message);
                        isVerificationInProgress = false;
                        $('.verification-modal').hide();
                        $('.modal-overlay').fadeOut();
                    }
                },
                error: function() {
                    alert('Failed to send verification code. Please try again.');
                    isVerificationInProgress = false;
                    $('.verification-modal').hide();
                    $('.modal-overlay').fadeOut();
                }
            });
        }

        // Verify button click handler
        $('.btn-verify').click(function() {
            const code = $('.verification-input').val();
            if (!code || code.length !== 6) {
                alert('Please enter a valid 6-digit verification code.');
                return;
            }
            
            var formData = new FormData();
            formData.append('action', 'verify');
            formData.append('code', code);
            
            $.ajax({
                url: 'register_handler.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('.verification-modal').hide();
                        $('.success-modal').fadeIn();
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('An error occurred during verification. Please try again.');
                }
            });
        });

        // Resend code handler
        $('.resend-code').click(function() {
            $('.verification-input').val(''); // Clear the input field
            generateVerificationCode();
            alert('New verification code has been sent.');
        });

        // Back button handler
        $('.btn-back').click(function() {
            isVerificationInProgress = false;
            $('.verification-modal').hide();
            $('.modal-overlay').fadeOut();
            $('.verification-input').val(''); // Clear the input field
        });

        // Login button handler
        $('.btn-login').click(function() {
            window.location.href = '../login/login.php';
        });

        // Helper function to mask phone number
        function maskPhoneNumber(phone) {
            if (phone.length <= 5) return phone;
            return phone.substring(0, 3) + '*'.repeat(phone.length - 5) + phone.slice(-2);
        }
    });
    $(document).ready(function() {
    // Real-time validation functions
    const validations = {
        password: function(value) {
            return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/.test(value) ? 
                '' : 'Password must be at least 8 characters and contain uppercase, lowercase, and numbers.';
        },
        phone: function(value) {
        return /^(?:\+63|0)9\d{9}$/.test(value) ?
            '' : 'Please enter a valid Philippine phone number (e.g., 09123456789 or +639123456789)';
        },
        birthday: function(value) {
            const birthDate = new Date(value);
            const today = new Date();
            const age = today.getFullYear() - birthDate.getFullYear();
            return age >= 15 ? '' : 'You must be at least 15 years old to register.';
        }
    };

    // Function to show error feedback
    function showError(field, message) {
        const element = $(`#${field}`);
        const feedbackDiv = element.siblings('.error-feedback');
        
        element.addClass('is-invalid');
        if (feedbackDiv.length === 0) {
            element.after(`<div class="error-feedback">${message}</div>`);
        } else {
            feedbackDiv.text(message).show();
        }
    }

    // Function to clear error feedback
    function clearError(field) {
        const element = $(`#${field}`);
        element.removeClass('is-invalid');
        element.siblings('.error-feedback').hide();
    }

    // Real-time validation listeners
    Object.keys(validations).forEach(field => {
        $(`#${field}`).on('input blur', function() {
            const error = validations[field](this.value);
            if (error) {
                showError(field, error);
            } else {
                clearError(field);
            }
        });
    });

    // Password confirmation validation
    $('#confirmPassword').on('input blur', function() {
        const password = $('#password').val();
        if (this.value !== password) {
            showError('confirmPassword', 'Passwords do not match.');
        } else {
            clearError('confirmPassword');
        }
    });

    // Update form submission handler
    $('#registrationForm').on('submit', function(e) {
        e.preventDefault();
        
        if (isVerificationInProgress) {
            return;
        }
        
        // Clear all previous errors
        $('.error-feedback').hide();
        $('.is-invalid').removeClass('is-invalid');
        
        var formData = new FormData(this);
        formData.append('action', 'validate');
        
        $.ajax({
            url: 'register_handler.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    isVerificationInProgress = true;
                    const phoneNumber = $('#phone').val();
                    const maskedPhone = maskPhoneNumber(phoneNumber);
                    $('#phoneDisplay').text(`Verification code sent to ${maskedPhone}`);
                    $('.modal-overlay').fadeIn();
                    $('.verification-modal').fadeIn();
                    generateVerificationCode();
                } else {
                    // Show error on specific field if available
                    if (response.field && response.field !== 'general') {
                        showError(response.field, response.message);
                    } else {
                        alert(response.message);
                    }
                }
            },
            error: function() {
                alert('An error occurred during validation. Please try again.');
            }
        });
    });
});
    </script>
</body>
</html>