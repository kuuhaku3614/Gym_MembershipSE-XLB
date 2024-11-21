<?php
require_once 'config.php';

// Check if a specific ID is requested
$rentalId = isset($_GET['id']) ? intval($_GET['id']) : null;

$query = "
    SELECT rs.*, dt.type_name as duration_type
    FROM rental_services rs 
    JOIN status_types st ON rs.status_id = st.id
    JOIN duration_types dt ON rs.duration_type_id = dt.id
    WHERE st.status_name = 'active'
";

if ($rentalId) {
    $query .= " AND rs.id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id', $rentalId, PDO::PARAM_INT);
} else {
    $stmt = $pdo->query($query);
}

$rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php foreach ($rentals as $rental): ?>
    <div class="service-box rental" data-id="<?= $rental['id'] ?>">
        <h6 class="rental-name"><?= htmlspecialchars($rental['service_name']) ?></h6>
        <p>Available Slots: <?= $rental['available_slots'] ?></p>
        <p class="text-primary rental-price">₱<?= number_format($rental['price'], 2) ?></p>
        <input type="hidden" class="rental-id" value="<?= $rental['id'] ?>">
    </div>
<?php endforeach; ?>