<?php if (!empty($_SESSION['cart'])): ?>
    <!-- Membership section -->
    <?php if (!empty($_SESSION['cart']['memberships'])): ?>
        <div class="cart-section">
            <h6 class="fw-bold mb-3">Membership Plans</h6>
            <?php foreach ($_SESSION['cart']['memberships'] as $index => $membership): ?>
                <div class="cart-item">
                    <div class="item-details">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-1"><?= htmlspecialchars($membership['name']) ?></p>
                                <p class="price mb-1">₱<?= number_format($membership['price'], 2) ?></p>
                                <p class="text-muted mb-0">Validity: <?= htmlspecialchars($membership['validity']) ?></p>
                                <p class="text-muted mb-0">Start: <?= date('m/d/Y', strtotime($membership['start_date'])) ?></p>
                                <p class="text-muted mb-0">End: <?= date('m/d/Y', strtotime($membership['end_date'])) ?></p>
                            </div>
                            <button class="remove-item" 
                                    onclick="removeFromCart('membership', <?= $index ?>)"
                                    title="Remove item">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Programs section -->
    <?php if (!empty($_SESSION['cart']['programs'])): ?>
        <div class="cart-section">
            <h6 class="fw-bold mb-3">Programs</h6>
            <?php foreach ($_SESSION['cart']['programs'] as $index => $program): ?>
                <div class="cart-item">
                    <div class="item-details">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-1"><?= htmlspecialchars($program['name']) ?></p>
                                <p class="price mb-1">₱<?= number_format($program['price'], 2) ?></p>
                                <p class="text-muted mb-0">Coach: <?= htmlspecialchars($program['coach_name']) ?></p>
                                <p class="text-muted mb-0">Start: <?= date('m/d/Y', strtotime($program['start_date'])) ?></p>
                                <p class="text-muted mb-0">End: <?= date('m/d/Y', strtotime($program['end_date'])) ?></p>
                            </div>
                            <button class="remove-item" 
                                    onclick="removeFromCart('program', <?= $index ?>)"
                                    title="Remove item">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Rentals section -->
    <?php if (!empty($_SESSION['cart']['rentals'])): ?>
        <div class="cart-section">
            <h6 class="fw-bold mb-3">Rental Services</h6>
            <?php foreach ($_SESSION['cart']['rentals'] as $index => $rental): ?>
                <div class="cart-item">
                    <div class="item-details">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-1"><?= htmlspecialchars($rental['name']) ?></p>
                                <p class="price mb-1">₱<?= number_format($rental['price'], 2) ?></p>
                                <p class="text-muted mb-0">Start: <?= date('m/d/Y', strtotime($rental['start_date'])) ?></p>
                                <p class="text-muted mb-0">End: <?= date('m/d/Y', strtotime($rental['end_date'])) ?></p>
                            </div>
                            <button class="remove-item" 
                                    onclick="removeFromCart('rental', <?= $index ?>)"
                                    title="Remove item">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Cart summary -->
    <div class="cart-summary mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="fw-bold">Total:</span>
            <span class="fw-bold">₱<?= number_format($_SESSION['cart']['total'], 2) ?></span>
        </div>
        <?php if ($_SESSION['cart']['total'] > 0): ?>
            <div class="d-grid gap-2">
                <button class="btn btn-danger" onclick="availServices()">
                    Avail Services
                </button>
                <button class="btn btn-outline-danger" onclick="clearCart()">
                    Clear Cart
                </button>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">Your cart is empty</p>
        <?php endif; ?>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger mt-3">
            <?= $_SESSION['error']; ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success mt-3">
            <?= $_SESSION['success']; ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
<?php endif; ?> 