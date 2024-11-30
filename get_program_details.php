<?php
require_once 'config.php';

// Check if a specific ID is requested
$programId = isset($_GET['id']) ? intval($_GET['id']) : null;

$query = "
    SELECT p.*, pt.type_name 
    FROM programs p 
    JOIN program_types pt ON p.program_type_id = pt.id
    JOIN status_types st ON p.status_id = st.id
    WHERE st.status_name = 'active'
";

if ($programId) {
    $query .= " AND p.id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id', $programId, PDO::PARAM_INT);
} else {
    $stmt = $pdo->query($query);
}

$programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php foreach ($programs as $program): ?>
    <div class="service-box program" data-id="<?= $program['id'] ?>">
        <h6 class="program-name"><?= htmlspecialchars($program['program_name']) ?></h6>
        <p><?= htmlspecialchars($program['type_name']) ?></p>
        <p class="text-primary program-price">₱<?= number_format($program['price'], 2) ?></p>
        <input type="hidden" class="program-id" value="<?= $program['id'] ?>">
    </div>
<?php endforeach; ?>