document.addEventListener('DOMContentLoaded', function() {
    const dropdownBtn = document.querySelector('.dropbtn');
    const dropdownContent = document.querySelector('.dropdown-content');
    
    // Toggle dropdown on button click
    dropdownBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        const isExpanded = dropdownBtn.getAttribute('aria-expanded') === 'true';
        dropdownContent.classList.toggle('show');
        dropdownBtn.setAttribute('aria-expanded', !isExpanded);
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!dropdownContent.contains(e.target) && !dropdownBtn.contains(e.target)) {
            dropdownContent.classList.remove('show');
            dropdownBtn.setAttribute('aria-expanded', 'false');
        }
    });
    
    // Close dropdown when pressing Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && dropdownContent.classList.contains('show')) {
            dropdownContent.classList.remove('show');
            dropdownBtn.setAttribute('aria-expanded', 'false');
            dropdownBtn.focus(); // Return focus to button
        }
    });
});