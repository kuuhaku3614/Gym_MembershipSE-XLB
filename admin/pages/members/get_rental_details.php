<?php
require_once 'config.php';

if (isset($_GET['id'])) {
    $rentalId = intval($_GET['id']);

    $stmt = $pdo->prepare("
        SELECT rs.*, st.status_name, dt.type_name as duration_type
        FROM rental_services rs 
        JOIN status_types st ON rs.status_id = st.id
        JOIN duration_types dt ON rs.duration_type_id = dt.id
        WHERE rs.id = ? AND st.status_name = 'active'
    ");
    $stmt->execute([$rentalId]);
    $rental = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($rental) {
        ?>
        <input type="hidden" class="rental-id" value="<?= $rental['id'] ?>">
        <h6 class="rental-name"><?= htmlspecialchars($rental['service_name']) ?></h6>
        <p><strong>Description:</strong> <?= htmlspecialchars($rental['description']) ?></p>
        <p><strong>Available Slots:</strong> <?= $rental['available_slots'] ?></p>
        <p><strong>Duration:</strong> <?= htmlspecialchars($rental['duration_type']) ?></p>
        <p><strong>Price:</strong> <span class="rental-price">₱<?= number_format($rental['price'], 2) ?></span></p>
        <?php
    } else {
        echo "Rental details not found.";
    }
} else {
    echo "Invalid rental ID.";
}
?>