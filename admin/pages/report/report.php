<body class="bg-light">

  <div class="container mt-4">
  <h1 class="nav-title">Report Overview</h1>
    <!-- Report Overview Section -->
    <div class="card shadow-sm p-4">
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
          <input type="text" class="form-control" id="preparedBy" value="JC Powerzone" >
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
          <h2 class="text-danger">₱10,000</h2>
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
  <script src="js/report.js"></script>
</body>