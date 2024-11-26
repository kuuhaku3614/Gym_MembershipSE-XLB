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
    const formData = new FormData();
    formData.append('action', 'remove');
    formData.append('type', type);
    formData.append('id', id.toString());

    fetch('services/cart_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartDisplay(data.cart);
        } else {
            alert(data.message || 'Failed to remove item');
        }
    })
    .catch(error => console.error('Error removing item:', error));
}

function loadCart() {
    fetch('services/cart_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartDisplay(data.cart);
        } else {
            console.error('Failed to load cart:', data.message);
        }
    })
    .catch(error => console.error('Error loading cart:', error));
}

function clearCart() {
    fetch('services/cart_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=clear'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartDisplay(data.cart);
        } else {
            alert(data.message || 'Failed to clear cart');
        }
    })
    .catch(error => console.error('Error clearing cart:', error));
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
        if (data.success) {
            proceedWithAvailing();
        } else {
            alert(data.errors ? data.errors.join('\n') : 'Cart validation failed');
        }
    })
    .catch(error => {
        console.error('Error validating cart:', error);
        alert('An error occurred while validating the cart');
    });
}

function proceedWithAvailing() {
    if (confirm('Are you sure you want to avail these services?')) {
        fetch('services/checkout_handler.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'profile.php';
            } else {
                alert(data.message || 'Failed to avail services');
            }
        })
        .catch(error => {
            console.error('Error during checkout:', error);
            alert('An error occurred while processing your request');
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