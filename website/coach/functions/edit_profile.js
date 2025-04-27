document.addEventListener('DOMContentLoaded', function() {
    const editProfileLink = document.querySelector('.edit-profile-link');
    const profileModal = document.getElementById('profileEditModal');
    const closeModalButton = document.querySelector('.close-modal');
    const photoInput = document.getElementById('photoInput');
    const profilePhoto = document.getElementById('profilePhoto');
    const profileUsername = document.getElementById('profile-username');
    const profileFullName = document.getElementById('profile-full-name');
    const usernameInput = document.getElementById('usernameInput');
    const saveButton = document.getElementById('saveChanges');
    const cancelButton = document.getElementById('cancelChanges');
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    
    // Track if changes were made
    let photoChanged = false;
    let personalDetailsChanged = false;
    let usernameChanged = false;
    let originalUsername = usernameInput ? usernameInput.value : '';
    let originalPhotoSrc = profilePhoto ? profilePhoto.src : '';
    let originalPersonalDetails = {
        first_name: document.getElementById('first-name') ? document.getElementById('first-name').value : '',
        last_name: document.getElementById('last-name') ? document.getElementById('last-name').value : '',
        sex: document.getElementById('sex') ? document.getElementById('sex').value : '',
        birthdate: document.getElementById('birthdate') ? document.getElementById('birthdate').value : '',
        phone_number: document.getElementById('phone_number') ? document.getElementById('phone_number').value : ''
    };
    let newPhotoFile = null;
    let isLoading = false;
    let lastPasswordChange = null;

    // Password toggle functionality
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                passwordInput.type = 'password';
                this.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
    });

    // Load profile data from the database
    function loadProfileData() {
        const formData = new FormData();
        formData.append('action', 'get_profile_data');
        
        fetch('/website/coach/functions/update_profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                updateUIWithProfileData(data.user_data);
                
                // Store original values after loading
                if (profilePhoto) {
                    originalPhotoSrc = profilePhoto.src;
                }
                if (usernameInput) {
                    originalUsername = usernameInput.value;
                }
                
                // Store original personal details
                originalPersonalDetails = {
                    first_name: document.getElementById('first-name') ? document.getElementById('first-name').value : '',
                    last_name: document.getElementById('last-name') ? document.getElementById('last-name').value : '',
                    sex: document.getElementById('sex') ? document.getElementById('sex').value : '',
                    birthdate: document.getElementById('birthdate') ? document.getElementById('birthdate').value : '',
                    phone_number: document.getElementById('phone_number') ? document.getElementById('phone_number').value : ''
                };

                // Store last password change date
                if (data.user_data.last_password_change) {
                    lastPasswordChange = new Date(data.user_data.last_password_change);
                    
                    // Check if password was changed in the last month
                    const now = new Date();
                    const diffDays = Math.floor((now - lastPasswordChange) / (1000 * 60 * 60 * 24));
                    
                    // If password change is not yet allowed, disable password fields and show a message
                    if (diffDays < 30) {
                        const securityTab = document.getElementById('security-tab');
                        if (securityTab) {
                            const passwordInfoMessage = document.createElement('div');
                            passwordInfoMessage.className = 'password-info-message alert alert-info';
                            
                            const daysRemaining = 30 - diffDays;
                            passwordInfoMessage.textContent = `Password can only be changed once every 30 days. You can change your password in ${daysRemaining} days.`;
                            
                            const updateSecurityButton = document.getElementById('updateSecurity');
                            if (updateSecurityButton) {
                                updateSecurityButton.disabled = true;
                                updateSecurityButton.parentNode.insertBefore(passwordInfoMessage, updateSecurityButton);
                            }
                        }
                    }
                }
            } else {
                console.error('Error loading profile data:', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
    
    // Update UI with profile data
    function updateUIWithProfileData(userData) {
        // Update profile photo
        if (profilePhoto && userData.photo_path) {
            profilePhoto.src = userData.photo_path;
        }
        
        // Update username display
        if (profileUsername && userData.username) {
            profileUsername.textContent = '@' + userData.username;
        }
        
        // Update username input
        if (usernameInput && userData.username) {
            usernameInput.value = userData.username;
        }
        
        // Update full name display
        if (profileFullName && userData.first_name && userData.last_name) {
            profileFullName.textContent = userData.first_name + ' ' + userData.last_name;
        }
        
        // Update first name input
        const firstNameInput = document.getElementById('first-name');
        if (firstNameInput && userData.first_name) {
            firstNameInput.value = userData.first_name;
        }
        
        // Update last name input
        const lastNameInput = document.getElementById('last-name');
        if (lastNameInput && userData.last_name) {
            lastNameInput.value = userData.last_name;
        }
        
        // Update sex select
        const sexSelect = document.getElementById('sex');
        if (sexSelect && userData.sex) {
            sexSelect.value = userData.sex;
        }
        
        // Update birthdate input
        const birthdateInput = document.getElementById('birthdate');
        if (birthdateInput && userData.birthdate) {
            birthdateInput.value = userData.birthdate;
        }
        
        // Update phone number input
        const phoneInput = document.getElementById('phone_number');
        if (phoneInput && userData.phone_number) {
            phoneInput.value = userData.phone_number;
        }
    }

    // Load profile data on page load
    loadProfileData();

    // Store original values when modal opens
    function storeOriginalValues() {
        if (profilePhoto) {
            originalPhotoSrc = profilePhoto.src;
        }
        if (usernameInput) {
            originalUsername = usernameInput.value;
        }
        
        // Store original personal details
        originalPersonalDetails = {
            first_name: document.getElementById('first-name') ? document.getElementById('first-name').value : '',
            last_name: document.getElementById('last-name') ? document.getElementById('last-name').value : '',
            sex: document.getElementById('sex') ? document.getElementById('sex').value : '',
            birthdate: document.getElementById('birthdate') ? document.getElementById('birthdate').value : '',
            phone_number: document.getElementById('phone_number') ? document.getElementById('phone_number').value : ''
        };
    }
    
    // Open modal when Edit Profile is clicked
    if (editProfileLink) {
        editProfileLink.addEventListener('click', function(e) {
            e.preventDefault();
            storeOriginalValues();
            if (profileModal) {
                profileModal.style.display = 'block';
                document.body.classList.add('modal-open');
            }
        });
    }
    
    // Close modal when X is clicked
    if (closeModalButton) {
        closeModalButton.addEventListener('click', function() {
            if (profileModal) {
                profileModal.style.display = 'none';
                document.body.classList.remove('modal-open');
            }
            resetChanges();
        });
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (profileModal && e.target === profileModal) {
            profileModal.style.display = 'none';
            document.body.classList.remove('modal-open');
            resetChanges();
        }
    });
    
    // Handle username input changes
    if (usernameInput) {
        usernameInput.addEventListener('input', function() {
            usernameChanged = usernameInput.value !== originalUsername;
            updateSaveButtonState();
        });
        
        // Validate username when Enter key is pressed
        usernameInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (saveButton && !saveButton.disabled && !isLoading) {
                    saveButton.click();
                }
            }
        });
    }
    
    // Handle personal details changes
    const personalDetailsInputs = [
        document.getElementById('first-name'),
        document.getElementById('last-name'),
        document.getElementById('sex'),
        document.getElementById('birthdate'),
        document.getElementById('phone_number')
    ];
    
    personalDetailsInputs.forEach(input => {
        if (input) {
            input.addEventListener('change', checkPersonalDetailsChanged);
            input.addEventListener('input', checkPersonalDetailsChanged);
        }
    });
    
    function checkPersonalDetailsChanged() {
        const currentDetails = {
            first_name: document.getElementById('first-name') ? document.getElementById('first-name').value : '',
            last_name: document.getElementById('last-name') ? document.getElementById('last-name').value : '',
            sex: document.getElementById('sex') ? document.getElementById('sex').value : '',
            birthdate: document.getElementById('birthdate') ? document.getElementById('birthdate').value : '',
            phone_number: document.getElementById('phone_number') ? document.getElementById('phone_number').value : ''
        };
        
        personalDetailsChanged = 
            currentDetails.first_name !== originalPersonalDetails.first_name ||
            currentDetails.last_name !== originalPersonalDetails.last_name ||
            currentDetails.sex !== originalPersonalDetails.sex ||
            currentDetails.birthdate !== originalPersonalDetails.birthdate ||
            currentDetails.phone_number !== originalPersonalDetails.phone_number;
        
        updateSaveButtonState();
    }
    
    // Check if username is available
    async function checkUsername(username) {
        try {
            const formData = new FormData();
            formData.append('action', 'check_username');
            formData.append('username', username);
            
            const response = await fetch('/website/coach/functions/update_profile.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            return !data.exists;
        } catch (error) {
            console.error('Error checking username:', error);
            return true; // Don't block the user if it's just a server error
        }
    }
    
    // Handle photo input changes
    if (photoInput) {
        photoInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                
                if (!validTypes.includes(file.type)) {
                    showMessage('error', 'Invalid file type. Please upload an image (JPEG, PNG, or GIF).');
                    photoInput.value = '';
                    return;
                }
                
                if (file.size > 5000000) { // 5MB limit
                    showMessage('error', 'File size exceeds 5MB. Please upload a smaller image.');
                    photoInput.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (profilePhoto) {
                        profilePhoto.src = e.target.result;
                    }
                    photoChanged = true;
                    newPhotoFile = file;
                    updateSaveButtonState();
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Update save button state based on changes
    function updateSaveButtonState() {
        if (saveButton) {
            const hasChanges = photoChanged || usernameChanged || personalDetailsChanged;
            saveButton.disabled = !hasChanges || isLoading;
            
            if (hasChanges && !isLoading) {
                saveButton.classList.add('active');
            } else {
                saveButton.classList.remove('active');
            }
        }
    }

    // Reset changes when canceled
    function resetChanges() {
        // Reset photo
        if (profilePhoto) {
            profilePhoto.src = originalPhotoSrc;
        }
        
        // Reset file input
        if (photoInput) {
            photoInput.value = '';
        }
        
        // Reset username
        if (usernameInput) {
            usernameInput.value = originalUsername;
        }
        
        // Reset personal details
        if (document.getElementById('first-name')) {
            document.getElementById('first-name').value = originalPersonalDetails.first_name;
        }
        if (document.getElementById('last-name')) {
            document.getElementById('last-name').value = originalPersonalDetails.last_name;
        }
        if (document.getElementById('sex')) {
            document.getElementById('sex').value = originalPersonalDetails.sex;
        }
        if (document.getElementById('birthdate')) {
            document.getElementById('birthdate').value = originalPersonalDetails.birthdate;
        }
        if (document.getElementById('phone_number')) {
            document.getElementById('phone_number').value = originalPersonalDetails.phone_number;
        }
        
        // Reset flags
        photoChanged = false;
        usernameChanged = false;
        personalDetailsChanged = false;
        newPhotoFile = null;
        
        // Update buttons
        updateSaveButtonState();
        
        // Clear messages
        clearMessages();
    }

    // Show success or error message
    function showMessage(type, message) {
        const containerClass = type === 'error' ? 'profile-error-message' : 'profile-success-message';
        
        // Create or get notification element
        let notificationElement = document.querySelector('.' + containerClass);
        if (!notificationElement) {
            notificationElement = document.createElement('div');
            notificationElement.className = containerClass;
            const modalContent = document.querySelector('.profile-edit-container');
            if (modalContent) {
                modalContent.insertBefore(notificationElement, modalContent.firstChild);
            } else {
                // If the container doesn't exist, create a fallback notification
                notificationElement.style.position = 'fixed';
                notificationElement.style.top = '20px';
                notificationElement.style.right = '20px';
                notificationElement.style.zIndex = '9999';
                notificationElement.style.padding = '10px 20px';
                notificationElement.style.borderRadius = '4px';
                notificationElement.style.backgroundColor = type === 'error' ? '#f44336' : '#4CAF50';
                notificationElement.style.color = 'white';
                document.body.appendChild(notificationElement);
            }
        }
        
        notificationElement.textContent = message;
        notificationElement.style.display = 'block';
        
        // Hide after 5 seconds
        setTimeout(() => {
            notificationElement.style.display = 'none';
        }, 5000);
    }

    // Clear all messages
    function clearMessages() {
        const successMessage = document.querySelector('.profile-success-message');
        const errorMessage = document.querySelector('.profile-error-message');
        
        if (successMessage) {
            successMessage.style.display = 'none';
        }
        if (errorMessage) {
            errorMessage.style.display = 'none';
        }
    }

    // Update username
    async function updateUsername() {
        if (!usernameChanged) {
            return true; // No changes to update
        }
        
        const username = usernameInput.value.trim();
        
        if (username.length < 3) {
            showMessage('error', 'Username must be at least 3 characters long.');
            return false;
        }
        
        // Check if username is available
        const isAvailable = await checkUsername(username);
        if (!isAvailable) {
            showMessage('error', 'Username is already taken. Please choose another one.');
            return false;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'update_username');
            formData.append('username', username);
            
            const response = await fetch('/website/coach/functions/update_profile.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.status === 'success') {
                if (profileUsername) {
                    profileUsername.textContent = '@' + username;
                }
                originalUsername = username;
                usernameChanged = false;
                return true;
            } else {
                showMessage('error', data.message || 'Error updating username');
                return false;
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('error', 'Error updating username. Please try again.');
            return false;
        }
    }

    // Update photo
    async function updatePhoto() {
        if (!photoChanged || !newPhotoFile) {
            return true; // No changes to update
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'update_photo');
            formData.append('photo', newPhotoFile);
            
            const response = await fetch('/website/coach/functions/update_profile.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.status === 'success') {
                if (profilePhoto && data.path) {
                    originalPhotoSrc = data.path;
                }
                photoChanged = false;
                newPhotoFile = null;
                return true;
            } else {
                showMessage('error', data.message || 'Error updating photo');
                return false;
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('error', 'Error updating photo. Please try again.');
            return false;
        }
    }

    // Update personal details
    async function updatePersonalDetails() {
        if (!personalDetailsChanged) {
            return true; // No changes to update
        }
        
        const firstName = document.getElementById('first-name') ? document.getElementById('first-name').value.trim() : '';
        const lastName = document.getElementById('last-name') ? document.getElementById('last-name').value.trim() : '';
        const sex = document.getElementById('sex') ? document.getElementById('sex').value : '';
        const birthdate = document.getElementById('birthdate') ? document.getElementById('birthdate').value : '';
        const phoneNumber = document.getElementById('phone_number') ? document.getElementById('phone_number').value.trim() : '';
        
        try {
            const formData = new FormData();
            formData.append('action', 'update_personal_details');
            formData.append('first_name', firstName);
            formData.append('last_name', lastName);
            formData.append('sex', sex);
            formData.append('birthdate', birthdate);
            formData.append('phone_number', phoneNumber);
            
            const response = await fetch('/website/coach/functions/update_profile.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.status === 'success') {
                // Update full name display
                if (profileFullName) {
                    profileFullName.textContent = firstName + ' ' + lastName;
                }
                
                // Update original values
                originalPersonalDetails = {
                    first_name: firstName,
                    last_name: lastName,
                    sex: sex,
                    birthdate: birthdate,
                    phone_number: phoneNumber
                };
                
                personalDetailsChanged = false;
                return true;
            } else {
                showMessage('error', data.message || 'Error updating personal details');
                return false;
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('error', 'Error updating personal details. Please try again.');
            return false;
        }
    }

    // Set loading state for the save button
    function setLoading(isLoading) {
        if (saveButton) {
            if (isLoading) {
                saveButton.disabled = true;
                saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
                saveButton.classList.add('loading');
            } else {
                saveButton.innerHTML = 'Save Changes';
                saveButton.classList.remove('loading');
                updateSaveButtonState();
            }
        }
    }

    // Handle save button click
    if (saveButton) {
        saveButton.addEventListener('click', async function() {
            if (isLoading) {
                return;
            }
            
            // Check if anything has changed
            if (!photoChanged && !usernameChanged && !personalDetailsChanged) {
                showMessage('error', 'No changes to save');
                return;
            }
            
            clearMessages();
            setLoading(true);
            
            try {
                // Update in sequence - username, photo, personal details
                const usernameUpdated = await updateUsername();
                if (!usernameUpdated) {
                    throw new Error('Username update failed');
                }
                
                const photoUpdated = await updatePhoto();
                if (!photoUpdated) {
                    throw new Error('Photo update failed');
                }
                
                const personalDetailsUpdated = await updatePersonalDetails();
                if (!personalDetailsUpdated) {
                    throw new Error('Personal details update failed');
                }
                
                // All updates successful
                showMessage('success', 'Profile updated successfully');

                // After successful update, consider either reloading or closing the modal
                setTimeout(() => {
                    // Check if profileModal exists before trying to access its style property
                    if (profileModal) {
                        profileModal.style.display = 'none';
                        document.body.classList.remove('modal-open');
                    }
                    
                    // Optionally reload page to reflect all changes
                    window.location.reload();
                }, 50);
                
            } catch (error) {
                console.error('Error saving changes:', error);
                if (!document.querySelector('.profile-error-message') || document.querySelector('.profile-error-message').style.display === 'none') {
                    showMessage('error', 'Error saving changes. Please try again.');
                }
            } finally {
                setLoading(false);
            }
        });
    }

    // Handle cancel button click
    if (cancelButton) {
        cancelButton.addEventListener('click', function() {
            resetChanges();
            if (profileModal) {
                profileModal.style.display = 'none';
                document.body.classList.remove('modal-open');
            }
        });
    }

    // Handle security tab - password update
    const updateSecurityButton = document.getElementById('updateSecurity');
    if (updateSecurityButton) {
        updateSecurityButton.addEventListener('click', async function() {
            const currentPassword = document.getElementById('current-password').value;
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            // Clear previous messages
            clearMessages();
            
            // Validate inputs
            if (!currentPassword) {
                showMessage('error', 'Please enter your current password');
                return;
            }
            
            if (!newPassword) {
                showMessage('error', 'Please enter a new password');
                return;
            }
            
            if (newPassword.length < 8) {
                showMessage('error', 'New password must be at least 8 characters long');
                return;
            }
            
            // Check if password contains uppercase, lowercase and number
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
            if (!passwordRegex.test(newPassword)) {
                showMessage('error', 'Password must contain at least one uppercase letter, one lowercase letter, and one number');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                showMessage('error', 'New password and confirmation do not match');
                return;
            }
            
            // Update password
            try {
                updateSecurityButton.disabled = true;
                updateSecurityButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
                
                const formData = new FormData();
                formData.append('action', 'update_password');
                formData.append('current_password', currentPassword);
                formData.append('new_password', newPassword);
                
                const response = await fetch('/website/coach/functions/update_profile.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    showMessage('success', 'Password updated successfully');
                    document.getElementById('current-password').value = '';
                    document.getElementById('new-password').value = '';
                    document.getElementById('confirm-password').value = '';
                } else {
                    showMessage('error', data.message || 'Error updating password');
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('error', 'Error updating password. Please try again.');
            } finally {
                updateSecurityButton.disabled = false;
                updateSecurityButton.innerHTML = 'Update Security Settings';
            }
        });
    }
    
    // Initialize save button state
    updateSaveButtonState();
});