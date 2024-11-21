<?php
require_once 'config.php';

if (isset($_GET['id'])) {
    $programId = intval($_GET['id']);

    $stmt = $pdo->query("
    SELECT p.*, pt.type_name, c.id as coach_id, 
    c.user_id as coach_user_id, st.status_name
    FROM programs p 
    JOIN program_types pt ON p.program_type_id = pt.id
    JOIN coaches c ON p.coach_id = c.id 
    JOIN status_types st ON p.status_id = st.id
    WHERE st.status_name = 'active'
");
    $stmt->execute([$programId]);
    $program = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($program) {
        ?>
        <input type="hidden" class="program-id" value="<?= $program['id'] ?>">
        <h6 class="program-name"><?= htmlspecialchars($program['program_name']) ?></h6>
        <p><strong>Type:</strong> <?= htmlspecialchars($program['type_name']) ?></p>
        <p><strong>Description:</strong> <?= htmlspecialchars($program['description']) ?></p>
        <p><strong>Coach:</strong> <?= htmlspecialchars($program['first_name'] . ' ' . $program['last_name']) ?></p>
        <p><strong>Price:</strong> <span class="program-price">₱<?= number_format($program['price'], 2) ?></span></p>
        <p><strong>Schedule:</strong> <?= htmlspecialchars($program['schedule']) ?></p>
        <?php
    } else {
        echo "Program details not found.";
    }
} else {
    echo "Invalid program ID.";
}
?>