<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}
require_once 'config.php';

// Initialize database connection
$database = new Database();
$pdo = $database->connect();

// Handle banning/unbanning users via POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate inputs
        $userId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
            exit;
        }

        // Start transaction
        $pdo->beginTransaction();

        // Fetch username of the user being modified
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception('User not found');
        }

        if ($action === 'ban') {
            // Get ban reasons
            $reasons = filter_input(INPUT_POST, 'reasons', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $otherReason = filter_input(INPUT_POST, 'other_reason', FILTER_SANITIZE_STRING);

            // Update user's banned status
            $stmt = $pdo->prepare("UPDATE users SET is_banned = 1 WHERE id = ?");
            $stmt->execute([$userId]);

            // Prepare reason string
            $reasonString = is_array($reasons) ? implode(', ', $reasons) : '';
            if (!empty($otherReason)) {
                $reasonString .= (!empty($reasonString) ? '; ' : '') . 'Other: ' . $otherReason;
            }

            // Log staff activity for banning
            $stmt = $pdo->prepare("INSERT INTO staff_activity_log (staff_id, activity, description) VALUES (?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'],
                'User Banned',
                "Banned user {$user['username']} - Reasons: {$reasonString}"
            ]);
        } elseif ($action === 'unban') {
            // Update user's banned status
            $stmt = $pdo->prepare("UPDATE users SET is_banned = 0 WHERE id = ?");
            $stmt->execute([$userId]);

            // Log staff activity for unbanning
            $stmt = $pdo->prepare("INSERT INTO staff_activity_log (staff_id, activity, description) VALUES (?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'],
                'User Unbanned',
                "Unbanned user {$user['username']}"
            ]);
        } else {
            throw new Exception('Invalid action');
        }

        // Commit transaction
        $pdo->commit();

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>User Management</h2>
    </div>
    <div class="card">
        <div class="card-body">
        <div class="table-responsive">
            <table id="accountTable" class="table table-hovered">
                <thead class="table-light border">
                    <tr>
                        <th class="border">User Name</th>
                        <th class="border">Role</th>
                        <th class="border">Status</th>
                        <th class="border">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $stmt = $pdo->prepare("SELECT u.id,u.username, r.role_name, u.is_banned 
                    FROM users u
                    JOIN roles r ON u.role_id = r.id
                    WHERE r.role_name IN ('member', 'user')");
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($users as $user) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                    echo "<td>" . htmlspecialchars($user['role_name']) . "</td>";
                    echo "<td>" . ($user['is_banned'] == 0 ? 'Active' : 'Banned') . "</td>";
                    echo "<td>";
                    if ($user['is_banned'] == 0) {
                        echo "<button class='btn btn-danger ban-btn' data-id='" . $user['id'] . "' data-bs-toggle='modal' data-bs-target='#banModal'>Ban</button>";
                    } else {
                        echo "<button class='btn btn-success unban-btn' data-id='" . $user['id'] . "' data-bs-toggle='modal' data-bs-target='#unbanModal'>Unban</button>";
                    }
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
        </div>
        </div>
    </div>

        <!-- Ban Modal -->
        <div class="modal fade" id="banModal" tabindex="-1" aria-labelledby="banModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="banModalLabel">Ban User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Select reason(s) for banning this user:</p>
                        <form id="banReasonForm">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Inappropriate Behavior" id="inappropriateBehavior" name="reasons[]">
                                <label class="form-check-label" for="inappropriateBehavior">Inappropriate Behavior</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Fraudulent Activities" id="fraudulentActivities" name="reasons[]">
                                <label class="form-check-label" for="fraudulentActivities">Fraudulent Activities</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Security Threats" id="securityThreats" name="reasons[]">
                                <label class="form-check-label" for="securityThreats">Security Threats</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Violation of Terms of Service" id="tosViolation" name="reasons[]">
                                <label class="form-check-label" for="tosViolation">Violation of Terms of Service</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="Others" id="otherReasons" name="reasons[]">
                                <label class="form-check-label" for="otherReasons">Others</label>
                            </div>
                            <div class="mt-3" id="otherReasonInput" style="display:none;">
                                <label for="otherReasonText" class="form-label">Specify Other Reason</label>
                                <input type="text" class="form-control" id="otherReasonText" name="other_reason">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="banBtn">Ban</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Unban Modal -->
        <div class="modal fade" id="unbanModal" tabindex="-1" aria-labelledby="unbanModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="unbanModalLabel">Unban User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to unban this user?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" id="unbanBtn">Unban</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>

$(document).ready(function() {
    // Initialize DataTable
    $('#accountTable').DataTable({
        responsive: true,
        order: [[3, 'desc']], // Sort by check-in time by default
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip', // Custom layout
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search members..."
        },
        columnDefs: [
            { orderable: false, targets: [0] } // Disable sorting for photo column
        ]
    });
    });
    $(document).ready(function() {
        // Show/hide other reason input when "Others" checkbox is checked
        $('#otherReasons').on('change', function() {
            $('#otherReasonInput').toggle(this.checked);
        });

        // Store the user ID when opening the ban modal
        $('.ban-btn').on('click', function() {
            const userId = $(this).data('id');
            $('#banBtn').data('userId', userId);
        });

        // Store the user ID when opening the unban modal
        $('.unban-btn').on('click', function() {
            const userId = $(this).data('id');
            $('#unbanBtn').data('userId', userId);
        });

        // Ban button click handler
        $('#banBtn').on('click', function(e) {
            e.preventDefault();
            const userId = $(this).data('userId');
            const reasons = $('#banReasonForm input[name="reasons[]"]:checked').map(function() {
                return this.value;
            }).get();
            const otherReason = $('#otherReasonText').val();

            $.post('pages/accounts/accounts.php', {
                id: userId,
                action: 'ban',
                reasons: reasons,
                other_reason: otherReason
            }, function(data) {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            }, 'json');
        });

        // Unban button click handler
        $('#unbanBtn').on('click', function(e) {
            e.preventDefault();
            const userId = $(this).data('userId');

            $.post('pages/accounts/accounts.php', {
                id: userId,
                action: 'unban'
            }, function(data) {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            }, 'json');
        });
    });
    </script>