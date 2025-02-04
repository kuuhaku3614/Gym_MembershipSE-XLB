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
    <div class="page-container">
        <div class="registration-container">
            <div class="registration-header">
                <h2 class="mb-0">Register Information</h2>
                <p class="text-muted medium mb-0">Start your journey with us!</p>
            </div>
            
            <form id="registrationForm" method="POST" enctype="multipart/form-data">
                <!-- Profile Photo Section -->
                <div class="profile-photo-upload mb-4">
                    <div class="d-flex justify-content-center align-items-center mb-1 w-100">
                        <div class="profile-photo-container">   
                            <div class="profile-photo-placeholder">
                                <i class="fas fa-camera"></i>
                                <span>Upload Photo</span>
                            </div>
                        </div>
                    </div>
                    <div class="file-upload-container">
                        <input type="file" id="profile_photo" name="profile_photo" accept=".jpg, .jpeg, .png" class="file-input" hidden>
                        <div class="file-upload-wrapper d-flex justify-content-center align-items-center text-center w-100">
                            <button type="button" class="btn-upload-trigger">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Upload</span>
                            </button>
                            <span class="file-name-display">No file chosen</span>
                        </div>
                    </div>
                </div>

                <div class="form-scroll-container">
                    <div class="form-content">
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

                        <div class="form-floating position-relative">
                            <input type="password" class="form-control" id="password" name="password" placeholder=" " required>
                            <label for="password">Password</label>
                            <button type="button" class="eyeToggler btn position-absolute" onclick="togglePassword('password')">
                                <i class="togglePW fas fa-eye"></i>
                            </button>
                        </div>
                        
                        <div class="form-floating">
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" placeholder=" " required>
                            <label for="confirmPassword">Confirm Password</label>
                        </div>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn btn-confirm btn-block">Confirm</button>
                    <a href="../login/login.php" class="btn btn-exit btn-block">Return to Login</a>
                </div>
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
        <div class="countdown-timer"></div>
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
    let countdownInterval;
    
    // File upload handling
    $('.btn-upload-trigger').on('click', function() {
        $('#profile_photo').click();
    });

    $('#profile_photo').on('change', function(event) {
        handleFileUpload(event);
    });

    // Form submission
    $('#registrationForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!validateProfilePhoto()) {
            return;
        }

        var formData = new FormData(this);
        formData.append('action', 'validate');
        
        // Show loading state
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.text();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        $.ajax({
            url: 'register_handler.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const phoneNumber = $('#phone').val();
                    const maskedPhone = maskPhoneNumber(phoneNumber);
                    $('#phoneDisplay').text(`Verification code sent to ${maskedPhone}`);
                    showModal('.verification-modal');
                    generateVerificationCode();
                } else {
                    if (response.field && response.field !== 'general') {
                        showError(response.field, response.message);
                    } else {
                        alert(response.message);
                    }
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            },
            complete: function() {
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });

    // Verification code input
    $('.verification-input').on('input', function() {
        this.value = this.value.replace(/[^\d]/g, '').substring(0, 6);
        
        if (this.value.length === 6) {
            $('.btn-verify').click();
        }
    });

    // Modal controls
    function showModal(modalSelector) {
        $('.modal-overlay').fadeIn().css('opacity', '1');
        $(modalSelector).show().addClass('modal-show');
    }

    function hideModal(modalSelector) {
        $(modalSelector).removeClass('modal-show');
        setTimeout(() => {
            $(modalSelector).hide();
            $('.modal-overlay').fadeOut();
        }, 300);
    }

    // Verify button handler
    $('.btn-verify').click(function() {
        const code = $('.verification-input').val();
        if (!code || code.length !== 6) {
            alert('Please enter a valid 6-digit verification code.');
            return;
        }
        
        var formData = new FormData($('#registrationForm')[0]);
        formData.append('action', 'verify');
        formData.append('code', code);
        
        // Show loading state
        const $btn = $(this);
        const originalText = $btn.text();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Verifying...');
        
        $.ajax({
            url: 'register_handler.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    hideModal('.verification-modal');
                    setTimeout(() => {
                        showModal('.success-modal');
                    }, 300);
                } else {
                    alert(response.message || 'Verification failed');
                    $('.verification-input').val('');
                }
            },
            error: function() {
                alert('An error occurred during verification. Please try again.');
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });

    // Resend code with countdown
    $('.resend-code').click(function() {
        const $btn = $(this);
        const $countdownTimer = $('.countdown-timer');
        
        if ($btn.prop('disabled')) return;
        
        $btn.prop('disabled', true);
        $('.verification-input').val('');
        
        // Start countdown
        let timeLeft = 60;
        $countdownTimer.show().text(`Please wait ${timeLeft} seconds to resend`);
        
        if (countdownInterval) clearInterval(countdownInterval);
        
        countdownInterval = setInterval(() => {
            timeLeft--;
            $countdownTimer.text(`Please wait ${timeLeft} seconds to resend`);
            
            if (timeLeft <= 0) {
                clearInterval(countdownInterval);
                $btn.prop('disabled', false);
                $countdownTimer.hide();
            }
        }, 1000);
        
        generateVerificationCode();
    });

    // Additional button handlers
    $('.btn-back').click(function() {
        hideModal('.verification-modal');
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }
    });

    $('.btn-login').click(function() {
        window.location.href = '../login/login.php';
    });

    // Helper functions
    function handleFileUpload(event) {
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
    }

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

    function validateProfilePhoto() {
        const fileInput = $('#profile_photo')[0];
        const file = fileInput.files[0];
        
        if (!file) {
            alert('Please upload a profile photo.');
            return false;
        }
        return true;
    }

    function maskPhoneNumber(phone) {
        if (phone.length <= 5) return phone;
        return phone.substring(0, 3) + '*'.repeat(phone.length - 5) + phone.slice(-2);
    }

    function showError(field, message) {
        const $input = $(`#${field}`);
        $input.addClass('is-invalid');
        const $feedback = $input.siblings('.error-feedback');
        if ($feedback.length) {
            $feedback.text(message).show();
        } else {
            $input.after(`<div class="error-feedback">${message}</div>`);
        }
    }
});
    </script>
</body>
</html>