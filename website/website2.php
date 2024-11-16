<?php
  session_start();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home</title>
  <link rel="stylesheet" href="../css/landing1.css">
</head>
<body>

  <?php include('includes/header.php'); ?>

  <header class="home-header"></header>

  <?php
require_once 'ContentManager.php';
$contentManager = new ContentManager();

// Get content for each section
$welcome = $contentManager->getSectionContent('welcome');
$offers = $contentManager->getSectionContent('offers');
$products = $contentManager->getSectionContent('products');
$about = $contentManager->getSectionContent('about_us');
$tagline = $contentManager->getSectionContent('tagline');
$contact = $contentManager->getSectionContent('contact');
$staffs = $contentManager->getSectionContent('staffs');
?>

<main class="home-main">
    <section class="S-Welcome">
<h1>Welcome To <span class="companyName">
    <?php
    echo htmlspecialchars(
        isset($welcome['company_name']) ? $welcome['company_name'] : 'Default Company Name'
    );
    ?>
</span></h1>

<p><?php echo nl2br(htmlspecialchars($welcome['message'] ?? '')); ?></p>

        <a href="?services=1" style="text-decoration: none;">
            <button class="joinButton">
                Join Now <img src="../icon/arrow-right-solid.svg" alt="" />
            </button>
        </a>
    </section>

    <hr class="home-sectionDivider" />

    <section class="S-Offers" id="S-Offers">
        <div class="offers-text">
            <h1><?php echo htmlspecialchars($offers['title']); ?></h1>
            <p><?php echo nl2br(htmlspecialchars($offers['description'])); ?></p>
        </div>

        <div class="offers-image">
            <?php if (!empty($offers['image1'])): ?>
                <img src="<?php echo htmlspecialchars($offers['image1']); ?>" alt="Offer 1">
            <?php endif; ?>
            <button class="leftButton">
                <img src="../icon/less-than-solid.svg" alt="leftButton" />
            </button>
            <button class="rightButton">
                <img src="../icon/greater-than-solid.svg" alt="rightButton" />
            </button>
        </div>
    </section>

    <hr class="home-sectionDivider" />

    <section class="S-Products">
        <h1><?php echo htmlspecialchars($products['title']); ?></h1>
        <p><?php echo htmlspecialchars($products['description']); ?></p>

        <div class="product-container">
            <?php
            for ($i = 1; $i <= 8; $i++) {
                if (!empty($products["product{$i}_image"])) {
                    ?>
                    <div class="product-box">
                        <img class="product-image" src="<?php echo htmlspecialchars($products["product{$i}_image"]); ?>" alt="" />
                        <p class="product-title"><?php echo htmlspecialchars($products["product{$i}_title"]); ?></p>
                        <p class="product-text"><?php echo htmlspecialchars($products["product{$i}_text"]); ?></p>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </section>

    <section class="S-AboutUs">
        <div class="image-container">
            <?php
            for ($i = 1; $i <= 4; $i++) {
                if (!empty($about["image$i"])) {
                    echo '<img class="img' . $i . '" src="' . htmlspecialchars($about["image$i"]) . '" alt="img" />';
                }
            }
            ?>
        </div>

        <div class="aboutUs-text">
            <h1><?php echo htmlspecialchars($about['title']); ?></h1>
            <p><?php echo nl2br(htmlspecialchars($about['description'])); ?></p>
        </div>
    </section>

    <section class="S-Tagline">
        <h1><?php echo htmlspecialchars($tagline['title']); ?></h1>
        <p><?php echo htmlspecialchars($tagline['subtitle']); ?></p>
        <a href="?services=1" style="text-decoration: none;">
            <button class="joinButton">
                Join Now <img src="../icon/arrow-right-solid.svg" alt="" />
            </button>
        </a>
    </section>

    <section class="S-Staffs">
        <h1>Gym Staffs</h1>
        <div class="staff-container">
            <?php
            for ($i = 1; $i <= 3; $i++) {
                if (!empty($staffs["staff{$i}_image"])) {
                    ?>
                    <div class="image-staff">
                        <img src="<?php echo htmlspecialchars($staffs["staff{$i}_image"]); ?>" alt="staff-image" />
                        <p class="name"><?php echo htmlspecialchars($staffs["staff{$i}_name"]); ?></p>
                        <p class="status"><?php echo htmlspecialchars($staffs["staff{$i}_status"]); ?></p>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </section>

    <section class="S-ContactUs">
        <div class="image-location">
            <?php if (!empty($contact['image'])): ?>
                <img src="<?php echo htmlspecialchars($contact['image']); ?>" alt="location" />
            <?php endif; ?>
        </div>
        <div class="ContactUs-text">
            <h1>Contact Us:</h1>
            <p><span>Location: </span><?php echo htmlspecialchars($contact['location']); ?></p>
            <p><span>#: </span><?php echo htmlspecialchars($contact['phone']); ?></p>
            <p><span>@: </span><?php echo htmlspecialchars($contact['email']); ?></p>
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
