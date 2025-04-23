<?php require_once 'DatabaseExtended.php';
// Get the logo and color
$logo = getWebsiteContent('logo');
$color = getWebsiteContent('color');

$primaryHex = isset($color['latitude']) ? decimalToHex($color['latitude']) : '#000000';
$secondaryHex = isset($color['longitude']) ? decimalToHex($color['longitude']) : '#000000';
?>
<style>
    :root {
        --primary-color: <?php echo $primaryHex; ?>;
        --secondary-color: <?php echo $secondaryHex; ?>;
    }
    #loader-wrapper {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 9999;
  background: rgba(0, 0, 0, 0.8); /* Semi-transparent dark overlay */
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  backdrop-filter: blur(5px); /* Blur effect for background */
  transition: opacity 0.5s ease-out;
}

.loader-container {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  height: 80px;
}

.stripe {
  width: 40px;
  height: 100%;
  transform: skewX(-30deg);
  position: relative;
  animation: wave 1.5s infinite ease-in-out;
  box-shadow: 0 0 15px rgba(255, 255, 255, 0.3);
}

.stripe:nth-child(1) {
  background-color: #ffffff;
}

.stripe:nth-child(3) {
  background-color: #404040;
}

.stripe:nth-child(2) {
  background-color: var(--primary-color);
}

.stripe:nth-child(1) {
  animation-delay: 0s;
}
.stripe:nth-child(2) {
  animation-delay: 0.2s;
}
.stripe:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes wave {
  0%,
  100% {
    transform: skewX(-30deg) translateY(0);
  }
  50% {
    transform: skewX(-30deg) translateY(-15px);
  }
}

.loading-text {
  margin-top: 30px;
  font-size: 18px;
  color: #ffffff;
  text-align: center;
  text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
}

</style>

<div id="loader-wrapper">
    <div class="loader-container">
        <div class="stripe"></div>
        <div class="stripe"></div>
        <div class="stripe"></div>
    </div>
    <div class="loading-text">Wait for a while...</div>
</div>

<script>
// Add a timestamp tracking variable
let pageLoadStartTime = 0;

// Show loader function with timing check
function showLoader() {
    const loader = document.getElementById('loader-wrapper');
    
    // Calculate time since last page unload
    const currentTime = new Date().getTime();
    const timeSinceUnload = localStorage.getItem('pageUnloadTime') ? 
        currentTime - parseInt(localStorage.getItem('pageUnloadTime')) : 9999;
    
    // Only show loader if page reload took more than 1 second (1000ms)
    if (timeSinceUnload > 1000) {
        loader.style.display = 'flex';
        loader.style.opacity = '1';
    } else {
        // For quick reloads, keep loader hidden
        loader.style.display = 'none';
        loader.style.opacity = '0';
    }
    
    // Store current time for tracking page load start
    pageLoadStartTime = currentTime;
}

// Hide loader function
function hideLoader() {
    const loader = document.getElementById('loader-wrapper');
    loader.style.opacity = '0';
    setTimeout(() => loader.style.display = 'none', 500);
}

// Store timestamp when user leaves/reloads the page
window.addEventListener('beforeunload', function() {
    localStorage.setItem('pageUnloadTime', new Date().getTime());
});

// Show loader when page starts loading
document.addEventListener('DOMContentLoaded', function() {
    showLoader();
});

// Hide loader when page is fully loaded
window.addEventListener('load', function() {
    hideLoader();
});

// Use it with AJAX calls or other loading events
// Example:
/*
fetch('/some-data')
    .then(response => {
        hideLoader();
        return response.json();
    })
    .catch(error => {
        hideLoader();
        console.error('Error:', error);
    });
*/
</script>