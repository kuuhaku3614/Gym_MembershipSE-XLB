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
                                        <th>Status</th>
                                        <th>Member Name</th>
                                        <th>Program</th>
                                        <th>Contact</th>
                                        <th>Schedule</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($members)): ?>
                                        <?php foreach ($members as $member): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($member['subscription_status']) ?></td>
                                                <td><?= htmlspecialchars($member['member_name']) ?></td>
                                                <td><?= $member['programs'] ?></td>
                                                <td><?= htmlspecialchars($member['contact']) ?></td>
                                                <td>
                                                    <?php 
                                                    $subscription_ids = explode(',', $member['subscription_ids']);
                                                    foreach ($subscription_ids as $subscription_id) {
                                                        $schedules = $coach->getProgramSubscriptionSchedule($subscription_id);
                                                        if (!empty($schedules)) {
                                                            foreach ($schedules as $schedule) {
                                                                echo htmlspecialchars(date('M d, Y', strtotime($schedule['date']))) . ' (' . 
                                                                     htmlspecialchars($schedule['day']) . ')<br>' .
                                                                     htmlspecialchars(date('h:i A', strtotime($schedule['start_time']))) . ' - ' .
                                                                     htmlspecialchars(date('h:i A', strtotime($schedule['end_time']))) . '<br>' .
                                                                     'Amount: ₱' . htmlspecialchars(number_format($schedule['amount'], 2)) .
                                                                     ' (' . htmlspecialchars($schedule['schedule_type']) . ')' .
                                                                     ' [' . ($schedule['is_paid'] ? 'Paid' : 'Unpaid') . ']<br><br>';
                                                            }
                                                        }
                                                    }
                                                    if (empty($schedules)) {
                                                        echo 'No schedules found';
                                                    }
                                                    ?>
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