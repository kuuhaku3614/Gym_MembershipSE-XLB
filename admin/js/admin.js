$(document).ready(function () {
  // Handle navigation item clicks EXCEPT logout
  $(".nav-item:not([href*='logout']), .nav-item.has-subnav, .sub-nav-item").on(
    "click",
    function (e) {
      e.preventDefault();
      let url = $(this).attr("href");
      window.history.pushState({ path: url }, "", url);
    }
  );

  // Special handler for logout
  $("a[href*='logout']").on("click", function (e) {
    e.preventDefault();

    // Perform a synchronous redirect to logout page
    window.location.href = $(this).attr("href");
  });

  // Navigation click handlers
  const navigationHandlers = {
    // Main nav items
    "dashboard-link": () => loadContent("pages/dashboard/dashboard.php"),
    "members-link": () => {
      loadContent("pages/members/members_new.php");
      if (window.location.pathname.endsWith('members')) {
        window.history.replaceState({ path: 'members_new' }, "", 'members_new');
      }
    },
    "walk_in-link": () => loadContent("pages/walk in/walk_in.php"),
    "gym_rates-link": () => loadContent("pages/gym rates/gym_rates.php"),
    "payment_records-link": () =>
      loadContent("pages/payment records/payment_records.php"),
    "notification-link": () =>
      loadContent("pages/notification/notification.php"),
    "accounts-link": () =>
      loadContent("pages/accounts/accounts.php"),
    "website_settings-link": () =>
      loadContent("pages/website settings/website_settings.php"),
    "content_management-link": () => loadContent("pages/website settings/content_management.php"),
    "report-link": () => loadContent("pages/report/report.php"),
    "staff_management-link": () =>
      loadContent("pages/staff management/staff_management.php"),

    // Sub nav items for Members
    "attendance-link": () => loadContent("pages/members/attendance.php"),
    "attendance_history-link": () =>
      loadContent("pages/members/attendance_history.php"),
    "member_status-link": () => loadContent("pages/members/members_status.php"),
    "add_member-link": () => loadContent("pages/members/add_member.php"),
    "renew_member-link": () => loadContent("pages/members/renew_member.php"),

    // Sub nav items for Gym Rates
    "programs-link": () => loadContent("pages/gym rates/programs.php"),
    "rentals-link": () => loadContent("pages/gym rates/rentals.php"),

    // Sub nav items for Notification
    "announcement-link": () =>
      loadContent("pages/notification/announcements.php"),
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
      },
      error: function () {
        $(".main-content").html("<p>Error loading the page content.</p>");
        alert("Error loading the page content.");
      },
    });
  }

  // Handle initial page load based on URL
  let url = window.location.href;
  const urlMappings = {
    dashboard: "dashboard-link",
    members_new: "members-link", // Only support members_new
    add_member: "add_member-link",
    renew_member: "renew_member-link",
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
    content_management: "content_management-link",
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

  // Your existing click handlers (these are kept for backwards compatibility)
  $("#dashboard-link").on("click", function (e) {
    e.preventDefault();
    viewAnalytics();
  });

  $("#members-link").on("click", function (e) {
    e.preventDefault();
    viewMembersNew();
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

  $("#accounts-link").on("click", function (e) {
    e.preventDefault();
    viewAccounts();
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

  function viewMembersNew() {
    $.ajax({
      type: "GET",
      url: "pages/members/members_new.php",
      dataType: "html",
      success: function (response) {
        $(".main-content").html(response);
        
        // Clean up existing DataTable if present
        if ($.fn.DataTable.isDataTable('#membersTable')) {
          $('#membersTable').DataTable().destroy();
        }
        
        // Initialize DataTable with Bootstrap 5 styling and state saving
        $('#membersTable').DataTable({
          autoWidth: false,
          stateSave: true,
          stateDuration: -1, // Save state forever
          columnDefs: [
            { orderable: false, targets: [0, 4] }, // Disable sorting on photo and action columns
            { className: "align-middle", targets: "_all" }, // Vertically center all columns
            { width: "80px", targets: 0 }, // Photo column width
            { width: "100px", targets: 4 } // Action column width
          ],
          language: {
            search: "Search members:",
            lengthMenu: "Show _MENU_ members per page",
            info: "Showing _START_ to _END_ of _TOTAL_ members",
            emptyTable: "No members found"
          },
          pageLength: 10,
          lengthMenu: [10, 25, 50, 100],
          order: [[1, 'asc']] // Sort by name by default
        });
      },
      error: function(xhr, status, error) {
        console.error("Error loading members:", error);
        $(".main-content").html('<div class="alert alert-danger">Error loading members. Please try again.</div>');
      }
    });
  }

  // function viewAttendance() {
  //   $.ajax({
  //     type: "GET",
  //     url: "../admin/members/attendance.php",
  //     dataType: "html",
  //     success: function (response) {
  //       $(".main-content").html(response);
  //     },
  //   });
  // }

  // function viewAttendanceHistory() {
  //   $.ajax({
  //     type: "GET",
  //     url: "../admin/members/attendance_history.php",
  //     dataType: "html",
  //     success: function (response) {
  //       $(".main-content").html(response);
  //     },
  //   });
  // }

  // function viewMemberStatus() {
  //   $.ajax({
  //     type: "GET",
  //     url: "../admin/members/member_status.php",
  //     dataType: "html",
  //     success: function (response) {
  //       $(".main-content").html(response);
  //     },
  //   });
  // }

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

  function viewAccounts() {
    $.ajax({
      type: "GET",
      url: "pages/accounts/accounts.php",
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

  // Function to update membership statuses
  function updateMembershipStatuses() {
    $.ajax({
      url: '/Gym_MembershipSE-XLB/admin/api/update_membership_status.php',
      method: 'POST',
      success: function(response) {
        if (response.success) {
          // Only reload if we're on the members_status page
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