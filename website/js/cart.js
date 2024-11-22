function updateCartDisplay(cart) {
    console.log('Updating cart display:', cart); // Debug log
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
                        <button class="remove-item" onclick="removeFromCart('membership', ${cart.membership.id})">
                            <i class="fas fa-times"></i>
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
                            </div>
                            <button class="remove-item" onclick="removeFromCart('program', ${program.id})">
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
                    <button class="btn btn-danger" onclick="checkout()">Proceed to Checkout</button>
                    <button class="btn btn-outline-danger" onclick="clearCart()">Clear Cart</button>
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

// Initialize cart when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing cart...'); // Debug log
    loadCart();
}); 