<body class="bg-light">
  <div class="container mt-4">
    <!-- Report Overview Section -->
    <div class="card shadow-sm p-4">
      <h3 class="mb-4">Report Overview</h3>
      <div class="row g-3">
        <div class="col-md-4">
          <label for="dateRangeFrom" class="form-label">Date Range</label>
          <input type="date" class="form-control" id="dateRangeFrom">
        </div>
        <div class="col-md-4">
          <label for="dateRangeTo" class="form-label">To</label>
          <input type="date" class="form-control" id="dateRangeTo">
        </div>
        <div class="col-md-4">
          <label for="preparedBy" class="form-label">Prepared By</label>
          <input type="text" class="form-control" id="preparedBy" value="JC Powerzone" readonly>
        </div>
      </div>
      <div class="row g-3 mt-3">
        <div class="col-md-4">
          <label for="dateGenerated" class="form-label">Date Generated</label>
          <input type="date" class="form-control" id="dateGenerated">
        </div>
      </div>
    </div>

    <!-- Total Earnings Summary Section -->
    <div class="card shadow-sm mt-4 p-4">
      <h4 class="mb-4">Total Earnings Summary</h4>
      <div class="row">
        <div class="col-md-6">
          <h5>Total Earnings</h5>
          <h2 class="text-danger">₱30,000</h2>
          <div>
            <h5>Monthly Earnings Trend</h5>
            <canvas id="monthlyEarningsChart"></canvas>
          </div>
        </div>
        <div class="col-md-6">
          <h5>Memberships</h5>
          <h2 class="text-danger">1000 members</h2>
          <div>
            <h5>Membership Breakdown</h5>
            <canvas id="membershipBreakdownChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Demographics Section -->
    <div class="card shadow-sm mt-4 p-4">
      <h4 class="mb-4">Demographics</h4>
      <h5>Member Age Distribution</h5>
      <canvas id="ageDistributionChart"></canvas>
    </div>

    <!-- Print Button -->
    <div class="text-center mt-4">
      <button class="btn btn-danger px-4">Print</button>
    </div>
  </div>
  <!-- <script>
    $(document).ready(function () {
  // Set up dummy data
  const monthlyEarningsData = [41000, 38000];
  const membershipData = [70, 25, 5];
  const ageDistributionData = [30, 40, 20, 10];

  // Initialize Monthly Earnings Chart
  const ctx1 = $("#monthlyEarningsChart");
  new Chart(ctx1, {
    type: "line",
    data: {
      labels: ["Jan", "Feb"],
      datasets: [
        {
          label: "Monthly Earnings",
          data: monthlyEarningsData,
          borderColor: "blue",
          tension: 0.4,
        },
      ],
    },
    options: {
      responsive: true,
    },
  });

  // Initialize Membership Breakdown Chart
  const ctx2 = $("#membershipBreakdownChart");
  new Chart(ctx2, {
    type: "doughnut",
    data: {
      labels: ["New", "Renewed", "Walk In"],
      datasets: [
        {
          data: membershipData,
          backgroundColor: ["blue", "green", "red"],
        },
      ],
    },
    options: {
      responsive: true,
    },
  });

  // Initialize Age Distribution Chart
  const ctx3 = $("#ageDistributionChart");
  new Chart(ctx3, {
    type: "bar",
    data: {
      labels: ["18-25", "26-35", "36-50", "50+"],
      datasets: [
        {
          label: "Age Distribution",
          data: ageDistributionData,
          backgroundColor: "blue",
        },
      ],
    },
    options: {
      responsive: true,
    },
  });

  // Print button functionality
  $(".btn-danger").click(function () {
    window.print();
  });
});

  </script> -->
  <script src="../js/report.js"></script>
</body>