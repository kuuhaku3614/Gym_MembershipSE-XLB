$(document).ready(function () {
  // Handle navigation item clicks
  $(".nav-item, .nav-item.has-subnav, .sub-nav-item").on("click", function (e) {
    e.preventDefault();

    let url = $(this).attr("href");
    window.history.pushState({ path: url }, "", url);
  });

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

  let url = window.location.href;

  if (url.endsWith("dashboard")) {
    $("#dashboard-link").trigger("click");
  } else if (url.endsWith("members")) {
    $("#members-link").trigger("click");
  } else if (url.endsWith("attendance")) {
    $("#attendance-link").trigger("click");
  } else if (url.endsWith("attendace_history")) {
    $("#attendance_history-link").trigger("click");
  } else if (url.endsWith("member_status")) {
    $("#member_status-link").trigger("click");
  } else if (url.endsWith("walk_in")) {
    $("#walk_in-link").trigger("click");
  } else if (url.endsWith("gym_rates")) {
    $("#gym_rates-link").trigger("click");
  } else if (url.endsWith("payment_records")) {
    $("#payment_records-link").trigger("click");
  } else if (url.endsWith("notification")) {
    $("#notification-link").trigger("click");
  } else if (url.endsWith("website_settings")) {
    $("#website_settings-link").trigger("click");
  } else if (url.endsWith("report")) {
    $("#report-link").trigger("click");
  } else if (url.endsWith("members")) {
    $("#members-link").trigger("click");
  } else if (url.endsWith("staff_management")) {
    $("#staff_management-link").trigger("click");
  } else {
    $("#dashboard-link").trigger("click");
  }

  function viewAnalytics() {
    $.ajax({
      type: "GET",
      url: "pages/dashboard/dashboard.php", // Adjusted relative path
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
