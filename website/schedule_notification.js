/**
 * Session Notifications JavaScript
 * This script adds interactivity to the session notifications page
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize the notification system
    initNotifications();
});

/**
 * Initialize all notification related functionality
 */
function initNotifications() {
    setupExpandableNotifications();
    setupFilters();
    setupNotificationMarking();
    setupSortingOptions();
}

/**
 * Make notifications expandable/collapsible
 */
function setupExpandableNotifications() {
    const notificationHeaders = document.querySelectorAll('.notification-header');
    
    notificationHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const content = this.nextElementSibling;
            
            // Toggle the expanded class
            this.parentElement.classList.toggle('expanded');
            
            // Toggle content visibility with smooth animation
            if (content.style.maxHeight) {
                content.style.maxHeight = null;
            } else {
                content.style.maxHeight = content.scrollHeight + "px";
            }
        });
    });
}

/**
 * Setup notification filtering functionality
 */
function setupFilters() {
    // Add filter controls to the page
    const container = document.querySelector('.container');
    const filterControls = document.createElement('div');
    filterControls.className = 'filter-controls';
    filterControls.innerHTML = `
        <div class="filter-group">
            <label for="filter-by">Filter by:</label>
            <select id="filter-by">
                <option value="all">All</option>
                <option value="cancelled">Cancelled only</option>
                <option value="completed">Completed only</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="coach-filter">Coach:</label>
            <select id="coach-filter">
                <option value="all">All Coaches</option>
                <!-- Coaches will be populated dynamically -->
            </select>
        </div>
        <div class="filter-group">
            <label for="date-filter">Date range:</label>
            <input type="date" id="date-from" placeholder="From">
            <input type="date" id="date-to" placeholder="To">
            <button id="clear-dates">Clear</button>
        </div>
    `;
    
    // Insert filter controls before the first section
    const firstSection = document.querySelector('.notifications-section');
    container.insertBefore(filterControls, firstSection);
    
    // Populate coaches dropdown
    populateCoachFilter();
    
    // Setup event listeners for filters
    document.getElementById('filter-by').addEventListener('change', applyFilters);
    document.getElementById('coach-filter').addEventListener('change', applyFilters);
    document.getElementById('date-from').addEventListener('change', applyFilters);
    document.getElementById('date-to').addEventListener('change', applyFilters);
    document.getElementById('clear-dates').addEventListener('click', function() {
        document.getElementById('date-from').value = '';
        document.getElementById('date-to').value = '';
        applyFilters();
    });
}

/**
 * Populate the coach filter dropdown with unique coach names
 */
function populateCoachFilter() {
    const coachFilter = document.getElementById('coach-filter');
    const coaches = new Set();
    
    // Get all coach names from notifications
    document.querySelectorAll('.notification-content p strong').forEach(strong => {
        if (strong.textContent === 'Coach:') {
            const coachName = strong.nextSibling.textContent.trim();
            coaches.add(coachName);
        }
    });
    
    // Add coach options to the dropdown
    coaches.forEach(coach => {
        const option = document.createElement('option');
        option.value = coach;
        option.textContent = coach;
        coachFilter.appendChild(option);
    });
}

/**
 * Apply all active filters to the notification list
 */
function applyFilters() {
    const filterBy = document.getElementById('filter-by').value;
    const coachFilter = document.getElementById('coach-filter').value;
    const dateFrom = document.getElementById('date-from').value;
    const dateTo = document.getElementById('date-to').value;
    
    // Get all notification items
    const notificationItems = document.querySelectorAll('.notification-item');
    
    notificationItems.forEach(item => {
        // Start with visible by default
        let shouldShow = true;
        
        // Check notification type filter
        if (filterBy !== 'all') {
            if (!item.classList.contains(filterBy)) {
                shouldShow = false;
            }
        }
        
        // Check coach filter
        if (shouldShow && coachFilter !== 'all') {
            const coachElement = item.querySelector('p strong:contains("Coach:")');
            if (coachElement) {
                const coachName = coachElement.nextSibling.textContent.trim();
                if (coachName !== coachFilter) {
                    shouldShow = false;
                }
            }
        }
        
        // Check date filter
        if (shouldShow && (dateFrom || dateTo)) {
            const dateText = item.querySelector('.notification-date').textContent;
            const itemDate = new Date(dateText);
            
            if (dateFrom && new Date(dateFrom) > itemDate) {
                shouldShow = false;
            }
            
            if (dateTo && new Date(dateTo) < itemDate) {
                shouldShow = false;
            }
        }
        
        // Show or hide the item
        item.style.display = shouldShow ? 'block' : 'none';
    });
    
    // Update section visibility
    updateSectionVisibility();
}

/**
 * Show/hide section headers based on visible notifications
 */
function updateSectionVisibility() {
    const sections = document.querySelectorAll('.notifications-section');
    
    sections.forEach(section => {
        const visibleItems = section.querySelectorAll('.notification-item[style="display: block;"]').length;
        const noNotificationsMessage = section.querySelector('p:not(.notification-item)');
        
        if (visibleItems === 0) {
            // Show "no notifications" message if it exists, or create one
            if (noNotificationsMessage) {
                noNotificationsMessage.style.display = 'block';
            } else {
                const message = document.createElement('p');
                message.textContent = 'No matching notifications found.';
                section.appendChild(message);
            }
        } else {
            // Hide the "no notifications" message if it exists
            if (noNotificationsMessage) {
                noNotificationsMessage.style.display = 'none';
            }
        }
    });
}

/**
 * Setup functionality to mark notifications as read
 */
function setupNotificationMarking() {
    // Add "Mark as read" button to each notification
    const notificationItems = document.querySelectorAll('.notification-item');
    
    notificationItems.forEach(item => {
        const markButton = document.createElement('button');
        markButton.className = 'mark-read-btn';
        markButton.textContent = 'Mark as read';
        
        markButton.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent expanding/collapsing when clicking the button
            
            // Add the "read" class to the notification
            item.classList.add('read');
            
            // Get the notification ID (for use with AJAX)
            const scheduleId = item.getAttribute('data-schedule-id');
            
            // Send AJAX request to mark as read on server
            // This is a placeholder - you'll need to implement the actual AJAX call
            console.log(`Marking notification ${scheduleId} as read`);
            
            // Optional: Update the button text or remove it
            this.textContent = 'Read';
            this.disabled = true;
        });
        
        item.querySelector('.notification-content').appendChild(markButton);
    });
}

/**
 * Setup sorting options for notifications
 */
function setupSortingOptions() {
    // Add sorting controls
    const container = document.querySelector('.container');
    const sortControls = document.createElement('div');
    sortControls.className = 'sort-controls';
    sortControls.innerHTML = `
        <label for="sort-by">Sort by:</label>
        <select id="sort-by">
            <option value="date-desc">Date (newest first)</option>
            <option value="date-asc">Date (oldest first)</option>
            <option value="coach">Coach name</option>
            <option value="program">Program name</option>
        </select>
    `;
    
    // Insert sorting controls before the filter controls
    const filterControls = document.querySelector('.filter-controls');
    container.insertBefore(sortControls, filterControls);
    
    // Setup event listener for sorting
    document.getElementById('sort-by').addEventListener('change', function() {
        const sortBy = this.value;
        sortNotifications(sortBy);
    });
}

/**
 * Sort notifications based on selected criteria
 */
function sortNotifications(sortBy) {
    const sections = document.querySelectorAll('.notifications-section');
    
    sections.forEach(section => {
        const list = section.querySelector('.notification-list');
        if (!list) return;
        
        const items = Array.from(list.querySelectorAll('.notification-item'));
        
        items.sort((a, b) => {
            switch(sortBy) {
                case 'date-desc':
                    return new Date(b.querySelector('.notification-date').textContent) - 
                           new Date(a.querySelector('.notification-date').textContent);
                case 'date-asc':
                    return new Date(a.querySelector('.notification-date').textContent) - 
                           new Date(b.querySelector('.notification-date').textContent);
                case 'coach':
                    return a.querySelector('p:contains("Coach:")').textContent.localeCompare(
                           b.querySelector('p:contains("Coach:")').textContent);
                case 'program':
                    return a.querySelector('h3').textContent.localeCompare(
                           b.querySelector('h3').textContent);
                default:
                    return 0;
            }
        });
        
        // Re-append sorted items
        items.forEach(item => list.appendChild(item));
    });
}

// Helper function for jQuery-like contains selector
Element.prototype.matches = Element.prototype.matches || Element.prototype.msMatchesSelector;
Element.prototype.closest = Element.prototype.closest || function (selector) {
    let el = this;
    while (el) {
        if (el.matches(selector)) {
            return el;
        }
        el = el.parentElement;
    }
    return null;
};

// Polyfill for :contains selector
document.querySelectorAll = document.querySelectorAll || function(selector) {
    if (selector.includes(':contains(')) {
        const parts = selector.split(':contains(');
        const baseSelector = parts[0];
        const searchText = parts[1].slice(0, -1);
        
        const elements = Array.from(document.querySelectorAll(baseSelector));
        return elements.filter(el => el.textContent.includes(searchText));
    }
    return document.querySelectorAll(selector);
};