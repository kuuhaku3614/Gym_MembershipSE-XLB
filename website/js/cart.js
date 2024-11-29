// Function to show alerts
function showAlert(type, message) {
    const alertContainer = document.querySelector('.alert-container');
    if (!alertContainer) {
        console.error('Alert container not found');
        return;
    }

    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'error' ? 'danger' : 'success'} alert-dismissible fade show`;
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    alertContainer.appendChild(alertDiv);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

function updateCartDisplay(cart) {
    const cartBody = document.querySelector('.cart-body');
    if (!cartBody) {
        console.error('Cart body element not found');
        return;
    }

    let html = '';

    // Memberships section
    if (cart.memberships && cart.memberships.length > 0) {
        cart.memberships.forEach((membership, index) => {
            html += `
                <div class="cart-item">
                    <div class="item-details">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-1">${membership.name}</p>
                                <p class="price mb-1">₱${parseFloat(membership.price).toFixed(2)}</p>
                                <p class="text-muted mb-0">Validity: ${membership.validity}</p>
                                <p class="text-muted mb-0">Start: ${formatDate(membership.start_date)}</p>
                                <p class="text-muted mb-0">End: ${formatDate(membership.end_date)}</p>
                            </div>
                            <button class="remove-item" 
                                    onclick="removeFromCart('membership', ${index})">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
    }

    // Programs section
    if (cart.programs && cart.programs.length > 0) {
        cart.programs.forEach((program, index) => {
            html += `
                <div class="cart-item">
                    <div class="item-details">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-1">${program.name}</p>
                                <p class="price mb-1">₱${parseFloat(program.price).toFixed(2)}</p>
                                <p class="text-muted mb-0">Coach: ${program.coach_name}</p>
                                <p class="text-muted mb-0">Start: ${formatDate(program.start_date)}</p>
                                <p class="text-muted mb-0">End: ${formatDate(program.end_date)}</p>
                            </div>
                            <button class="remove-item" 
                                    onclick="removeFromCart('program', ${index})">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
    }

    // Rentals section
    if (cart.rentals && cart.rentals.length > 0) {
        cart.rentals.forEach((rental, index) => {
            html += `
                <div class="cart-item">
                    <div class="item-details">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-1">${rental.name}</p>
                                <p class="price mb-1">₱${parseFloat(rental.price).toFixed(2)}</p>
                                <p class="text-muted mb-0">Start: ${formatDate(rental.start_date)}</p>
                                <p class="text-muted mb-0">End: ${formatDate(rental.end_date)}</p>
                            </div>
                            <button class="remove-item" 
                                    onclick="removeFromCart('rental', ${index})">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
    }

    // Registration Fee section
    if (cart.registration_fee) {
        html += `
            <div class="cart-section">
                <h6 class="fw-bold mb-3">One-time Fees</h6>
                <div class="cart-item">
                    <div class="item-details">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-1">${cart.registration_fee.name}</p>
                                <p class="price mb-1">₱${parseFloat(cart.registration_fee.price).toFixed(2)}</p>
                                <p class="text-muted mb-0">One-time registration fee for new members</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // Cart summary
    html += `
        <div class="cart-summary mt-3">
            <div class="d-flex justify-content-between align-items-center">
                <span class="fw-bold">Total:</span>
                <span class="fw-bold">₱${parseFloat(cart.total).toFixed(2)}</span>
            </div>
            ${cart.total > 0 ? `
                <div class="d-grid gap-2 mt-3">
                    <button class="btn btn-danger" onclick="availServices()">
                        Avail Services
                    </button>
                    <button class="btn btn-outline-danger" onclick="clearCart()">
                        Clear Cart
                    </button>
                </div>
            ` : `
                <p class="text-center text-muted mt-3">Your cart is empty</p>
            `}
        </div>
    `;

    cartBody.innerHTML = html;
}

function removeFromCart(type, id) {
    const formData = new URLSearchParams();
    formData.append('action', 'remove');
    formData.append('type', type);
    formData.append('id', id.toString());

    fetch('services/cart_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Cache-Control': 'no-cache'
        },
        body: formData.toString(),
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Network response was not ok: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            updateCartDisplay(data.data.cart);
        } else {
            const message = data.data && data.data.message ? data.data.message : 'Failed to remove item';
            alert(message);
        }
    })
    .catch(error => {
        console.error('Error removing item:', error);
        alert('Failed to remove item from cart');
    });
}

function loadCart() {
    fetch('services/cart_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Cache-Control': 'no-cache'
        },
        body: 'action=get',
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Network response was not ok: ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        if (!text.trim()) {
            throw new Error('Empty response received');
        }
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Parse error:', e);
            throw new Error('Invalid JSON response');
        }
    })
    .then(response => {
        if (!response) {
            throw new Error('No response data');
        }
        
        if (response.success) {
            if (response.data && response.data.cart) {
                updateCartDisplay(response.data.cart);
            } else if (response.data && response.data.message) {
                updateCartDisplay({ memberships: [], programs: [], rentals: [], total: 0 });
            }
        } else {
            const message = response.data && response.data.message ? response.data.message : 'Unknown error';
            console.error('Server error:', message);
            if (message === 'Not logged in') {
                window.location.href = 'login/login.php';
            } else {
                updateCartDisplay({ memberships: [], programs: [], rentals: [], total: 0 });
            }
        }
    })
    .catch(error => {
        console.error('Error loading cart:', error);
        updateCartDisplay({ memberships: [], programs: [], rentals: [], total: 0 });
    });
}

function clearCart() {
    if (!confirm('Are you sure you want to clear your cart?')) {
        return;
    }

    console.log('Clearing cart...');
    const formData = new URLSearchParams();
    formData.append('action', 'clear');

    fetch('services/cart_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Cache-Control': 'no-cache'
        },
        body: formData.toString(),
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            console.error('Server responded with status:', response.status);
            throw new Error(`Server error: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Clear cart response:', data);
        if (data.success) {
            updateCartDisplay(data.data.cart);
            alert('Cart cleared successfully');
        } else {
            const message = data.data && data.data.message ? data.data.message : 'Failed to clear cart';
            console.error('Clear cart failed:', message);
            alert(message);
            if (message === 'Not logged in') {
                window.location.href = 'login/login.php';
            }
        }
    })
    .catch(error => {
        console.error('Error clearing cart:', error);
        alert('Failed to clear cart. Please try again or refresh the page.');
    });
}

function availServices() {
    fetch('services/cart_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=validate'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data.showConfirm) {
            proceedWithAvailing();
        } else {
            alert(data.data.message || 'An error occurred while validating your cart.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing your request.');
    });
}

function proceedWithAvailing() {
    if (confirm('Are you sure you want to avail these services?')) {
        fetch('services/checkout_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '/Gym_MembershipSE-XLB/website/profile.php';
            } else {
                alert(data.data.message || 'An error occurred during checkout.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while processing your request.');
        });
    }
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return 'Invalid Date';
    
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    const year = date.getFullYear();
    
    return `${month}/${day}/${year}`;
}

// Initialize cart when page loads
document.addEventListener('DOMContentLoaded', loadCart); 