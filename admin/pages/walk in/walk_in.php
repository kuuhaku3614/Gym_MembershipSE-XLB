<?php
  require_once("functions/walk_in.class.php");

  $Obj = new Walk_in_class();

  // Handle AJAX request
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_walkin') {
    header('Content-Type: application/json');
    
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
    exit;
  }

  $array = $Obj->fetchWalkin();
?>


<div class="container mt-4">
    <h1 class="nav-title">Walk-in</h1>  
    <div class="d-flex justify-content-between align-items-center mb-3">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addWalkInModal">
            Add Walk-in
        </button>
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
<div class="modal fade" id="addWalkInModal" tabindex="-1" aria-labelledby="addWalkInModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addWalkInModalLabel">Add New Walk-in</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addWalkInForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone_number" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone_number" name="phone_number" required>
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
});
</script>