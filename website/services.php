<?php
    session_start();
    require_once '../config.php';
    include('includes/header.php');
    require_once 'includes/loadingScreen.php';
    require_once 'services/services.class.php';
    $Obj = new Services_Class();
    $standard_plans = $Obj->displayStandardPlans();
    $special_plans = $Obj->displaySpecialPlans();
    $rental_services = $Obj->displayRentalServices();
    $walkin = $Obj->displayWalkinServices();
    // For general display, keep using displayProgramServices (with schedules filter)
    $program_services = $Obj->displayProgramServices();
    // For Teach Programs section, show ALL active programs (even those with no schedules)
    $all_programs = $Obj->getProgramsList(); // We'll add this method to fetch all active programs

    
?>

<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="../css/browse_services.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

<section class="main-content">

<div class="services-header text-center d-flex flex-column justify-content-center align-items-center mb-5">
    <h1 class="display-2 fw-bold text-white" style="text-shadow: 4px 4px 8px rgba(0, 0, 0, 0.6);">Choose your programs</h1>
    <p class="lead text-white fs-2" style="text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.6);">Discover our comprehensive range of fitness solutions and memberships</p>
</div>
<div class="services-page">
    <div class="container-fluid p-0">
        <div class="alert-container"></div>
        <div class="content-wrapper">
            
            <!-- Filter and Search Controls -->
            <div class="container mb-4">
                <div class="row g-3">
                    <!-- Search Bar -->
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-dark text-white"><i class="fas fa-search"></i></span>
                            <input type="text" id="serviceSearch" class="form-control" placeholder="Search services...">
                        </div>
                    </div>
                    
                    <!-- Price Range Filter -->
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-dark text-white"><i class="fas fa-peso-sign"></i></span>
                            <select id="priceFilter" class="form-select">
                                <option value="all">All Prices</option>
                                <option value="0-500">₱0 - ₱500</option>
                                <option value="501-1000">₱501 - ₱1000</option>
                                <option value="1001-2000">₱1001 - ₱2000</option>
                                <option value="2001+">₱2001+</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Category Filter -->
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-dark text-white"><i class="fas fa-tags"></i></span>
                            <select id="categoryFilter" class="form-select">
                                <option value="all">All Categories</option>
                                <option value="special">Special Gym Rates</option>
                                <option value="normal">Normal Gym Rates</option>
                                <option value="walkin">Walk-In Rates</option>
                                <option value="programs">Coaching Programs</option>
                                <option value="other">Other Services</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container text-center mb-4 button-shortcuts">
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
                            <span class="text-white">Other Services</span>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Filter Results Container -->
            <div id="filterResultsContainer" class="container mb-4" style="display: none;">
                <h2 class="section-heading">Search Results</h2>
                <div class="row g-4 mb-4" id="filterResults">
                    <!-- Filter results will be dynamically loaded here -->
                </div>
            </div>
            
    <?php if (isset($_SESSION['personal_details']['role_name']) && ($_SESSION['personal_details']['role_name'] === 'coach' || $_SESSION['personal_details']['role_name'] === 'coach/staff')): ?>
        <!-- Coach Section -->
        <div class="section-container" data-category="teach">
            <h2 class="section-heading">Teach Programs</h2>
            <div class="row g-4 mb-4">
                <?php foreach ($all_programs as $program) { 
                    // Default image path
                    $defaultImage = '../cms_img/default/program.jpeg';

                    // Get the image path
                    $imagePath = $defaultImage; // Set default first
                    if (!empty($program['image']) && file_exists(__DIR__ . "/../cms_img/programs/" . $program['image'])) {
                        $imagePath = '../cms_img/programs/' . $program['image'];
                    }
                    // No available_types for new programs, so skip types display for now

                ?>
                    <div class="col-sm-6 col-md-6 col-lg-3 service-card" data-category="teach" data-name="<?= strtolower(htmlspecialchars($program['program_name'])) ?>">
                        <a href="services/teach_program.php?id=<?= $program['program_id'] ?>" class="program-link">
                            <div class="card h-100">
                                <div class="card-header text-white text-center"
                                    style="background-image: url('<?= $imagePath ?>'); 
                                    background-size: cover; 
                                    background-position: center;
                                    height: 200px;
                                    position: relative;">
                                    <div class="overlay-header">
                                        <h2 class="fw-bold mb-0 service-name"><?= htmlspecialchars($program['program_name']) ?></h2>
                                    </div>
                                    <div class="overlay-darker"></div>
                                </div>
                                <div class="card-body">
                                    <p class="card-text mb-1" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; width: 100% "><?= htmlspecialchars($program['description']) ?></p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php } ?>
            </div>


                <br>
                <hr class="my-4 section-divider" style="border: 2px solid #000;">
        </div>
    <?php endif; ?>

            <!-- Special Plans Section -->
            <div class="section-container" data-category="special">
                <h2 class="section-heading">SPECIAL GYM RATES</h2>
                <div class="row g-4 mb-4">
                    <?php foreach ($special_plans as $arr) { 
                        // Default image path
                        $defaultImage = '../cms_img/default/membership.jpeg';

                        // Get the image path
                        $imagePath = $defaultImage; // Set default first
                        if (!empty($arr['image']) && file_exists(__DIR__ . "/../cms_img/gym_rates/" . $arr['image'])) {
                            $imagePath = '../cms_img/gym_rates/' . $arr['image'];
                        }
                        $price = floatval($arr['price']);
                    ?>
                        <div class="col-sm-6 col-md-6 col-lg-3 service-card" data-category="special" data-price="<?= $price ?>" data-name="<?= strtolower($arr['plan_name']) ?>">
                            <a href="services/avail_membership.php?id=<?= $arr['plan_id'] ?>" class="program-link">
                                <div class="card">
                                    <div class="card-header text-white text-center" 
                                        style="background-image: url('<?= $imagePath ?>'); 
                                                background-size: cover; 
                                                background-position: center;
                                                position: relative;">
                                        <!-- Add an overlay to ensure text is readable -->
                                        <div class="overlay-header">
                                            <h2 class="fw-bold mb-0 service-name"><?= $arr['plan_name'] ?></h2>
                                        </div>
                                        <!-- Dark overlay to make text readable on any image -->
                                        <div class="overlay-darker"></div>
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
                <hr class="my-4 section-divider" style="border: 2px solid #000;">
            </div>

            <!-- Standard Plans Section -->
            <div class="section-container" data-category="normal">
                <h2 class="section-heading">NORMAL GYM RATES</h2>
                <div class="row g-4 mb-4">
                    <?php foreach ($standard_plans as $arr) { 
                        // Default image path
                        $defaultImage = '../cms_img/default/membership.jpeg';

                        // Get the image path
                        $imagePath = $defaultImage; // Set default first
                        if (!empty($arr['image']) && file_exists(__DIR__ . "/../cms_img/gym_rates/" . $arr['image'])) {
                            $imagePath = '../cms_img/gym_rates/' . $arr['image'];
                        }
                        $price = floatval($arr['price']);
                    ?>
                        <div class="col-sm-6 col-md-6 col-lg-3 service-card" data-category="normal" data-price="<?= $price ?>" data-name="<?= strtolower($arr['plan_name']) ?>">
                            <a href="services/avail_membership.php?id=<?= $arr['plan_id'] ?>" class="program-link">
                                <div class="card">
                                    <div class="card-header text-white text-center" 
                                        style="background-image: url('<?= $imagePath ?>'); 
                                                background-size: cover; 
                                                background-position: center;
                                                position: relative;">
                                        <!-- Add an overlay to ensure text is readable -->

                                        <div class="overlay-header">
                                        <h2 class="fw-bold mb-0 service-name"><?= $arr['plan_name'] ?></h2>
                                        </div>
                                        <div class="overlay-darker"></div>
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
                <hr class="my-4 section-divider" style="border: 2px solid #000;">
            </div>

            <!-- Walk in Section -->
            <div class="section-container" data-category="walkin">
                <h2 class="section-heading">WALK-IN GYM RATES</h2>
                <div class="row g-4 mb-4">
                <?php foreach ($walkin as $arr) { 
                    // Default image path
                    $defaultImage = '../cms_img/default/walkIn.jpeg';

                    // Get the image path
                    $imagePath = $defaultImage; // Set default first
                    // if (!empty($arr['image']) && file_exists(__DIR__ . "/../cms_img/gym_rates/" . $arr['image'])) {
                    //     $imagePath = '../cms_img/walk/' . $arr['image'];
                    // }
                    $price = floatval($arr['price']);
                ?>
                        <div class="col-sm-6 col-md-6 col-lg-3 service-card" data-category="walkin" data-price="<?= $price ?>" data-name="walk-in">
                            <a href="services/avail_walkin.php?id=<?= $arr['walkin_id'] ?>" class="program-link">
                                <div class="card">
                                    <div class="card-header text-white text-center"
                                    style="background-image: url('<?= $imagePath ?>'); 
                                    background-size: cover; 
                                    background-position: center;
                                    position: relative;">
                            
                                        <div class="overlay-header">
                                        <h2 class="fw-bold mb-0 service-name">Walk-in</h2>
                                        </div>
                                        <div class="overlay-darker"></div>
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
                <hr class="my-4 section-divider" style="border: 2px solid #000;">
            </div>
            
            <!-- Programs Section -->
            <div class="section-container" data-category="programs">
                <h2 class="section-heading">COACHING PROGRAMS</h2>
                <div class="row g-4 mb-4 program-services-row">
                    <?php foreach ($program_services as $program) { 
                        // Only show if at least one coach is assigned
                        if (empty($program['available_types'])) continue;
                        // Default image path
                        $defaultImage = '../cms_img/default/program.jpeg';

                        // Get the image path
                        $imagePath = $defaultImage; // Set default first
                        if (!empty($program['image']) && file_exists(__DIR__ . "/../cms_img/programs/" . $program['image'])) {
                            $imagePath = '../cms_img/programs/' . $program['image'];
                        }
                        
                        // Format available types
                        $types = $program['available_types'] ? explode(',', $program['available_types']) : [];
                        $typeLabels = array_map(function($type) {
                            return ucfirst($type) . ' Training';
                        }, $types);
                        ?>
                        <div class="col-sm-6 col-md-6 col-lg-3 service-card" data-category="programs" data-name="<?= strtolower(htmlspecialchars($program['program_name'])) ?>">
                            <a href="services/avail_program.php?id=<?= $program['program_id'] ?>" class="program-link">
                                <div class="card h-100">
                                    <div class="card-header text-white text-center"
                                        style="background-image: url('<?= $imagePath ?>'); 
                                        background-size: cover; 
                                        background-position: center;
                                        height: 150px;
                                        position: relative;">
                                        <div class="overlay-header">
                                            <h2 class="fw-bold mb-0 service-name"><?= htmlspecialchars($program['program_name']) ?></h2>
                                        </div>
                                        <div class="overlay-darker"></div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text mb-1">Program Type:</p>
                                        <?php if (!empty($typeLabels)): ?>
                                            <p class="card-text mb-1">
                                                <?= implode(',<br>', array_map('htmlspecialchars', $typeLabels)) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php } ?>
                </div>
                
                <br id="Optional-Services">
                <hr class="my-4 section-divider" style="border: 2px solid #000;">
            </div>

            <!-- Rental Services Section -->
            <div class="section-container" data-category="other">
                <h2 class="section-heading">OTHER OFFERED SERVICES</h2>
                <div class="row g-4 mb-4">
                    <?php foreach ($rental_services as $rental){
                        // Default image path
                        $defaultImage = '../cms_img/default/rental.jpeg';

                        // Get the image path
                        $imagePath = $defaultImage; // Set default first
                        if (!empty($rental['image']) && file_exists(__DIR__ . "/../cms_img/rentals/" . $rental['image'])) {
                            $imagePath = '../cms_img/rentals/' . $rental['image'];
                        }  
                        $price = floatval($rental['price']);
                        ?>
                        <div class="col-sm-6 col-md-6 col-lg-3 service-card" data-category="other" data-price="<?= $price ?>" data-name="<?= strtolower($rental['service_name']) ?>">
                            <a href="services/avail_rental.php?id=<?= $rental['rental_id'] ?>" class="program-link">
                                <div class="card">
                                    <div class="card-header text-white text-center"
                                    style="background-image: url('<?= $imagePath ?>'); 
                                    background-size: cover; 
                                    background-position: center;
                                    position: relative;">
                                    <div class="overlay-header">
                                        <h2 class="fw-bold mb-0 service-name"><?= $rental['service_name'] ?></h2>
                                    </div>
                                    <div class="overlay-darker"></div>
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
</div>

<!-- Add JavaScript for the filters -->
<script>

</script>
</section>

<?php if (isset($_SESSION['success_message'])): ?>
    <div id="successToast" class="toast position-fixed top-50 start-50 translate-middle p-3" role="alert" aria-live="assertive" aria-atomic="true" style="z-index: 1050; width: 400px; font-size: 1.2rem;">
        <div class="toast-header" style="background-color: transparent; font-size: 1.5rem;">
            <strong class="me-auto">Success</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" style="text-align: center;">
            <?= $_SESSION['success_message']; ?>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let toastEl = document.getElementById('successToast');
            let toast = new bootstrap.Toast(toastEl);
            toast.show();

            // Remove session message after displaying
            setTimeout(() => { 
                fetch('clear_session.php'); 
            }, 3500);
        });
    </script>

    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<!-- Cart Overlay -->
<div class="cart-overlay" id="cartOverlay"></div>

<!-- Cart Sidebar -->
<div class="cart-sidebar" id="cartSidebar" aria-label="Shopping Cart">
    <div class="cart-header">
        <h5>Item List</h5>
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

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('serviceSearch');
    const categoryFilter = document.getElementById('categoryFilter');
    const priceFilter = document.getElementById('priceFilter');
    const filterResultsContainer = document.getElementById('filterResultsContainer');
    const filterResults = document.getElementById('filterResults');
    const sectionContainers = document.querySelectorAll('.section-container');
    const sectionHeadings = document.querySelectorAll('.section-heading');
    const sectionDividers = document.querySelectorAll('.section-divider');
    
    // Function to check if any filter is active
    function isFilterActive() {
        return (
            searchInput.value.trim() !== '' || 
            categoryFilter.value !== 'all' || 
            priceFilter.value !== 'all'
        );
    }
    
    // Function to filter services
    function filterServices() {
        // Get filter values
        const searchTerm = searchInput.value.toLowerCase().trim();
        const category = categoryFilter.value;
        const priceRange = priceFilter.value;
        
        // Track if we have any matches
        let hasMatches = false;
        
        // Clear previous results
        filterResults.innerHTML = '';
        
        // Get all service cards
        const serviceCards = document.querySelectorAll('.service-card');
        
        if (isFilterActive()) {
            // Hide all section containers and their headings
            sectionContainers.forEach(container => {
                container.style.display = 'none';
            });
            
            // Show filter results container
            filterResultsContainer.style.display = 'block';
            
            // Filter the cards
            serviceCards.forEach(card => {
                // Get card data
                const cardName = card.getAttribute('data-name') || '';
                const cardCategory = card.getAttribute('data-category') || '';
                const cardPriceStr = card.getAttribute('data-price') || '0';
                const cardPrice = parseFloat(cardPriceStr);
                
                // Category filter
                let categoryMatch = category === 'all' || cardCategory === category;
                
                // Price filter
                let priceMatch = priceRange === 'all';
                if (priceRange === '0-500' && cardPrice >= 0 && cardPrice <= 500) priceMatch = true;
                if (priceRange === '501-1000' && cardPrice > 500 && cardPrice <= 1000) priceMatch = true;
                if (priceRange === '1001-2000' && cardPrice > 1000 && cardPrice <= 2000) priceMatch = true;
                if (priceRange === '2001+' && cardPrice > 2000) priceMatch = true;
                
                // Name search filter
                const nameMatch = cardName.includes(searchTerm);
                
                // If the card matches all filters
                if (nameMatch && categoryMatch && priceMatch) {
                    hasMatches = true;
                    // Clone the card and add it to the results
                    const clone = card.cloneNode(true);
                    filterResults.appendChild(clone);
                }
            });
            
            // If no matches found, show message
            if (!hasMatches) {
                filterResults.innerHTML = '<div class="col-12 text-center"><h3>No matching services found</h3></div>';
            }
        } else {
            // Reset display if no filters active
            filterResultsContainer.style.display = 'none';
            
            // Show all section containers
            sectionContainers.forEach(container => {
                container.style.display = 'block';
            });
        }
        if (!isFilterActive()) {
            restoreScrollPosition();
        }
    }
    
    // Add event listeners to filters
    searchInput.addEventListener('input', filterServices);
    categoryFilter.addEventListener('change', filterServices);
    priceFilter.addEventListener('change', filterServices);
});

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
<!-- Add this right after your last <script> tag in services.php -->
<script>
    let lastScrollPosition = 0;
  // Set login status based on PHP session
  const userLoggedIn = <?php echo isset($_SESSION['personal_details']) ? 'true' : 'false'; ?>;
     // Function to save scroll position
    function saveScrollPosition() {
    lastScrollPosition = window.scrollY;
    localStorage.setItem('servicesScrollPosition', lastScrollPosition);
    }
    // Function to restore scroll position
    function restoreScrollPosition() {
    const savedPosition = localStorage.getItem('servicesScrollPosition');
    if (savedPosition !== null) {
        window.scrollTo({
        top: parseInt(savedPosition),
        behavior: 'auto' // Use 'auto' instead of 'smooth' for immediate positioning
        });
    }
    }
    function saveBeforeFilter() {
    if (isFilterActive()) {
        saveScrollPosition();
    }
    }
    // Add these event listeners to your existing DOMContentLoaded event
    document.addEventListener('DOMContentLoaded', function() {
    // Restore position when page loads
    window.addEventListener('load', function() {
        setTimeout(restoreScrollPosition, 50); // Small delay to ensure page is fully rendered
    });
    
    // Save position before filtering
    const searchInput = document.getElementById('serviceSearch');
    const categoryFilter = document.getElementById('categoryFilter');
    const priceFilter = document.getElementById('priceFilter');
    
    searchInput.addEventListener('focus', saveBeforeFilter);
    categoryFilter.addEventListener('focus', saveBeforeFilter);
    priceFilter.addEventListener('focus', saveBeforeFilter);
    
    // Save position before clicking on service cards
    const programLinks = document.querySelectorAll('.program-link');
    programLinks.forEach(link => {
        link.addEventListener('click', saveScrollPosition);
    });
    
    // Save position when using scroll buttons
    const scrollButtons = document.querySelectorAll('.scroll-btn');
    scrollButtons.forEach(button => {
        button.addEventListener('click', saveScrollPosition);
    });
    
    // Save position when opening/closing cart
    const cartBtn = document.getElementById('showCartBtn');
    const closeCart = document.getElementById('closeCart');
    
    if (cartBtn) cartBtn.addEventListener('click', saveScrollPosition);
    if (closeCart) closeCart.addEventListener('click', restoreScrollPosition);
    });

  // Function to check if user is logged in and show popup if not
    function checkLoginAndShowPopup(event) {
    if (!userLoggedIn) {
        event.preventDefault();
        
        // Create modal overlay
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.style.position = 'fixed';
        overlay.style.top = '0';
        overlay.style.left = '0';
        overlay.style.width = '100%';
        overlay.style.height = '100%';
        overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        overlay.style.zIndex = '1000';
        
        // Create modal container
        const modal = document.createElement('div');
        modal.className = 'login-modal';
        modal.style.position = 'fixed';
        modal.style.top = '50%';
        modal.style.left = '50%';
        modal.style.transform = 'translate(-50%, -50%)';
        modal.style.backgroundColor = 'white';
        modal.style.padding = '20px';
        modal.style.borderRadius = '8px';
        modal.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.2)';
        modal.style.zIndex = '1001';
        modal.style.maxWidth = '400px';
        modal.style.width = '90%';
        modal.style.textAlign = 'center';
        
        // Create modal content
        const icon = document.createElement('i');
        icon.className = 'fas fa-exclamation-circle';
        icon.style.fontSize = '3rem';
        icon.style.color = '#c92f2f';
        icon.style.marginBottom = '1rem';
        
        const title = document.createElement('h3');
        title.textContent = 'Login Required';
        title.style.marginBottom = '15px';
        title.style.fontSize = '1.5rem';
        
        const message = document.createElement('p');
        message.textContent = 'Please log in first to avail Gym offers, programs, and services.';
        message.style.marginBottom = '20px';
        
        // Add register message
        const registerMessage = document.createElement('p');
        registerMessage.style.fontSize = '0.9rem';
        registerMessage.style.marginBottom = '15px';
        
        const registerText = document.createTextNode("Don't have an account? ");
        registerMessage.appendChild(registerText);
        
        const registerLink = document.createElement('a');
        registerLink.textContent = 'Sign up here';
        registerLink.href = '../register/register.php';
        registerLink.style.color = '#c92f2f';
        registerLink.style.textDecoration = 'underline';
        registerLink.style.fontWeight = 'bold';
        registerLink.style.cursor = 'pointer';
        registerMessage.appendChild(registerLink);
        
        const buttonContainer = document.createElement('div');
        buttonContainer.style.display = 'flex';
        buttonContainer.style.justifyContent = 'center';
        buttonContainer.style.gap = '10px';
        
        const loginButton = document.createElement('button');
        loginButton.textContent = 'Login';
        loginButton.style.backgroundColor = '#c92f2f';
        loginButton.style.color = 'white';
        loginButton.style.padding = '10px 20px';
        loginButton.style.border = 'none';
        loginButton.style.borderRadius = '4px';
        loginButton.style.cursor = 'pointer';
        loginButton.style.fontWeight = 'bold';
        loginButton.addEventListener('click', function() {
        window.location.href = '../login/login.php';
        });
        
        const cancelButton = document.createElement('button');
        cancelButton.textContent = 'Cancel';
        cancelButton.style.backgroundColor = '#6c757d';
        cancelButton.style.color = 'white';
        cancelButton.style.padding = '10px 20px';
        cancelButton.style.border = 'none';
        cancelButton.style.borderRadius = '4px';
        cancelButton.style.cursor = 'pointer';
        cancelButton.addEventListener('click', function() {
        document.body.removeChild(overlay);
        });
        
        // Assemble the modal
        buttonContainer.appendChild(loginButton);
        buttonContainer.appendChild(cancelButton);
        
        modal.appendChild(icon);
        modal.appendChild(title);
        modal.appendChild(message);
        modal.appendChild(registerMessage);  // Add the register message
        modal.appendChild(buttonContainer);
        
        overlay.appendChild(modal);
        document.body.appendChild(overlay);
    }
    }

  // Add the event listener to all program links when the document is loaded
  document.addEventListener('DOMContentLoaded', function() {
    // Add the event listeners to all program links
    const programLinks = document.querySelectorAll('.program-link');
    programLinks.forEach(link => {
      link.addEventListener('click', checkLoginAndShowPopup);
    });
  });
</script>
