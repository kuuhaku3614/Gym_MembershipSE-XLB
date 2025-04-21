// /**
//  * NotificationManager - Handles real-time notification updates
//  */
// class NotificationManager {
//     constructor(options = {}) {
//         // Default configuration
//         this.config = {
//             apiEndpoint: '/admin/pages/notification/functions/notification-api.php',
//             updateInterval: 30000, // 30 seconds
//             selectors: {
//                 badge: '.notification-badge',
//                 count: '.notification-count',
//                 container: '.notification-container',
//                 markAllReadBtn: '.mark-all-read-btn'
//             },
//             ...options
//         };
        
//         // Initialize properties
//         this.updateTimer = null;
//         this.unreadCount = 0;
//         this.totalCount = 0;
        
//         // Bind methods
//         this.fetchNotificationCount = this.fetchNotificationCount.bind(this);
//         this.updateNotificationBadges = this.updateNotificationBadges.bind(this);
//         this.markAsRead = this.markAsRead.bind(this);
//         this.markAllAsRead = this.markAllAsRead.bind(this);
        
//         // Initialize
//         this.init();
//     }
    
//     /**
//      * Initialize the notification manager
//      */
//     init() {
//         // Initial fetch
//         this.fetchNotificationCount();
        
//         // Set up periodic updates
//         this.updateTimer = setInterval(this.fetchNotificationCount, this.config.updateInterval);
        
//         // Event listeners
//         document.addEventListener('DOMContentLoaded', () => {
//             // Add event listener for "Mark All as Read" button if it exists
//             const markAllReadBtn = document.querySelector(this.config.selectors.markAllReadBtn);
//             if (markAllReadBtn) {
//                 markAllReadBtn.addEventListener('click', this.markAllAsRead);
//             }
            
//             // Add event listeners to all notification cards
//             this.setupNotificationCardListeners();
//         });
//     }
    
//     /**
//      * Set up click listeners for notification cards
//      */
//     setupNotificationCardListeners() {
//         const notificationCards = document.querySelectorAll('.notification-card');
        
//         notificationCards.forEach(card => {
//             card.addEventListener('click', (event) => {
//                 // Get notification data from the card
//                 const notificationId = card.dataset.id;
//                 const notificationType = card.dataset.type;
                
//                 if (notificationId && notificationType) {
//                     // Mark this notification as read
//                     this.markAsRead(notificationId, notificationType);
//                 }
//             });
//         });
//     }
    
//     /**
//      * Fetch the current notification count from the API
//      */
//     fetchNotificationCount() {
//         fetch(`${this.config.apiEndpoint}?action=get_count`)
//             .then(response => {
//                 if (!response.ok) {
//                     throw new Error('Network response was not ok');
//                 }
//                 return response.json();
//             })
//             .then(data => {
//                 if (data.success) {
//                     this.unreadCount = data.unread_count;
//                     this.totalCount = data.total_count;
//                     this.updateNotificationBadges();
//                 }
//             })
//             .catch(error => {
//                 console.error('Error fetching notification count:', error);
//             });
//     }
    
//     /**
//      * Update all notification badges and counts in the UI
//      */
//     updateNotificationBadges() {
//         // Update badge elements
//         const badges = document.querySelectorAll(this.config.selectors.badge);
//         badges.forEach(badge => {
//             // If unread count is 0, hide badge or show 0
//             if (this.unreadCount === 0) {
//                 if (badge.classList.contains('hide-zero')) {
//                     badge.style.display = 'none';
//                 } else {
//                     badge.style.display = '';
//                     badge.textContent = '0';
//                 }
//             } else {
//                 badge.style.display = '';
//                 badge.textContent = this.unreadCount;
//             }
//         });
        
//         // Update count text elements
//         const countElements = document.querySelectorAll(this.config.selectors.count);
//         countElements.forEach(element => {
//             element.textContent = this.unreadCount;
//         });
        
//         // Trigger a custom event that other components can listen for
//         document.dispatchEvent(new CustomEvent('notificationsUpdated', {
//             detail: {
//                 unreadCount: this.unreadCount,
//                 totalCount: this.totalCount
//             }
//         }));
//     }
    
//     /**
//      * Mark a specific notification as read
//      * @param {number} id - The notification ID
//      * @param {string} type - The notification type
//      */
//     markAsRead(id, type) {
//         const formData = new FormData();
//         formData.append('id', id);
//         formData.append('type', type);
        
//         fetch(`${this.config.apiEndpoint}?action=mark_read`, {
//             method: 'POST',
//             body: formData
//         })
//             .then(response => {
//                 if (!response.ok) {
//                     throw new Error('Network response was not ok');
//                 }
//                 return response.json();
//             })
//             .then(data => {
//                 if (data.success) {
//                     // Update the count after marking as read
//                     this.fetchNotificationCount();
//                 }
//             })
//             .catch(error => {
//                 console.error('Error marking notification as read:', error);
//             });
//     }
    
//     /**
//      * Mark all notifications as read
//      */
//     markAllAsRead() {
//         fetch(`${this.config.apiEndpoint}?action=mark_all_read`, {
//             method: 'POST'
//         })
//             .then(response => {
//                 if (!response.ok) {
//                     throw new Error('Network response was not ok');
//                 }
//                 return response.json();
//             })
//             .then(data => {
//                 if (data.success) {
//                     // Update the count after marking all as read
//                     this.fetchNotificationCount();
                    
//                     // Optionally update UI to show all notifications as read
//                     const container = document.querySelector(this.config.selectors.container);
//                     if (container) {
//                         const newBadges = container.querySelectorAll('.new-badge');
//                         newBadges.forEach(badge => {
//                             badge.style.display = 'none';
//                         });
//                     }
//                 }
//             })
//             .catch(error => {
//                 console.error('Error marking all notifications as read:', error);
//             });
//     }
    
//     /**
//      * Clean up resources (call when page is unloaded)
//      */
//     destroy() {
//         if (this.updateTimer) {
//             clearInterval(this.updateTimer);
//         }
//     }
// }

// // Create and export an instance
// const notificationManager = new NotificationManager();