<?php
    session_start();
    require_once '../coach.class.php';

    if (!isset($_SESSION['user_id'])) {
        header('Location: ../../login/login.php');
        exit();
    }

    $coach = new Coach_class();
    $message = '';

    // Handle status toggle
    if (isset($_POST['toggle_status']) && isset($_POST['program_id'])) {
        $currentStatus = $_POST['current_status'];
        $programId = $_POST['program_id'];
        if ($coach->toggleProgramStatus($programId, $_SESSION['user_id'], $currentStatus)) {
            echo "<script>alert('Program status updated successfully!');</script>";
        } else {
            echo "<script>alert('Failed to update program status.');</script>";
        }
    }

    $coachPrograms = $coach->getCoachPrograms($_SESSION['user_id']);
    include('../coach.nav.php');
?>

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
                                                <td><?= htmlspecialchars($program['description']) ?></td>
                                                <td><?= htmlspecialchars($program['program_duration']) ?></td>
                                                <td>₱<?= number_format($program['program_price'], 2) ?></td>
                                                <td>
                                                    <?php if ($program['program_status'] === 'active'): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="program_id" value="<?= $program['program_id'] ?>">
                                                        <input type="hidden" name="current_status" value="<?= $program['program_status'] ?>">
                                                        <?php if ($program['program_status'] === 'active'): ?>
                                                            <button type="submit" name="toggle_status" class="btn btn-danger btn-sm">
                                                                <i class="fas fa-times-circle"></i> Deactivate
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="submit" name="toggle_status" class="btn btn-success btn-sm">
                                                                <i class="fas fa-check-circle"></i> Activate
                                                            </button>
                                                        <?php endif; ?>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No programs available</td>
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
