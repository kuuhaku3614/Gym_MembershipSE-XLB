$(document).ready(function () {
  $("#walkInTable").DataTable({
    pageLength: 10,
    ordering: false,
    responsive: true,
    dom: '<"row"<l f>>rtip',
  });

  // Refresh button action
  $("#refreshBtn").click(function () {
    location.reload();
  });

  // Pay button action
  $(".btn-pay").click(function () {
    let row = $(this).closest("tr");
    row
      .find(".payment-unpaid")
      .text("Paid")
      .removeClass("payment-unpaid")
      .addClass("payment-paid");
    $(this).remove();
  });

  // Walk in button action
  $('.btn-primary:contains("Walk in")').click(function () {
    let row = $(this).closest("tr");
    row
      .find(".status-pending")
      .text("Walked-in")
      .removeClass("status-pending")
      .addClass("status-walked-in");
    $(this).remove();
  });

  // Remove button action
  $(".btn-danger").click(function () {
    $(this).closest("tr").remove();
  });
});
