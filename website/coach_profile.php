<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    session_start();
    require_once 'user_account/coach.class.php';

    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login/login.php');
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
    
    // Debug information
    error_log("Coach ID: " . $_SESSION['user_id']);
    error_log("Number of programs found: " . count($coachPrograms));

    include('includes/header.php');
?>

    <header class="container-fluid" id="title">
        <div class="container-xl" id="name" style="position: relative;">
            <h1><?= $_SESSION['personal_details']['name'] ?></h1>
            <?php if (isset($_SESSION['personal_details']['role_name'])): ?>
                <?php if ($_SESSION['personal_details']['role_name'] === 'member'): ?>
                    <h5>Member</h5>
                <?php elseif ($_SESSION['personal_details']['role_name'] === 'coach'): ?>
                    <h5>Coach</h5>
                <?php endif; ?>
            <?php endif; ?>
            <a href="profile.php" class="btn btn-link" style="position: absolute; right: 0; top: 50%; transform: translateY(-50%);">
                <i class="fas fa-dumbbell"></i>
            </a>
        </div>
    </header>

    <section>
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h4>My Programs</h4>
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


            <!-- Program Members Table -->
            <div class="card mt-4 mb-4">
                <div class="card-header">
                    <h4>Program Members</h4>
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
                                <?php 
                                $programMembers = $coach->getProgramMembers($_SESSION['user_id']);
                                if (!empty($programMembers)): 
                                ?>
                                    <?php foreach ($programMembers as $member): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($member['member_name']) ?></td>
                                            <td><?= htmlspecialchars($member['program_name']) ?></td>
                                            <td><?= htmlspecialchars($member['program_duration']) ?></td>
                                            <td>₱<?= number_format($member['program_price'], 2) ?></td>
                                            <td><?= date('M d, Y', strtotime($member['start_date'])) ?></td>
                                            <td><?= date('M d, Y', strtotime($member['end_date'])) ?></td>
                                            <td>
                                                <?php if ($member['membership_status'] === 'active'): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php elseif ($member['membership_status'] === 'expired'): ?>
                                                    <span class="badge bg-danger">Expired</span>
                                                <?php elseif ($member['membership_status'] === 'pending'): ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?= ucfirst($member['membership_status']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No members have subscribed to your programs yet</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <style>
        :root {
            --red: #ff0000 !important;
            --white: #ffffff !important;
            --black: #000000 !important;
        }

        body {
            background-color: whitesmoke !important;
        }

        header {
            margin-bottom: 2rem !important;
        }

        #title {
            background-color: <?php echo (isset($_SESSION['personal_details']['role_name']) && $_SESSION['personal_details']['role_name'] === 'coach') ? '#000000' : 'var(--red)'; ?> !important;
            color: white !important;
            padding: 2rem 0 !important;
            box-shadow: 0px 5px 10px gray !important;
            display: flex !important;
            justify-content: center !important;
        }

        #name h1 {
            font-size: 2.5rem !important;
            font-weight: 900 !important;
            font-style: italic !important;
            font-family: arial !important;
            margin: 0 !important;
        }

        #name h5 {
            font-size: 1.25rem !important;
            font-family: arial !important;
        }

        #name {
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
        }

        section {
            width: 100% !important;
            padding: 0 5% !important;
            display: flex !important;
            flex-wrap: wrap !important;
        }

        .card {
            width: 100%;
            margin-bottom: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .card-header {
            background-color: var(--black);
            color: var(--white);
            padding: 1rem;
            border-radius: 10px 10px 0 0;
        }

        .card-header h4 {
            margin: 0;
            font-weight: 600;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
    </style>