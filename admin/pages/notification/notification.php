<style>
    .header {
        background-color: #f8f9fa;
        padding: 20px;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .header h1 {
        margin: 0;
        font-size: 24px;
        color: #343a40;
    }

    .actions {
        display: flex;
        gap: 10px;
    }

    .search-input {
        padding: 8px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        width: 200px;
    }

    .search-btn,
    .refresh-btn {
        padding: 8px 12px;
        border: none;
        border-radius: 4px;
        background-color: #0d6efd;
        color: white;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .search-btn:hover,
    .refresh-btn:hover {
        background-color: #45a049;
    }

    .filter-select {
        padding: 8px;
        border: 1px solid #ced4da;
        border-radius: 4px;
    }
    .profile-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #4CAF50;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }
</style>

<?php
// config.php
require_once '../../../config.php';

$database = new Database();
$connection = $database->connect();

// notification_functions.php
function createNotification($connection, $staffId, $userId, $serviceType, $serviceId) {
    try {
        // Start a database transaction
        $connection->beginTransaction();

        // Insert transaction first
        $stmt = $connection->prepare("INSERT INTO transactions (staff_id, user_id) VALUES (?, ?)");
        $stmt->execute([$staffId, $userId]);
        $transactionId = $connection->lastInsertId();

        // Retrieve service details based on service type
        $notificationDetails = getServiceDetails($connection, $serviceType, $serviceId, $userId);

        // Close the transaction
        $connection->commit();

        return [
            'transaction_id' => $transactionId,
            'details' => $notificationDetails
        ];

    } catch (PDOException $e) {
        $connection->rollBack();
        error_log("Notification Creation Error: " . $e->getMessage());
        return false;
    }
}

function getServiceDetails($connection, $serviceType, $serviceId, $userId) {
    $personalDetails = getPersonalDetails($connection, $userId);

    switch ($serviceType) {
        case 'membership':
            $stmt = $connection->prepare("SELECT plan_name, price FROM membership_plans WHERE id = ?");
            break;
        case 'program':
            $stmt = $connection->prepare("SELECT program_name, price FROM programs p 
                                    JOIN coach_program_types cpt ON p.id = cpt.program_id 
                                    WHERE p.id = ?");
            break;
        case 'rental':
            $stmt = $connection->prepare("SELECT service_name, price FROM rental_services WHERE id = ?");
            break;
        default:
            return null;
    }

    $stmt->execute([$serviceId]);
    $serviceDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    return [
        'user_name' => $personalDetails['first_name'] . ' ' . $personalDetails['last_name'],
        'service_name' => $serviceDetails['plan_name'] ?? $serviceDetails['program_name'] ?? $serviceDetails['service_name'],
        'price' => $serviceDetails['price']
    ];
}

function getPersonalDetails($connection, $userId) {
    $stmt = $connection->prepare("SELECT first_name, last_name FROM personal_details WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getNotifications($connection, $staffId) {
    $stmt = $connection->prepare("
        SELECT 
            t.id as transaction_id, 
            pd.first_name, 
            pd.last_name, 
            t.created_at,
            CASE 
                WHEN m.id IS NOT NULL THEN 'Membership'
                WHEN ps.id IS NOT NULL THEN 'Program'
                WHEN rs.id IS NOT NULL THEN 'Rental'
                ELSE 'Unknown'
            END as service_type,
            COALESCE(
                mp.plan_name, 
                p.program_name, 
                rs.service_name  -- Update this to match your actual column name
            ) as service_name,
            COALESCE(
                mp.price, 
                cpt.price, 
                rs.price
            ) as service_price
        FROM transactions t
        JOIN personal_details pd ON t.user_id = pd.user_id
        LEFT JOIN memberships m ON m.transaction_id = t.id
        LEFT JOIN membership_plans mp ON m.membership_plan_id = mp.id
        LEFT JOIN program_subscriptions ps ON ps.transaction_id = t.id
        LEFT JOIN programs p ON ps.program_id = p.id
        LEFT JOIN coach_program_types cpt ON p.id = cpt.program_id
        LEFT JOIN rental_subscriptions rs ON rs.transaction_id = t.id
        WHERE t.staff_id = ?
        ORDER BY t.created_at DESC
    ");
    
    try {
        $stmt->execute([$staffId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log the error
        error_log("Notification Fetch Error: " . $e->getMessage());
        // You might want to handle this more gracefully in a production environment
        return [];
    }
}

// notifications.php
session_start();
require_once '../../../config.php';

$database = new Database();
$connection = $database->connect();

// Assume staff_id is stored in session after login
$staffId = $_SESSION['user_id'];

$notifications = getNotifications($connection, $staffId);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="nav-title">Notifications</h1>
        <div class="header">
            <div class="actions">
                <input type="text" class="search-input" placeholder="Search notifications..." />
                <button class="search-btn">Search</button>
                <select class="filter-select">
                    <option value="">All Types</option>
                    <option value="membership">Membership</option>
                    <option value="program">Program</option>
                    <option value="rental">Rental</option>
                </select>
            </div>
        </div>
        <div class="container mt-5">
            <div class="row">
                <div class="col">
                    <table class="table table-hover table-striped">
                        <tbody>
                            <?php foreach ($notifications as $notification): ?>
                            <tr data-bs-toggle="modal" data-bs-target="#notificationModal" 
                                onclick="populateModal(
                                    '<?= $notification['service_type'] ?>',
                                    '<?= $notification['first_name'] . ' ' . $notification['last_name'] ?>',
                                    '<?= $notification['service_name'] ?>',
                                    '<?= date('F d, Y', strtotime($notification['created_at'])) ?>',
                                    '<?= $notification['service_price'] ? 'P ' . number_format($notification['service_price'], 2) : '' ?>'
                                )">
                                <td class="service_name"><?= $notification['service_type'] ?></td>
                                <td class="request_details">
                                    <?= $notification['first_name'] . ' ' . $notification['last_name'] ?> 
                                    has requested <?= $notification['service_name'] ?> 
                                    on <?= date('F d, Y', strtotime($notification['created_at'])) ?>
                                </td>
                                <td class="date"><?= date('F d, Y', strtotime($notification['created_at'])) ?></td>
                                <td class="service_details">
                                    <?= $notification['service_price'] ? 'P ' . number_format($notification['service_price'], 2) : '' ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Notification Modal -->
    <div class="modal fade" id="notificationModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="d-flex align-items-center">
                            <div id="modalProfileIcon" class="profile-icon me-3"></div>
                            <div>
                                <h5 id="modalSenderName" class="modal-title"></h5>
                                <p id="modalSenderType" class="text-muted mb-0"></p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p id="modalMessage" class="mb-4"></p>
                        <div class="row">
                            <div class="col-6">
                                <strong>Schedule Date:</strong>
                                <p id="modalDate"></p>
                            </div>
                            <div class="col-6">
                                <strong>Total Payment:</strong>
                                <p id="modalPayment"></p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Reject</button>
                        <button type="button" class="btn btn-success" data-bs-dismiss="modal">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        function populateModal(type, sender, message, scheduleDate, totalPayment) {
            document.getElementById('modalProfileIcon').textContent = sender.charAt(0).toUpperCase();
            document.getElementById('modalSenderName').textContent = sender;
            document.getElementById('modalSenderType').textContent = type;
            document.getElementById('modalMessage').textContent = `${sender} has requested ${message}.`;
            document.getElementById('modalDate').textContent = scheduleDate;
            document.getElementById('modalPayment').textContent = totalPayment || 'N/A';
        }
    </script>
</body>
</html>