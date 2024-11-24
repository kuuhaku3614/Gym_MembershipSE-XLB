function updateCartDisplay(cart) {
    console.log('Cart contents:', cart);  // Debug log
    console.log('Rentals:', cart.rentals); // Specifically log rentals
    console.log('Updating cart display:', cart);
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
                        </div>
                        <button class="remove-item" 
                                onclick="removeFromCart('membership', ${cart.membership.id})"
                                aria-label="Remove ${cart.membership.name} from cart"
                                title="Remove from cart">
                            <i class="fas fa-times" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    // Programs section
    if (cart.programs && cart.programs.length > 0) {
        cart.programs.forEach(program => {
            html += `
                <div class="cart-item">
                    <div class="item-details">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-1">${program.name}</p>
                                <p class="price mb-1">₱${parseFloat(program.price).toFixed(2)}</p>
                                <p class="text-muted mb-0">Validity: ${program.validity}</p>
                                ${program.coach_name ? `<p class="text-muted mb-0">Coach: ${program.coach_name}</p>` : ''}
                            </div>
                            <button class="remove-item" 
                                    onclick="removeFromCart('program', ${program.id})"
                                    aria-label="Remove ${program.name} from cart"
                                    title="Remove from cart">
                                <i class="fas fa-times" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
    }

    // Rentals section with quantity controls
    if (cart.rentals && cart.rentals.length > 0) {
        cart.rentals.forEach(rental => {
            html += `
                <div class="cart-item">
                    <div class="item-details">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-1">${rental.name}</p>
                                <p class="price mb-1">₱${parseFloat(rental.price).toFixed(2)} × ${rental.quantity}</p>
                                <p class="text-muted mb-0">Validity: ${rental.validity}</p>
                                <div class="quantity-controls mt-2">
                                    <button class="btn btn-sm btn-outline-secondary" 
                                            onclick="updateQuantity(${rental.id}, ${rental.quantity - 1})"
                                            aria-label="Decrease quantity of ${rental.name}"
                                            title="Decrease quantity">
                                        <i class="fas fa-minus" aria-hidden="true"></i>
                                    </button>
                                    <span class="mx-2" aria-label="Quantity">${rental.quantity}</span>
                                    <button class="btn btn-sm btn-outline-secondary" 
                                            onclick="updateQuantity(${rental.id}, ${rental.quantity + 1})"
                                            aria-label="Increase quantity of ${rental.name}"
                                            title="Increase quantity">
                                        <i class="fas fa-plus" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <button class="remove-item" 
                                    onclick="removeFromCart('rental', ${rental.id})"
                                    aria-label="Remove ${rental.name} from cart"
                                    title="Remove from cart">
                                <i class="fas fa-times" aria-hidden="true"></i>
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
                    <button class="btn btn-danger" 
                            onclick="checkout()"
                            aria-label="Proceed to checkout"
                            title="Proceed to checkout">
                        Proceed to Checkout
                    </button>
                    <button class="btn btn-outline-danger" 
                            onclick="clearCart()"
                            aria-label="Clear shopping cart"
                            title="Clear cart">
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
    console.log('Removing item:', type, id); // Debug log
    fetch('services/cart_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=remove&type=${type}&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        console.log('Remove response:', data); // Debug log
        if (data.success) {
            updateCartDisplay(data.cart);
        } else {
            console.error('Failed to remove item:', data.message);
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

// Add this new function to handle quantity updates
function updateQuantity(rentalId, newQuantity) {
    if (newQuantity < 1) return; // Prevent negative quantities
    
    fetch('services/cart_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_quantity&rental_id=${rentalId}&quantity=${newQuantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartDisplay(data.cart);
        }
    })
    .catch(error => console.error('Error updating quantity:', error));
}

// Initialize cart when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing cart...'); // Debug log
    loadCart();
}); 