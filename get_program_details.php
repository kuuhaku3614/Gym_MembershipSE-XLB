<?php
require_once 'config.php';

// Check if a specific ID is requested
$programId = isset($_GET['id']) ? intval($_GET['id']) : null;

$query = "
    SELECT DISTINCT
        p.*, 
        pt.type_name, 
        cpt.id AS coach_program_id,
        cpt.price AS coach_program_price,
        cpt.status AS coach_program_status,
        u.username AS coach_username,
        u.id AS coach_id,
        r.role_name AS coach_role
    FROM programs p 
    JOIN program_types pt ON p.program_type_id = pt.id
    LEFT JOIN coach_program_types cpt ON p.id = cpt.program_id
    LEFT JOIN users u ON cpt.coach_id = u.id
    LEFT JOIN roles r ON u.role_id = r.id
    WHERE p.status = 'active'
";

if ($programId) {
    $query .= " AND p.id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id', $programId, PDO::PARAM_INT);
} else {
    $stmt = $pdo->query($query);
}

// Group programs by their ID to handle multiple coaches
$programsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
$programs = [];

foreach ($programsRaw as $row) {
    $programId = $row['id'];
    
    // Initialize program if not exists
    if (!isset($programs[$programId])) {
        $programs[$programId] = [
            'program_details' => $row,
            'coaches' => []
        ];
    }
    
    // Add coach if exists and not already added
    if (!empty($row['coach_id'])) {
        $coachExists = false;
        foreach ($programs[$programId]['coaches'] as $existingCoach) {
            if ($existingCoach['coach_id'] == $row['coach_id']) {
                $coachExists = true;
                break;
            }
        }
        
        if (!$coachExists) {
            $programs[$programId]['coaches'][] = [
                'coach_id' => $row['coach_id'],
                'coach_username' => $row['coach_username'],
                'coach_role' => $row['coach_role'],
                'coach_program_price' => $row['coach_program_price'],
                'coach_program_status' => $row['coach_program_status']
            ];
        }
    }
}
?>

<?php foreach ($programs as $programId => $program): 
    $programDetails = $program['program_details'];
    $coaches = $program['coaches'];
    
    // Default price from first active coach or set to 0 if no active coach found
    $activeCoaches = array_filter($coaches, function($coach) {
        return $coach['coach_program_status'] === 'active';
    });
    
    $price = !empty($activeCoaches) 
        ? reset($activeCoaches)['coach_program_price'] 
        : 0;
?>
    <div class="service-box program" data-id="<?= $programDetails['id'] ?>">
        <h6 class="program-name"><?= htmlspecialchars($programDetails['program_name']) ?></h6>
        <p>Type: <?= htmlspecialchars($programDetails['type_name']) ?></p>
        
        <?php if (!empty($coaches)): ?>
            <div class="coach-selection">
                <label for="coach-select-<?= $programDetails['id'] ?>">Select Coach:</label>
                <select 
                    id="coach-select-<?= $programDetails['id'] ?>" 
                    class="coach-select form-control" 
                    data-program-id="<?= $programDetails['id'] ?>"
                >
                    <?php foreach ($coaches as $coach): ?>
                        <option 
                            value="<?= $coach['coach_id'] ?>" 
                            data-price="<?= $coach['coach_program_price'] ?? 0 ?>"
                            <?= $coach['coach_program_status'] !== 'active' ? 'disabled' : '' ?>
                        >
                            <?= htmlspecialchars($coach['coach_username']) ?> 
                            (<?= htmlspecialchars($coach['coach_role']) ?>) 
                            <?= $coach['coach_program_status'] !== 'active' ? '- Inactive' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        
        <p class="text-primary program-price">
            ₱<span class="price-display"><?= number_format($price, 2) ?></span>
        </p>
        
        <input type="hidden" class="program-id" value="<?= $programDetails['id'] ?>">
        <input type="hidden" class="default-coach-id" value="<?= !empty($activeCoaches) ? reset($activeCoaches)['coach_id'] : '' ?>">
    </div>
<?php endforeach; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add event listener to coach selection dropdowns
    const coachSelects = document.querySelectorAll('.coach-select');
    
    coachSelects.forEach(select => {
        select.addEventListener('change', function() {
            // Get selected option
            const selectedOption = this.options[this.selectedIndex];
            
            // Update price display
            const priceSpan = this.closest('.service-box').querySelector('.price-display');
            const price = selectedOption.getAttribute('data-price');
            priceSpan.textContent = parseFloat(price).toFixed(2);
        });
    });
});
</script>