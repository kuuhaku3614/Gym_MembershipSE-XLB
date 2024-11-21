<?php
require_once 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create a new database connection
$database = new Database();
$pdo = $database->connect();

// Fetch Active Programs
$programStmt = $pdo->prepare("
    SELECT p.id, p.program_name, pt.type_name, pd.first_name, pd.last_name, p.price 
    FROM programs p
    JOIN program_types pt ON p.program_type_id = pt.id
    JOIN coaches c ON p.coach_id = c.id
    JOIN users u ON c.user_id = u.id
    JOIN personal_details pd ON u.id = pd.user_id
    JOIN status_types st ON p.status_id = st.id
    WHERE st.status_name = 'active'
");
$programStmt->execute();
$programs = $programStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Active Rental Services
$rentalStmt = $pdo->prepare("
    SELECT rs.id, rs.service_name, rs.description, rs.available_slots, rs.price 
    FROM rental_services rs 
    JOIN status_types st ON rs.status_id = st.id
    JOIN duration_types dt ON rs.duration_type_id = dt.id
    WHERE st.status_name = 'active'
");
$rentalStmt->execute();
$rentals = $rentalStmt->fetchAll(PDO::FETCH_ASSOC);

// Generate HTML output
$output = '<h4>Available Programs</h4>';
$output .= '<div class="services-scrollable-container">';
foreach ($programs as $program) {
    $output .= '
        <div class="service-box program" data-id="' . $program['id'] . '">
            <h6>' . $program['program_name'] . '</h6>
            <p><strong>Type:</strong> ' . $program['type_name'] . '</p>
            <p><strong>Coach:</strong> ' . $program['first_name'] . ' ' . $program['last_name'] . '</p>
            <p><strong>Price:</strong> ₱' . number_format($program['price'], 2) . '</p>
        </div>
    ';
}
$output .= '</div>';

$output .= '<h4>Available Rental Services</h4>';
$output .= '<div class="services-scrollable-container">';
foreach ($rentals as $rental) {
    $output .= '
        <div class="service-box rental" data-id="' . $rental['id'] . '">
            <h6>' . $rental['service_name'] . '</h6>
            <p><strong>Description:</strong> ' . $rental['description'] . '</p>
            <p><strong>Available Slots:</strong> ' . $rental['available_slots'] . '</p>
            <p><strong>Price:</strong> ₱' . number_format($rental['price'], 2) . '</p>
        </div>
    ';
}
$output .= '</div>';

echo $output;
?>
