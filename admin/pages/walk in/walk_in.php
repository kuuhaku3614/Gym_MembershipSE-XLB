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
        }
        exit;
    }
  }

  $array = $Obj->fetchWalkin();
  $current_price = $Obj->getWalkInPrice();
?>


<div class="container mt-4">
    <h1 class="nav-title">Walk-in</h1>  
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addWalkInModal">
                Add Walk-in
            </button>
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#updatePriceModal">
                Update Walk-in Price
            </button>
        </div>
    </div>

    <div class="table-responsive">
          <table class="table table-bordered table-hover">
              <thead class="table-dark">
                  <tr class="text-center">
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
                        <td><?php echo ($row['is_paid'] == 1) ? 'Paid' : 'Unpaid'; ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick="editWalkIn(<?php echo $row['id']; ?>)">Edit</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteWalkIn(<?php echo $row['id']; ?>)">Delete</button>
                        </td>
                    </tr>
                <?php 
                    }
                } else {
                ?>
                    <tr>
                        <td colspan="8" class="text-center">No records found</td>
                    </tr>
                <?php } ?>
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
                    <div class="alert alert-info mb-4">
                        <h6 class="alert-heading">Current Walk-in Rate</h6>
                        <h4 class="mb-0">₱<?php echo number_format($current_price, 2); ?></h4>
                    </div>
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        <!-- Right Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone_number" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="phone_number" name="phone_number" required>
                            </div>
                        </div>
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
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="savePriceBtn">Save changes</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle form submission
    $('#addWalkInForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '/Gym_MembershipSE-XLB/admin/pages/walk in/walk_in.php',
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

    // Handle price update
    $('#savePriceBtn').click(function() {
        var price = $('#newPrice').val();
        
        if (!price || price <= 0) {
            alert('Please enter a valid price');
            return;
        }

        $.ajax({
            url: '/Gym_MembershipSE-XLB/admin/pages/walk in/walk_in.php',
            type: 'POST',
            data: $('#updatePriceForm').serialize(),
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
</script>