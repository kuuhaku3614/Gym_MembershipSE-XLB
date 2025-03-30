// Get DOM elements
const burgerMenu = document.getElementById('burgerMenu');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');

// Toggle sidebar function
function toggleSidebar() {
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
    burgerMenu.innerHTML = sidebar.classList.contains('active') 
        ? '<i class="fas fa-times"></i>' 
        : '<i class="fas fa-bars"></i>';
}

// Event listeners
burgerMenu.addEventListener('click', toggleSidebar);
overlay.addEventListener('click', toggleSidebar);

// Close sidebar when clicking a nav item on mobile
document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', function(e) {
        // Remove active class from all items
        document.querySelectorAll('.nav-item').forEach(navItem => {
            navItem.classList.remove('active');
        });
        // Add active class to clicked item
        this.classList.add('active');
        
        // Close sidebar on mobile if clicked
        if (window.innerWidth <= 768) {
            toggleSidebar();
        }
    });
});

// Add dropdown functionality
document.querySelectorAll('.nav-item.has-subnav').forEach(item => {
    item.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Toggle the clicked dropdown
        this.classList.toggle('open');
        const subNav = this.nextElementSibling;
        
        // Add null check
        if (subNav) {
            subNav.classList.toggle('open');
        }
        
        // Handle active states
        if (!e.target.closest('.sub-nav-item')) {
            document.querySelectorAll('.nav-item').forEach(navItem => {
                if (navItem !== this) {
                    navItem.classList.remove('active');
                }
            });
            this.classList.add('active');
        }
    });
});

// Handle sub-nav-item clicks
document.querySelectorAll('.sub-nav-item').forEach(item => {
    item.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Remove active class from all sub-nav-items
        document.querySelectorAll('.sub-nav-item').forEach(subItem => {
            subItem.classList.remove('active');
        });
        
        // Add active class to clicked sub-nav-item
        this.classList.add('active');
        
        // Close sidebar on mobile if clicked
        if (window.innerWidth <= 768) {
            toggleSidebar();
        }
    });
});

// Handle window resize
window.addEventListener('resize', function() {
    if (window.innerWidth > 768) {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        burgerMenu.innerHTML = '<i class="fas fa-bars"></i>';
    }
});
