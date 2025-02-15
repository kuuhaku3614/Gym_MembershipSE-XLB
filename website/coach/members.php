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
                                        <th>Price</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($members)): ?>
                                        <?php foreach ($members as $member): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($member['member_name']) ?></td>
                                                <td><?= htmlspecialchars($member['program_name']) ?></td>
                                                <td><?= htmlspecialchars($member['program_duration']) ?></td>
                                                <td>₱<?= number_format($member['program_price'], 2) ?></td>
                                                <td><?= date('M d, Y', strtotime($member['start_date'])) ?></td>
                                                <td><?= date('M d, Y', strtotime($member['end_date'])) ?></td>
                                                <td>
                                                    <?php if (strtotime($member['end_date']) >= time()): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Expired</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No members enrolled in your programs</td>
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