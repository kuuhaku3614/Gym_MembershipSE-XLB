<?php
    session_start();
    include('includes/header.php');
    require_once 'services/services.class.php';

    $Obj = new Services_Class();
    $standard_plans = $Obj->displayStandardPlans();
    $special_plans = $Obj->displaySpecialPlans();
    $programs = $Obj->displayPrograms();
    $rental_services = $Obj->displayRentalServices();
?>
<link rel="stylesheet" href="../css/browse_services.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

<div class="services-page">
    <div class="container-fluid p-0">
        <div class="content-wrapper">
            <!-- Standard Plans Section -->
            <h2 class="section-heading">GYM RATES</h2>
            <div class="row g-4 mb-4">
                <?php foreach ($standard_plans as $arr){ ?>
                    <div class="col-sm-6 col-md-6 col-lg-3">
                        <a href="services/avail_membership.php?id=<?= $arr['plan_id'] ?>" class="program-link">
                            <div class="card shadow">
                                <div class="card-header text-white text-center">
                                    <h2 class="fw-bold mb-0"><?= $arr['plan_name'] ?></h2>
                                </div>
                                <div class="card-body">
                                    <p class="card-text mb-1">Price: Php <?= number_format($arr['price'], 2) ?></p>
                                    <p class="card-text mb-1">Validity: <?= $arr['validity'] ?></p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php } ?>
            </div>

            <br>

            <!-- Special Plans Section -->
            <h2 class="section-heading">SPECIAL RATES</h2>
            <div class="row g-4 mb-4">
                <?php foreach ($special_plans as $arr){ ?>
                    <div class="col-sm-6 col-md-6 col-lg-3">
                        <a href="services/avail_membership.php?id=<?= $arr['plan_id'] ?>" class="program-link">
                            <div class="card shadow">
                                <div class="card-header text-white text-center">
                                    <h2 class="fw-bold mb-0"><?= $arr['plan_name'] ?></h2>
                                </div>
                                <div class="card-body">
                                    <p class="card-text mb-1">Price: Php <?= number_format($arr['price'], 2) ?></p>
                                    <p class="card-text mb-1">Validity: <?= $arr['validity'] ?></p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php } ?>
            </div>

            <!-- Programs Section -->
            <h2 class="section-heading">PROGRAMS</h2>
            <div class="row g-4 mb-4">
                <?php foreach ($programs as $program){ ?>
                    <div class="col-sm-6 col-md-6 col-lg-3">
                        <a href="services/avail_program.php?id=<?= $program['program_id'] ?>" class="program-link">
                            <div class="card shadow">
                                <div class="card-header text-white text-center">
                                    <h2 class="fw-bold mb-0"><?= $program['program_name'] ?></h2>
                                </div>
                                <div class="card-body">
                                    <p class="card-text mb-1">Validity: <?= $program['validity'] ?></p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php } ?>
            </div>

            <br>

            <!-- Rental Services Section -->
            <h2 class="section-heading">OPTIONAL SERVICES</h2>
            <div class="row g-4 mb-4">
                <?php foreach ($rental_services as $rental){ ?>
                    <div class="col-sm-6 col-md-6 col-lg-3">
                        <a href="services/avail_rental.php?id=<?= $rental['rental_id'] ?>" class="program-link">
                            <div class="card shadow">
                                <div class="card-header text-white text-center">
                                    <h2 class="fw-bold mb-0"><?= $rental['service_name'] ?></h2>
                                </div>
                                <div class="card-body">
                                    <p class="card-text mb-1">Price: Php <?= number_format($rental['price'], 2) ?></p>
                                    <p class="card-text mb-1">Validity: <?= $rental['validity'] ?></p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<!-- Add this right after your last div and before the cart overlay -->
<button class="cart-btn" id="showCartBtn">
    <i class="fas fa-shopping-cart"></i>
</button>

<!-- Cart Overlay -->
<div class="cart-overlay" id="cartOverlay"></div>

<!-- Cart Sidebar -->
<div class="cart-sidebar" id="cartSidebar">
    <div class="cart-header">
        <h5>Shopping Cart</h5>
        <button class="close-cart" id="closeCart">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="cart-body">
        <!-- Cart items will be dynamically loaded here -->
    </div>
</div>

<!-- Make sure these scripts are in the correct order -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/cart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cartBtn = document.getElementById('showCartBtn');
    const cartSidebar = document.getElementById('cartSidebar');
    const cartOverlay = document.getElementById('cartOverlay');
    const closeCart = document.getElementById('closeCart');

    function openCart() {
        cartSidebar.classList.add('active');
        cartOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        loadCart(); // Load cart contents when opening
    }

    function closeCartMenu() {
        cartSidebar.classList.remove('active');
        cartOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    cartBtn.addEventListener('click', openCart);
    closeCart.addEventListener('click', closeCartMenu);
    cartOverlay.addEventListener('click', closeCartMenu);

    // Close cart when pressing Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeCartMenu();
        }
    });
});
</script>