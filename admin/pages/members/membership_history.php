<?php
require_once '../../../config.php';
$database = new Database();
$pdo = $database->connect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership History - Gym Management System</title>
    <style>
        /* Ensure table headers are visible */
        #historyTable thead th {
            color: #212529 !important;
            font-weight: 600 !important;
            border-bottom: 2px solid #dee2e6 !important;
        }

        .status-badge {
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
        }
        
        /* DataTables styling */
        .dataTables_wrapper .dataTables_scroll {
            margin-bottom: 0.5em;
        }
        
        .dataTables_wrapper .dataTables_scrollHead {
            overflow: hidden !important;
        }
        
        .dataTables_wrapper .dataTables_scrollBody {
            overflow-x: auto !important;
            overflow-y: auto !important;
        }
        
        /* Fix header alignment */
        .dataTables_scrollHeadInner {
            width: 100% !important;
        }
        
        .dataTables_scrollHeadInner table {
            width: 100% !important;
            margin: 0 !important;
        }
        
        /* Ensure consistent column widths */
        #historyTable {
            width: 100% !important;
        }
        
        #historyTable th {
            white-space: nowrap;
            padding: 8px 10px;
        }

    </style>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="main-content">
        <?php include '../../includes/header.php'; ?>

        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h2 class="card-title mb-0">Membership History</h2>
                            <a href="../admin/member_status" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Member Status
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="historyTable" class="table table-striped table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr class="bg-light">
                                            <th class="fw-bold text-dark">Member Name</th>
                                            <th class="fw-bold text-dark">Membership Plan</th>
                                            <th class="fw-bold text-dark">Start Date</th>
                                            <th class="fw-bold text-dark">End Date</th>
                                            <th class="fw-bold text-dark">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be loaded dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Membership Details Modal -->
    <div class="modal fade" id="membershipDetailsModal" tabindex="-1" aria-labelledby="membershipDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="membershipDetailsModalLabel">Membership Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Details will be loaded dynamically -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Load Scripts at the end -->
    <script src="../vendor/jQuery-3.7.1/jquery-3.7.1.min.js"></script>
    <script src="../vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/datatable-2.1.8/datatables.min.js"></script>
    <script src="../vendor/datatable-2.1.8/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            const historyTable = $('#historyTable').DataTable({
                ajax: {
                    url: '../admin/pages/members/functions/membership_history.class.php?action=getAll',
                    dataSrc: function(json) {
                        if (json.error) {
                            console.error('Error:', json.error);
                            return [];
                        }
                        return json;
                    }
                },
                columns: [
                    { data: 'member_name' },
                    { data: 'membership_type' },
                    { data: 'start_date' },
                    { data: 'end_date' },
                    {
                        data: 'membership_id',
                        render: function(data) {
                            return `<button class="btn btn-sm btn-info view-details" data-membership="${data}">
                                        <i class="fas fa-eye"></i> View Details
                                    </button>`;
                        }
                    }
                ],
                order: [[2, 'desc']], // Sort by start date by default
                pageLength: 10,
                responsive: true
            });

            // Handle view details button click
            $('#historyTable').on('click', '.view-details', function() {
                const membershipId = $(this).data('membership');
                // Load membership details via AJAX
                $.ajax({
                    url: '../admin/pages/members/functions/membership_history.class.php',
                    method: 'GET',
                    data: { 
                        action: 'getDetails',
                        membership_id: membershipId 
                    },
                    success: function(response) {
                        if (response.error) {
                            alert(response.error);
                            return;
                        }
                        // Populate modal with membership details
                        $('#membershipDetailsModal .modal-body').html(response.html);
                        $('#membershipDetailsModal').modal('show');
                    },
                    error: function(xhr) {
                        let errorMessage = 'Error loading membership details';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
                        }
                        alert(errorMessage);
                    }
                });
            });
        });
    </script>
</body>
</html>
