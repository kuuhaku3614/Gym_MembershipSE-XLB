<?php
// Configuration and Database Connection
require_once '../config.php';

// Function to fetch dynamic content
function getDynamicContent($section) {
    global $conn;
    $query = "SELECT * FROM website_content WHERE section = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $section);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to fetch products
function getProducts($limit = 8) {
    global $conn;
    $query = "SELECT * FROM products LIMIT ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to fetch staff members
function getStaffMembers() {
    global $conn;
    $query = "SELECT * FROM staff";
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Session start and header inclusion
session_start();
include('includes/header.php');

// Fetch dynamic content for different sections
$welcomeContent = getDynamicContent('welcome');
$offersContent = getDynamicContent('offers');
$aboutUsContent = getDynamicContent('about_us');
$contactContent = getDynamicContent('contact');
?>

  <header class="home-header"></header>

  <main class="home-main">
    <section class="S-Welcome">
      <h1>Welcome To <span class="companyName"><?php echo htmlspecialchars($welcomeContent['company_name'] ?? 'Company Name'); ?></span></h1>
      <p><?php echo htmlspecialchars($welcomeContent['description'] ?? 'Welcome description goes here.'); ?></p>
      <a href="?services=1" style="text-decoration: none;">
        <button class="joinButton">
          Join Now <img src="../icon/arrow-right-solid.svg" alt="Join Now" />
        </button>
      </a>
    </section>

    <hr class="home-sectionDivider" />

    <section class="S-Offers" id="S-Offers">
      <div class="offers-text">
        <h1>Gym Offers</h1>
        <p><?php echo htmlspecialchars($offersContent['description'] ?? 'Offers description goes here.'); ?></p>
      </div>

      <div class="offers-image">
        <button class="leftButton">
          <img src="../icon/less-than-solid.svg" alt="Previous" />
        </button>
        <button class="rightButton">
          <img src="../icon/greater-than-solid.svg" alt="Next" />
        </button>
      </div>
    </section>

    <hr class="home-sectionDivider" />

    <section class="S-Products">
      <h1>Products you may like</h1>
      <p>Check out our latest fitness products!</p>

      <div class="product-container">
        <?php 
        $products = getProducts();
        foreach ($products as $product): 
        ?>
          <div class="product-box">
            <img class="product-image" src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" />
            <p class="product-title"><?php echo htmlspecialchars($product['name']); ?></p>
            <p class="product-text"><?php echo htmlspecialchars($product['description']); ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="S-AboutUs">
      <div class="image-container">
        <img class="img1" src="../img/gallery.jpg" alt="Gallery Image 1" />
        <img class="img2" src="../img/gallery2.jpg" alt="Gallery Image 2" />
        <img class="img3" src="../img/gallery3.jpg" alt="Gallery Image 3" />
        <img class="img4" src="../img/gallery5.jpg" alt="Gallery Image 4" />
      </div>

      <div class="aboutUs-text">
        <h1>About Us</h1>
        <p><?php echo htmlspecialchars($aboutUsContent['description'] ?? 'About us description goes here.'); ?></p>
      </div>
    </section>

    <section class="S-Tagline">
      <h1>Go Home or Go Hard</h1>
      <a href="?services=1" style="text-decoration: none;">
        <button class="joinButton">
          Join Now <img src="../icon/arrow-right-solid.svg" alt="Join Now" />
        </button>
      </a>
    </section>

    <section class="S-Staffs">
      <h1>Gym Staffs</h1>

      <div class="staff-container">
        <?php 
        $staffMembers = getStaffMembers();
        foreach ($staffMembers as $staff): 
        ?>
          <div class="image-staff">
            <img src="<?php echo htmlspecialchars($staff['image_path'] ?? '../img/default.jpg'); ?>" alt="Staff Image" />
            <p class="name"><?php echo htmlspecialchars($staff['name']); ?></p>
            <p class="status"><?php echo htmlspecialchars($staff['status']); ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="S-ContactUs">
      <div class="image-location">
        <img src="../img/3months Promo.jpg" alt="Location" />
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
</body>
</html>