// Sample data for the graph
const ctx = document.getElementById("membershipGraph").getContext("2d");
const membershipData = {
  labels: [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
  ],
  datasets: [
    {
      label: "New Members",
      data: [3, 2, 5, 10, 9, 2, 3, 0, 1, 8, 15, 0],
      borderColor: "#3498db",
      tension: 0.1,
      fill: false,
    },
  ],
};

new Chart(ctx, {
  type: "line",
  data: membershipData,
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      y: {
        beginAtZero: true,
        max: 20,
        ticks: {
          stepSize: 5,
        },
      },
    },
  },
});
$(document).ready(function() {
  // Initialize DataTables for both announcement tables
  $('#administrativeAnnouncementsTable, #activityAnnouncementsTable').DataTable({
      order: [[0, 'desc'], [1, 'desc']],
      pageLength: 5,
      lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
      responsive: true,
      columnDefs: [
          { width: '20%', targets: 0 },  // Date column
          { width: '15%', targets: 1 },  // Time column
          { width: '65%', targets: 2 }   // Message column
      ],
      language: {
          search: "_INPUT_",
          searchPlaceholder: "Search announcements...",
      }
  });
});