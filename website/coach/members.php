<?php
    session_start();
    require_once '../coach.class.php';

    if (!isset($_SESSION['user_id'])) {
        header('Location: ../../login/login.php');
        exit();
    }

    // Handle payment processing
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_payment') {
        $scheduleIds = json_decode($_POST['schedule_ids'] ?? '[]', true);
        if (empty($scheduleIds)) {
            echo json_encode(['success' => false, 'message' => 'No schedules selected']);
            exit();
        }

        $coach = new Coach_class();
        $result = $coach->processSchedulePayments($scheduleIds);

        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Payment processed successfully' : 'Failed to process payment'
        ]);
        exit();
    }

    function formatSchedule($schedule) {
        $date = date('M d, Y', strtotime($schedule['date'])) . ' (' . $schedule['day'] . ')';
        $time = date('h:i A', strtotime($schedule['start_time'])) . ' - ' . date('h:i A', strtotime($schedule['end_time']));
        $amount = '₱' . number_format($schedule['amount'], 2);
        $type = $schedule['schedule_type'];
        $program = $schedule['program_name'];
        $status = $schedule['is_paid'] ? 
            '<span class="badge bg-success">Paid</span>' : 
            '<span class="badge bg-warning">Unpaid</span>';
        
        return [
            'date' => $date,
            'time' => $time,
            'amount' => $amount,
            'type' => $type,
            'program' => $program,
            'status' => $status
        ];
    }

    $coach = new Coach_class();
    $members = $coach->getProgramMembers($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program Members</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="../../vendor/bootstrap-5.3.3/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../../vendor/fontawesome-free-5.15.4-web/css/all.min.css">
    
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <style>
        .modal-content {
            padding: 0 !important;
            max-width: 100% !important;
        }

        #schedtd {
            font-family: var(--bs-body-font-family);
            font-size: var(--bs-body-font-size);
            font-weight: var(--bs-body-font-weight);
        }

        #schedtd div {
            font-family: inherit;
            font-size: inherit;
            font-weight: 600;
        }
        @media screen and (max-width: 480px) {
        #content {
            margin-top: 150px!important;
            padding: 0!important;
            font-size: xx-small!important;
        }
        th{
            font-size: 10px!important;
        }
    }
    </style>
</head>
<body>
    <?php include('../coach.nav.php'); ?>

    <div id="content">
    <div class="dashboard-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h4>MEMBERS</h4>
                <div>
                    <span class="badge bg-light text-dark">
                        <i class="fas fa-calendar-day"></i> <?php echo date('F d, Y l'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

        <div class="container">
            <div class="card mb-4">
                        <div class="card-header">
                           <i class="fas fa-users"></i> Program Members
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="memberTable" class="table table-hover">
                                <thead class="table-light">
                                        <tr>
                                            <th></th>
                                            <th>Name</th>
                                            <th>Program</th>
                                            <th>Contact</th>
                                            <th>Payment Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($members)): ?>
                                            <?php foreach ($members as $member): 
                                                $subscription_ids = explode(',', $member['subscription_ids']);
                                                $latestSchedule = null;
                                                $allSchedules = [];
                                                
                                                foreach ($subscription_ids as $subscription_id) {
                                                    $schedules = $coach->getProgramSubscriptionSchedule($subscription_id);
                                                    if (!empty($schedules)) {
                                                        foreach ($schedules as $schedule) {
                                                            $allSchedules[] = $schedule;
                                                            if ($latestSchedule === null || 
                                                                ($schedule['date'] < $latestSchedule['date']) || 
                                                                ($schedule['date'] == $latestSchedule['date'] && $schedule['start_time'] < $latestSchedule['start_time'])) {
                                                                $latestSchedule = $schedule;
                                                            }
                                                        }
                                                    }
                                                }
                                                ?>
                                                <tr>
                                                    <td id="schedtd">
                                                        <?php
                                                        if (!empty($allSchedules)) {
                                                            $output[] = sprintf(
                                                                '<button type="button" class="btn btn-sm btn-info ms-2" data-bs-toggle="modal" data-bs-target="#scheduleModal%d">
                                                                    <i class="fas fa-calendar-alt"></i>
                                                                </button></div>',
                                                                $member['member_id']
                                                            );
                                                        }

                                                        echo implode('', $output);
                                                        ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($member['member_name']) ?></td>
                                                    <td><?= $member['programs'] ?></td>
                                                    <td><?= htmlspecialchars($member['contact']) ?></td>
                                                    <td>
                                                        <?php
                                                        if ($member['total_sessions'] > 0) {
                                                            $paid_sessions = (int)$member['paid_sessions'];
                                                            $total_sessions = (int)$member['total_sessions'];
                                                            $unpaid_sessions = $total_sessions - $paid_sessions;
                                                            
                                                            if ($unpaid_sessions == 0) {
                                                                echo 'Fully Paid';
                                                            } else {
                                                                echo 'Partially Paid - ' . $unpaid_sessions . ' unpaid pending sessions';
                                                                echo '<div class="mt-2">';
                                                                echo '<button type="button" class="btn btn-sm btn-primary" onclick="handlePayment(' . $member['member_id'] . ')">';
                                                                echo '<i class="fas fa-money-bill-wave me-1"></i>Pay';
                                                                echo '</button>';
                                                                echo '</div>';
                                                            }
                                                        } else {
                                                            echo 'No Sessions';
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
            </div>
        </div>

        <!-- Schedule Modals -->
        <?php if (!empty($members)): 
            foreach ($members as $member): 
                $subscription_ids = explode(',', $member['subscription_ids']);
                $allSchedules = [];
                
                foreach ($subscription_ids as $subscription_id) {
                    $schedules = $coach->getProgramSubscriptionSchedule($subscription_id);
                    if (!empty($schedules)) {
                        $allSchedules = array_merge($allSchedules, $schedules);
                    }
                }

                // Schedule Modal
                if (!empty($allSchedules)): 
                    usort($allSchedules, function($a, $b) {
                        $dateCompare = strcmp($a['date'], $b['date']);
                        if ($dateCompare === 0) {
                            return strcmp($a['start_time'], $b['start_time']);
                        }
                        return $dateCompare;
                    });
                    
                    $modalContent = [];
                    $modalContent[] = sprintf(
                        '<div class="modal fade" id="scheduleModal%d" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">All Schedules - %s</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">',
                        $member['member_id'],
                        htmlspecialchars($member['member_name'])
                    );

                    $modalContent[] = '<div class="table-responsive"><table class="table">';
                    $modalContent[] = '<thead><tr><th>Date</th><th>Time</th><th>Program</th><th>Status</th></tr></thead><tbody>';

                    foreach ($allSchedules as $schedule) {
                        $formattedSchedule = formatSchedule($schedule);
                        $modalContent[] = sprintf(
                            '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                            $formattedSchedule['date'],
                            $formattedSchedule['time'],
                            $formattedSchedule['program'] . ' (' . $formattedSchedule['type'] . ')',
                            $formattedSchedule['status']
                        );
                    }

                    $modalContent[] = '</tbody></table></div>';
                    $modalContent[] = '</div></div></div></div>';
                    
                    echo implode('', $modalContent);
                endif;

                // Payment Modal
                $unpaidSchedules = $coach->getUnpaidSchedules($member['member_id']);
                if (!empty($unpaidSchedules)):
                    $totalAmount = array_sum(array_column($unpaidSchedules, 'amount'));
                    
                    $paymentModalContent = [];
                    $paymentModalContent[] = sprintf(
                        '<div class="modal fade" id="paymentModal%d" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Payment Details - %s</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">',
                        $member['member_id'],
                        htmlspecialchars($member['member_name'])
                    );

                    $paymentModalContent[] = sprintf(
                        '<div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selectAll%d" onchange="toggleAllSchedules(%d)">
                                <label class="form-check-label" for="selectAll%d">Select All</label>
                            </div>
                            <div>
                                Total Selected: <span class="fw-bold" id="totalAmount%d">₱0.00</span>
                            </div>
                        </div>',
                        $member['member_id'],
                        $member['member_id'],
                        $member['member_id'],
                        $member['member_id']
                    );

                    $paymentModalContent[] = '<div class="table-responsive"><table class="table">';
                    $paymentModalContent[] = '<thead><tr><th></th><th>Date</th><th>Time</th><th>Program</th><th>Amount</th></tr></thead><tbody>';

                    foreach ($unpaidSchedules as $schedule) {
                        $date = date('M d, Y', strtotime($schedule['date']));
                        $time = date('h:i A', strtotime($schedule['start_time'])) . ' - ' . date('h:i A', strtotime($schedule['end_time']));
                        $amount = number_format($schedule['amount'], 2);
                        $program = $schedule['program_name'] . ' (' . $schedule['schedule_type'] . ')';
                        
                        $paymentModalContent[] = sprintf(
                            '<tr>
                                <td>
                                    <input class="form-check-input schedule-checkbox" type="checkbox" 
                                        data-member="%d" 
                                        data-amount="%s" 
                                        data-schedule="%d" 
                                        onchange="updateTotal(%d)">
                                </td>
                                <td>%s</td>
                                <td>%s</td>
                                <td>%s</td>
                                <td>₱%s</td>
                            </tr>',
                            $member['member_id'],
                            $schedule['amount'],
                            $schedule['schedule_id'],
                            $member['member_id'],
                            $date,
                            $time,
                            $program,
                            $amount
                        );
                    }

                    $paymentModalContent[] = '</tbody></table></div>';
                    
                    $paymentModalContent[] = '<div class="modal-footer">';
                    $paymentModalContent[] = sprintf(
                        '<button type="button" class="btn btn-primary" onclick="processPayment(%d)" id="confirmPayment%d" disabled>
                            <i class="fas fa-check me-1"></i>Confirm Payment
                        </button>',
                        $member['member_id'],
                        $member['member_id']
                    );
                    $paymentModalContent[] = '</div>';
                    
                    $paymentModalContent[] = '</div></div></div></div>';
                    
                    echo implode('', $paymentModalContent);
                endif;
            endforeach;
        endif; ?>

    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    $('#memberTable').DataTable({
        order: [[4, 'desc'], [5, 'asc']], // Sort by date (desc) and time (asc)

        responsive: true
    });
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    if (tooltipTriggerList.length > 0) {
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
    }
});
        function handlePayment(memberId) {
            const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal' + memberId));
            paymentModal.show();
        }

        function updateTotal(memberId) {
            const checkboxes = document.querySelectorAll(`input[type="checkbox"][data-member="${memberId}"]`);
            let total = 0;
            let selectedSchedules = [];

            checkboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    total += parseFloat(checkbox.dataset.amount);
                    selectedSchedules.push(parseInt(checkbox.dataset.schedule));
                }
            });

            // Update total display
            document.getElementById(`totalAmount${memberId}`).textContent = `₱${total.toFixed(2)}`;
            
            // Enable/disable confirm button based on selection
            const confirmButton = document.getElementById(`confirmPayment${memberId}`);
            confirmButton.disabled = selectedSchedules.length === 0;
        }

        function toggleAllSchedules(memberId) {
            const selectAllCheckbox = document.getElementById(`selectAll${memberId}`);
            const checkboxes = document.querySelectorAll(`input[type="checkbox"][data-member="${memberId}"]`);
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });

            updateTotal(memberId);
        }

        async function processPayment(memberId) {
            const checkboxes = document.querySelectorAll(`input[type="checkbox"][data-member="${memberId}"]:checked`);
            const selectedSchedules = Array.from(checkboxes).map(checkbox => checkbox.dataset.schedule);
            
            if (selectedSchedules.length === 0) {
                alert('Please select at least one schedule to pay');
                return;
            }

            const confirmPayment = confirm('Are you sure you want to process payment for the selected sessions?');
            if (!confirmPayment) return;

            try {
                const confirmButton = document.getElementById(`confirmPayment${memberId}`);
                confirmButton.disabled = true;
                confirmButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing...';

                const formData = new FormData();
                formData.append('action', 'process_payment');
                formData.append('schedule_ids', JSON.stringify(selectedSchedules));

                const response = await fetch('members.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert('Payment processed successfully');
                    // Close modal and refresh page
                    const modal = bootstrap.Modal.getInstance(document.getElementById(`paymentModal${memberId}`));
                    modal.hide();
                    location.reload();
                } else {
                    throw new Error(result.message || 'Failed to process payment');
                }
            } catch (error) {
                alert(error.message);
                confirmButton.disabled = false;
                confirmButton.innerHTML = '<i class="fas fa-check me-1"></i>Confirm Payment';
            }
        }

    </script>
</body>
</html>