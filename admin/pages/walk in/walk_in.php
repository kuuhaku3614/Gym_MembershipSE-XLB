<div class="container mt-4">
        <h2 class="text-center text-danger mb-4">Walk-in</h2>
        
        <div class="d-flex justify-content-between">
            <button class="btn btn-primary" id="addButton">Add</button><!-- Button to trigger modal -->
            <div class="d-flex gap-2">
                <button class="btn btn-primary" id="refreshBtn">Refresh</button>
                <select class="form-select" style="width: 100px;">
                    <option>--Select--</option>
                </select>
            </div>
        </div>

        <table id="walkInTable" class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>No.</th>
                    <th>Name</th>
                    <th>Date</th>
                    <th>Time in</th>
                    <th>Payment Status</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>Alfaith Luzon</td>
                    <td>30/10/24</td>
                    <td></td>
                    <td>
                        <span class="payment-unpaid">Unpaid</span>
                        <button id="payWalkIn" class="btn btn-primary btn-pay btn-sm">Pay</button>
                    </td>
                    <td><span class="status-pending">Pending</span></td>
                    <td>
                        <button class="btn btn-primary btn-sm">Walk in</button>
                        <button class="btn btn-danger btn-sm">Remove</button>
                    </td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>Stephanie Bobon</td>
                    <td>30/10/24</td>
                    <td></td>
                    <td>
                        <span class="payment-unpaid">Unpaid</span>
                        <button id="payWalkIn" class="btn btn-primary btn-pay btn-sm">Pay</button>
                    </td>
                    <td><span class="status-pending">Pending</span></td>
                    <td>
                        <button class="btn btn-primary btn-sm">Walk in</button>
                        <button class="btn btn-danger btn-sm">Remove</button>
                    </td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>Kim Dy</td>
                    <td>17/10/24</td>
                    <td>10am</td>
                    <td><span class="payment-paid">Paid</span></td>
                    <td><span class="status-walked-in">Walked-in</span></td>
                    <td>
                        <button class="btn btn-danger btn-sm">Remove</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

<!-- Modal -->
<div class="modal fade" id="addWalkinModal" tabindex="-1" aria-labelledby="addWalkinModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addWalkinModalLabel">Add Walk-in</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="addWalkinForm">
          <div class="mb-3">
            <label for="firstName" class="form-label">First Name</label>
            <input type="text" class="form-control" id="firstName" placeholder="First name">
          </div>
          <div class="mb-3">
            <label for="lastName" class="form-label">Last Name</label>
            <input type="text" class="form-control" id="lastName" placeholder="Last name">
          </div>
          <div class="mb-3">
            <label for="middleName" class="form-label">Middle Name (optional)</label>
            <input type="text" class="form-control" id="middleName" placeholder="Middle name">
          </div>
          <div class="mb-3">
            <label for="phoneNo" class="form-label">Phone No.</label>
            <input type="text" class="form-control" id="phoneNo" placeholder="Phone no.">
          </div>
          <div class="mb-3">
            <label for="scheduleDate" class="form-label">Schedule Date</label>
            <input type="date" class="form-control" id="scheduleDate">
          </div>
          <div class="mb-3">
            <label for="membershipType" class="form-label">Membership Type/Promo</label>
            <input type="text" class="form-control" id="membershipType" value="Walk-in" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label">Payment Status</label><br>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="paymentStatus" id="paidStatus" value="Paid">
              <label class="form-check-label" for="paidStatus">Paid</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="paymentStatus" id="unpaidStatus" value="Unpaid" checked>
              <label class="form-check-label" for="unpaidStatus">Unpaid</label>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Total Payment</label>
            <input type="text" class="form-control" id="totalPayment" value="P 70.00" readonly>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary">Save</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="confirmIdentityModal" tabindex="-1" aria-labelledby="confirmIdentityModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-danger fw-bold" id="confirmIdentityModalLabel">Confirm Identity</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <!-- User Icon -->
        <img src="https://via.placeholder.com/100" alt="User Icon" class="mb-3" style="width: 100px; height: 100px;">
        <!-- User Information -->
        <div class="mb-2">
          <p class="mb-0"><strong>Full Name:</strong> Jamsk</p>
          <p class="mb-0"><strong>Age:</strong> 20</p>
        </div>
        <!-- Password Field -->
        <div class="mt-3">
          <input type="password" id="identityPassword" class="form-control text-center" placeholder="Password">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger w-100" id="confirmButton">Confirm</button>
      </div>
    </div>
  </div>
</div>


    <script>
        $(document).ready(function() {
            $('#walkInTable').DataTable({
                pageLength: 10,
                ordering: false,
                responsive: true,
                dom: '<"row"<l f>>rtip'
            });

            // Refresh button action
            $('#refreshBtn').click(function() {
                location.reload();
            });

            // Pay button action
            $('.btn-pay').click(function() {
                let row = $(this).closest('tr');
                row.find('.payment-unpaid').text('Paid').removeClass('payment-unpaid').addClass('payment-paid');
                $(this).remove();
            });

            // Walk in button action
            $('.btn-primary:contains("Walk in")').click(function() {
                let row = $(this).closest('tr');
                row.find('.status-pending').text('Walked-in').removeClass('status-pending').addClass('status-walked-in');
                $(this).remove();
            });

            // Remove button action
            $('.btn-danger').click(function() {
                $(this).closest('tr').remove();
            });
        });

        $(document).ready(function () {
            // Open modal when the add button is clicked
            $('#addButton').click(function () {
            $('#addWalkinModal').modal('show');
            });
        });

        $(document).ready(function () {
            // Open the modal when the pay button is clicked
            $('#payWalkIn').click(function () {
            $('#confirmIdentityModal').modal('show');
            });

            // Placeholder for the Confirm button click action
            $('#confirmButton').click(function () {
            const password = $('#identityPassword').val();
            if (password === "") {
                alert("Please enter a password!");
            } else {
                alert("Identity confirmed!");
                $('#confirmIdentityModal').modal('hide');
            }
            });
        });

    </script>
</body>
</html>

            
            