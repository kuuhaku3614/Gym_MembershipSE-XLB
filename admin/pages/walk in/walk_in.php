<div class="container mt-4">
        <h1 class="nav-title">Walk-in</h1>

        <div class="search-section">
            <div class="row align-items-center">
                <div class="col-md-6 ">
                    <div class="search-controls">
                        <button class="btn btn-primary" id="addButton">Add</button><!-- Button to trigger modal -->
                    </div>
                </div>
                <div class="col-md-6 d-flex justify-content-end">
                          <button class="btn btn-secondary" type="button" id="refreshBtn">Refresh</button>
                          <select class="form-select ms-2" style="width: 100px;">
                            <option>--Select--</option>
                          </select>
                </div>
            </div>
        </div>

        <div class="table-responsive">
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
                    </td>
                    <td><span class="status-pending">Pending</span></td>
                    <td>
                        <button class="btn btn-primary btn-sm">Confirm</button>
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
                    </td>
                    <td><span class="status-pending">Pending</span></td>
                    <td>
                        <button class="btn btn-primary btn-sm">Confirm</button>
                        <button class="btn btn-danger btn-sm">Remove</button>
                    </td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>Kim Dy</td>
                    <td>17/10/24</td>
                    <td>10am</td>
                    <td><span class="payment-paid">Paid</span></td>
                    <td><span class="status-walked-in">Confirmed</span></td>
                    <td>
                        <button class="btn btn-danger btn-sm">Remove</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
  </div>

<!-- Add Walk-in Modal -->
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
            <input type="text" class="form-control" id="firstName" placeholder="First name" required>
          </div>
          <div class="mb-3">
            <label for="lastName" class="form-label">Last Name</label>
            <input type="text" class="form-control" id="lastName" placeholder="Last name" required>
          </div>
          <div class="mb-3">
            <label for="middleName" class="form-label">Middle Name (optional)</label>
            <input type="text" class="form-control" id="middleName" placeholder="Middle name">
          </div>
          <div class="mb-3">
            <label for="phoneNo" class="form-label">Phone No.</label>
            <input type="text" class="form-control" id="phoneNo" placeholder="Phone no." required>
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

<!-- Identity Confirmation Modal -->
<div class="modal fade" id="confirmIdentityModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-danger fw-bold">Confirm Identity</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img src="https://via.placeholder.com/100" alt="User Icon" class="mb-3" style="width: 100px; height: 100px;">
        <div class="mb-2">
          <p class="mb-0"></p> <!-- Customer name will be inserted here -->
        </div>
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
$(document).ready(function () {
    // Initialize DataTable only once and store the reference
    let dataTable;
    
    // Check if DataTable is already initialized
    if (!$.fn.DataTable.isDataTable('#walkInTable')) {
        dataTable = $("#walkInTable").DataTable({
            pageLength: 10,
            ordering: false,
            responsive: true,
            dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>rtip',
        });
    } else {
        dataTable = $('#walkInTable').DataTable();
    }

    // Add button action
    $('#addButton').on('click', function() {
        $('#addWalkinForm')[0].reset();
        $('#addWalkinModal').modal('show');
    });

    // Save button action in Add Modal
    $('#addWalkinModal .btn-primary').on('click', function() {
        const firstName = $('#firstName').val().trim();
        const lastName = $('#lastName').val().trim();
        const middleName = $('#middleName').val().trim();
        const phoneNo = $('#phoneNo').val().trim();

        if (!firstName || !lastName || !phoneNo) {
            alert('Please fill in all required fields (First Name, Last Name, and Phone No.)');
            return;
        }

        const today = new Date();
        const date = String(today.getDate()).padStart(2, '0') + '/' +
                     String(today.getMonth() + 1).padStart(2, '0') + '/' +
                     String(today.getFullYear()).slice(-2);

        const fullName = middleName ? 
            `${firstName} ${middleName} ${lastName}` : 
            `${firstName} ${lastName}`;

        // Use the stored DataTable reference
        dataTable.row.add([
            '', 
            fullName,
            date,
            '',
            '<span class="payment-unpaid">Unpaid</span>',
            '<span class="status-pending">Pending</span>',
            '<button class="btn btn-primary btn-sm">Confirm</button> ' +
            '<button class="btn btn-danger btn-sm">Remove</button>'
        ]).draw();

        $('#addWalkinModal').modal('hide');
    });

    // Refresh button action
    $("#refreshBtn").click(function () {
        location.reload();
    });

    // Confirm button action with identity verification
    $('#walkInTable').on('click', 'button.btn-primary:contains("Confirm")', function() {
        const row = $(this).closest("tr");
        const customerName = row.find("td:eq(1)").text();
        
        $('#confirmIdentityModal .modal-body').find('p:first').html(`<strong>Customer Name:</strong> ${customerName}`);
        $('#confirmIdentityModal').modal('show');
        
        $('#confirmButton').off('click').on('click', function() {
            const password = $('#identityPassword').val();
            
            if (!password) {
                alert("Please enter a password!");
                return;
            }
            
            $('#confirmIdentityModal').modal('hide');
            $('#identityPassword').val('');
            
            row.find("td:eq(4)")
               .html('<span class="payment-paid">Paid</span>');
            
            row.find("td:eq(5)")
               .html('<span class="status-walked-in">Confirmed</span>');
            
            const now = new Date();
            const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            row.find("td:eq(3)").text(timeStr);
            
            row.find("td:eq(6)").html(
                '<button class="btn btn-danger btn-sm">Remove</button>'
            );
        });
    });

    // Remove button action using event delegation
    $('#walkInTable').on('click', 'button.btn-danger', function() {
        dataTable.row($(this).closest("tr")).remove().draw();
    });
});
</script>

            
            