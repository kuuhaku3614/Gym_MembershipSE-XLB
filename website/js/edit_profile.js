document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('profileEditModal');
    const closeBtn = document.querySelector('.close-modal');
    const editProfileLink = document.querySelector('a[href=""]');
    const usernameInput = document.getElementById('usernameInput');
    const editUsernameBtn = document.querySelector('.edit-username-btn');
    const photoInput = document.getElementById('photoInput');
    const saveActions = document.querySelector('.save-actions');
    const saveBtn = document.getElementById('saveChanges');
    const cancelBtn = document.getElementById('cancelChanges');
    const inputWrapper = document.querySelector('.input-wrapper');
    
    let originalUsername = usernameInput.value;
    let originalPhoto = document.getElementById('profilePhoto').src;
    let hasChanges = false;
    let newPhotoFile = null;
    let isEditing = false;

    // Show modal with animation
    editProfileLink.addEventListener('click', function(e) {
        e.preventDefault();
        modal.style.display = 'flex';
        // Trigger reflow
        modal.offsetHeight;
        modal.style.opacity = '1';
    });

    // Close modal with animation
    function closeModal() {
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.style.display = 'none';
            resetChanges();
        }, 300);
    }

    closeBtn.addEventListener('click', closeModal);
    window.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
    });

    // Handle username editing with visual feedback
    editUsernameBtn.addEventListener('click', function() {
        isEditing = true;
        usernameInput.readOnly = false;
        usernameInput.focus();
        inputWrapper.classList.add('editing');
        checkForChanges();
    });

    usernameInput.addEventListener('blur', function() {
        if (!hasChanges) {
            isEditing = false;
            inputWrapper.classList.remove('editing');
        }
    });

    usernameInput.addEventListener('input', function() {
        checkForChanges();
        checkUsername(this.value);
    });

    // Handle photo upload with preview
    photoInput.addEventListener('change', function(e) { 
        if (e.target.files && e.target.files[0]) {
            newPhotoFile = e.target.files[0];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                document.getElementById('profilePhoto').src = e.target.result;
                checkForChanges();
            };
            
            reader.readAsDataURL(e.target.files[0]);
        }
    });

    // Check for changes and toggle save actions
    function checkForChanges() {
        const usernameChanged = usernameInput.value !== originalUsername;
        const photoChanged = newPhotoFile !== null;
        hasChanges = usernameChanged || photoChanged;

        if (hasChanges) {
            saveActions.classList.add('visible');
        } else {
            saveActions.classList.remove('visible');
        }
    }

    // Save changes with success animation
    saveBtn.addEventListener('click', async function() {
        if (!hasChanges) return;

        const originalContent = saveBtn.innerHTML;
        
        try {
            // Show loading spinner
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            saveBtn.disabled = true;
            cancelBtn.style.display = 'none';

            let success = true;

            // Update username if changed
            if (usernameInput.value !== originalUsername) {
                const usernameResponse = await updateUsername(usernameInput.value);
                if (!usernameResponse.success) {
                    showError(usernameResponse.message);
                    success = false;
                }
            }

            // Update photo if changed
            if (success && newPhotoFile) {
                const photoResponse = await updatePhoto(newPhotoFile);
                if (!photoResponse.success) {
                    showError(photoResponse.message);
                    success = false;
                }
            }

            if (success) {
                // Show success animation
                saveBtn.innerHTML = '<i class="fas fa-check"></i>';
                saveBtn.classList.add('success');
                
                // Update original values
                originalUsername = usernameInput.value;
                originalPhoto = document.getElementById('profilePhoto').src;
                
                // Update UI after successful save
                setTimeout(() => {
                    hasChanges = false;
                    isEditing = false;
                    inputWrapper.classList.remove('editing');
                    saveActions.classList.remove('visible');
                    saveBtn.innerHTML = originalContent;
                    saveBtn.classList.remove('success');
                    saveBtn.disabled = false;
                    cancelBtn.style.display = 'block';
                    
                    // Update header UI
                    updateHeaderUI(usernameInput.value, originalPhoto);
                }, 1000);
            } else {
                resetSaveButton(originalContent);
            }

        } catch (error) {
            console.error('Error saving changes:', error);
            showError('An error occurred while saving changes');
            resetSaveButton(originalContent);
        }
    });

    function resetSaveButton(originalContent) {
        saveBtn.innerHTML = originalContent;
        saveBtn.disabled = false;
        saveBtn.classList.remove('success');
        cancelBtn.style.display = 'block';
    }

    // Show error with animation
    function showError(message) {
        const errorDiv = document.querySelector('.username-error') || createErrorElement(message);
        errorDiv.textContent = message;
        errorDiv.classList.add('visible');
        inputWrapper.classList.add('error');
    }

    // Reset all changes
    function resetChanges() {
        usernameInput.value = originalUsername;
        usernameInput.readOnly = true;
        document.getElementById('profilePhoto').src = originalPhoto;
        newPhotoFile = null;
        hasChanges = false;
        isEditing = false;
        inputWrapper.classList.remove('editing');
        inputWrapper.classList.remove('error');
        saveActions.classList.remove('visible');
        removeUsernameError();
        resetSaveButton('Save Changes');
    }

    cancelBtn.addEventListener('click', resetChanges);

    // Error handling
    function removeUsernameError() {
        const errorDiv = document.querySelector('.username-error');
        if (errorDiv) {
            errorDiv.classList.remove('visible');
        }
        inputWrapper.classList.remove('error');
    }

    function createErrorElement(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'username-error';
        errorDiv.textContent = message;
        usernameInput.parentElement.insertAdjacentElement('afterend', errorDiv);
        return errorDiv;
    }

    async function checkUsername(username) {
        try {
            // Log the current page URL for debugging
            console.log('Current page URL:', window.location.href);
            
            // Get the base URL dynamically
            const pathArray = window.location.pathname.split('/');
            const baseUrl = '/' + pathArray[1]; // Adjust this based on your actual path structure
            
            const url = `${baseUrl}/includes/update_profile.php`;
            console.log('Attempting to fetch from:', url);
    
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=check_username&username=${encodeURIComponent(username)}`
            });
            
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('Response data:', data);
            
            if (data.exists) {
                showUsernameError('Username already exists');
                return false;
            } else {
                removeUsernameError();
                return true;
            }
        } catch (error) {
            console.error('Detailed error checking username:', error);
            console.error('Error stack:', error.stack);
            showUsernameError('Error checking username availability');
            return false;
        }
    }

    function showUsernameError(message) {
        const errorDiv = document.querySelector('.username-error') || 
                        createErrorElement(message);
        errorDiv.style.display = 'block';
        errorDiv.textContent = message;
        usernameInput.parentElement.classList.add('error');
    }

    function removeUsernameError() {
        const errorDiv = document.querySelector('.username-error');
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
        usernameInput.parentElement.classList.remove('error');
    }

    function createErrorElement(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'username-error';
        errorDiv.textContent = message;
        usernameInput.parentElement.insertAdjacentElement('afterend', errorDiv);
        return errorDiv;
    }


    
    async function updateUsername(username) {
        try {
            const pathArray = window.location.pathname.split('/');
            const baseUrl = '/' + pathArray[1];
            const url = `${baseUrl}/includes/update_profile.php`;
            
            console.log('Attempting to update username at:', url);
            
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_username&username=${encodeURIComponent(username)}`
            });
            
            console.log('Update username response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('Update username response data:', data);
            
            return {
                success: data.status === 'success',
                message: data.message
            };
        } catch (error) {
            console.error('Detailed error updating username:', error);
            console.error('Error stack:', error.stack);
            return {
                success: false,
                message: 'Error updating username'
            };
        }
    }
    
    async function updatePhoto(file) {
        try {
            const pathArray = window.location.pathname.split('/');
            const baseUrl = '/' + pathArray[1];
            const url = `${baseUrl}/includes/update_profile.php`;
            
            console.log('Attempting to update photo at:', url);
            
            const formData = new FormData();
            formData.append('action', 'update_photo');
            formData.append('photo', file);
    
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });
            
            console.log('Update photo response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('Update photo response data:', data);
            
            return {
                success: data.status === 'success',
                message: data.message
            };
        } catch (error) {
            console.error('Detailed error updating photo:', error);
            console.error('Error stack:', error.stack);
            return {
                success: false,
                message: 'Error updating photo'
            };
        }
    }
});