$(document).ready(function () {
  // Set up dummy data
  const monthlyEarningsData = [41000, 38000];
  const membershipData = [70, 25, 5];
  const ageDistributionData = [30, 40, 20, 10];

  // Initialize Monthly Earnings Chart
  const ctx1 = $("#monthlyEarningsChart");
  new Chart(ctx1, {
    type: "line",
    data: {
      labels: ["Jan", "Feb"],
      datasets: [
        {
          label: "Monthly Earnings",
          data: monthlyEarningsData,
          borderColor: "blue",
          tension: 0.4,
        },
      ],
    },
    options: {
      responsive: true,
    },
  });

  // Initialize Membership Breakdown Chart
  const ctx2 = $("#membershipBreakdownChart");
  new Chart(ctx2, {
    type: "doughnut",
    data: {
      labels: ["New", "Renewed", "Walk In"],
      datasets: [
        {
          data: membershipData,
          backgroundColor: ["blue", "green", "red"],
        },
      ],
    },
    options: {
      responsive: true,
    },
  });

  // Initialize Age Distribution Chart
  const ctx3 = $("#ageDistributionChart");
  new Chart(ctx3, {
    type: "bar",
    data: {
      labels: ["18-25", "26-35", "36-50", "50+"],
      datasets: [
        {
          label: "Age Distribution",
          data: ageDistributionData,
          backgroundColor: "blue",
        },
      ],
    },
    options: {
      responsive: true,
    },
  });

  // Print button functionality
  $(".btn-danger").click(function () {
    window.print();
  });
});

alert("report.js loaded");
