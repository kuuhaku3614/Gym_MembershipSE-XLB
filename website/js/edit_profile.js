document.addEventListener('DOMContentLoaded', function() {
    const editProfileLink = document.querySelector('.edit-profile-link');
    const profileModal = document.getElementById('profileEditModal');
    const closeModalButton = document.querySelector('.close-modal');
    const photoInput = document.getElementById('photoInput');
    const profilePhoto = document.getElementById('profilePhoto');
    const usernameInput = document.getElementById('usernameInput');
    const saveButton = document.getElementById('saveChanges');
    const cancelButton = document.getElementById('cancelChanges');
    
    // Track if changes were made
    let photoChanged = false;
    let usernameChanged = false;
    let originalUsername = usernameInput ? usernameInput.value : '';
    let originalPhotoSrc = profilePhoto ? profilePhoto.src : '';
    let newPhotoFile = null;
    let isLoading = false;

    // Store original values when modal opens
    function storeOriginalValues() {
        if (profilePhoto) {
            originalPhotoSrc = profilePhoto.src;
        }
        if (usernameInput) {
            originalUsername = usernameInput.value;
        }
    }

    // Function to determine the correct path for API calls
    function getCorrectPath() {
        const currentUrl = window.location.pathname;
        
        // Check if we're in the coach section
        if (currentUrl.includes('/coach/')) {
            return '../../website/includes/update_profile.php';
        } else {
            return 'includes/update_profile.php';
        }
    }
    
    // Open modal when Edit Profile is clicked
    if (editProfileLink) {
        editProfileLink.addEventListener('click', function(e) {
            e.preventDefault();
            storeOriginalValues();
            profileModal.style.display = 'block';
            document.body.classList.add('modal-open');
        });
    }
    
    // Close modal when X is clicked
    if (closeModalButton) {
        closeModalButton.addEventListener('click', function() {
            profileModal.style.display = 'none';
            document.body.classList.remove('modal-open');
            resetChanges();
        });
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === profileModal) {
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
    
    // Check if username is available
    async function checkUsername(username) {
        try {
            const apiUrl = getCorrectPath();
            const formData = new FormData();
            formData.append('action', 'check_username');
            formData.append('username', username);
            
            const response = await fetch(apiUrl, {
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
            if (e.target.files && e.target.files[0]) {
                const file = e.target.files[0];
                
                // Check file size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    showNotification('error', 'Image file size must be less than 5MB');
                    photoInput.value = '';
                    return;
                }
                
                // Check file type
                if (!file.type.match('image.*')) {
                    showNotification('error', 'Only image files are allowed');
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
                showNotification('error', 'No changes to save');
                return;
            }
            
            if (isLoading) return;
            
            // Validate username if changed
            if (usernameChanged) {
                const username = usernameInput.value.trim();
                
                if (username === '') {
                    showNotification('error', 'Username cannot be empty');
                    return;
                }
                
                const isAvailable = await checkUsername(username);
                if (!isAvailable) {
                    showNotification('error', 'Username already taken');
                    return;
                }
            }
            
            // Start loading animation
            setLoading(true);
            
            try {
                const apiUrl = getCorrectPath();
                const formData = new FormData();
                
                if (photoChanged && newPhotoFile) {
                    formData.append('action', 'update_photo');
                    formData.append('photo', newPhotoFile);
                    
                    const photoResponse = await fetch(apiUrl, {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (!photoResponse.ok) {
                        throw new Error(`HTTP error! status: ${photoResponse.status}`);
                    }
                    
                    const photoData = await photoResponse.json();
                    
                    if (photoData.status !== 'success') {
                        throw new Error(photoData.message || 'Failed to update photo');
                    }
                }
                
                if (usernameChanged) {
                    const usernameFormData = new FormData();
                    usernameFormData.append('action', 'update_username');
                    usernameFormData.append('username', usernameInput.value.trim());
                    
                    const usernameResponse = await fetch(apiUrl, {
                        method: 'POST',
                        body: usernameFormData
                    });
                    
                    if (!usernameResponse.ok) {
                        throw new Error(`HTTP error! status: ${usernameResponse.status}`);
                    }
                    
                    const usernameData = await usernameResponse.json();
                    
                    if (usernameData.status !== 'success') {
                        throw new Error(usernameData.message || 'Failed to update username');
                    }
                }
                
                // Success - all updates completed
                showNotification('success', 'Profile updated successfully');
                
                // Reset change flags
                photoChanged = false;
                usernameChanged = false;
                originalUsername = usernameInput.value;
                updateSaveButtonState();
                
                // Close the modal after a delay
                setTimeout(() => {
                    profileModal.style.display = 'none';
                    document.body.classList.remove('modal-open');
                    // Reload page to reflect changes
                    window.location.reload();
                }, 1500);
                
            } catch (error) {
                console.error('Error saving profile changes:', error);
                showNotification('error', 'Error updating profile. Please try again later.');
            } finally {
                setLoading(false);
            }
        });
    }
    
    // Cancel changes
    if (cancelButton) {
        cancelButton.addEventListener('click', function() {
            resetChanges();
            profileModal.style.display = 'none';
            document.body.classList.remove('modal-open');
        });
    }
    
    // Reset any changes
    function resetChanges() {
        if (photoChanged && profilePhoto) {
            // Restore original photo
            profilePhoto.src = originalPhotoSrc;
            photoChanged = false;
            
            // Reset file input
            if (photoInput) {
                photoInput.value = '';
            }
        }
        
        if (usernameChanged && usernameInput) {
            // Restore original username
            usernameInput.value = originalUsername;
            usernameChanged = false;
        }
        
        updateSaveButtonState();
    }
    
    // Update save button state
    function updateSaveButtonState() {
        if (saveButton) {
            const hasChanges = photoChanged || usernameChanged;
            saveButton.disabled = !hasChanges || isLoading;
            
            if (hasChanges && !isLoading) {
                saveButton.classList.add('active');
            } else {
                saveButton.classList.remove('active');
            }
        }
    }
    
    // Show notification message
    function showNotification(type, message) {
        const containerClass = type === 'error' ? 'profile-error-message' : 'profile-success-message';
        
        // Create notification element if it doesn't exist
        let notificationElement = document.querySelector('.' + containerClass);
        if (!notificationElement) {
            notificationElement = document.createElement('div');
            notificationElement.className = containerClass;
            const modalContent = document.querySelector('.profile-edit-container');
            modalContent.insertBefore(notificationElement, modalContent.firstChild);
        }
        
        notificationElement.textContent = message;
        notificationElement.style.display = 'block';
        
        // Hide after 3 seconds
        setTimeout(() => {
            notificationElement.style.display = 'none';
        }, 3000);
    }
    
    // Handle loading state for the save button
    function setLoading(loading) {
        isLoading = loading;
        
        if (saveButton) {
            if (loading) {
                saveButton.classList.add('loading');
                saveButton.innerHTML = '<span class="spinner"></span> Saving...';
            } else {
                saveButton.classList.remove('loading');
                saveButton.innerHTML = 'Save Changes';
            }
            
            updateSaveButtonState();
        }
    }
    
    // Initialize save button state
    updateSaveButtonState();
});