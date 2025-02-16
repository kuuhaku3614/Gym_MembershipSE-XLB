<?php
    session_start();
    require_once '../coach.class.php';

    if (!isset($_SESSION['user_id'])) {
        header('Location: ../../login/login.php');
        exit();
    }

    $coach = new Coach_class();

    // Handle program status toggle
    if (isset($_POST['toggle_status'])) {
        $programId = $_POST['program_id'];
        $currentStatus = $_POST['current_status'];
        $success = $coach->toggleProgramStatus($programId, $_SESSION['user_id'], $currentStatus);
        // For AJAX responses
        if (isset($_POST['ajax'])) {
            echo json_encode(['success' => $success]);
            exit;
        }
    }

    $coachPrograms = $coach->getCoachPrograms($_SESSION['user_id']);
    include('../coach.nav.php');
?>

<?php if (!empty($message)): ?>
<div class="alert alert-info alert-dismissible fade show" role="alert">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div id="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">My Programs</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Program Name</th>
                                        <th>Description</th>
                                        <th>Duration</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($coachPrograms)): ?>
                                        <?php foreach ($coachPrograms as $program): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($program['program_name']) ?></td>
                                                <td><?= htmlspecialchars($program['coach_program_description']) ?></td>
                                                <td><?= htmlspecialchars($program['duration'] . ' ' . $program['duration_type']) ?></td>
                                                <td>₱<?= number_format($program['coach_program_price'], 2) ?></td>
                                                <td>
                                                    <?php if ($program['coach_program_status'] === 'active'): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button type="button" 
                                                            class="btn <?= $program['coach_program_status'] === 'active' ? 'btn-danger' : 'btn-success' ?> btn-sm"
                                                            onclick="toggleStatus(<?= $program['coach_program_type_id'] ?>, '<?= $program['coach_program_status'] ?>', this)">
                                                        <?php if ($program['coach_program_status'] === 'active'): ?>
                                                            <i class="fas fa-times-circle"></i> Deactivate
                                                        <?php else: ?>
                                                            <i class="fas fa-check-circle"></i> Activate
                                                        <?php endif; ?>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No programs found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleStatus(programId, currentStatus, button) {
    // Create form data
    const formData = new FormData();
    formData.append('program_id', programId);
    formData.append('current_status', currentStatus);
    formData.append('toggle_status', '1');
    formData.append('ajax', '1');

    // Send AJAX request
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update button and badge without page reload
            const row = button.closest('tr');
            const badge = row.querySelector('.badge');
            const isCurrentlyActive = currentStatus === 'active';
            
            // Update badge
            badge.className = isCurrentlyActive ? 'badge bg-danger' : 'badge bg-success';
            badge.textContent = isCurrentlyActive ? 'Inactive' : 'Active';
            
            // Update button
            button.className = isCurrentlyActive ? 'btn btn-success btn-sm' : 'btn btn-danger btn-sm';
            button.innerHTML = isCurrentlyActive ? 
                '<i class="fas fa-check-circle"></i> Activate' : 
                '<i class="fas fa-times-circle"></i> Deactivate';
            
            // Update onclick handler
            button.setAttribute('onclick', `toggleStatus(${programId}, '${isCurrentlyActive ? 'inactive' : 'active'}', this)`);
            
            // Show alert
            alert('Program status updated successfully!');
        } else {
            alert('Failed to update program status.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update program status.');
    });
}
</script>
