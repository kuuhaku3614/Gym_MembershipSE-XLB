<?php
// Configuration and Database Connection
require_once '../config.php';

// Modify getImagePath to return a web-accessible path
function getImagePath($imageName) {
  error_log('Attempting to load image: ' . $imageName);
  // If the image name is already a full web-relative path, return it directly
  if (strpos($imageName, 'cms_img/') === 0) {
      return '../' . $imageName;
  }

  // Fallback option if just the filename is provided
  $possibleSubdirectories = ['products', 'gallery', 'offers', 'staff', ''];
  
  foreach ($possibleSubdirectories as $subdir) {
      $potentialPath = '../cms_img/' . ($subdir ? $subdir . '/' : '') . $imageName;
      
      if (file_exists($potentialPath)) {
          return $potentialPath;
      }
  }

  // If no image found, return default
  return '../img/default.jpg';
  error_log('Final image path: ' . $finalPath);
    return $finalPath;
}

// Centralized database query function with error handling
function executeQuery($query, $params = [], $fetchMode = PDO::FETCH_ASSOC) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll($fetchMode);
    } catch (PDOException $e) {
        // Log error or handle as appropriate
        error_log('Database query error: ' . $e->getMessage());
        return [];
    }
}

// Content retrieval functions
function getDynamicContent($section) {
    $query = "SELECT * FROM website_content WHERE section = :section";
    $result = executeQuery($query, ['section' => $section]);
    return !empty($result) ? $result[0] : [];
}

function getOffers($limit = 4) {
    $query = "SELECT * FROM gym_offers LIMIT :limit";
    return executeQuery($query, ['limit' => $limit]);
}

function getProducts($limit = 8) {
    $query = "SELECT * FROM products LIMIT :limit";
    return executeQuery($query, ['limit' => $limit]);
}

function getStaffMembers() {
    $query = "SELECT * FROM staff";
    return executeQuery($query);
}

function getGalleryImages($limit = 4) {
    $query = "SELECT * FROM gallery_images LIMIT :limit";
    return executeQuery($query, ['limit' => $limit]);
}

// Session start and header inclusion
include('includes/header.php');

// Fetch dynamic content
$welcomeContent = getDynamicContent('welcome');
$offersContent = getDynamicContent('offers');
$aboutUsContent = getDynamicContent('about_us');
$contactContent = getDynamicContent('contact');

// Fetch data for sections
$offers = getOffers();
$products = getProducts();
$staffMembers = getStaffMembers();
$galleryImages = getGalleryImages();
?>

  <!-- Suggested CSS to be added to your stylesheet -->
  <style>
    .offers-carousel {
      position: relative;
      width: 100%;
      overflow: hidden;
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
      width: 100%;
      height: auto;
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
      top: 50%;
      width: 100%;
      transform: translateY(-50%);
    }

    .carousel-dots {
      display: flex;
      justify-content: center;
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
  </style>
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
                                <img 
                                    src="<?php echo htmlspecialchars(getImagePath($product['image_path'])); ?>"
                                    alt="<?php echo htmlspecialchars($offer['title'] ?? 'Gym Offer'); ?>"
                                />
                                <div class="carousel-caption">
                                    <h2><?php echo htmlspecialchars($offer['title']); ?></h2>
                                    <p><?php echo htmlspecialchars($offer['description']); ?></p>
                                    <a href="<?php echo htmlspecialchars($offer['link'] ?? '#'); ?>" class="offer-button">
                                        Learn More
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
                        <p class="product-title"><?php echo htmlspecialchars($product['name']); ?></p>
                        <p class="product-text"><?php echo htmlspecialchars($product['description']); ?></p>
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
                        src="<?php echo htmlspecialchars(getImagePath($product['image_path'])); ?>"
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
                                src="<?php echo htmlspecialchars(getImagePath($product['image_path'])); ?>"
                                alt="<?php echo htmlspecialchars($staff['name']); ?>" 
                            />
                            <p class="name"><?php echo htmlspecialchars($staff['name']); ?></p>
                            <p class="status"><?php echo htmlspecialchars($staff['status']); ?></p>
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
                <img src="<?php echo htmlspecialchars(getImagePath('3months Promo.jpg')); ?>" alt="Location" />
            </div>
            <div class="ContactUs-text">
                <h1>Contact Us:</h1>
                <p><span>Location: </span><?php echo htmlspecialchars($contactContent['location'] ?? 'Zamboanga City'); ?></p>
                <p><span>#: </span><?php echo htmlspecialchars($contactContent['phone'] ?? '+639999999999'); ?></p>
                <p><span>@: </span><?php echo htmlspecialchars($contactContent['email'] ?? 'gymfitness@gmail.com'); ?></p>
            </div>
        </section>
    </main>

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
</body>
</html>