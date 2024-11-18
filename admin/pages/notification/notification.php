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

<div class="header">
    <h1>Notifications</h1>
    <div class="actions">
        <input type="text" class="search-input" placeholder="Search notifications..." />
        <button class="search-btn">Search</button>
        <select class="filter-select">
            <option value="">All Types</option>
            <option value="walk-in">Walk-in</option>
            <option value="membership">Membership</option>
            <option value="renewal">Renewal</option>
        </select>
        <button class="refresh-btn">Refresh</button>
    </div>
</div>
    <div class="container mt-5">
        <div class="row">
            <div class="col">
                <table class="table table-hover table-striped">

                    <tbody>
                        <tr data-bs-toggle="modal" data-bs-target="#notificationModal" onclick="populateModal('Walk-in', 'Alfaith Luzon', 'Walk-in on October 12, 2024', 'October 12, 2024', 'P 70.00')">
                            <td>Walk-in</td>
                            <td>Alfaith Luzon has requested to Walk-in on October 12, 2024</td>
                            <td>October 12, 2024</td>
                            <td>P 70.00</td>
                        </tr>
                        <tr data-bs-toggle="modal" data-bs-target="#notificationModal" onclick="populateModal('Membership', 'Gerby Hallasgo', 'Student Promo membership', 'October 11, 2024', '')">
                            <td>Membership</td>
                            <td>Gerby Hallasgo wants to avail 'Student Promo' membership</td>
                            <td>October 11, 2024</td>
                            <td>P 400.00</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
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