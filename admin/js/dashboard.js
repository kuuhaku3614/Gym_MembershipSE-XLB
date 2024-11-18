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

// Sample data for tables
$(document).ready(function () {
  $("#announcementsTable").DataTable({
    data: [
      ["Gym maintenance scheduled", "2024-10-21"],
      ["New fitness class added", "2024-10-22"],
      ["Holiday schedule update", "2024-10-23"],
    ],
  });

  $("#activitiesTable").DataTable({
    data: [
      ["Yoga Class", "2024-10-21", "09:00 AM"],
      ["Zumba Session", "2024-10-21", "11:00 AM"],
      ["CrossFit Training", "2024-10-22", "02:00 PM"],
    ],
  });
});
