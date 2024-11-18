  <div class="notifications-container">
    <div class="header">
        <h1>Notifications</h1>
        <div class="actions">
            <input type="text" class="search-input" placeholder="Search notifications...">
            <button class="search-btn">Search</button>
            <select class="filter-select">
                <option value="">All Types</option>
                <option value="walk-in">Walk-in</option>
                <option value="membership">Membership</option>
                <option value="renewal">Renewal</option>
            </select>
            <button class="refresh-btn">Refresh</button>
        </div>
    </div>
    <div class="notification-list">
    <div class="notification walk-in">
        <p>Walk-in Request: Alfaith Luzon has requested to Walk-in on October 12, 2024.</p>
      </div>
      <div class="notification membership">
        <p>Membership Request: Gerby Hallasgo wants to avail 'Student Promo' membership starting October 11, 2024.</p>
      </div>
      <div class="notification renewal">
        <p>Renew Membership Request: Jamal al badi wants to renew membership with 'Student's Promo' starting October 10, 2024.</p>
      </div>
      <div class="notification walk-in">
        <p>Walk-in Request: Anna Belle has requested to Walk-in on October 9, 2024.</p>
      </div>
      <div class="notification membership">
        <p>Membership Request: Emily Copper wants to avail 'Student Promo' membership starting October 9, 2024.</p>
      </div>
      <div class="notification membership">
        <p>Membership Request: Emily Copper wants to avail 'Student Promo' membership starting October 9, 2024.</p>
      </div>
      <div class="notification membership">
        <p>Membership Request: Emily Copper wants to avail 'Student Promo' membership starting October 9, 2024.</p>
      </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.querySelector('.search-input');
    const searchBtn = document.querySelector('.search-btn');
    const filterSelect = document.querySelector('.filter-select');
    const refreshBtn = document.querySelector('.refresh-btn');
    const notifications = document.querySelectorAll('.notification');

    const applyFilters = () => {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const filterType = filterSelect.value;

        let visibleNotificationsCount = 0;

        notifications.forEach(notification => {
            const notificationText = notification.textContent.toLowerCase();
            const matchesSearch = searchTerm === '' || notificationText.includes(searchTerm);
            const matchesFilter = filterType === '' || notification.classList.contains(filterType);

            const isVisible = matchesSearch && matchesFilter;
            notification.style.display = isVisible ? 'block' : 'none';

            if (isVisible) visibleNotificationsCount++;
        });

        // Optional: Show message if no notifications match
        const noResultsMessage = document.querySelector('.no-results-message');
        if (visibleNotificationsCount === 0) {
            if (!noResultsMessage) {
                const message = document.createElement('div');
                message.classList.add('no-results-message');
                message.textContent = 'No notifications found.';
                document.querySelector('.notification-list').appendChild(message);
            }
        } else if (noResultsMessage) {
            noResultsMessage.remove();
        }
    };

    // Search button event
    searchBtn.addEventListener('click', applyFilters);

    // Search input enter key event
    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            applyFilters();
        }
    });

    // Filter select event
    filterSelect.addEventListener('change', applyFilters);

    // Refresh button event
    refreshBtn.addEventListener('click', () => {
        searchInput.value = '';
        filterSelect.selectedIndex = 0;
        
        // Show all notifications
        notifications.forEach(notification => {
            notification.style.display = 'block';
        });

        // Remove no results message if exists
        const noResultsMessage = document.querySelector('.no-results-message');
        if (noResultsMessage) {
            noResultsMessage.remove();
        }
    });
});
</script>