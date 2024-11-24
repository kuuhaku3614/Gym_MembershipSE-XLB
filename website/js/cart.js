function updateCartDisplay(cart) {
    console.log('Cart contents:', cart);
    const cartBody = document.querySelector('.cart-body');
    if (!cartBody) {
        console.error('Cart body element not found');
        return;
    }

    let html = '';

    // Membership section
    if (cart.membership) {
        html += `
            <div class="cart-item">
                <div class="item-details">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1">${cart.membership.name}</p>
                            <p class="price mb-1">₱${parseFloat(cart.membership.price).toFixed(2)}</p>
                            <p class="text-muted mb-0">Validity: ${cart.membership.validity}</p>
                            <p class="text-muted mb-0">Start: ${formatDate(cart.membership.start_date)}</p>
                            <p class="text-muted mb-0">End: ${formatDate(cart.membership.end_date)}</p>
                        </div>
                        <button class="remove-item" 
                                onclick="removeFromCart('membership', ${cart.membership.id})"
                                aria-label="Remove ${cart.membership.name} from cart">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    // Programs section - each program is displayed individually
    if (cart.programs && cart.programs.length > 0) {
        cart.programs.forEach((program, index) => {
            html += `
                <div class="cart-item">
                    <div class="item-details">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-1">${program.name}</p>
                                <p class="price mb-1">₱${parseFloat(program.price).toFixed(2)}</p>
                                <p class="text-muted mb-0">Validity: ${program.validity}</p>
                                <p class="text-muted mb-0">Coach: ${program.coach_name}</p>
                                <p class="text-muted mb-0">Start: ${formatDate(program.start_date)}</p>
                                <p class="text-muted mb-0">End: ${formatDate(program.end_date)}</p>
                            </div>
                            <button class="remove-item" 
                                    onclick="removeFromCart('program', ${index})"
                                    aria-label="Remove ${program.name} from cart">
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
                                <p class="text-muted mb-0">Validity: ${rental.validity}</p>
                                <p class="text-muted mb-0">Start: ${formatDate(rental.start_date)}</p>
                                <p class="text-muted mb-0">End: ${formatDate(rental.end_date)}</p>
                            </div>
                            <button class="remove-item" 
                                    onclick="removeFromCart('rental', ${index})"
                                    aria-label="Remove ${rental.name} from cart">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
    }

    // Add summary section
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
            console.error('Failed to remove item:', data.message);
            if (data.debug) {
                console.log('Debug info:', data.debug);
            }
        }
    })
    .catch(error => console.error('Error removing item:', error));
}

function loadCart() {
    console.log('Loading cart...'); // Debug log
    fetch('services/cart_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get'
    })
    .then(response => response.json())
    .then(data => {
        console.log('Cart data:', data); // Debug log
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
        }
    })
    .catch(error => console.error('Error clearing cart:', error));
}

// Add this function to handle checkout
function availServices() {
    // First validate the cart
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
            // If validation passes, proceed with availing services
            proceedWithAvailing();
        } else {
            // Show validation errors
            alert(data.errors.join('\n'));
        }
    })
    .catch(error => {
        console.error('Error validating cart:', error);
        alert('An error occurred while validating the cart.');
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
                alert(data.message);
                // Redirect to a success page or reload current page
                window.location.href = `avail_success.php?id=${data.membership_id}`;
            } else {
                alert('Failed to avail services: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error during service availing:', error);
            alert('An error occurred while processing your request. Please try again.');
        });
    }
}

// Initialize cart when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing cart...'); // Debug log
    loadCart();
});

// Add this helper function for date formatting
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return 'Invalid Date';
    
    // Format as MM/DD/YYYY
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    const year = date.getFullYear();
    
    return `${month}/${day}/${year}`;
} 