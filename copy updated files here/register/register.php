<?php
require_once '../config.php';
function executeQuery($query, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Database query error: ' . $e->getMessage());
        return [];
    }
}
// Fetch specific content for sections
$welcomeContent = executeQuery("SELECT * FROM website_content WHERE section = 'welcome'")[0] ?? [];
$logo = executeQuery("SELECT * FROM website_content WHERE section = 'logo'")[0] ?? [];
require_once '../website/includes/loadingScreen.php';
?>
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
                <h2 class="mb-0">Sign Up Information</h2>
                <p class="text-muted medium mb-0">Start your journey with us!</p>
            </div>
            
            <form id="registrationForm" method="POST" enctype="multipart/form-data">
                <!-- Profile Photo Section (unchanged from original) -->
                <div class="profile-photo-upload">
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
                                    <input type="text" class="form-control" id="firstName" name="first_name" placeholder=" ">
                                    <label for="firstName">First Name</label>
                                    <div id="firstNameError" class="error-message">First name is required</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="lastName" name="last_name" placeholder=" ">
                                    <label for="lastName">Last Name</label>
                                    <div id="lastNameError" class="error-message">Last name is required</div>
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
                                    <select class="form-select form-control" id="sex" name="sex">
                                        <option value="" selected disabled>Select</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="NA">NA</option>
                                    </select>
                                    <label for="sex">Sex</label>
                                    <div id="sexError" class="error-message">Sex is required</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating">
                                    <input type="date" class="form-control" id="birthday" name="birthday" placeholder=" ">
                                    <label for="birthday">Birthday</label>
                                    <div id="birthdayError" class="error-message">Birthday is required</div>
                                </div>
                            </div>
                        </div>

                        <div class="form-floating">
                            <input type="tel" class="form-control" id="phone" name="phone" placeholder=" ">
                            <label for="phone">Phone No.</label>
                            <div id="phoneError" class="error-message">Please enter a valid Philippine phone number</div>
                        </div>

                        <div class="form-floating">
                            <input type="text" class="form-control" id="username" name="username" placeholder=" ">
                            <label for="username">Username</label>
                            <div id="usernameError" class="error-message">Username is required</div>
                        </div>

                        <div class="form-floating position-relative">
                            <input type="password" class="form-control" id="password" name="password" placeholder=" ">
                            <label for="password">Password</label>
                            <button type="button" class="eyeToggler btn position-absolute" onclick="togglePassword('password')">
                                <i class="togglePW fas fa-eye"></i>
                            </button>
                            <div id="passwordError" class="error-message">Password must be at least 8 characters with uppercase, lowercase, and numbers</div>
                        </div>
                        
                        <div class="form-floating">
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" placeholder=" ">
                            <label for="confirmPassword">Confirm Password</label>
                            <button type="button" class="eyeToggler btn position-absolute" onclick="togglePassword('confirmPassword')">
                                <i class="togglePW fas fa-eye"></i>
                            </button>
                            <div id="confirmPasswordError" class="error-message">Passwords do not match</div>
                        </div>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn btn-confirm btn-block">Sign Up</button>
                    <a href="../login/login.php" class="btn btn-exit btn-block">Return to Login</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Overlay -->
    <div class="modal-overlay"></div>

    <!-- Verification Modal -->
    <div class="verification-modal">
        <img src="../<?php 
                echo htmlspecialchars($logo['location']); 
            ?>" alt="JC Powerzone Logo" class="modal-logo">
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
        // Validation functions
        function validateFirstName() {
            const firstName = $('#firstName').val().trim();
            const isValid = firstName.length > 0;
            $('#firstName').toggleClass('invalid', !isValid);
            $('#firstNameError').toggle(!isValid);
            return isValid;
        }

        function validateLastName() {
            const lastName = $('#lastName').val().trim();
            const isValid = lastName.length > 0;
            $('#lastName').toggleClass('invalid', !isValid);
            $('#lastNameError').toggle(!isValid);
            return isValid;
        }

        function validateSex() {
            const sex = $('#sex').val();
            const isValid = sex && sex !== '';
            $('#sex').toggleClass('invalid', !isValid);
            $('#sexError').toggle(!isValid);
            return isValid;
        }

        function validateBirthday() {
            const birthday = $('#birthday').val();
            const isValid = birthday !== '';
            
            if (isValid) {
                const today = new Date();
                const birthDate = new Date(birthday);
                const age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();
                
                const finalAge = monthDiff < 0 || 
                    (monthDiff === 0 && today.getDate() < birthDate.getDate()) 
                    ? age - 1 : age;
                
                const ageValid = finalAge >= 15;
                
                $('#birthday').toggleClass('invalid', !ageValid);
                $('#birthdayError').toggle(!ageValid);
                $('#birthdayError').text(ageValid ? 'Birthday is required' : 'You must be at least 15 years old');
                
                return ageValid;
            }
            
            $('#birthday').toggleClass('invalid', true);
            $('#birthdayError').show();
            return false;
        }

        function validatePhoneNumber() {
            const phone = $('#phone').val().trim();
            const phoneRegex = /^(?:\+63|0)9\d{9}$/;
            const isValid = phoneRegex.test(phone);
            $('#phone').toggleClass('invalid', !isValid);
            $('#phoneError').toggle(!isValid);
            return isValid;
        }

        function validateUsername() {
            const username = $('#username').val().trim();
            const isValid = username.length > 0;
            $('#username').toggleClass('invalid', !isValid);
            $('#usernameError').toggle(!isValid);
            return isValid;
        }

        function validatePassword() {
            const password = $('#password').val();
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
            const isValid = passwordRegex.test(password);
            $('#password').toggleClass('invalid', !isValid);
            $('#passwordError').toggle(!isValid);
            return isValid;
        }

        function validateConfirmPassword() {
            const password = $('#password').val();
            const confirmPassword = $('#confirmPassword').val();
            const isValid = password === confirmPassword && confirmPassword !== '';
            $('#confirmPassword').toggleClass('invalid', !isValid);
            $('#confirmPasswordError').toggle(!isValid);
            return isValid;
        }

        function validateProfilePhoto() {
            const file = $('#profile_photo')[0].files[0];
            const allowedTypes = ['image/jpeg', 'image/png'];
            const maxSize = 5 * 1024 * 1024; // 5MB

            let isValid = true;
            if (!file) {
                isValid = false;
                // Create and show custom alert
                const alertHtml = `
                    <div class="custom-alert" style="
                        position: fixed;
                        top: 20px;
                        left: 50%;
                        transform: translateX(-50%);
                        background-color: #ff4444;
                        color: white;
                        padding: 15px 30px;
                        border-radius: 5px;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                        z-index: 9999;
                        opacity: 0;
                        transition: opacity 0.3s ease;
                    ">Please upload a profile photo</div>`;
                
                $('body').append(alertHtml);
                $('.custom-alert').css('opacity', '1');
                
                // Highlight the upload area
                $('.profile-photo-container').addClass('error-shake');
                $('.profile-photo-placeholder').addClass('error');
                
                // Scroll to photo placeholder
                $('html, body').animate({
                    scrollTop: $('.profile-photo-container').offset().top - 100
                }, 500);
                
                // Remove shake after 500ms
                setTimeout(() => {
                    $('.profile-photo-container').removeClass('error-shake');
                }, 500);
                
                // Remove alert and error state after 3 seconds
                setTimeout(() => {
                    $('.custom-alert').fadeOut(function() {
                        $(this).remove();
                    });
                    $('.profile-photo-placeholder').removeClass('error');
                }, 3000);
            } else if (!allowedTypes.includes(file.type)) {
                isValid = false;
                const alertHtml = `
                    <div class="custom-alert" style="
                        position: fixed;
                        top: 20px;
                        left: 50%;
                        transform: translateX(-50%);
                        background-color: #ff4444;
                        color: white;
                        padding: 15px 30px;
                        border-radius: 5px;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                        z-index: 9999;
                        opacity: 0;
                        transition: opacity 0.3s ease;
                    ">Please upload a valid image (JPEG or PNG)</div>`;
                
                $('body').append(alertHtml);
                $('.custom-alert').css('opacity', '1');
                
                $('.profile-photo-container').addClass('error-shake');
                $('.profile-photo-placeholder').addClass('error');
                
                $('html, body').animate({
                    scrollTop: $('.profile-photo-container').offset().top - 100
                }, 500);
                
                setTimeout(() => {
                    $('.profile-photo-container').removeClass('error-shake');
                }, 500);
                
                setTimeout(() => {
                    $('.custom-alert').fadeOut(function() {
                        $(this).remove();
                    });
                    $('.profile-photo-placeholder').removeClass('error');
                }, 3000);
                resetFileInput();
            } else if (file.size > maxSize) {
                isValid = false;
                const alertHtml = `
                    <div class="custom-alert" style="
                        position: fixed;
                        top: 20px;
                        left: 50%;
                        transform: translateX(-50%);
                        background-color: #ff4444;
                        color: white;
                        padding: 15px 30px;
                        border-radius: 5px;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                        z-index: 9999;
                        opacity: 0;
                        transition: opacity 0.3s ease;
                    ">File size exceeds 5MB limit</div>`;

                $('body').append(alertHtml);
                $('.custom-alert').css('opacity', '1');

                $('.profile-photo-container').addClass('error-shake');
                $('.profile-photo-placeholder').addClass('error');

                $('html, body').animate({
                    scrollTop: $('.profile-photo-container').offset().top - 100
                }, 500);

                setTimeout(() => {
                    $('.profile-photo-container').removeClass('error-shake');
                }, 500);

                setTimeout(() => {
                    $('.custom-alert').fadeOut(function() {
                        $(this).remove();
                    });
                    $('.profile-photo-placeholder').removeClass('error');
                }, 3000);
                resetFileInput();
            }

            return isValid;
        }

        // Bind validation to input events
        $('#firstName').on('input', validateFirstName);
        $('#lastName').on('input', validateLastName);
        $('#sex').on('change', validateSex);
        $('#birthday').on('change', validateBirthday);
        $('#phone').on('input', validatePhoneNumber);
        $('#username').on('input', validateUsername);
        $('#password').on('input', validatePassword);
        $('#confirmPassword').on('input', validateConfirmPassword);
    
    // File upload handling
    $('.btn-upload-trigger').on('click', function() {
        $('#profile_photo').click();
    });

    $('#profile_photo').on('change', function(event) {
        handleFileUpload(event);
    });

    // Form submission validation
    $('#registrationForm').on('submit', function(e) {
            e.preventDefault();

            const isProfilePhotoValid = validateProfilePhoto();
            const isFirstNameValid = validateFirstName();
            const isLastNameValid = validateLastName();
            const isSexValid = validateSex();
            const isBirthdayValid = validateBirthday();
            // const isPhoneValid = validatePhoneNumber();
            const isUsernameValid = validateUsername();
            const isPasswordValid = validatePassword();
            const isConfirmPasswordValid = validateConfirmPassword();

            if (isProfilePhotoValid && 
                isFirstNameValid && 
                isLastNameValid && 
                isSexValid && 
                isBirthdayValid && 
                // isPhoneValid && 
                isUsernameValid && 
                isPasswordValid && 
                isConfirmPasswordValid) {
                // Existing form submission logic from the original script
                var formData = new FormData(this);
                formData.append('action', 'validate');
                
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.text();
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
                
                $.ajax({
                    url: '../register/register_handler.php',
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
            }
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
                    if (response.redirect) {
                        // Redirect directly to website instead of showing success modal
                        window.location.href = response.redirect;
                    } else {
                        // Fallback to the original success modal behavior
                        hideModal('.verification-modal');
                        setTimeout(() => {
                            showModal('.success-modal');
                        }, 300);
                    }
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
        window.location.href = '../website/website.php'; // Changed from '../login/login.php'
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

    // function validateProfilePhoto() {
    //     const fileInput = $('#profile_photo')[0];
    //     const file = fileInput.files[0];
        
    //     if (!file) {
    //         alert('Please upload a profile photo.');
    //         return false;
    //     }
    //     return true;
    // }

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