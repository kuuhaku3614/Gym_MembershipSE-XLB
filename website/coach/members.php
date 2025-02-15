<?php
    session_start();
    require_once '../coach.class.php';

    if (!isset($_SESSION['user_id'])) {
        header('Location: ../../login/login.php');
        exit();
    }

    $coach = new Coach_class();
    $members = $coach->getProgramMembers($_SESSION['user_id']);
    include('../coach.nav.php');
?>

<div id="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Program Members</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Member Name</th>
                                        <th>Program</th>
                                        <th>Duration</th>
                                        <th>Contact</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($members)): ?>
                                        <?php foreach ($members as $member): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></td>
                                                <td><?= htmlspecialchars($member['program_name']) ?></td>
                                                <td><?= htmlspecialchars($member['duration'] . ' ' . $member['duration_type']) ?></td>
                                                <td><?= htmlspecialchars($member['contact_no']) ?></td>
                                                <td><?= date('M d, Y', strtotime($member['start_date'])) ?></td>
                                                <td><?= date('M d, Y', strtotime($member['end_date'])) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $member['subscription_status'] === 'active' ? 'success' : 
                                                        ($member['subscription_status'] === 'expiring' ? 'warning' : 'danger') ?>">
                                                        <?= ucfirst($member['subscription_status']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No members found</td>
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