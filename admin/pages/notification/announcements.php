<?php
require_once '../../../config.php';

$sql = "
SELECT id, applied_date, applied_time, message, announcement_type 
FROM announcements 
WHERE is_active = 1 
ORDER BY applied_date DESC, applied_time DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<style>
:root {
    /* Refined Color Palette */
    --primary-color: #c92f2f;
    --secondary-color: #f8f9fa;
    --text-primary: #2c3e50;
    --text-secondary: #6c757d;
    --border-color: #dee2e6;
    --background-light: #ffffff;
    --background-muted: #f8f9fa;
    --gradient-primary: linear-gradient(135deg, var(--primary-color), #a01e1e);
    
    /* Enhanced Typography */
    --font-family: 'Inter', 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    --font-size-base: 0.95rem;
    --font-size-small: 0.85rem;
    --font-weight-light: 300;
    --font-weight-normal: 400;
    --font-weight-semibold: 600;
    --font-weight-bold: 700;
    
    /* Refined Shadows and Transitions */
    --box-shadow-light: 0 4px 15px rgba(0, 0, 0, 0.08);
    --box-shadow-medium: 0 6px 20px rgba(0, 0, 0, 0.12);
    --transition-smooth: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

/* Global Reset and Base Styles */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: var(--font-family);
    color: var(--text-primary);
    background-color: var(--background-muted);
    line-height: 1.6;
}

/* Announcements Container */
.announcements-container {
    background-color: var(--background-light);
    border-radius: 16px;
    box-shadow: var(--box-shadow-light);
    padding: 2.5rem;
    margin-top: 2rem;
}

/* Card Styling */
.card {
    border: none;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: var(--box-shadow-medium);
    transition: var(--transition-smooth);
}

.card-header {
    background: var(--gradient-primary);
    color: white;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.card-header h5 {
    margin: 0;
    font-weight: var(--font-weight-bold);
    font-size: 1.3rem;
    letter-spacing: -0.5px;
}

.card-body {
    background-color: var(--background-light);
    padding: 2rem;
}

/* Form Input Styling */
#messageInput,
#dateInput,
#time,
#type {
    font-family: var(--font-family);
    font-size: var(--font-size-base);
    border: 2px solid var(--border-color);
    border-radius: 12px;
    padding: 0.875rem 1.25rem;
    transition: var(--transition-smooth);
    background-color: var(--background-muted);
    color: var(--text-primary);
}

#messageInput:focus,
#dateInput:focus,
#time:focus,
#type:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 4px rgba(201, 47, 47, 0.1);
    outline: none;
}

#messageInput {
    resize: vertical;
    min-height: 150px;
}

/* Button Styling */
.btn-danger {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    border-radius: 10px;
    font-weight: var(--font-weight-semibold);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1.75rem;
    transition: var(--transition-smooth);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-danger:hover {
    background-color: #a01e1e;
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.25);
}

/* Table Styling */
#announcementsTable {
    border-radius: 16px;
    overflow: hidden;
    box-shadow: var(--box-shadow-light);
    background-color: var(--background-light);
}

#announcementsTable thead {
    background-color: var(--background-muted);
}

#announcementsTable th {
    color: var(--text-secondary);
    font-weight: var(--font-weight-bold);
    text-transform: uppercase;
    font-size: var(--font-size-small);
    padding: 1.25rem;
    letter-spacing: 0.5px;
}

#announcementsTable td {
    vertical-align: middle;
    padding: 1.25rem;
    border-bottom: 1px solid var(--border-color);
}

.remove-btn {
    background-color: #fff0f0;
    color: var(--primary-color);
    border: none;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: var(--transition-smooth);
    font-size: var(--font-size-small);
}

.remove-btn:hover {
    background-color: #ffe0e0;
    transform: scale(1.05);
    color: #a01e1e;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .announcements-container {
        padding: 1.5rem;
        border-radius: 0;
    }

    .card-body {
        padding: 1.5rem;
    }

    .btn-danger {
        width: 100%;
        justify-content: center;
    }

    #announcementsTable td, 
    #announcementsTable th {
        padding: 1rem;
    }
}

/* DataTables Responsive Styling */
.dataTables_wrapper {
    margin-top: 2rem;
}

.dataTables_filter input,
.dataTables_length select {
    border-radius: 10px;
    border: 2px solid var(--border-color);
    padding: 0.625rem 1.25rem;
    font-size: var(--font-size-base);
}

.dataTables_paginate .paginate_button {
    border-radius: 8px;
    margin: 0 0.25rem;
    padding: 0.625rem 1.25rem;
    background-color: var(--background-muted);
    color: var(--text-secondary);
    border: none;
    transition: var(--transition-smooth);
    font-size: var(--font-size-small);
}

.dataTables_paginate .paginate_button.current,
.dataTables_paginate .paginate_button:hover {
    background-color: var(--primary-color);
    color: white !important;
}
</style>
<div class="container-fluid px-4 py-3">
    <!-- Announcement Form Section -->
    <form id="announcementForm">
    <input type="hidden" name="action" value="add" />
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header text-white bg-primary">
                    <h5 class="card-title mb-0">Make Announcements</h5>
                </div>
                <div class="card-body">
                    <form id="announcementForm">
                        <div class="mb-3">
                            <textarea class="form-control" id="messageInput" rows="4" 
                                placeholder="Enter your announcement message here..." required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="dateInput" class="form-label">Date</label>
                                <input type="date" class="form-control" id="dateInput" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="time" class="form-label">Time</label>
                                <input type="time" class="form-control" id="time" name="time" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="type" class="form-label">Announcement Type</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="">Select Announcement Type</option>
                                    <option value="administrative">Administrative</option>
                                    <option value="activity">Activity</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn bg-primary text-white">
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
    <div class="mt-4">
        <button type="button" class="btn btn-secondary" id="showPreviousBtn">
            <i class="fas fa-history me-2"></i>View Previous Announcements
        </button>
    </div>
    <table id="announcementsTable" class="table table-striped table-hover">
    <thead>
        <tr>
            <th>Date</th>
            <th>Time</th>
            <th>Type</th>
            <th>Message</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($announcements as $announcement): ?>
            <tr>
                <td><?= date('F d, Y', strtotime($announcement['applied_date'])) ?></td>
                <td><?= date('h:i A', strtotime($announcement['applied_time'])) ?></td>
                <td><?= ucfirst(htmlspecialchars($announcement['announcement_type'])) ?></td>
                <td><?= htmlspecialchars($announcement['message']) ?></td>
                <td>
                    <button class='btn btn-danger btn-sm remove-btn' data-id='<?= $announcement["id"] ?>'>
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
    // Initialize DataTable
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

    // Add Announcement Form Submission
    $('#announcementForm').on('submit', function(e) {
        e.preventDefault();

        const message = $('#messageInput').val().trim();
        const date = $('#dateInput').val();
        const time = $('#time').val();
        const type = $('#type').val();

        // Client-side validation
        if (!message || !date || !time || !type) {
            alert('Please fill in all fields');
            return;
        }

        $.ajax({
            url: 'pages/notification/functions/insert_announcement.php',
            method: 'POST',
            data: {
                message: message,
                date: date,
                time: time,
                type: type
            },
            success: function(response) {
                try {
                    const res = JSON.parse(response);
                    if (res.status === 'success') {
                        alert(res.message);
                        location.reload(); // Refresh page to show new announcement
                    } else {
                        alert(res.message);
                    }
                } catch (e) {
                    console.error('Error parsing response:', response);
                    alert('Unexpected error occurred');
                }
            },
            error: function() {
                alert('Error adding announcement. Please try again.');
            }
        });
    });

    // Remove Announcement Button Handler
    $('#announcementsTable').on('click', '.remove-btn', function() {
    const id = $(this).data('id');
    
    // Add validation to ensure ID exists and is a number
    if (!id || isNaN(id)) {
        alert('Invalid announcement ID');
        return;
    }

    if (confirm('Are you sure you want to remove this announcement?')) {
        $.ajax({
            url: 'pages/notification/functions/remove_announcement.php',
            method: 'POST',
            data: { id: id },
            dataType: 'json', // Explicitly expect JSON response
            success: function(response) {
                if (response.status === 'success') {
                    // Remove the row from DataTable instead of reloading
                    const row = $(this).closest('tr');
                    table.row(row).remove().draw();
                    alert(response.message);
                } else {
                    alert(response.message || 'Error removing announcement');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    response: xhr.responseText,
                    status: status,
                    error: error
                });
                alert('Error removing announcement. Please try again.');
            }
        });
    }
});
});
</script>