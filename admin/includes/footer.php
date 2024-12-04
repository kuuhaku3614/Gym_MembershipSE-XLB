
<!-- Vendor Scripts -->
<script src="../vendor/jQuery-3.7.1/jquery-3.7.1.min.js"></script>
<script src="../vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/datatable-2.1.8/datatables.min.js"></script>
<script src="../vendor/datatable-2.1.8/dataTables.bootstrap5.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="../vendor/chartjs-4.4.5/chart.js"></script>

<!-- Custom Scripts -->
<script src="../admin/js/navbar.js"></script>
<script src="../admin/js/admin.js"></script>
<!-- <script>
        // Scroll preservation script
        document.addEventListener('DOMContentLoaded', function() {
            // Check if there's a scroll position to restore
            const urlParams = new URLSearchParams(window.location.search);
            const scrollTo = urlParams.get('scrollTo');
            
            if (scrollTo) {
                const element = document.querySelector(`[data-section="${scrollTo}"]`);
                if (element) {
                    element.scrollIntoView({ behavior: 'auto' });
                }
            }

            // Add scroll preservation to forms
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function() {
                    // Store the section's identifier for scroll preservation
                    const sectionElement = this.closest('.section');
                    if (sectionElement) {
                        const scrollInput = document.createElement('input');
                        scrollInput.type = 'hidden';
                        scrollInput.name = 'scroll_to';
                        scrollInput.value = sectionElement.dataset.section || '';
                        this.appendChild(scrollInput);
                    }
                });
            });
        });
        // message
        document.addEventListener('DOMContentLoaded', function() {
        const messageAlerts = document.querySelectorAll('#message-alert');
        
        messageAlerts.forEach(messageAlert => {
            const removeMessage = () => {
                messageAlert.style.transition = 'opacity 0.5s ease-out';
                messageAlert.style.opacity = '0';
                
                setTimeout(() => {
                    messageAlert.remove();
                }, 500);
            };

            // Set timeout to remove message
            setTimeout(removeMessage, 2000); // 5 seconds

            // Optional: Allow manual dismissal by clicking
            messageAlert.addEventListener('click', removeMessage);

            // Debug logging
            console.log('Message alert found:', messageAlert);
        });
    });
    </script> -->