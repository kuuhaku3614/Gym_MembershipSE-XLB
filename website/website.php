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
$contactContent = executeQuery("SELECT * FROM website_content WHERE section = 'contact'")[0] ?? [];

$offers = executeQuery("SELECT * FROM gym_offers LIMIT 4");
$products = executeQuery("SELECT * FROM products LIMIT 8");
$galleryImages = executeQuery("SELECT * FROM gallery_images LIMIT 4");
$staffMembers = executeQuery("SELECT * FROM staff");
include('includes/header.php');
?>

  <!-- Suggested CSS to be added to your stylesheet -->
  <style>
    .offers-carousel {
    position: relative;
    width: 500px;  /* Fixed width */
    height: 500px; /* Fixed height */
    overflow: hidden;
    margin: 0 auto;
}

.carousel-wrapper {
    display: flex;
    transition: transform 0.5s ease;
}

.carousel-slide {
    min-width: 100%;
    display: none;
    position: relative;
}

.carousel-slide.active {
    display: block;
}

.carousel-slide img {
    width: 100%;  /* Fixed width */
    height: 100%; /* Fixed height */
    object-fit: cover; /* This ensures images maintain aspect ratio */
}

.carousel-caption {
    position: absolute;
    bottom: 20px;
    left: 20px;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 15px;
    border-radius: 5px;
}

.carousel-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: absolute;
    width: 100%;
    bottom: 10px; /* Move controls to bottom */
    padding: 0 10px;
}

.carousel-dots {
    display: flex;
    justify-content: center;
    position: absolute;
    bottom: -25px; /* Position dots below the carousel */
    left: 50%;
    transform: translateX(-50%);
    z-index: 2;
}

.carousel-dot {
    height: 10px;
    width: 10px;
    background-color: #bbb;
    border-radius: 50%;
    display: inline-block;
    margin: 0 5px;
    cursor: pointer;
}

.carousel-dot.active {
    background-color: #717171;
}

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

    .image-card {
      width: 250px;
      height: 350px;
      background-color: #f4f4f4;
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .image-card:hover {
      transform: scale(1.05);
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }

    .image-card img {
      width: 100%;
      height: 250px;
      object-fit: cover;
    }

    .image-card-content {
      padding: 15px;
      text-align: center;
    }

    /* Products Section Specific Styles */
    .S-Products .product-container {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 20px;
    }

    .S-Products .product-box {
      width: 250px;
      height: 350px;
      background-color: #f4f4f4;
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .S-Products .product-box img {
      width: 100%;
      height: 250px;
      object-fit: cover;
    }

    .S-Products .product-title {
      font-weight: bold;
      text-align: center;
      margin: 10px 0 5px;
    }

    .S-Products .product-text {
      text-align: center;
      color: #666;
      padding: 0 15px;
    }

    /* Gallery Section Specific Styles */
    .S-AboutUs .image-container {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
      max-width: 1200px;
      margin: 0 auto;
    }

    .S-AboutUs .image-container img {
      width: 100%;
      height: 300px;
      object-fit: cover;
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
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
  <header class="home-header"></header>

  <main class="home-main">
    <!-- Welcome Section -->
    <section class="S-Welcome">
            <h1>Welcome To <span class="companyName"><?php 
                echo htmlspecialchars($welcomeContent['company_name'] ?? 'Company Name'); 
            ?></span></h1>
            <p><?php 
                echo htmlspecialchars($welcomeContent['description'] ?? 'Welcome description goes here.'); 
            ?></p>
            <a href="?services=1" style="text-decoration: none;">
                <button class="joinButton">
                    Join Now <img src="../icon/arrow-right-solid.svg" alt="Join Now" />
                </button>
            </a>
        </section>

        <hr class="home-sectionDivider" />

       <!-- Offers Section -->
<section class="S-Offers" id="S-Offers">
    <div class="offers-text">
        <h1>Gym Offers</h1>
        <p><?php 
            echo htmlspecialchars($offersContent['description'] ?? 'Offers description goes here.'); 
        ?></p>
    </div>

    <div class="offers-carousel">
    <div class="carousel-container">
        <div class="carousel-wrapper">
            <?php foreach ($offers as $index => $offer): ?>
                <div class="carousel-slide <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>">
                    <img class="" 
                        src="<?php echo htmlspecialchars(getImagePath($offer['image_path'])); ?>"
                        alt="<?php echo htmlspecialchars($offer['title'] ?? 'Gym Offer'); ?>"
                    />
                    <!-- <div class="carousel-caption">
                        <h2><?php echo htmlspecialchars($offer['title']); ?></h2>
                        <p><?php echo htmlspecialchars($offer['description']); ?></p>
                        <a href="#">Learn More</a>
                    </div> -->
                </div>
            <?php endforeach; ?>
        </div>
    </div>
            <div class="carousel-controls">
                <button class="carousel-prev leftButton">
                    <img src="../icon/less-than-solid.svg" alt="Previous" />
                </button>
                <div class="carousel-dots">
                    <?php foreach ($offers as $index => $offer): ?>
                        <span 
                            class="carousel-dot <?php echo $index === 0 ? 'active' : ''; ?>" 
                            data-slide="<?php echo $index; ?>"
                        ></span>
                    <?php endforeach; ?>
                </div>
                <button class="carousel-next rightButton">
                    <img src="../icon/greater-than-solid.svg" alt="Next" />
                </button>
            </div>
    </div>
</section>

        <hr class="home-sectionDivider" />

<!-- Products Section -->
<section class="S-Products">
    <h1>Products you may like</h1>
    <p>Check out our latest fitness products!</p>

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
<section class="S-AboutUs">
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

    <div class="aboutUs-text">
        <h1>About Us</h1>
        <p><?php echo htmlspecialchars($aboutUsContent['description'] ?? 'About us description goes here.'); ?></p>
    </div>
</section>
        <!-- tagline section -->
        <section class="S-Tagline">
          <h1>Go Home or Go Hard</h1>
          <a href="?services=1" style="text-decoration: none;">
            <button class="joinButton">
              Join Now <img src="../icon/arrow-right-solid.svg" alt="Join Now" />
            </button>
          </a>
        </section>
       <!-- Staff Section -->
<section class="S-Staffs">
    <h1>Gym Staffs</h1>
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

        <!-- Contact Section -->
        <section class="S-ContactUs">
            <div class="image-location">
              <!-- location section -->
                <img src="" />
            </div>
            <div class="ContactUs-text">
                <h1>Contact Us:</h1>
                <p><span>Location: </span><?php echo htmlspecialchars($contactContent['location'] ?? 'Zamboanga City'); ?></p>
                <p><span>#: </span><?php echo htmlspecialchars($contactContent['phone'] ?? '+639999999999'); ?></p>
                <p><span>@: </span><?php echo htmlspecialchars($contactContent['email'] ?? 'gymfitness@gmail.com'); ?></p>
            </div>
        </section>
    </main>

  
<footer>
        <div>
            <ul class="footer-links">
                <li><a href="#">Home</a></li>
                <li><a href="#">Services</a></li>
                <li><a href="#">About</a></li>
                <li><a href="#">Contact</a></li>
            </ul>
        </div>
        <hr class="home-sectionDivider" />
        <p>&copy; <?php echo date('Y'); ?> Your Website. All rights reserved.</p>
    </footer>

    <!-- Add JavaScript for Carousel Functionality -->
  <script>
document.addEventListener('DOMContentLoaded', function() {
    const carousel = document.querySelector('.carousel-wrapper');
    const slides = document.querySelectorAll('.carousel-slide');
    const dots = document.querySelectorAll('.carousel-dot');
    const prevButton = document.querySelector('.carousel-prev');
    const nextButton = document.querySelector('.carousel-next');

    // Check if elements exist before proceeding
    if (!slides.length || !dots.length || !prevButton || !nextButton) {
        console.warn('Carousel elements not found');
        return;
    }

    let currentSlide = 0;

    function showSlide(index) {
        // Safely handle slide and dot updates
        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === index);
        });

        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });

        currentSlide = (index + slides.length) % slides.length;
    }

    // Next slide
    nextButton.addEventListener('click', () => {
        showSlide(currentSlide + 1);
    });

    // Previous slide
    prevButton.addEventListener('click', () => {
        showSlide(currentSlide - 1);
    });

    // Dot navigation
    dots.forEach(dot => {
        dot.addEventListener('click', () => {
            const slideIndex = parseInt(dot.getAttribute('data-slide'));
            showSlide(slideIndex);
        });
    });

    // Optional: Auto-advance slides every 5 seconds
    setInterval(() => {
        showSlide(currentSlide + 1);
    }, 5000);

    // Initialize first slide
    showSlide(0);
});
  </script>
</body>
</html>