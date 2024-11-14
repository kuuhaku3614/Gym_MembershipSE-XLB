<?php
    session_start();
    // Check if user is logged in
    $isLoggedIn = isset($_SESSION['user_id']);

    if (isset($_GET['logout'])) {
        session_destroy();
        header('Location: website.php');
    }

    // Function to check if user is logged in and redirect if not
    function requireLogin() {
      if (!isset($_SESSION['user_id'])) {
          header('Location: ../login/login.php');
          exit();
      }
    }

    if (isset($_GET['logout'])) {
      session_destroy();
      header('Location: website.php');
      exit();
    }

    // Handle Buy Now button click
    if (isset($_GET['services'])) {
      requireLogin();
      // Redirect to buy page if logged in
      header('Location: services.php');
      exit();
    }

    // function getFullName() {
    //     if (isset($_SESSION['user_id'])) {
    //         return $_SESSION['user_id']['first_name'] . ' ' . $_SESSION['user_id']['last_name'];
    //     }
    //     return '';
    // }

    function getFullName() {
        if (isset($_SESSION['user_id']) && is_int($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id']; // Retrieve the logged-in user's ID from the session
    
            // Database connection (replace with your database credentials)
            $conn = new mysqli('localhost', 'root', '', 'gym_managementdb');
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
    
            // Prepare a SQL query to fetch the first and last name
            $stmt = $conn->prepare("SELECT first_name, last_name FROM personal_details WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
    
            if ($row = $result->fetch_assoc()) {
                // Concatenate and return the full name
                return $row['first_name'] . ' ' . $row['last_name'];
            }
    
            // Close connections
            $stmt->close();
            $conn->close();
    
            // Return a default value if the user is not found
            return 'Unknown User';
        }
    
        // Handle case where user_id is not set or invalid
        return 'Guest';
    }
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Home</title>
    <link rel="stylesheet" href="../css/landing1.css" />
  </head>
  <body>
    <nav class="home-navbar">
      <div class="home-logo">
        <img src="" alt="logo" />
      </div>
      <ul class="nav-links">
        <li><a href="#">Home</a></li>
        <li><a href="#">Services</a></li>
        <li><a href="#S-About">About</a></li>
        <li><a href="#S-ContactUs">Contact</a></li>
      </ul>

      <?php if ($isLoggedIn): ?>
        <div class="dropdown">
                <button class="dropbtn"></button>
                <div class="dropdown-content">
                    <a href="user_account/profile.html" class="username"><?php echo getFullName();?></a>
                    <hr>
                    <a href="#"> Notifications</a>
                    <a href="?logout=1"> Logout</a>
                </div>
            </div>
        <?php else: ?>
          <a href="../login/login.php" class="home-signIn">Sign In</a>
        <?php endif; ?>

      

      

      <!-- <div class="home-userImage">
        <img src="" alt="user" />
      </div> -->
    </nav>

    <header class="home-header"></header>

    <main class="home-main">
      <section class="S-Welcome">
        <h1>Welcome To <span class="companyName">Company Name</span></h1>
        <p>
          Lorem ipsum dolor, sit amet consectetur adipisicing elit. Obcaecati,
          eum! Lorem ipsum dolor, sit amet consectetur adipisicing elit.
          Obcaecati, eum!
        </p>
        <a href="?services=1" style="text-decoration: none;">
          <button class="joinButton">
            Join Now <img src="../icon/arrow-right-solid.svg" alt="" />
          </button>
        </a>
      </section>

      <hr class="home-sectionDivider" />

      <section class="S-Offers" id="S-Offers">
        <div class="offers-text">
          <h1>Gym Offers</h1>
          <p>
            Lorem ipsum dolor sit amet consectetur adipisicing elit. Temporibus
            voluptate atque veniam. Soluta sunt vero omnis accusamus quia
            consectetur quas recusandae aspernatur ducimus veritatis nulla
            doloremque dolorum, minima exercitationem corrupti?
          </p>
        </div>

        <div class="offers-image">
          <button class="leftButton">
            <img src="../icon/less-than-solid.svg" alt="leftButton" /></button
          ><button class="rightButton">
            <img src="../icon/greater-than-solid.svg" alt="rightButton" />
          </button>
        </div>
      </section>

      <hr class="home-sectionDivider" />

      <section class="S-Products">
        <h1>Products you may like</h1>
        <p>
          Lorem ipsum dolor sit amet consectetur adipisicing elit. Dolores, a.
        </p>

        <div class="product-container">
          <div class="product-box">
            <img class="product-image" src="../img/supplements.JPG" alt="" />
            <p class="product-title">Title</p>
            <p class="product-text">Text</p>
          </div>

          <div class="product-box">
            <img class="product-image" src="../img/supplements.JPG" alt="" />
            <p class="product-title">Title</p>
            <p class="product-text">Text</p>
          </div>

          <div class="product-box">
            <img class="product-image" src="../img/supplements.JPG" alt="" />
            <p class="product-title">Title</p>
            <p class="product-text">Text</p>
          </div>

          <div class="product-box">
            <img class="product-image" src="../img/supplements.JPG" alt="" />
            <p class="product-title">Title</p>
            <p class="product-text">Text</p>
          </div>

          <div class="product-box">
            <img class="product-image" src="../img/supplements.JPG" alt="" />
            <p class="product-title">Title</p>
            <p class="product-text">Text</p>
          </div>

          <div class="product-box">
            <img class="product-image" src="../img/supplements.JPG" alt="" />
            <p class="product-title">Title</p>
            <p class="product-text">Text</p>
          </div>

          <div class="product-box">
            <img class="product-image" src="../img/supplements.JPG" alt="" />
            <p class="product-title">Title</p>
            <p class="product-text">Text</p>
          </div>

          <div class="product-box">
            <img class="product-image" src="../img/supplements.JPG" alt="" />
            <p class="product-title">Title</p>
            <p class="product-text">Text</p>
          </div>
        </div>
      </section>

      <section class="S-AboutUs">
        <div class="image-container">
          <img class="img1" src="../img/gallery.jpg" alt="img" />
          <img class="img2" src="../img/gallery2.jpg" alt="img" />
          <img class="img3" src="../img/gallery3.jpg" alt="img" />
          <img class="img4" src="../img/gallery5.jpg" alt="img" />
        </div>

        <div class="aboutUs-text">
          <h1>About Us</h1>
          <p>
            Lorem ipsum dolor sit amet consectetur adipisicing elit. Labore
            harum esse illo fugit voluptatem similique atque at dicta suscipit
            dolores!
          </p>
        </div>
      </section>

      <section class="S-Tagline">
        <h1>Go Home or Go Hard</h1>
        <p></p>
        <a href="?services=1" style="text-decoration: none;">
          <button class="joinButton">
            Join Now <img src="../icon/arrow-right-solid.svg" alt="" />
          </button>
        </a>
        
      </section>

      <section class="S-Staffs">
        <h1>Gym Staffs</h1>

        <div class="staff-container">
          <div class="image-staff">
            <img src="../img/default.jpg" alt="staff-image" />
            <p class="name">Name</p>
            <p class="status">Status</p>
          </div>

          <div class="image-staff">
            <img src="../img/default.jpg" alt="staff-image" />
            <p class="name">Name</p>
            <p class="status">Status</p>
          </div>

          <div class="image-staff">
            <img src="../img/default.jpg" alt="staff-image" />
            <p class="name">Name</p>
            <p class="status">Status</p>
          </div>
        </div>
      </section>

      <section class="S-ContactUs">
        <div class="image-location">
          <img src="../img/3months Promo.jpg" alt="location" />
        </div>
        <div class="ContactUs-text">
          <h1>Contact Us:</h1>
          <p><span>Location: </span>*****, Zamboanga City</p>
          <p><span>#: </span>+639999999999</p>
          <p><span>@: </span>gymfitness@gmail.com</p>
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
      <p>&copy; 2023 Your Website. All rights reserved.</p>
    </footer>
  </body>
</html>
