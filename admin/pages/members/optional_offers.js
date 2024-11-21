$(document).ready(function() {
    function loadAvailableServices() {
        // Load Programs
        $('.services-scrollable-container:first').load('get_programs.php', function(response, status, xhr) {
            if (status == "error") {
                console.error("Error loading programs:", xhr.status, xhr.statusText);
            }
        });
        // Load Rental Services
        $('.services-scrollable-container:last').load('get_rentals.php', function(response, status, xhr) {
            if (status == "error") {
                console.error("Error loading rentals:", xhr.status, xhr.statusText);
            }
        });
    }

    // Add click event handlers for service details
    $(document).on('click', '.service-box.program', function() {
        var programId = $(this).data('id');
        loadProgramDetails(programId);
    });

    $(document).on('click', '.service-box.rental', function() {
        var rentalId = $(this).data('id');
        loadRentalDetails(rentalId);
    });

    function loadProgramDetails(programId) {
        $('#programDetailContent').load('get_program_details.php?id=' + programId, function(response, status, xhr) {
            if (status == "success") {
                $('#programDetailModal').modal('show');
            } else {
                console.error("Error loading program details:", xhr.status, xhr.statusText);
            }
        });
    }

    function loadRentalDetails(rentalId) {
        $('#rentalDetailContent').load('get_rental_details.php?id=' + rentalId, function(response, status, xhr) {
            if (status == "success") {
                $('#rentalDetailModal').modal('show');
            } else {
                console.error("Error loading rental details:", xhr.status, xhr.statusText);
            }
        });
    }

    // Call the function to load services when page is ready
    loadAvailableServices();
});