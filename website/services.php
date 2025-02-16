<?php
    session_start();
    include('includes/header.php');
    require_once 'services/services.class.php';

    $Obj = new Services_Class();
    $standard_plans = $Obj->displayStandardPlans();
    $special_plans = $Obj->displaySpecialPlans();
    $programs = $Obj->displayPrograms();
    $rental_services = $Obj->displayRentalServices();
    $walkin = $Obj->displayWalkinServices();
?>
<link rel="stylesheet" href="../css/browse_services.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

<style>
        html{
            background-color: transparent;
        }
        body{
            height: 100vh;
            background-color: #efefef!important;
        }
        .home-navbar{
            background-color: #c92f2f;
            position: fixed;
            border-radius: 0;
        }
        .main-content{
            padding-top: 80px;
        }
        /* Default red color for all sections */

        .card .card-header {
            background-color: #ff0000 !important;
        }

        /* Black color only for OFFER PROGRAMS section */
        .coach-section {
            color: #000000 !important;
        }

        .coach-card .card-header {
            background-color: #000000 !important;
        }

        .coach-card:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
}
</style>

<section class="main-content">

<div class="services-header text-center d-flex flex-column justify-content-center align-items-center mb-5">
    <div class="divider mx-auto my-4" style="width: 60%; height: 3px; background-color: red;"></div>
    <h1 class="display-2 fw-bold text-white" style="text-shadow: 4px 4px 8px rgba(255, 0, 0, 0.6);">Choose your programs</h1>
    <p class="lead text-white fs-2" style="text-shadow: 2px 2px 6px rgba(255, 0, 0, 0.6);">Discover our comprehensive range of fitness solutions and memberships</p>
    <div class="divider mx-auto my-4" style="width: 60%; height: 3px; background-color: red;"></div>
</div>

<div class="services-page">
    <div class="container-fluid p-0">
        <div class="alert-container"></div>
        <div class="content-wrapper">
            <?php if (isset($_SESSION['personal_details']['role_name']) && $_SESSION['personal_details']['role_name'] === 'coach'): ?>
                <h2 class="section-heading coach-section">OFFER PROGRAMS</h2>
                <div class="row g-4 mb-4">
                    <?php foreach ($programs as $program){ ?>
                        <div class="col-sm-6 col-md-6 col-lg-3">
                            <a href="services/offer_program.php?id=<?= $program['program_id'] ?>" class="program-link">
                                <div class="card shadow coach-card">
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
            <?php endif; ?>

            <div class="container text-center mb-4 button-shortcuts">
                <h2 class="mb-2 text-start">Browse Offers</h2>
                <div class="row justify-content-between">
                    <div class="col-md-4 mb-2">
                        <button class="btn scroll-btn py-4" data-target="#Gym-Rates">
                            <i class="fas fa-dumbbell me-2 text-white" style="font-size: 1.4rem;"></i>
                            <span class="text-white">Gym Rates</span>
                        </button>
                    </div>
                    <div class="col-md-4 mb-2">
                        <button class="btn scroll-btn py-4" data-target="#Programs">
                            <i class="fas fa-calendar-alt me-2 text-white" style="font-size: 1.4rem;"></i>
                            <span class="text-white">Programs</span>
                        </button>
                    </div>
                    <div class="col-md-4 mb-2">
                        <button class="btn scroll-btn py-4" data-target="#Optional-Services">
                            <i class="fas fa-plus-circle me-2 text-white" style="font-size: 1.4rem;"></i>
                            <span class="text-white">Optional Services</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Special Plans Section -->
            <h2 class="section-heading">SPECIAL RATES</h2>
            <div class="row g-4 mb-4">
                <?php foreach ($special_plans as $arr){ ?>
                    <div class="col-sm-6 col-md-6 col-lg-3">
                        <a href="services/avail_membership.php?id=<?= $arr['plan_id'] ?>" class="program-link">
                            <div class="card">
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

            <br id="Gym-Rates">
            <hr class="my-4" style="border: 2px solid #000;">

            <!-- Standard Plans Section -->
            <h2 class="section-heading">GYM RATES</h2>
            <div class="row g-4 mb-4">
                <?php foreach ($standard_plans as $arr){ ?>
                    <div class="col-sm-6 col-md-6 col-lg-3">
                        <a href="services/avail_membership.php?id=<?= $arr['plan_id'] ?>" class="program-link">
                            <div class="card">
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
            <hr class="my-4" style="border: 2px solid #000;">

            <!-- Walk in Section -->
            <h2 class="section-heading">WALK IN</h2>
            <div class="row g-4 mb-4">
                <?php foreach ($walkin as $arr){ ?>
                    <div class="col-sm-6 col-md-6 col-lg-3">
                        <a href="services/avail_walkin.php?id=<?= $arr['walkin_id'] ?>" class="program-link">
                            <div class="card">
                                <div class="card-header text-white text-center">
                                    <h2 class="fw-bold mb-0">Walk-in</h2>
                                </div>
                                <div class="card-body">
                                    <p class="card-text mb-1">Price: Php <?= number_format($arr['price'], 2) ?></p>
                                    <p class="card-text mb-1">Validity: 1 day</p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php } ?>
            </div>

            <br id="Programs">
            <hr class="my-4" style="border: 2px solid #000;">

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

            <br id="Optional-Services">
            <hr class="my-4" style="border: 2px solid #000;">

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
</section>

<!-- Service Details Modal
<div class="modal fade" id="serviceModal" tabindex="-1" aria-labelledby="serviceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="height: 90vh;">
            <div class="modal-header py-2">
            <h5 id="serviceModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="height: calc(100% - 42px); overflow-y: auto;">
                <!-- Content will be loaded here dynamically -->
            <!-- </div>
        </div>
    </div>
</div> --> -->


<!-- Cart Overlay -->
<div class="cart-overlay" id="cartOverlay"></div>

<!-- Cart Sidebar -->
<div class="cart-sidebar" id="cartSidebar" aria-label="Shopping Cart">
    <div class="cart-header">
        <h5>Shopping Cart</h5>
        <button class="close-cart" 
                id="closeCart" 
                aria-label="Close shopping cart"
                title="Close cart">
            <i class="fas fa-times" aria-hidden="true"></i>
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

// document.addEventListener('DOMContentLoaded', function() {
//         // Prevent default link behavior and show modal instead
//         document.querySelectorAll('.program-link').forEach(link => {
//                 link.addEventListener('click', function(e) {
//                         e.preventDefault();
//                         const url = this.href;
//                         fetch(url)
//                                 .then(response => response.text())
//                                 .then(data => {
//                                         const modal = new bootstrap.Modal(document.getElementById('serviceModal'));
//                                         document.querySelector('.modal-body').innerHTML = data;
//                                         document.getElementById('serviceModalLabel').textContent = this.querySelector('.fw-bold').textContent;
//                                         modal.show();
//                                 });
//                 });
//         });
// });

      // Add event listeners to all buttons with the class "scroll-btn"
  document.querySelectorAll('.scroll-btn').forEach(button => {
    button.addEventListener('click', function () {
      // Get the target element's ID from the data-target attribute
      const targetId = this.getAttribute('data-target');
      const targetElement = document.querySelector(targetId);

      // Scroll to the target element smoothly
      if (targetElement) {
        targetElement.scrollIntoView({ behavior: 'smooth' });
      }
    });
  });

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