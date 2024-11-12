<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard</title>
    <!-- Bootstrap CSS -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
    />
    <link rel="stylesheet" href="admin.css" />
  </head>
  <body>
    <!-- Topbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
      <div class="container-fluid">
        <!-- Logo -->
        <a class="navbar-brand" href="#">
          <img src="path/to/logo.png" alt="Logo" height="40" />
        </a>
        <!-- Right Aligned Profile -->
        <div class="d-flex">
          <div class="nav-item dropdown">
            <a
              class="nav-link dropdown-toggle d-flex align-items-center"
              href="#"
              id="navbarDropdown"
              role="button"
              data-bs-toggle="dropdown"
              aria-expanded="false"
            >
              <img
                src="path/to/profile.jpg"
                alt="User"
                class="rounded-circle"
                height="40"
                width="40"
              />
            </a>
            <ul
              class="dropdown-menu dropdown-menu-end"
              aria-labelledby="navbarDropdown"
            >
              <li><a class="dropdown-item" href="#">Profile</a></li>
              <li><a class="dropdown-item" href="#">Settings</a></li>
              <li><hr class="dropdown-divider" /></li>
              <li><a class="dropdown-item" href="#">Logout</a></li>
            </ul>
          </div>
        </div>
      </div>
    </nav>

    <div class="d-flex">
      <!-- Sidebar -->
      <nav class="sidebar d-flex flex-column">
        <ul class="menu list-unstyled">
          <li>
            <a href="dashboard" id="dashboard-link" class="nav-link"
              >Dashboard</a
            >
          </li>
          <!-- Members Collapse -->
          <li>
              <a
                href="members"
                id="members-link"
                class="collapse-link nav-link"
                data-bs-toggle="collapse"
                data-bs-target="#membersMenu"
                aria-expanded="false"
              >
                Members
              </a>
              <div id="membersMenu" class="collapse nav-link">
                <ul class="collapse-inner list-unstyled">
                  <li>
                    <a href="attendance" id="attendance-link" class="nav-link">Attendance</a>
                  </li>
                  <li>
                    <a href="attendance_history" id="attendance_history-link" class="nav-link">Attendance History</a>
                  </li>
                  <li>
                    <a href="member_status" id="member_status-link" class="nav-link">Member Status</a>
                  </li>
                </ul>
              </div>
            </li>


          <li>
            <a href="walkIn" id="walk_in-link" class="nav-link">Walk-In</a>
          </li>
          <!-- Gym Rates Collapse -->
          <li>
            <button
              class="collapse-button collapsed"
              data-bs-toggle="collapse"
              data-bs-target="#gymRatesMenu"
              aria-expanded="false"
            >
              <a href="#">Gym Rates</a>
            </button>
            <div id="gymRatesMenu" class="collapse">
              <ul class="collapse-inner list-unstyled">
                <li>
                  <a href="#">Programs</a>
                </li>
                <li>
                  <a href="#">Rentals</a>
                </li>
                <li>
                  <a href="#">Coaching</a>
                </li>
              </ul>
            </div>
          </li>
          <li>
            <a href="payment_records"  id="payment_records-link" class="nav-link" >Payment Records</a>
          </li>
          <!-- Notification Collapse -->
          <li>
            <button
              class="collapse-button collapsed"
              data-bs-toggle="collapse"
              data-bs-target="#notificationMenu"
              aria-expanded="false"
            >
              <a href="#">Notification</a>
            </button>
            <div id="notificationMenu" class="collapse">
              <ul class="collapse-inner list-unstyled">
                <li>
                  <a href="#">Announcements</a>
                </li>
              </ul>
            </div>
          </li>
          <li>
            <a href="website_settings"  id="website_settings-link" class="nav-link">Website Settings</a>
          </li>
          <li>
            <a href="report"  id="report-link" class="nav-link">Report</a>
          </li>
          <!-- Staff Management Collapse -->
          <li>
            <button
              class="collapse-button collapsed"
              data-bs-toggle="collapse"
              data-bs-target="#staffMenu"
              aria-expanded="false"
            >
              <a href="#">Staff Management</a>
            </button>
            <div id="staffMenu" class="collapse">
              <ul class="collapse-inner list-unstyled">
                <li>
                  <a href="#">Staff Activity Log</a>
                </li>
                <li>
                  <a href="#">Coach Logs</a>
                </li>
              </ul>
            </div>
          </li>
          <li>
            <a href="#">Logout</a>
          </li>
        </ul>
      </nav>

        <!-- Main content area -->
        <main class="p-4 flex-grow-1">
          <h1>Dashboard</h1>
          <p>Welcome to the admin dashboard</p>
        </main>
      </div>


    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <script src="../js/admin.js"></script>
    <script src="../jQuery-3.7.1/jquery-3.7.1.min.js"></script>
  </body>
</html>
