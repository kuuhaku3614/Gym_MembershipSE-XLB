<?php
  require_once("functions/walk_in.class.php");

  $Obj = new Walk_in_class();

  // Handle AJAX request for adding walk-in
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_walkin') {
            $first_name = $_POST['first_name'] ?? '';
            $middle_name = $_POST['middle_name'] ?? '';
            $last_name = $_POST['last_name'] ?? '';
            $phone_number = $_POST['phone_number'] ?? '';

            // Validate required fields
            if (empty($first_name) || empty($last_name) || empty($phone_number)) {
                echo json_encode(['status' => 'error', 'message' => 'First name, last name, and phone number are required']);
                exit;
            }

            // Add walk-in record with name fields directly
            $result = $Obj->addWalkInRecord($first_name, $middle_name, $last_name, $phone_number);

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
                      <th>Full Name</th>
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
                        <td><?php 
                            // Build full name from individual fields
                            $full_name = $row['first_name'] . 
                                       (!empty($row['middle_name']) ? ' ' . $row['middle_name'] : '') . 
                                       ' ' . $row['last_name'];
                            echo htmlspecialchars($full_name); 
                        ?></td>
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
                                    Mark
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
                            <!-- Personal Details Fields -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="first_name">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="middle_name">Middle Name</label>
                                    <input type="text" class="form-control" id="middle_name" name="middle_name">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="last_name">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
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

    <!-- Update Price Modal (Unchanged) -->
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
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#walk_inTable').DataTable({
            "responsive": true,
            "order": [[3, 'desc'], [4, 'desc']] // Order by date descending, then time
        });
        
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
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                            confirmButtonColor: '#3085d6',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Close modal after user acknowledges the success message
                            $('#addWalkInModal').modal('hide');
                            // Reload page to show new record
                            location.reload();
                        });
                        
                    } else {
                        // Show error message
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while processing your request'
                    });
                }
            });
        });

        // Handle update price form submission
        $('#updatePriceForm').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: '../admin/pages/walk in/walk_in.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                            confirmButtonColor: '#3085d6',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            $('#updatePriceModal').modal('hide');
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while processing your request'
                    });
                }
            });
        });
    });

    function processWalkIn(id) {
        Swal.fire({
            title: 'Process Walk-in',
            text: 'Are you sure you want to mark as paid this walk-in record?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes'
        }).then((result) => {
            if (result.isConfirmed) {
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
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'Failed to process walk-in'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while processing the request'
                        });
                    }
                });
            }
        });
    }

    function removeWalkIn(id) {
        Swal.fire({
            title: 'Remove Walk-in',
            text: 'Are you sure you want to remove this walk-in record?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, remove it!'
        }).then((result) => {
            if (result.isConfirmed) {
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
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'Failed to remove walk-in record'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while processing the request'
                        });
                    }
                });
            }
        });
    }
    </script>
</div>