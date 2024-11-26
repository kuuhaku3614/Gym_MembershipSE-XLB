<?php if (!empty($cart)): ?>
    <!-- Membership section -->
    <?php if ($cart['membership']): ?>
        <div class="cart-item">
            <div class="item-details">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-1"><?= htmlspecialchars($cart['membership']['name']) ?></p>
                        <p class="price mb-1">₱<?= number_format($cart['membership']['price'], 2) ?></p>
                        <p class="text-muted mb-0">Validity: <?= htmlspecialchars($cart['membership']['validity']) ?></p>
                        <p class="text-muted mb-0">Start: <?= date('m/d/Y', strtotime($cart['membership']['start_date'])) ?></p>
                        <p class="text-muted mb-0">End: <?= date('m/d/Y', strtotime($cart['membership']['end_date'])) ?></p>
                    </div>
                    <button class="remove-item" 
                            onclick="removeFromCart('membership', <?= $cart['membership']['id'] ?>)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Programs section -->
    <?php if (!empty($cart['programs'])): ?>
        <?php foreach ($cart['programs'] as $index => $program): ?>
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
                                onclick="removeFromCart('program', <?= $index ?>)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Rentals section -->
    <?php if (!empty($cart['rentals'])): ?>
        <?php foreach ($cart['rentals'] as $index => $rental): ?>
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
                                onclick="removeFromCart('rental', <?= $index ?>)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Cart summary -->
    <div class="cart-summary mt-3">
        <div class="d-flex justify-content-between align-items-center">
            <span class="fw-bold">Total:</span>
            <span class="fw-bold">₱<?= number_format($cart['total'], 2) ?></span>
        </div>
        <?php if ($cart['total'] > 0): ?>
            <div class="d-grid gap-2 mt-3">
                <button class="btn btn-danger" onclick="availServices()">
                    Avail Services
                </button>
                <button class="btn btn-outline-danger" onclick="clearCart()">
                    Clear Cart
                </button>
            </div>
        <?php else: ?>
            <p class="text-center text-muted mt-3">Your cart is empty</p>
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