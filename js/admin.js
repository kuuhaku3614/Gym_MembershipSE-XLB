$(document).ready(function () {
  $(".nav-link").on("click", function (e) {
    e.preventDefault();
    $(".nav-link").removeClass("link-active");
    $(this).addClass("link-active");

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

  $("#payment_records-link").on("click", function (e) {
    e.preventDefault();
    viewPaymentRecords();
  });

  $("#website_settings-link").on("click", function (e) {
    e.preventDefault();
    websiteSettings();
  });

  $("#report-link").on("click", function (e) {
    e.preventDefault();
    getReport();
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
  } else if (url.endsWith("walkIn")) {
    $("#walk_in-link").trigger("click");
  } else if (url.endsWith("payment_records")) {
    $("#payment_records-link").trigger("click");
  } else if (url.endsWith("website_settings")) {
    $("#website_settings-link").trigger("click");
  } else if (url.endsWith("report")) {
    $("#report-link").trigger("click");
  } else if (url.endsWith("members")) {
    $("#members-link").trigger("click");
  } else {
    $("#dashboard-link").trigger("click");
  }

  function viewAnalytics() {
    $.ajax({
      type: "GET",
      url: "../admin/dashboard.php",
      dataType: "html",
      success: function (response) {
        $(".content-page").html(response);
      },
    });
  }

  function viewWalkIn() {
    $.ajax({
      type: "GET",
      url: "../admin/walk_in.php",
      dataType: "html",
      success: function (response) {
        $(".content-page").html(response);
      },
    });
  }

  function viewMembers() {
    $.ajax({
      type: "GET",
      url: "../admin/members/members.php",
      dataType: "html",
      success: function (response) {
        $(".content-page").html(response);
      },
    });
  }

  function viewAttendance() {
    $.ajax({
      type: "GET",
      url: "../admin/members/attendance.php",
      dataType: "html",
      success: function (response) {
        $(".content-page").html(response);
      },
    });
  }

  function viewAttendanceHistory() {
    $.ajax({
      type: "GET",
      url: "../admin/members/attendance_history.php",
      dataType: "html",
      success: function (response) {
        $(".content-page").html(response);
      },
    });
  }

  function viewMemberStatus() {
    $.ajax({
      type: "GET",
      url: "../admin/members/member_status.php",
      dataType: "html",
      success: function (response) {
        $(".content-page").html(response);
      },
    });
  }

  function viewPaymentRecords() {
    $.ajax({
      type: "GET",
      url: "../admin/payment_record.php",
      dataType: "html",
      success: function (response) {
        $(".content-page").html(response);
      },
    });
  }

  function websiteSettings() {
    $.ajax({
      type: "GET",
      url: "../admin/website_setting.php",
      dataType: "html",
      success: function (response) {
        $(".content-page").html(response);
      },
    });
  }

  function getReport() {
    $.ajax({
      type: "GET",
      url: "../admin/report.php",
      dataType: "html",
      success: function (response) {
        $(".content-page").html(response);
      },
    });
  }
});
