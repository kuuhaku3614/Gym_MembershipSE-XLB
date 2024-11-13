$(document).ready(function () {
  // Handle all navigation item clicks
  $(".nav-item, .nav-item.has-subnav, .sub-nav-item").on("click", function (e) {
    e.preventDefault();
    let url = $(this).attr("href");
    window.history.pushState({ path: url }, "", url);
  });

  // Navigation click handlers
  const navigationHandlers = {
    // Main nav items
    "dashboard-link": () => loadContent("pages/dashboard/dashboard.php"),
    "members-link": () => loadContent("pages/members/members.php"),
    "walk_in-link": () => loadContent("pages/walk in/walk_in.php"),
    "gym_rates-link": () => loadContent("pages/gym rates/gym_rates.php"),
    "payment_records-link": () => loadContent("pages/payment records/payment_records.php"),
    "notification-link": () => loadContent("pages/notification/notification.php"),
    "website_settings-link": () => loadContent("pages/website settings/website_settings.php"),
    "report-link": () => loadContent("pages/report/report.php"),
    "staff_management-link": () => loadContent("pages/staff management/staff_management.php"),
    
    // Sub nav items for Members
    "attendance-link": () => loadContent("pages/members/attendance.php"),
    "attendance_history-link": () => loadContent("pages/members/attendance_history.php"),
    "member_status-link": () => loadContent("pages/members/members_status.php"),
    
    // Sub nav items for Gym Rates
    "programs-link": () => loadContent("pages/gym rates/programs.php"),
    "rentals-link": () => loadContent("pages/gym rates/rentals.php"),
    
    // Sub nav items for Notification
    "announcement-link": () => loadContent("pages/notification/announcements.php"),
    
    // Sub nav items for Staff Management
    "activity_log-link": () => loadContent("pages/staff management/staff_log.php"),
    "coach_log-link": () => loadContent("pages/staff management/coach_log.php")
  };

  // Attach click handlers to all navigation items
  Object.keys(navigationHandlers).forEach(id => {
    $(`#${id}`).on("click", function(e) {
      e.preventDefault();
      navigationHandlers[id]();
    });
  });

  // Common content loading function
  function loadContent(url) {
    $.ajax({
      type: "GET",
      url: url,
      dataType: "html",
      success: function(response) {
        $(".main-content").html(response);
      },
      error: function() {
        $(".main-content").html("<p>Error loading the page content.</p>");
        alert("Error loading the page content.");
      }
    });
  }

  // Handle initial page load based on URL
  let url = window.location.href;
  const urlMappings = {
    "dashboard": "dashboard-link",
    "members": "members-link",
    "attendance": "attendance-link",
    "attendance_history": "attendance_history-link",
    "member_status": "member_status-link",
    "walk_in": "walk_in-link",
    "gym_rates": "gym_rates-link",
    "programs": "programs-link",
    "rentals": "rentals-link",
    "payment_records": "payment_records-link",
    "notification": "notification-link",
    "announcement": "announcement-link",
    "website_settings": "website_settings-link",
    "report": "report-link",
    "staff_management": "staff_management-link",
    "activity_log": "activity_log-link",
    "coach_log": "coach_log-link"
  };

  let matched = false;
  Object.entries(urlMappings).forEach(([key, value]) => {
    if (url.endsWith(key)) {
      $(`#${value}`).trigger("click");
      matched = true;
    }
  });

  if (!matched) {
    $("#dashboard-link").trigger("click");
  }

  // Your existing click handlers (these are kept for backwards compatibility)
  $("#dashboard-link").on("click", function (e) {
    e.preventDefault();
    viewAnalytics();
  });

  $("#members-link").on("click", function (e) {
    e.preventDefault();
    viewMembers();
  });

  $("#attendance-link").on("click", function (e) {
    e.preventDefault();
    viewAttendance();
  });

  $("#attendance_history-link").on("click", function (e) {
    e.preventDefault();
    viewAttendanceHistory();
  });

  $("#member_status-link").on("click", function (e) {
    e.preventDefault();
    viewMemberStatus();
  });

  $("#walk_in-link").on("click", function (e) {
    e.preventDefault();
    viewWalkIn();
  });

  $("#gym_rates-link").on("click", function (e) {
    e.preventDefault();
    viewGymRates();
  });

  $("#payment_records-link").on("click", function (e) {
    e.preventDefault();
    viewPaymentRecords();
  });

  $("#notification-link").on("click", function (e) {
    e.preventDefault();
    viewNotification();
  });

  $("#website_settings-link").on("click", function (e) {
    e.preventDefault();
    websiteSettings();
  });

  $("#report-link").on("click", function (e) {
    e.preventDefault();
    getReport();
  });

  $("#staff_management-link").on("click", function (e) {
    e.preventDefault();
    viewStaff();
  });

  // Your existing functions
  function viewAnalytics() {
    $.ajax({
      type: "GET",
      url: "pages/dashboard/dashboard.php",
      dataType: "html",
      success: function (response) {
        $(".main-content").html(response);
      },
      error: function () {
        $(".main-content").html("<p>Error loading the page content.</p>");
        alert("Error loading the page content.");
      },
    });
  }

  function viewMembers() {
    $.ajax({
      type: "GET",
      url: "pages/members/members.php",
      dataType: "html",
      success: function (response) {
        $(".main-content").html(response);
      },
    });
  }

  function viewAttendance() {
    $.ajax({
      type: "GET",
      url: "../admin/members/attendance.php",
      dataType: "html",
      success: function (response) {
        $(".main-content").html(response);
      },
    });
  }

  function viewAttendanceHistory() {
    $.ajax({
      type: "GET",
      url: "../admin/members/attendance_history.php",
      dataType: "html",
      success: function (response) {
        $(".main-content").html(response);
      },
    });
  }

  function viewMemberStatus() {
    $.ajax({
      type: "GET",
      url: "../admin/members/member_status.php",
      dataType: "html",
      success: function (response) {
        $(".main-content").html(response);
      },
    });
  }

  function viewWalkIn() {
    $.ajax({
      type: "GET",
      url: "pages/walk in/walk_in.php",
      dataType: "html",
      success: function (response) {
        $(".main-content").html(response);
      },
      error: function () {
        $(".main-content").html("<p>Error loading the page content.</p>");
        alert("Error loading the page content.");
      },
    });
  }

  function viewGymRates() {
    $.ajax({
      type: "GET",
      url: "pages/gym rates/gym_rates.php",
      dataType: "html",
      success: function (response) {
        $(".main-content").html(response);
      },
      error: function () {
        $(".main-content").html("<p>Error loading the page content.</p>");
        alert("Error loading the page content.");
      },
    });
  }

  function viewPaymentRecords() {
    $.ajax({
      type: "GET",
      url: "pages/payment records/payment_records.php",
      dataType: "html",
      success: function (response) {
        $(".main-content").html(response);
      },
    });
  }

  function viewNotification() {
    $.ajax({
      type: "GET",
      url: "pages/notification/notification.php",
      dataType: "html",
      success: function (response) {
        $(".main-content").html(response);
      },
    });
  }

  function websiteSettings() {
    $.ajax({
      type: "GET",
      url: "pages/website settings/website_settings.php",
      dataType: "html",
      success: function (response) {
        $(".main-content").html(response);
      },
    });
  }

  function getReport() {
    $.ajax({
      type: "GET",
      url: "pages/report/report.php",
      dataType: "html",
      success: function (response) {
        $(".main-content").html(response);
      },
    });
  }

  function viewStaff() {
    $.ajax({
      type: "GET",
      url: "pages/staff management/staff_management.php",
      dataType: "html",
      success: function (response) {
        $(".main-content").html(response);
      },
    });
  }
});