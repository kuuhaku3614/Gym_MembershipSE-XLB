$(document).ready(function () {
  // Handle navigation item clicks EXCEPT logout
  $(".nav-item:not([href*='logout']), .nav-item.has-subnav, .sub-nav-item").on(
    "click",
    function (e) {
      e.preventDefault();
      let url = $(this).attr("href");
      window.history.pushState({ path: url }, "", url);
      window.location.reload();
    }
  );

  // Special handler for logout
  $("a[href*='logout']").on("click", function (e) {
    e.preventDefault();
    window.location.href = $(this).attr("href");
  });

  // Navigation click handlers
  const navigationHandlers = {
    // Main nav items
    "dashboard-link": () => {
      loadContent("pages/dashboard/dashboard.php");
      window.location.reload();
    },
    "members-link": () => {
      loadContent("pages/members/members_new.php");
      if (window.location.pathname.endsWith('members')) {
        window.history.replaceState({ path: 'members_new' }, "", 'members_new');
      }
      window.location.reload();
    },
    "walk_in-link": () => {
      loadContent("pages/walk in/walk_in.php");
      window.location.reload();
    },
    "gym_rates-link": () => {
      loadContent("pages/gym rates/gym_rates.php");
      window.location.reload();
    },
    "payment_records-link": () => {
      loadContent("pages/payment records/payment_records.php");
      window.location.reload();
    },
    "notification-link": () => {
      loadContent("pages/notification/notification.php");
      window.location.reload();
    },
    "accounts-link": () => {
      loadContent("pages/accounts/accounts.php");
      window.location.reload();
    },
    "website_settings-link": () => {
      loadContent("pages/website settings/website_settings.php");
      window.location.reload();
    },
    "report-link": () => {
      loadContent("pages/report/report.php");
      window.location.reload();
    },
    "staff_management-link": () => {
      loadContent("pages/staff management/staff_management.php");
      window.location.reload();
    },

    // Sub nav items for Members
    "attendance-link": () => {
      loadContent("pages/members/attendance.php");
      window.location.reload();
    },
    "attendance_history-link": () => {
      loadContent("pages/members/attendance_history.php");
      window.location.reload();
    },
    "member_status-link": () => {
      loadContent("pages/members/members_status.php");
      window.location.reload();
    },
    "add_member-link": () => {
      loadContent("pages/members/add_member.php");
      window.location.reload();
    },

    // Sub nav items for Gym Rates
    "programs-link": () => {
      loadContent("pages/gym rates/programs.php");
      window.location.reload();
    },
    "rentals-link": () => {
      loadContent("pages/gym rates/rentals.php");
      window.location.reload();
    },

    // Sub nav items for Notification
    "announcement-link": () => {
      loadContent("pages/notification/announcements.php");
      window.location.reload();
    },
  };

  // Attach click handlers to all navigation items
  Object.keys(navigationHandlers).forEach((id) => {
    $(`#${id}`).on("click", function (e) {
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
      success: function (response) {
        $(".main-content").html(response);
        window.location.reload();
      },
      error: function () {
        $(".main-content").html("<p>Error loading the page content.</p>");
        alert("Error loading the page content.");
        window.location.reload();
      },
    });
  }

  // Handle initial page load based on URL
  let url = window.location.href;
  const urlMappings = {
    dashboard: "dashboard-link",
    members_new: "members-link",
    add_member: "add_member-link",
    attendance: "attendance-link",
    attendance_history: "attendance_history-link",
    member_status: "member_status-link",
    walk_in: "walk_in-link",
    gym_rates: "gym_rates-link",
    programs: "programs-link",
    rentals: "rentals-link",
    payment_records: "payment_records-link",
    notification: "notification-link",
    announcement: "announcement-link",
    accounts: "accounts-link",
    website_settings: "website_settings-link",
    report: "report-link",
    staff_management: "staff_management-link",
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

  // Click handlers with reload
  $("#dashboard-link").on("click", function (e) {
    e.preventDefault();
    viewAnalytics();
    window.location.reload();
  });

  $("#members-link").on("click", function (e) {
    e.preventDefault();
    viewMembersNew();
    window.location.reload();
  });

  $("#attendance-link").on("click", function (e) {
    e.preventDefault();
    viewAttendance();
    window.location.reload();
  });

  $("#attendance_history-link").on("click", function (e) {
    e.preventDefault();
    viewAttendanceHistory();
    window.location.reload();
  });

  $("#member_status-link").on("click", function (e) {
    e.preventDefault();
    viewMemberStatus();
    window.location.reload();
  });

  $("#walk_in-link").on("click", function (e) {
    e.preventDefault();
    viewWalkIn();
    window.location.reload();
  });

  $("#gym_rates-link").on("click", function (e) {
    e.preventDefault();
    viewGymRates();
    window.location.reload();
  });

  $("#payment_records-link").on("click", function (e) {
    e.preventDefault();
    viewPaymentRecords();
    window.location.reload();
  });

  $("#notification-link").on("click", function (e) {
    e.preventDefault();
    viewNotification();
    window.location.reload();
  });

  $("#accounts-link").on("click", function (e) {
    e.preventDefault();
    viewAccounts();
    window.location.reload();
  });

  $("#website_settings-link").on("click", function (e) {
    e.preventDefault();
    websiteSettings();
    window.location.reload();
  });

  $("#report-link").on("click", function (e) {
    e.preventDefault();
    getReport();
    window.location.reload();
  });

  $("#staff_management-link").on("click", function (e) {
    e.preventDefault();
    viewStaff();
    window.location.reload();
  });

  // View functions with reload
  function viewAnalytics() {
    $.ajax({
      type: "GET",
      url: "pages/dashboard/dashboard.php",
      dataType: "html",
      success: function (response) {
        $(".main-content").html(response);
        window.location.reload();
      },
      error: function () {
        $(".main-content").html("<p>Error loading the page content.</p>");
        alert("Error loading the page content.");
        window.location.reload();
      },
    });
  }

  function viewMembersNew() {
    $.ajax({
      type: "GET",
      url: "pages/members/members_new.php",
      dataType: "html",
      success: function (response) {
        $(".main-content").html(response);
        window.location.reload();
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
        window.location.reload();
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
        window.location.reload();
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
        window.location.reload();
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
        window.location.reload();
      },
      error: function () {
        $(".main-content").html("<p>Error loading the page content.</p>");
        alert("Error loading the page content.");
        window.location.reload();
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
        window.location.reload();
      },
      error: function () {
        $(".main-content").html("<p>Error loading the page content.</p>");
        alert("Error loading the page content.");
        window.location.reload();
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
        window.location.reload();
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
        window.location.reload();
      },
    });
  }

  function viewAccounts() {
    $.ajax({
      type: "GET",
      url: "pages/accounts/accounts.php",
      dataType: "html",
      success: function (response) {
        $(".main-content").html(response);
        window.location.reload();
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
        window.location.reload();
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
        window.location.reload();
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
        window.location.reload();
      },
    });
  }

  // Function to update membership statuses
  function updateMembershipStatuses() {
    $.ajax({
      url: '/Gym_MembershipSE-XLB/admin/api/update_membership_status.php',
      method: 'POST',
      success: function(response) {
        if (response.success) {
          if (window.location.pathname.includes('members_status.php')) {
            location.reload();
          }
        } else {
          console.error('Error updating statuses:', response.message);
        }
      },
      error: function(xhr, status, error) {
        console.error('Ajax error:', error);
      }
    });
  }

  // Update statuses when any admin page loads
  updateMembershipStatuses();

  // Update statuses every 30 minutes
  setInterval(updateMembershipStatuses, 30 * 60 * 1000);
});