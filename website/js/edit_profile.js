document.addEventListener('DOMContentLoaded', function() {
    const editProfileLink = document.querySelector('.edit-profile-link');
    const profileModal = document.getElementById('profileEditModal');
    const closeModalButton = document.querySelector('.close-modal');
    const photoInput = document.getElementById('photoInput');
    const profilePhoto = document.getElementById('profilePhoto');
    const usernameInput = document.getElementById('usernameInput');
    const editUsernameBtn = document.querySelector('.edit-username-btn');
    const saveButton = document.getElementById('saveChanges');
    const cancelButton = document.getElementById('cancelChanges');
    
    // Track if changes were made
    let photoChanged = false;
    let usernameChanged = false;
    let originalUsername = usernameInput ? usernameInput.value : '';
    let newPhotoFile = null;
    
    // Function to determine the correct path for API calls
    function getCorrectPath() {
        const currentUrl = window.location.pathname;
        console.log('Current page URL:', window.location.href);
        
        // Check if we're in the coach section
        if (currentUrl.includes('/coach/')) {
            return '../includes/update_profile.php';
        } else {
            return 'includes/update_profile.php';
        }
    }
    
    // Open modal when Edit Profile is clicked
    if (editProfileLink) {
        editProfileLink.addEventListener('click', function(e) {
            e.preventDefault();
            profileModal.style.display = 'block';
        });
    }
    
    // Close modal when X is clicked
    if (closeModalButton) {
        closeModalButton.addEventListener('click', function() {
            profileModal.style.display = 'none';
            resetChanges();
        });
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === profileModal) {
            profileModal.style.display = 'none';
            resetChanges();
        }
    });
    
    // Toggle username input readonly state
    if (editUsernameBtn) {
        editUsernameBtn.addEventListener('click', function() {
            if (usernameInput.readOnly) {
                usernameInput.readOnly = false;
                usernameInput.focus();
                editUsernameBtn.innerHTML = '<i class="fas fa-check"></i>';
            } else {
                // Validate and save the new username
                validateUsername();
            }
        });
    }
    
    // Handle username input changes
    if (usernameInput) {
        usernameInput.addEventListener('input', function() {
            if (usernameInput.value !== originalUsername) {
                usernameChanged = true;
            } else {
                usernameChanged = false;
            }
            updateSaveButtonState();
        });
        
        // Validate username when Enter key is pressed
        usernameInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !usernameInput.readOnly) {
                e.preventDefault();
                validateUsername();
            }
        });
    }
    
    // Validate username
    async function validateUsername() {
        const newUsername = usernameInput.value.trim();
        
        if (newUsername === '') {
            showError('Username cannot be empty');
            return;
        }
        
        if (newUsername === originalUsername) {
            usernameInput.readOnly = true;
            editUsernameBtn.innerHTML = '<i class="fas fa-pencil-alt"></i>';
            return;
        }
        
        try {
            const valid = await checkUsername(newUsername);
            if (valid) {
                usernameInput.readOnly = true;
                editUsernameBtn.innerHTML = '<i class="fas fa-pencil-alt"></i>';
                usernameChanged = true;
                updateSaveButtonState();
            }
        } catch (error) {
            showError('Error validating username');
            console.error('Error validating username:', error);
        }
    }
    
    // Check if username is available
    async function checkUsername(username) {
        const apiUrl = getCorrectPath();
        console.log('Attempting to fetch from:', apiUrl);
        
        try {
            const formData = new FormData();
            formData.append('action', 'check_username');
            formData.append('username', username);
            
            const response = await fetch(apiUrl, {
                method: 'POST',
                body: formData
            });
            
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.available) {
                return true;
            } else {
                showError('Username already taken');
                return false;
            }
        } catch (error) {
            console.log('Detailed error checking username:', error);
            console.log('Error stack:', error.stack);
            // Don't block the user if it's just a server error
            return true;
        }
    }
    
    // Handle photo input changes
    if (photoInput) {
        photoInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const file = e.target.files[0];
                
                // Check file size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    showError('Image file size must be less than 5MB');
                    photoInput.value = '';
                    return;
                }
                
                // Check file type
                if (!file.type.match('image.*')) {
                    showError('Only image files are allowed');
                    photoInput.value = '';
                    return;
                }
                
                // Preview the image
                const reader = new FileReader();
                reader.onload = function(e) {
                    profilePhoto.src = e.target.result;
                    photoChanged = true;
                    newPhotoFile = file;
                    updateSaveButtonState();
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Save changes
    if (saveButton) {
        saveButton.addEventListener('click', async function() {
            if (!photoChanged && !usernameChanged) {
                showError('No changes to save');
                return;
            }
            
            try {
                const apiUrl = getCorrectPath();
                
                const formData = new FormData();
                formData.append('action', 'update_profile');
                
                if (photoChanged && newPhotoFile) {
                    formData.append('photo', newPhotoFile);
                }
                
                if (usernameChanged) {
                    formData.append('username', usernameInput.value.trim());
                }
                
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    showSuccess('Profile updated successfully');
                    
                    // If username was changed, update it in the UI
                    if (usernameChanged) {
                        originalUsername = usernameInput.value;
                    }
                    
                    // Reset change flags
                    photoChanged = false;
                    usernameChanged = false;
                    updateSaveButtonState();
                    
                    // Close the modal after a delay
                    setTimeout(() => {
                        profileModal.style.display = 'none';
                        // Reload page to reflect changes
                        window.location.reload();
                    }, 1500);
                } else {
                    showError(data.message || 'Failed to update profile');
                }
            } catch (error) {
                console.error('Error saving profile changes:', error);
                showError('Error updating profile. Please try again later.');
            }
        });
    }
    
    // Cancel changes
    if (cancelButton) {
        cancelButton.addEventListener('click', function() {
            resetChanges();
            profileModal.style.display = 'none';
        });
    }
    
    // Reset any changes
    function resetChanges() {
        if (photoChanged) {
            // Restore original photo
            const originalPhotoSrc = profilePhoto.getAttribute('data-original') || profilePhoto.src;
            profilePhoto.src = originalPhotoSrc;
            photoChanged = false;
        }
        
        if (usernameChanged) {
            // Restore original username
            usernameInput.value = originalUsername;
            usernameChanged = false;
        }
        
        if (usernameInput) {
            usernameInput.readOnly = true;
        }
        
        if (editUsernameBtn) {
            editUsernameBtn.innerHTML = '<i class="fas fa-pencil-alt"></i>';
        }
        
        updateSaveButtonState();
    }
    
    // Update save button state
    function updateSaveButtonState() {
        if (saveButton) {
            if (photoChanged || usernameChanged) {
                saveButton.classList.add('active');
                saveButton.disabled = false;
            } else {
                saveButton.classList.remove('active');
                saveButton.disabled = true;
            }
        }
    }
    
    // Show error message
    function showError(message) {
        // Create error element if it doesn't exist
        let errorElement = document.querySelector('.profile-error-message');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'profile-error-message';
            const modalContent = document.querySelector('.profile-edit-container');
            modalContent.insertBefore(errorElement, modalContent.firstChild);
        }
        
        errorElement.textContent = message;
        errorElement.style.display = 'block';
        
        // Hide after 3 seconds
        setTimeout(() => {
            errorElement.style.display = 'none';
        }, 3000);
    }
    
    // Show success message
    function showSuccess(message) {
        // Create success element if it doesn't exist
        let successElement = document.querySelector('.profile-success-message');
        if (!successElement) {
            successElement = document.createElement('div');
            successElement.className = 'profile-success-message';
            const modalContent = document.querySelector('.profile-edit-container');
            modalContent.insertBefore(successElement, modalContent.firstChild);
        }
        
        successElement.textContent = message;
        successElement.style.display = 'block';
        
        // Hide after 3 seconds
        setTimeout(() => {
            successElement.style.display = 'none';
        }, 3000);
    }
    
    // Initialize save button state
    updateSaveButtonState();
});