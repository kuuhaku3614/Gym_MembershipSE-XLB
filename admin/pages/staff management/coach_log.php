<body class="bg-light">

<div class="container-fluid py-4">
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Coach Activity Log</h2>
    <div class="d-flex align-items-center">
        <input type="text" id="dateFilter" class="form-control date-filter me-2" placeholder="DD/MM/YYYY" style="width: 40vh;">
        <button class="btn btn-danger me-2" type="button" id="filterBtn">Filter</button>
        <button class="btn btn-secondary" type="button" id="refreshBtn">Refresh</button>
    </div>
</div>

        <div class="table-responsive">
        <table id="coachLogTable" class="table table-striped table-bordered">
        <thead>
                    <tr>
                        <th>No.</th>
                        <th>Timestamp</th>
                        <th>Staff Name</th>
                        <th>Activity</th>
                    </tr>
                </thead>
                <tbody id="activityTableBody">
                    <tr>
                        <td>1</td>
                        <td>2024-10-09 08:15:00</td>
                        <td>Gerby</td>
                        <td>---</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>2024-10-09 08:15:00</td>
                        <td>Reign</td>
                        <td>---</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        $(document).ready(function() {

            const table = $("#coachLogTable").DataTable({
            pageLength: 10,
            ordering: false,
            responsive: true,
            dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>rtip',
            });

            // Initialize date picker
            flatpickr("#dateFilter", {
                dateFormat: "d/m/Y",
                allowInput: true,
                defaultDate: "today"
            });

            // Search functionality
            $('#searchBtn').click(function() {
                let searchText = $('#searchInput').val().toLowerCase();
                $('#activityTableBody tr').each(function() {
                    let staffName = $(this).find('td:eq(2)').text().toLowerCase();
                    $(this).toggle(staffName.includes(searchText));
                });
            });

            // Filter by date
            $('#filterBtn').click(function() {
                let filterDate = $('#dateFilter').val();
                if (filterDate) {
                    let [day, month, year] = filterDate.split('/');
                    let filterDateObj = new Date(year, month - 1, day);
                    
                    $('#activityTableBody tr').each(function() {
                        let timestamp = new Date($(this).find('td:eq(1)').text());
                        let show = timestamp.toDateString() === filterDateObj.toDateString();
                        $(this).toggle(show);
                    });
                }
            });

            // Refresh button
            $('#refreshBtn').click(function() {
                $('#searchInput').val('');
                $('#dateFilter').val('');
                $('#activityTableBody tr').show();
            });

            // Add sample data function
            function addActivity(staffName, activity) {
                let now = new Date();
                let timestamp = now.toISOString().slice(0, 19).replace('T', ' ');
                let rowCount = $('#activityTableBody tr').length + 1;
                
                let newRow = `
                    <tr>
                        <td>${rowCount}</td>
                        <td>${timestamp}</td>
                        <td>${staffName}</td>
                        <td>${activity}</td>
                    </tr>
                `;
                $('#activityTableBody').append(newRow);
            }

            // Example of adding new activity (for demonstration)
            // Uncomment to test
            /*
            setTimeout(() => {
                addActivity('John Doe', 'Logged in to system');
            }, 3000);
            */
        });
    </script>
</body>
</html>