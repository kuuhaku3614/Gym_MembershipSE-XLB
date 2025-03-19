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

$offers = executeQuery("SELECT * FROM gym_offers LIMIT 8");
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
            ?></h1>
            <p class="description subtext1"><?php 
                echo htmlspecialchars($welcomeContent['description'] ?? 'Welcome description goes here.'); 
            ?></p>
            <?php if (!$isLoggedIn): ?>
            <a href="../register/register.php" style="text-decoration: none;">
                <button class="joinButton">
                    Start your Journey <img src="../icon/arrow-right-solid.svg" alt="Join Now" />
                </button>
            </a>
            <?php endif; ?>
        </div>
        <div class="location-pointer">
            <div class="location-box">
                <span>Gym Location</span>
                <i class="location-arrow">↓</i>
            </div>
        </div>
    </header>

    <style>
        @media (max-width: 768px) {
            .home-header {
                height: 70vh;
            }
            .companyName {
                font-size: 2em;
            }

            .description {
                font-size: 1em;
            }

            .joinButton {
                font-size: 0.9em;
                padding: 8px 16px;
            }

            .joinButton img {
                padding: 5px;
                width: 16px;
                height: 16px;
            }
            .design-container {
                height: 40px;
                width: 80px;
                left: 30px;
                top: 30px;
            }
        }
    </style>
<!-- Offers Section -->
<section class="S-Offers" id="S-Offers">

    <div class="design-container">
        <div class="rhombus rhombus-3"></div>
        <div class="rhombus rhombus-1"></div>
        <div class="rhombus rhombus-4"></div>
    </div>

    <div class="offers-text">
        <h1 class="title">Gym Offers</h1>
        <p class="subtext"><?php 
            echo htmlspecialchars($offersContent['description'] ?? 'Offers description goes here.'); 
        ?></p>
  
    </div>

    <!-- Navigation Buttons -->
  
    <div class="slider-navigation d-flex justify-content-end">
        <button class="nav-button prev-button btn btn-secondary" style="font-size: 1.5em;">‹</button>
        <button class="nav-button next-button btn btn-secondary" style="font-size: 1.5em;">›</button>
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

<style>
    @media (max-width: 768px) {
        .S-Offers{
            animation: none;
            position: relative;
        }
        .title {
            font-size: 2em!important;
            text-align: right;
        }
        .S-Offers .title{
            margin-top: 15px;
        }
        .offers-text{
            width: 100%;
        }

        .S-Offers .offers-text .subtext, .subtext {
            font-size: 0.7em;
            justify-content: center;

        }

        .S-Offers .nav-button {
            display: none;
        }

        .S-Offers .offers-wrapper {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .S-Offers .offer-slide {
            flex: 0 0 auto;
            width: 40%;
            max-height: 200px;
            
        }
    }
</style>

<!-- Products Section -->
<section class="S-Products">
<div class="design-container">
    <div class="rhombus rhombus-3"></div>
    <div class="rhombus rhombus-1"></div>
    <div class="rhombus rhombus-4"></div>
    </div>
    <div class="products-text">
        <h1 class="title">Products you may like</h1>
        <p class="subtext">Check out our latest fitness products!</p>
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

                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<style>
    @media (max-width: 768px) {
        .S-Products .products-text {
            text-align: center;
        }
        .S-Products .product-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-auto-rows: auto;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            gap: 5px;
            align-self: center;
        }

        .S-Products .product-box {
            width: 100%;
            height: 80%;
            max-width: none;
        }

        .S-Products .product-image {
            width: 100%;
            height: 80%;
        }
        .image-card-content {
            font-size: 0.9em;
        }
        .S-Products .title {
            text-align: left;
        }
    }
</style>
<section class="S-AboutUs" id="S-AboutUs">

    
    <div class="design-container">
        <div class="rhombus rhombus-1"></div>
        <div class="rhombus rhombus-2"></div>
        <div class="rhombus rhombus-3"></div>
    </div>

    <div class="aboutUs-text">
    
        <h1 class="title">About Us</h1>
        <div class="aboutUs-content">
        <p class="subtext"><?php echo htmlspecialchars($aboutUsContent['description'] ?? 'About us description goes here.'); ?></p>
        <div class="joinNow-container">
            <?php if (!$isLoggedIn): ?>
            <a href="../register/register.php" style="text-decoration: none;">
                <button class="joinButton-1">
                    Join Now <img src="../icon/arrow-right-solid.svg" alt="Join Now" />
                </button>
            </a>
            <?php endif; ?>
        </div>
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

<style>
    @media (max-width: 768px) {
        .S-AboutUs{
            flex-direction: column;
            height: auto;
            gap: 5px;
        }
        .S-AboutUs .title {
          margin-top: 60px;
          text-align: left;
        }
        .aboutUs-text {
            text-align: center;
            padding: 0 10px;
            width: 100%;
        }

        .aboutUs-text .title {
            font-size: 2em;
        }
        .aboutUs-content{
            width: 100%;
            display: flex;
            gap:10px;
        }

        .aboutUs-text .subtext {
            font-size: 0.6em;
            max-height: 400px;
            overflow-y: auto;
            width: 70%;
        }

        .joinNow-container {
            margin-top: 20px;
            width: 100px!important;
            padding: 10px;
            align-self: flex-end;
        }

        .joinButton-1 {
            font-size: 0.5em;
            width: 120%;
            height: 50%;
            padding: 2px 4px;
        }
        .joinNow-container p {
            font-size: 0.6em;
            margin-bottom: 2px;

        }
        .joinNow-container .tagline {
            font-size: 1.2em;
        }
        .S-AboutUs a {
            display: flex;
            justify-content: center;
        }

        .joinButton-1 img {
            width: 15px;
            height: 15px;
            padding: 4px;
        }

        .image-container {
            min-width: 100%;
            height: 310px;
            display: flex;
            flex-wrap: wrap;
            overflow-x: auto;
            justify-content: center;
            -webkit-overflow-scrolling: touch;
            gap: 5px;
        }

        .image-container img {
            flex: 0 0 auto;
            width: 100px;
            max-height: 50%; /* Increased height */
            object-fit: cover; /* Ensure the image covers the area */
        }
    
}
</style>

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

<style>
    @media (max-width: 768px) {
        .S-Staffs .title {
            text-align: center;
            margin-bottom: 10px;
            text-align: left;
        }

        .staff-container {
            justify-content: space-around!important;
            display: flex;
            flex-direction: column;
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            max-height: 220px;
            width: 100%;
        }

        .S-Staffs .image-staff { 
            height: 200px;
            width: 160px;
        }

        .S-Staffs .image-staff img {
            width: 100%;
            height: 70%;
        }

        .S-Staffs .image-card-content {
            font-size: 0.6em;
        }

        .S-Staffs .title {
            text-align: left;
        }
    }
</style>

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
        <h1 class="title">You can find us here, sign up, local supplements and equipments</h1>

        <div class="joinNow-container">
            <?php if (!$isLoggedIn): ?>
            <a href="../register/register.php" style="text-decoration: none;">
                <button class="joinButton-2">
                    Sign up to start now <img src="../icon/arrow-right-solid.svg" alt="Join Now" />
                </button>
            </a>
            <?php endif; ?>
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

<style>
    @media (max-width: 768px) {
        .S-ContactUs {
            flex-direction: column;
        }

        .S-ContactUs .title {
            margin-top: 40px;
            font-size: 2em!important;
            text-align: left;
        }

        .contactUs-text {
            width: 100%;
            text-align: center;
        }

        .S-ContactUs .joinNow-container {
            margin-top: 0px;
        }

        .S-ContactUs .joinButton-2 {
            font-size: 0.6em;
            padding: 4px 10px;
        }

        .S-ContactUs .joinButton-2 img {
            width: 16px;
            height: 16px;
        }

        .S-ContactUs .image-location {
            width: 100%;
            height: 200px;
        }
        .S-ContactUs .image-location #map {
            width: 100%;
            height: 100%;
        }
        .footer-container {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
        }
        .footer{
            padding: 20px;
        }
        .contact-info, .operational-info, .menu-list a, .footer-container h3 {
            font-size: 0.7em;
        }
        .copyright {
            margin-top: 0px;
        }
        .menu-list li{
            margin-bottom: 5px;
        }
    }
</style>


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
                <p>Simosa<br>
               Canelar, Zamboanga City 7000</p>
                <p>+ (63) 9056013159</p>    
            </div>
            <!-- <div class="social-icons">
                <a href="#"><img src="facebook.png" alt="Facebook"></a>
                <a href="#"><img src="twitter.png" alt="Twitter"></a>
                <a href="#"><img src="instagram.png" alt="Instagram"></a>
                <a href="#"><img src="linkedin.png" alt="LinkedIn"></a>
            </div>-->
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
                <p>+ (63) 9056013159</p>
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
    document.addEventListener('DOMContentLoaded', function () {
    const wrapper = document.querySelector('.offers-wrapper');
    const prevButton = document.querySelector('.prev-button');
    const nextButton = document.querySelector('.next-button');
    const slideWidth = wrapper.querySelector('.offer-slide').offsetWidth + 20; // Include gap

    prevButton.addEventListener('click', () => {
        wrapper.scrollBy({ left: -slideWidth * 4, behavior: 'smooth' }); // Scroll 4 slides left
    });

    nextButton.addEventListener('click', () => {
        wrapper.scrollBy({ left: slideWidth * 4, behavior: 'smooth' }); // Scroll 4 slides right
    });
});
  </script>
  <!-- location pointer -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const locationBox = document.querySelector('.location-box');
        const mapSection = document.getElementById('S-ContactUs');
        
        locationBox.addEventListener('click', function() {
        mapSection.scrollIntoView({ 
            behavior: 'smooth' 
        });
        });
    });
</script>
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