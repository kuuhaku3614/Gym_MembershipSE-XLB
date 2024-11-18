<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/functions/config.php';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $response = ["status" => "error", "message" => "An error occurred."];

    if ($_POST['action'] === 'add') {
        $message = $conn->real_escape_string($_POST['message'] ?? '');
        $date = $conn->real_escape_string($_POST['date'] ?? '');

        if (!$message || !$date) {
            $response['message'] = "Message and date are required.";
        } else {
            $sql = "INSERT INTO announcements (message, applied_date) VALUES ('$message', '$date')";
            if ($conn->query($sql)) {
                $response = ["status" => "success", "message" => "Announcement added successfully!"];
            } else {
                $response['message'] = "Database error: " . $conn->error;
            }
        }
    } elseif ($_POST['action'] === 'remove') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $response['message'] = "Invalid announcement ID.";
        } else {
            $sql = "DELETE FROM announcements WHERE id = $id";
            if ($conn->query($sql)) {
                $response = ["status" => "success", "message" => "Announcement removed successfully!"];
            } else {
                $response['message'] = "Database error: " . $conn->error;
            }
        }
    }

    echo json_encode($response);
    exit();
}

$sql = "
SELECT id, applied_date, message FROM announcements ORDER BY id DESC;
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<style>
.card {
    border: none;
    border-radius: 0.5rem;
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,.125);
    padding: 1rem;
}

#messageInput {
    resize: none;
    border-color: #dee2e6;
}

#messageInput:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25);
}

.table thead th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    color: #495057;
}

.remove-btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

/* Custom DataTables styling */
.dataTables_wrapper .dataTables_length select {
    padding: 0.375rem 2.25rem 0.375rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 0.25rem;
    border: 1px solid #dee2e6;
}

.dataTables_wrapper .dataTables_filter input {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 0.25rem;
    border: 1px solid #dee2e6;
    margin-left: 0.5rem;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 0.375rem 0.75rem;
    margin: 0 0.25rem;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #c92f2f;
    color: white !important;
    border-color: #c92f2f;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: #0d6efd;
    color: white !important;
    border-color: #0d6efd;
}
</style>
<div class="container-fluid px-4 py-3">
    <!-- Announcement Form Section -->
    <form id="announcementForm">
    <input type="hidden" name="action" value="add" />
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">Make Announcements</h5>
                </div>
                <div class="card-body">
                    <form id="announcementForm">
                        <div class="mb-3">
                            <textarea class="form-control" id="messageInput" rows="4" 
                                placeholder="Enter your announcement message here..." required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="dateInput" class="form-label">Date </label>
                                <input type="date" class="form-control" id="dateInput" required>
                            </div>
                            <div class="col-md-6 d-flex align-items-end justify-content-end">
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-paper-plane me-2"></i>Send Announcement
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </form>
    <!-- Manage Announcements Section -->
    <div class="table-responsive">
    <table id="announcementsTable" class="table table-striped table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Message</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($announcements as $announcement): ?>
                <tr>
                    <td><?= htmlspecialchars($announcement['id']) ?></td>
                    <td><?= date('F d, Y', strtotime($announcement['applied_date'])) ?></td>
                    <td><?= htmlspecialchars($announcement['message']) ?></td>
                    <td>
                        <button class="btn btn-danger btn-sm remove-btn" data-id="<?= htmlspecialchars($announcement['id']) ?>">
                            <i class="fas fa-trash-alt me-1"></i>Remove
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable without AJAX
    const table = $('#announcementsTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 5,
        lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
        responsive: true,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search announcements...",
        }
    });

    // Insert announcement with form validation
    $('#announcementForm').on('submit', function(e) {
        e.preventDefault();

        const message = $('#messageInput').val().trim();
        const date = $('#dateInput').val();

        if (!message || !date) {
            alert('Please fill in all fields');
            return;
        }

        $.ajax({
            url: '../admin/pages/notification/functions/insert_announcement.php', // Corrected path
            method: 'POST',
            data: {
                action: 'add',
                message: message,
                date: date
            },
            success: function(response) {
                const res = JSON.parse(response);
                if (res.status === 'success') {
                    alert(res.message);
                    location.reload(); // Reload page to update table
                } else {
                    alert(res.message);
                }
            },
            error: function() {
                alert('Error adding announcement. Please try again.');
            }
        });
    });

    // Remove announcement
    $('#announcementsTable').on('click', '.remove-btn', function() {
        const id = $(this).data('id');

        if (confirm('Are you sure you want to remove this announcement?')) {
            $.ajax({
                url: '../admin/pages/notification/functions/remove_announcement.php', // Corrected path
                method: 'POST',
                data: {
                    id: id
                },
                success: function(response) {
                    try {
                        const res = JSON.parse(response);
                        if (res.status === 'success') {
                            alert(res.message);
                            location.reload(); // Reload page instead of using table.ajax.reload()
                        } else {
                            alert(res.message);
                        }
                    } catch (e) {
                        console.error('Error parsing response:', response);
                        alert('Error processing server response');
                    }
                },
                error: function() {
                    alert('Error removing announcement. Please try again.');
                }
            });
        }
    });

    // Refresh button handler
    $('#refreshBtn').on('click', function() {
        const button = $(this);
        button.html('<span class="spinner-border spinner-border-sm"></span>').prop('disabled', true);
        
        table.ajax.reload(function() {
            button.html('<i class="fas fa-sync-alt me-1"></i>Refresh').prop('disabled', false);
        });
    });
});
</script>