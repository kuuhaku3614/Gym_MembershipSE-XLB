<?php
require_once 'config.php';
$database = new Database();

$pdo = $database->connect();
?>
<body class="bg-light">

  <div class="container mt-4">
    <h1 class="nav-title">Member Attendance Analysis</h1>
    <div class="card shadow-sm p-4">
      <h4 class="mb-4">Attendance Table</h4>
      <table class="table table-striped table-bordered" id="attendanceTable">
        <thead>
          <tr>
            <th>Username</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Total Check-ins</th>
            <th>Total Missed</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $attendance_sql = "
            SELECT 
              u.username, 
              pd.first_name, 
              pd.last_name,
              COUNT(CASE WHEN ah.status = 'checked_in' THEN 1 END) as total_check_ins,
              COUNT(CASE WHEN ah.status = 'missed' THEN 1 END) as total_missed
            FROM attendance_history ah
            JOIN attendance a ON ah.attendance_id = a.id
            JOIN users u ON a.user_id = u.id
            JOIN personal_details pd ON u.id = pd.user_id
            GROUP BY u.id
            ORDER BY total_check_ins DESC;
          ";
          $attendance_stmt = $pdo->prepare($attendance_sql);
          $attendance_stmt->execute();
          $attendance = $attendance_stmt->fetchAll(PDO::FETCH_ASSOC);
          foreach ($attendance as $row) {
          ?>
            <tr>
              <td><?= $row['username'] ?></td>
              <td><?= $row['first_name'] ?></td>
              <td><?= $row['last_name'] ?></td>
              <td><?= $row['total_check_ins'] ?></td>
              <td><?= $row['total_missed'] ?></td>
            </tr>
          <?php
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="container mt-4">
    <h1 class="nav-title">Member Service Utilization</h1>
    <div class="card shadow-sm p-4">
      <h4 class="mb-4">Member Service Utilization Table</h4>
      <table class="table table-striped table-bordered" id="utilizationTable">
        <thead>
          <tr>
            <th>Username</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Membership Count</th>
            <th>Program Subscriptions</th>
            <th>Rental Subscriptions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $utilization_sql = "
            SELECT 
              u.username,
              pd.first_name,
              pd.last_name,
              COUNT(DISTINCT m.id) as membership_count,
              COUNT(DISTINCT ps.id) as program_subscriptions,
              COUNT(DISTINCT rs.id) as rental_subscriptions
            FROM users u
            JOIN personal_details pd ON u.id = pd.user_id
            LEFT JOIN transactions t ON u.id = t.user_id
            LEFT JOIN memberships m ON t.id = m.transaction_id
            LEFT JOIN program_subscriptions ps ON t.id = ps.transaction_id
            LEFT JOIN rental_subscriptions rs ON t.id = rs.transaction_id
            GROUP BY u.id
            ORDER BY membership_count DESC;
          ";
          $utilization_stmt = $pdo->prepare($utilization_sql);
          $utilization_stmt->execute();
          $utilization = $utilization_stmt->fetchAll(PDO::FETCH_ASSOC);
          foreach ($utilization as $row) {
          ?>
            <tr>
              <td><?= $row['username'] ?></td>
              <td><?= $row['first_name'] ?></td>
              <td><?= $row['last_name'] ?></td>
              <td><?= $row['membership_count'] ?></td>
              <td><?= $row['program_subscriptions'] ?></td>
              <td><?= $row['rental_subscriptions'] ?></td>
            </tr>
          <?php
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="container mt-4">
    <h1 class="nav-title">Monthly Earnings</h1>
    <div class="card shadow-sm p-4">
      <h4 class="mb-4">Monthly Earnings Table</h4>
      <table class="table table-striped table-bordered mt-4" id="earningsTable">
        <thead>
          <tr>
            <th>Month</th>
            <th>Year</th>
            <th>Total Memberships</th>
            <th>Total Amount</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $earnings_sql = "
          SELECT 
            MONTH(created_at) as month,
            YEAR(created_at) as year,
            COUNT(*) as total_memberships,
            SUM(amount) as total_amount
          FROM memberships
          GROUP BY month, year
          ORDER BY year, month;
          ";
          $earnings_stmt = $pdo->prepare($earnings_sql);
          $earnings_stmt->execute();
          $earnings = $earnings_stmt->fetchAll(PDO::FETCH_ASSOC);
          foreach ($earnings as $row) {
          ?>
            <tr>
              <td><?= date('F', mktime(0, 0, 0, $row['month'], 10)) ?></td>
              <td><?= $row['year'] ?></td>
              <td><?= number_format($row['total_memberships'], 0) ?></td>
              <td><?= number_format($row['total_amount'], 2) ?></td>
            </tr>
          <?php
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="container mt-4">
    <h1 class="nav-title">Total Program Subscriptions</h1>
    <div class="card shadow-sm p-4">
      <h4 class="mb-4">Program Subscriptions Table</h4>
      <table class="table table-striped table-bordered mt-4" id="programsTable">
        <thead>
          <tr>
            <th>Month</th>
            <th>Year</th>
            <th>Total Subscriptions</th>
            <th>Total Amount</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $programs_sql = "
          SELECT 
            MONTH(created_at) as month,
            YEAR(created_at) as year,
            COUNT(*) as total_subscriptions,
            SUM(amount) as total_amount
          FROM program_subscriptions
          GROUP BY month, year
          ORDER BY year, month;
          ";
          $programs_stmt = $pdo->prepare($programs_sql);
          $programs_stmt->execute();
          $programs = $programs_stmt->fetchAll(PDO::FETCH_ASSOC);
          foreach ($programs as $row) {
          ?>
            <tr>
              <td><?= date('F', mktime(0, 0, 0, $row['month'], 10)) ?></td>
              <td><?= $row['year'] ?></td>
              <td><?= number_format($row['total_subscriptions'], 0) ?></td>
              <td><?= number_format($row['total_amount'], 2) ?></td>
            </tr>
          <?php
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="container mt-4">
    <h1 class="nav-title">Total Rental Subscriptions</h1>
    <div class="card shadow-sm p-4">
      <h4 class="mb-4">Rentals Table</h4>
      <table class="table table-striped table-bordered mt-4" id="rentalsTable">
        <thead>
          <tr>
            <th>Month</th>
            <th>Year</th>
            <th>Total Rentals</th>
            <th>Total Amount</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $rentals_sql = "
          SELECT 
            MONTH(created_at) as month,
            YEAR(created_at) as year,
            COUNT(*) as total_rentals,
            SUM(amount) as total_amount
          FROM rental_subscriptions
          GROUP BY month, year
          ORDER BY year, month;
          ";
          $rentals_stmt = $pdo->prepare($rentals_sql);
          $rentals_stmt->execute();
          $rentals = $rentals_stmt->fetchAll(PDO::FETCH_ASSOC);
          foreach ($rentals as $row) {
          ?>
            <tr>
              <td><?= date('F', mktime(0, 0, 0, $row['month'], 10)) ?></td>
              <td><?= $row['year'] ?></td>
              <td><?= number_format($row['total_rentals'], 0) ?></td>
              <td><?= number_format($row['total_amount'], 2) ?></td>
            </tr>
          <?php
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
 
  
</body>