<?php
// Configuration and Database Connection
require_once '../config.php';

// Function to dynamically fetch image paths
function getImagePath($imageName) {
    if (strpos($imageName, 'cms_img/') === 0) {
        return '../' . $imageName; // Already a valid path
    }
    $possibleSubdirectories = ['products', 'gallery', 'offers', 'staff'];
    foreach ($possibleSubdirectories as $subdir) {
        $potentialPath = '../cms_img/' . $subdir . '/' . $imageName;
        if (file_exists($potentialPath)) {
            return $potentialPath;
        }
    }
    return '../cms_img/default.jpg'; // Default fallback
}

// Centralized function for querying the database
function executeQuery($query, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Database query error: ' . $e->getMessage());
        return [];
    }
}

// Fetch specific content for sections
$welcomeContent = executeQuery("SELECT * FROM website_content WHERE section = 'welcome'")[0] ?? [];
$offersContent = executeQuery("SELECT * FROM website_content WHERE section = 'offers'")[0] ?? [];
$aboutUsContent = executeQuery("SELECT * FROM website_content WHERE section = 'about_us'")[0] ?? [];
// Fetch contact details with latitude and longitude
$contactContent = executeQuery("SELECT * FROM website_content WHERE section = 'contact'")[0] ?? [];

// Get coordinates, use default if not set
$latitude = $contactContent['latitude'] ?? 6.913126;
$longitude = $contactContent['longitude'] ?? 122.072516;

$offers = executeQuery("SELECT * FROM gym_offers LIMIT 4");
$products = executeQuery("SELECT * FROM products LIMIT 6");
$galleryImages = executeQuery("SELECT * FROM gallery_images LIMIT 4");
$staffMembers = executeQuery("SELECT * FROM staff");
require_once('includes/header.php');
?>
  <style>
   .offer-button {
      display: inline-block;
      background-color: #f4f4f4;
      color: black;
      padding: 10px 20px;
      text-decoration: none;
      border-radius: 5px;
      margin-top: 10px;
    }
    /* Global Image Container Styles */
    .fixed-image-container {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 20px;
      padding: 20px 0;
    }

    .S-AboutUs .image-container img:hover {
      transform: scale(1.05);
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
      .S-AboutUs .image-container {
        grid-template-columns: 1fr;
      }

      .image-card,
      .S-Staffs .image-staff,
      .S-Products .product-box {
        width: 100%;
        max-width: 350px;
      }
    }
  </style>
  <body>
  <main class="home-main">
  <header class="home-header">
        <div class="header-content">
            <h1 class="companyName"><?php 
                echo htmlspecialchars($welcomeContent['company_name'] ?? 'Company Name'); 
            ?></span></h1>
            <p class="description"><?php 
                echo htmlspecialchars($welcomeContent['description'] ?? 'Welcome description goes here.'); 
            ?></p>
            <a href="?services=1" style="text-decoration: none;">
                <button class="joinButton">
                    Start your Journey <img src="../icon/arrow-right-solid.svg" alt="Join Now" />
                </button>
            </a>
        </div>
  </header>
   

       <!-- Offers Section -->
<section class="S-Offers" id="S-Offers">

    <div class="design-container">
    <div class="rhombus rhombus-3"></div>
    <div class="rhombus rhombus-1"></div>
    <div class="rhombus rhombus-4"></div>
    </div>

    <div class="offers-text">
        <h1 class="title">Gym Offers</h1>
        <p class="text-secondary"><?php 
            echo htmlspecialchars($offersContent['description'] ?? 'Offers description goes here.'); 
        ?></p>
    </div>

    <div class="offers-container">
        <div class="offers-wrapper">
            <?php foreach ($offers as $offer): ?>
                <div class="offer-slide">
                    <img src="<?php echo htmlspecialchars(getImagePath($offer['image_path'])); ?>"
                         alt="<?php echo htmlspecialchars($offer['title'] ?? 'Gym Offer'); ?>">
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Products Section -->
<section class="S-Products">
<div class="design-container">
    <div class="rhombus rhombus-3"></div>
    <div class="rhombus rhombus-1"></div>
    <div class="rhombus rhombus-4"></div>
    </div>
    <div class="products-text">
        <h1 class="title">Products you may like</h1>
        <p class="text-secondary">Check out our latest fitness products!</p>
    </div>
    <div class="product-container">
        <?php foreach ($products as $product): ?>
            <div class="product-box">
                <img 
                    class="product-image" 
                    src="<?php echo htmlspecialchars(getImagePath($product['image_path'])); ?>"
                    alt="<?php echo htmlspecialchars($product['name']); ?>" 
                />
                <div class="image-card-content">
                    <p class="product-title"><?php echo htmlspecialchars($product['name']); ?></p>
                    <p class="product-text"><?php echo htmlspecialchars($product['description']); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

        <!-- About Us/gallery Section -->
<section class="S-AboutUs" id="S-AboutUs">

    
    <div class="design-container">
        <div class="rhombus rhombus-1"></div>
        <div class="rhombus rhombus-2"></div>
        <div class="rhombus rhombus-3"></div>
    </div>

    <div class="aboutUs-text">
    
        <h1 class="title">About Us</h1>
        <p class="text-secondary"><?php echo htmlspecialchars($aboutUsContent['description'] ?? 'About us description goes here.'); ?></p>

        <div class="joinNow-container">
            <h1 class="tagline">Go Home or Go Hard</h1>
            <p>Come be one of us now</p>
            <a href="?services=1" style="text-decoration: none;">
                <button class="joinButton-1">
                    Join Now <img src="../icon/arrow-right-solid.svg" alt="Join Now" />
                </button>
            </a>
        </div>
    </div>

    <div class="image-container">
        <?php 
        $imageClasses = ['img1', 'img2', 'img3', 'img4'];
        foreach ($galleryImages as $index => $image): 
        ?>
            <img 
                class="<?php echo htmlspecialchars($imageClasses[$index] ?? 'gallery-image'); ?>" 
                src="<?php echo htmlspecialchars(getImagePath($image['image_path'])); ?>"
                alt="<?php echo htmlspecialchars($image['alt_text'] ?? 'Gallery Image'); ?>" 
            />
        <?php endforeach; ?>
    </div>
</section>

        <!-- <section class="S-Tagline">
          
          <a href="?services=1" style="text-decoration: none;">
            <button class="joinButton">
              Join Now <img src="../icon/arrow-right-solid.svg" alt="Join Now" />
            </button>
          </a>
        </section> -->

<section class="S-Staffs">
    
<div class="design-container">
    <div class="rhombus rhombus-6"></div>
    <div class="rhombus rhombus-2"></div>
    <div class="rhombus rhombus-4"></div>
    </div>

    <h1 class="title">Gym Staffs</h1>
    
    <div class="staff-container">
        <?php if (!empty($staffMembers)) : ?>
            <?php foreach ($staffMembers as $staff): ?>
                <div class="image-staff">
                    <img 
                        src="<?php echo htmlspecialchars(getImagePath($staff['image_path'])); ?>"
                        alt="<?php echo htmlspecialchars($staff['name']); ?>" 
                    />
                    <div class="image-card-content">
                        <p class="name"><?php echo htmlspecialchars($staff['name']); ?></p>
                        <p class="status"><?php echo htmlspecialchars($staff['status']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-staff">No added Staff</p>
        <?php endif; ?>
    </div>
</section>

<?php
// Fetch contact details with latitude and longitude
$contactContent = executeQuery("SELECT * FROM website_content WHERE section = 'contact'")[0] ?? [];

// Get coordinates, use default if not set
$latitude = $contactContent['latitude'] ?? 6.913126;
$longitude = $contactContent['longitude'] ?? 122.072516;
?>

<section class="S-ContactUs" id="S-ContactUs">

    <div class="design-container">
    <div class="rhombus rhombus-1"></div>
    <div class="rhombus rhombus-2"></div>
    <div class="rhombus rhombus-3"></div>
    </div>
    <div class="contactUs-text">
        <h1>You can find us here, sign up, local supplements and equipments</h1>

        <div class="joinNow-container">
                <a href="?services=1" style="text-decoration: none;">
                    <button class="joinButton-2">
                        Sign up to start now <img src="../icon/arrow-right-solid.svg" alt="Join Now" />
                    </button>
                </a>
            </div>
    </div>
  <div class="image-location">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    
    <div id="map"></div>
    
    <script>
      var map = L.map('map').setView([<?php echo $latitude; ?>, <?php echo $longitude; ?>], 16);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
      L.marker([<?php echo $latitude; ?>, <?php echo $longitude; ?>])
        .addTo(map)
        .bindPopup('Gym Location')
        .openPopup();
    </script>
  </div>
</section>


<footer class="footer">
 <!-- <div class="design-container">
    <div class="rhombus rhombus-1"></div>
    <div class="rhombus rhombus-2"></div>
    <div class="rhombus rhombus-3"></div>
    </div> -->

    <div class="footer-container">
        <div class="logo-section">
        <div class="home-logo">
        <img src="../cms_img/jc_logo1.png" alt="Gym Logo" class="logo-image">
            </div>
            <div class="contact-info">
                <p>9464 Columbia Ave.<br>
                New York, NY 10029</p>
                <p>info@bull.com</p>
            </div>
            <div class="social-icons">
                <a href="#"><img src="facebook.png" alt="Facebook"></a>
                <a href="#"><img src="twitter.png" alt="Twitter"></a>
                <a href="#"><img src="instagram.png" alt="Instagram"></a>
                <a href="#"><img src="linkedin.png" alt="LinkedIn"></a>
            </div>
        </div>

        <div class="menu-section">
            <h3>Menu</h3>
            <ul class="menu-list">
                <li><a href="#">Home</a></li>
                <li><a href="#">Services</a></li>
                <li><a href="#S-AboutUs">About</a></li>
                <li><a href="#S-ContactUs">Contact Us</a></li>
            </ul>
        </div>

        <!-- <div class="quick-links-section">
            <h3>Quick Links</h3>
            <ul class="quick-links-list">
                <li><a href="#">Login</a></li>
                <li><a href="#">Register</a></li>
                <li><a href="#">Contact Us</a></li>
                <li><a href="#">Privacy Policy</a></li>
            </ul>
        </div> -->

        <div class="operational-section">
            <h3>Operational</h3>
            <div class="operational-info">
                <p>Every day: 9:00 – 22:00<br>
                Sat - Sun: 8:00 – 21:00</p>
                <p>New Schedule?</p>
                <p>+ (123) 1600-567-8990</p>
            </div>
        </div>
    </div>

    <div class="copyright">
        Copyright © XLB. All Rights Reserved.
    </div>
</footer>

</main>

    <!-- Add JavaScript for Carousel Functionality -->
  <script>
// document.addEventListener('DOMContentLoaded', function() {
//     const carousel = document.querySelector('.carousel-wrapper');
//     const slides = document.querySelectorAll('.carousel-slide');
//     const dots = document.querySelectorAll('.carousel-dot');
//     const prevButton = document.querySelector('.carousel-prev');
//     const nextButton = document.querySelector('.carousel-next');

//     // Check if elements exist before proceeding
//     if (!slides.length || !dots.length || !prevButton || !nextButton) {
//         console.warn('Carousel elements not found');
//         return;
//     }

//     let currentSlide = 0;

//     function showSlide(index) {
//         // Safely handle slide and dot updates
//         slides.forEach((slide, i) => {
//             slide.classList.toggle('active', i === index);
//         });

//         dots.forEach((dot, i) => {
//             dot.classList.toggle('active', i === index);
//         });

//         currentSlide = (index + slides.length) % slides.length;
//     }

//     // Next slide
//     nextButton.addEventListener('click', () => {
//         showSlide(currentSlide + 1);
//     });

//     // Previous slide
//     prevButton.addEventListener('click', () => {
//         showSlide(currentSlide - 1);
//     });

//     // Dot navigation
//     dots.forEach(dot => {
//         dot.addEventListener('click', () => {
//             const slideIndex = parseInt(dot.getAttribute('data-slide'));
//             showSlide(slideIndex);
//         });
//     });

//     // Optional: Auto-advance slides every 5 seconds
//     setInterval(() => {
//         showSlide(currentSlide + 1);
//     }, 5000);

//     // Initialize first slide
//     showSlide(0);
// });
window.addEventListener("scroll", function(){
    var header = document.querySelector(".home-navbar");
    header.classList.toggle("sticky", window.scrollY > window.innerHeight);
})
  </script>
</body>
</html>