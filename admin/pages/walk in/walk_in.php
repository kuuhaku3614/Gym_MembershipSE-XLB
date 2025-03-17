<?php
  require_once("functions/walk_in.class.php");

  $Obj = new Walk_in_class();

  // Handle AJAX request for adding walk-in
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_walkin') {
            $name = $_POST['name'] ?? '';
            $phone_number = $_POST['phone_number'] ?? '';

            if (empty($name) || empty($phone_number)) {
                echo json_encode(['status' => 'error', 'message' => 'Name and phone number are required']);
                exit;
            }

            $result = $Obj->addWalkInRecord($name, $phone_number);

            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Walk-in record added successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to add walk-in record']);
            }
        } elseif ($_POST['action'] === 'update_price') {
            $price = $_POST['price'] ?? '';

            if (!is_numeric($price) || $price <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Please enter a valid price']);
                exit;
            }

            $result = $Obj->updateWalkInPrice($price);

            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Walk-in price updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update walk-in price']);
            }
        } elseif ($_POST['action'] === 'process_walkin') {
            $id = $_POST['id'] ?? '';

            if (empty($id)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid walk-in ID']);
                exit;
            }

            $result = $Obj->processWalkInRecord($id);

            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Walk-in processed successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to process walk-in']);
            }
        } elseif ($_POST['action'] === 'remove_walkin') {
            $id = $_POST['id'] ?? '';

            if (empty($id)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid walk-in ID']);
                exit;
            }

            $result = $Obj->removeWalkInRecord($id);

            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Walk-in record removed successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to remove walk-in record']);
            }
        }
        exit;
    }
  }

  $array = $Obj->fetchWalkin();
  $current_price = $Obj->getWalkInPrice();
?>


<div class="container mt-4">

<div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Walk-In</h2>
        <div class="mt-2">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addWalkInModal">
            Add Walk-in
        </button>
        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#updatePriceModal">
            Update Walk-in Price
        </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
          <table id="walk_inTable" class="table table-striped table-bordered">
              <thead>
                  <tr>
                      <th>No.</th>
                      <th>Name</th>
                      <th>Phone Number</th>
                      <th>Date</th>
                      <th>Time in</th>
                      <th>Payment Status</th>
                      <th>Status</th>
                      <th>Action</th>
                  </tr>
              </thead>
              <tbody>
              <?php 
                if(!empty($array)){
                    $count = 1;
                    foreach($array as $row){
                ?>
                    <tr class="text-center">
                        <td><?php echo $count++; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                        <td><?php 
                            $date = new DateTime($row['date']);
                            echo $date->format('F j, Y'); // Format: Month Day, Year
                        ?></td>
                        <td><?php 
                            if ($row['time_in']) {
                                $time = new DateTime($row['time_in']);
                                echo $time->format('g:i A'); // Format: Hour:Minute AM/PM
                            } else {
                                echo "N/A";
                            }
                        ?></td>
                        <td class="text-center">
                            <?php if ($row['is_paid'] == 1): ?>
                                <span class="badge bg-success">Paid</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Unpaid</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php 
                                $status = strtolower($row['status']);
                                $statusClass = match($status) {
                                    'pending' => 'bg-warning text-dark',
                                    'walked-in' => 'bg-success',
                                    'cancelled' => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                                echo "<span class='badge {$statusClass}'>" . ucfirst($row['status']) . "</span>";
                            ?>
                        </td>
                        <td>
                            <?php if (strtolower($row['status']) === 'pending'): ?>
                                <button class="btn btn-success btn-sm" onclick="processWalkIn(<?php echo $row['id']; ?>)">
                                    Walk-in
                                </button>
                                <button class="btn btn-danger btn-sm ms-1" onclick="removeWalkIn(<?php echo $row['id']; ?>)">
                                    Remove
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php
                    } // Closing foreach
                }
                ?>
              </tbody>
          </table>
        </div>
</div>

<!-- Add Walk-in Modal -->
<div class="modal fade" id="addWalkInModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="addWalkInModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addWalkInModalLabel">Add New Walk-in</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addWalkInForm">
                <div class="modal-body">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="name">Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        <!-- Right Column -->
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="phone_number">Phone Number</label>
                                <input type="tel" class="form-control" id="phone_number" name="phone_number" required>
                            </div>
                        </div>
                    </div>
                    <div class="amount-display">
                        <span class="amount-label">Amount:</span>
                        <span class="amount-value">₱<?php echo number_format($current_price, 2); ?></span>
                    </div>
                    <input type="hidden" name="action" value="add_walkin">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Price Modal -->
<div class="modal fade" id="updatePriceModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="updatePriceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="updatePriceModalLabel">Update Walk-in Price</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-4">
                    <h6 class="alert-heading">Current Walk-in Rate</h6>
                    <h4 class="mb-0">₱<?php echo number_format($current_price, 2); ?></h4>
                </div>
                    <form id="updatePriceForm">
                        <div class="mb-3">
                            <label for="newPrice" class="form-label">New Walk-in Price</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" class="form-control" id="newPrice" name="price" required min="0">
                            </div>
                        </div>
                        <input type="hidden" name="action" value="update_price">
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
            </div>
            <div class="modal-footer">
                
            </div>
        </div>
    </div>
</div>

<style>
    .amount-display {
        border: 1px solid lightgrey;
        color: red;
        border-radius: 4px;
        padding: 8px 15px;
        margin: 10px 0;
        display: inline-block;
        width: 100%;
        text-align: right;
    }

    .amount-label {
        font-size: 0.9rem;
        margin-right: 8px;
    }

    .amount-value {
        font-size: 1.1rem;
        font-weight: 600;
    }

    .badge {
        font-weight: 500;
        padding: 6px 12px;
        border-radius: 4px;
    }

    .table td {
        vertical-align: middle;
    }

    /* Modal styling updates */
    .modal-content {
        border: none;
        border-radius: 10px;
    }

    .modal-header {
        background-color: var(--danger-color);
        color: white;
        border-radius: 10px 10px 0 0;
        padding: 15px 20px;
    }

    .modal-title {
        font-weight: 600;
    }

    .form-group label {
        color: var(--gray-700);
        font-weight: 500;
        margin-bottom: 8px;
    }

    .form-control {
        border-radius: 4px;
        border: 1px solid var(--gray-300);
    }

    .form-control:focus {
        border-color: var(--danger-color);
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
</style>

<script>
$(document).ready(function() {
    // Handle form submission
    $('#addWalkInForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '../admin/pages/walk in/walk_in.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Show success message
                    alert(response.message);
                    // Close modal
                    $('#addWalkInModal').modal('hide');
                    // Reload page to show new record
                    location.reload();
                } else {
                    // Show error message
                    alert(response.message);
                }
            },
            error: function() {
                alert('An error occurred while processing your request');
            }
        });
    });
    // Initialize DataTable
    $('#walk_inTable').DataTable({
        responsive: true,
        order: [[3, 'desc']], // Sort by check-in time by default
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip', // Custom layout
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search members...",
            emptyTable: "No records found"
        },
        columnDefs: [
            { orderable: false, targets: [0, 7] } // Disable sorting for number and action columns
        ],
        drawCallback: function() {
            // Check if there are no records and modify the table structure if needed
            if ($('#walk_inTable tbody tr.no-records-row').length > 0) {
                // Remove the action column header if showing "no records" row
                if ($('#walk_inTable thead th').length === 8) {
                    $('#walk_inTable thead th:last-child').hide();
                }
            } else {
                // Make sure the action column header is visible when records exist
                $('#walk_inTable thead th:last-child').show();
            }
        }
    });

        // Handle price update form submission
    $('#updatePriceForm').on('submit', function(e) {
        e.preventDefault();
        
        var price = $('#newPrice').val();
        
        if (!price || price <= 0) {
            alert('Please enter a valid price');
            return;
        }

        $.ajax({
            url: '../admin/pages/walk in/walk_in.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert(response.message);
                    $('#updatePriceModal').modal('hide');
                    location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('An error occurred while processing your request');
            }
        });
    });
});

function processWalkIn(id) {
    if (!confirm('Process this walk-in record?')) {
        return;
    }

    $.ajax({
        url: '../admin/pages/walk in/walk_in.php',
        type: 'POST',
        data: {
            action: 'process_walkin',
            id: id
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                alert('Walk-in processed successfully!');
                location.reload();
            } else {
                alert(response.message || 'Failed to process walk-in');
            }
        },
        error: function() {
            alert('An error occurred while processing the request');
        }
    });
}

function removeWalkIn(id) {
    if (!confirm('Are you sure you want to remove this walk-in record?')) {
        return;
    }

    $.ajax({
        url: '../admin/pages/walk in/walk_in.php',
        type: 'POST',
        data: {
            action: 'remove_walkin',
            id: id
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                alert('Walk-in record removed successfully!');
                location.reload();
            } else {
                alert(response.message || 'Failed to remove walk-in record');
            }
        },
        error: function() {
            alert('An error occurred while processing the request');
        }
    });
}
</script>