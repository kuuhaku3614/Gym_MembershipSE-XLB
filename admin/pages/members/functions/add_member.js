$(document).ready(function () {
  // Initialize variables
  let selectedPrograms = [];
  let totalAmount = window.registrationFee;
  let totalProgramsFee = 0;
  let selectedSchedules = new Map(); // Track selected schedules with their time slots

  // Function to check if a schedule is already selected
  function isScheduleSelected(scheduleId, scheduleDay, startTime, endTime) {
    // For group schedules, just check the ID
    if (scheduleId && !startTime) {
      return selectedSchedules.has(scheduleId);
    }

    // For personal schedules, check only for time overlap on the same day
    for (const program of selectedPrograms) {
      if (program.day === scheduleDay) {
        const newStart = new Date(`2000-01-01 ${startTime}`);
        const newEnd = new Date(`2000-01-01 ${endTime}`);
        const existingStart = new Date(`2000-01-01 ${program.startTime}`);
        const existingEnd = new Date(`2000-01-01 ${program.endTime}`);

        // Check for time overlap
        if (newStart < existingEnd && newEnd > existingStart) {
          return true;
        }
      }
    }
    return false;
  }

  // Calculate and update all totals
  function updateTotalAmount() {
    // Start with registration fee
    let total = window.registrationFee;

    // Add plan price if selected
    const selectedPlan = $('input[name="membership_plan"]:checked');
    if (selectedPlan.length) {
      total += parseFloat(selectedPlan.data("price")) || 0;
    }

    // Add program and rental costs
    total += totalProgramsFee;
    $(".rental-service-checkbox:checked").each(function () {
      total += parseFloat($(this).data("price")) || 0;
    });

    // Update totals in both summary and review
    $(".totalAmount").text("₱" + total.toFixed(2));
    // Also update the registration fee display in the review section
   $(".review-registration-fee").text("₱ " + window.registrationFee.toFixed(2)); // Update review display
   // Update the total in the review section too
   $("#review-total-amount").text("₱" + total.toFixed(2));
    $(".review-programs-fee").text("₱" + totalProgramsFee.toFixed(2)); // Already present
   // Update rental fee display in review
    let totalRentalsFeeReview = 0;
    $('input[name="rental_services[]"]:checked').each(function() {
        totalRentalsFeeReview += parseFloat($(this).data('price')) || 0;
    });
    $('.review-rentals-fee').text("₱" + totalRentalsFeeReview.toFixed(2)); // Already present, ensure it's updated

    // Ensure registration fee amount in summary is correct (in case it changes dynamically later)
    $('.registration-fee-amount').text('₱' + window.registrationFee.toFixed(2));
    totalAmount = total;
  }

  // Handle plan selection
  $('input[name="membership_plan"]').change(function () {
    const selectedPlan = $(this);
    const planPrice = parseFloat(selectedPlan.data("price"));
    const planName = selectedPlan.data("name");
    const duration = selectedPlan.data("duration");
    const durationType = selectedPlan.data("duration-type");
    const startDate = $("#membership_start_date").val();

    // Update plan summary
    $("#selectedPlan").html(`
            <p>Plan: ${planName}</p>
            <p>Duration: ${duration} ${durationType}</p>
            <p>Price: ₱${planPrice.toFixed(2)}</p>
            <p>Start Date: ${startDate || "Not selected"}</p>
        `);

    // Update totals
    updateTotalAmount();

    // Update review section if visible
    if ($("#phase4").is(":visible")) {
      updateReviewInformation();
    }
  });

  // Initialize totals on page load
  updateTotalAmount();

  // Handle start date changes
  $("#membership_start_date").change(function () {
    const startDate = $(this).val();
    const selectedPlan = $("#selectedPlan");

    if (selectedPlan.length) {
      const lines = selectedPlan.html().split("</p>");
      lines[3] = `<p>Start Date: ${startDate || "Not selected"}`;
      selectedPlan.html(lines.join("</p>"));

      // Update totals
      updateTotalAmount();

      // Update review section if visible
      if ($("#phase4").is(":visible")) {
        updateReviewInformation();
      }
    }
  });

  // Handle program selection
  $(".program-card").click(function () {
    const card = $(this);
    const programId = card.data("program-id");

    if (card.hasClass("selected")) {
      // Remove program
      card.removeClass("selected");
      selectedPrograms = selectedPrograms.filter((p) => p.id !== programId);
    } else {
      // Add program
      card.addClass("selected");
    }

    // Always update summary after selection changes
    updateProgramsSummary();

    // Update review section if visible
    if ($("#phase4").is(":visible")) {
      updateReviewInformation();
    }
  });

  // Handle program coach selection
  $(".program-coach").on("change", function () {
    const coachProgramTypeId = $(this).val();
    if (!coachProgramTypeId) return;

    const programCard = $(this).closest(".program-card");
    const programName = programCard.find(".card-title").text().trim();
    const programType = programCard.data("program-type");

    // Reset dropdown to default immediately
    $(this).val("").find("option:first").prop("selected", true);

    // Store current program card for reference
    $("#scheduleModal").data("current-program-card", programCard);

    scheduleModal = new bootstrap.Modal(
      document.getElementById("scheduleModal")
    );

    // Pre-fetch data before showing modal
    $.ajax({
      url: `${BASE_URL}/admin/pages/members/add_member.php`,
      method: "GET",
      data: {
        action: "get_schedule",
        coach_program_type_id: coachProgramTypeId,
      },
      success: function (response) {
        const tableBody = $("#scheduleTableBody");
        const tableHead = $("#scheduleTableHead");
        const programDesc = $("#programDesc");

        // Clear previous content
        tableBody.empty();
        programDesc.empty();

        if (response.success && response.data && response.data.length > 0) {
          // Different headers for personal and group schedules
          if (response.program_type === "personal") {
            // For personal training, group time slots by day, duration, and price
            const scheduleGroups = {};

            // First, organize all time slots by day, duration, and price
            response.data.forEach((schedule) => {
              // Create a unique key for each combination
              const groupKey = `${schedule.day}-${schedule.duration_rate}-${schedule.price}-${schedule.coach_name}`;

              if (!scheduleGroups[groupKey]) {
                scheduleGroups[groupKey] = {
                  day: schedule.day,
                  duration: schedule.duration_rate,
                  price: schedule.price,
                  coach: schedule.coach_name,
                  slots: [],
                };
              }

              // Calculate time slots for this schedule
              const startTime = new Date(`2000-01-01 ${schedule.start_time}`);
              const endTime = new Date(`2000-01-01 ${schedule.end_time}`);
              const totalMinutes = (endTime - startTime) / 1000 / 60;
              const numSlots = Math.floor(
                totalMinutes / schedule.duration_rate
              );

              // Create slots for this schedule
              for (let i = 0; i < numSlots; i++) {
                const slotStart = new Date(
                  startTime.getTime() + i * schedule.duration_rate * 60000
                );
                const slotEnd = new Date(
                  slotStart.getTime() + schedule.duration_rate * 60000
                );

                scheduleGroups[groupKey].slots.push({
                  id: schedule.id,
                  startTime: slotStart.toLocaleTimeString("en-US", {
                    hour12: true,
                    hour: "2-digit",
                    minute: "2-digit",
                  }),
                  endTime: slotEnd.toLocaleTimeString("en-US", {
                    hour12: true,
                    hour: "2-digit",
                    minute: "2-digit",
                  }),
                });
              }
            });

            // Now create rows for each unique combination
            const rows = Object.values(scheduleGroups).map((group) => {
              const timeButtons = group.slots.map((slot) =>
                `<button class="btn btn-primary btn-sm select-schedule m-1" type="button"
                      data-id="${slot.id}"
                      data-type="personal"
                      data-coach-program-type-id="${coachProgramTypeId}"
                      data-program="${programName}"
                      data-coach="${group.coach}"
                      data-day="${group.day}"
                      data-starttime="${slot.startTime}"
                      data-endtime="${slot.endTime}"
                      data-price="${group.price}">
                      ${slot.startTime} - ${slot.endTime}
                  </button>`).join("");
                  return`
                      <tr>
                          <td>${group.day}</td>
                          <td>${group.duration}</td>
                          <td>₱${group.price}</td>
                          <td>${timeButtons}</td>
                      </tr>`;
            });

            // Sort rows by day, duration, and price
            rows.sort((a, b) => {
              const dayOrder = [
                "Monday",
                "Tuesday",
                "Wednesday",
                "Thursday",
                "Friday",
                "Saturday",
                "Sunday",
              ];
              const dayA = $(a).find("td:first").text();
              const dayB = $(b).find("td:first").text();
              return dayOrder.indexOf(dayA) - dayOrder.indexOf(dayB);
            });

            // Update table header for the new format
            tableHead.html(`
              <tr>
                <th>Day</th>
                <th>Duration (mins)</th>
                <th>Price</th>
                <th>Available Time Slots</th>
              </tr>
            `);

            tableBody.html(rows.join(""));
          } else {
            // Group schedule display
            const rows = response.data.map(
              (schedule) => `
                <tr>
                  <td>${schedule.day}</td>
                  <td>${schedule.start_time} - ${schedule.end_time}</td>
                  <td>${schedule.current_subscribers} / ${schedule.capacity}</td>
                  <td>₱${schedule.price}</td>
                  <td>
                    <button type="button" class="btn btn-sm btn-primary select-schedule"
                      data-id="${schedule.id}"
                      data-type="group"
                      data-coach-program-type-id="${coachProgramTypeId}"
                      data-program="${programName}"
                      data-coach="${schedule.coach_name}"
                      data-day="${schedule.day}"
                      data-starttime="${schedule.start_time}"
                      data-endtime="${schedule.end_time}"
                      data-price="${schedule.price}">
                      Select
                    </button>
                  </td>
                </tr>
              `
            ).join("");

            tableHead.html(`
              <tr>
                <th>Day</th>
                <th>Time</th>
                <th>Capacity</th>
                <th>Price</th>
                <th>Action</th>
              </tr>
            `);
            tableBody.html(rows);
          }
        } else {
          tableBody.html(
            '<tr><td colspan="6" class="text-center">No schedules found</td></tr>'
          );
          programDesc.hide();
        }

        // Clear any previous selections
        $(".schedule-row").removeClass("selected");

        // Reset modal title
        $("#scheduleModalLabel").text("Select Schedule");

        scheduleModal.show();
      },
      error: function (xhr, status, error) {
        console.error("Error fetching schedules:", error);
        console.error("Status:", status);
        console.error("Response:", xhr.responseText);
        $("#scheduleTableBody").html(
          '<tr><td colspan="6" class="text-center">Failed to load schedules</td></tr>'
        );
        $("#programDesc").hide();
        scheduleModal.show();
      },
    });
  });

  // Reset modal when hidden
  $("#scheduleModal").on("hidden.bs.modal", function () {
    // Clear modal content
    $("#scheduleTableBody").empty();
    $("#programDesc").empty().hide();

    // Reset any program-specific UI elements
    $(".program-specific-element").hide();

    // Update the modal title to default
    $("#scheduleModalLabel").text("Select Schedule");

    // Remove stored program card reference
    $(this).removeData("current-program-card");

    // Ensure all program coach dropdowns show default option
    $(".program-coach").each(function () {
      $(this).val("").find("option:first").prop("selected", true);
    });
  });

  // Handle rental service selection
  $(document).on("change", 'input[name="rental_services[]"]', function () {
    updateRentalServicesSummary();
    updateTotalAmount();
  });

  // Handle rental service removal
  $(document).on("click", ".remove-rental", function () {
    const rentalId = $(this).closest(".summary-row").data("rental-id");
    const checkbox = $(`input[name="rental_services[]"][value="${rentalId}"]`);

    // Uncheck the checkbox
    checkbox.prop("checked", false);

    // Remove selected class from the rental card
    checkbox.closest(".rental-option").removeClass("selected");

    // Remove from summary
    $(this).closest(".summary-row").remove();

    // Update totals
    updateTotalAmount();
  });

  // Handle program removal using event delegation
  $(document).on("click", ".remove-program", function (e) {
    e.preventDefault();
    e.stopPropagation();

    const summaryRow = $(this).closest(".summary-row");
    const index = summaryRow.data("index");
    const programId = summaryRow.data("program-id");

    if (typeof index !== "undefined" && programId) {
      // Remove program from array
      selectedPrograms = selectedPrograms.filter((program, i) => i !== index);

      // Reset the program card's selection state
      $(`.program-card[data-program-id="${programId}"]`).removeClass(
        "selected"
      );

      // Update UI
      updateProgramsSummary();

      // Update review section if visible
      if ($("#phase4").is(":visible")) {
        updateReviewInformation();
      }
    }
  });

  // Helper function to convert time string to minutes for comparison
  function timeToMinutes(timeStr) {
    const [time, period] = timeStr.split(" ");
    let [hours, minutes] = time.split(":").map(Number);

    // Convert to 24-hour format
    if (period === "PM" && hours !== 12) {
      hours += 12;
    } else if (period === "AM" && hours === 12) {
      hours = 0;
    }

    return hours * 60 + minutes;
  }

  // Function to check if two time ranges overlap
  function checkTimeOverlap(start1, end1, start2, end2) {
    const start1Mins = timeToMinutes(start1);
    const end1Mins = timeToMinutes(end1);
    const start2Mins = timeToMinutes(start2);
    const end2Mins = timeToMinutes(end2);

    // No overlap if one ends before or at the same time the other starts
    return !(end1Mins <= start2Mins || end2Mins <= start1Mins);
  }

  // Function to check if a schedule conflicts with existing selections
  function hasScheduleConflict(newSchedule) {
    return selectedPrograms.some((program) => {
      // Only check if it's the same day
      if (program.day === newSchedule.day) {
        return checkTimeOverlap(
          newSchedule.startTime,
          newSchedule.endTime,
          program.startTime,
          program.endTime
        );
      }
      return false;
    });
  }

  // Handle schedule selection using event delegation
  $(document).on("click", ".select-schedule", function (e) {
    e.preventDefault();
    e.stopPropagation();

    const button = $(this);
    const scheduleData = {
      id: button.data("id"),
      type: button.data("type"),
      coach_program_type_id: button.data("coach-program-type-id"),
      program: button.data("program"),
      coach: button.data("coach"),
      day: button.data("day"),
      startTime: button.data("starttime"),
      endTime: button.data("endtime"),
      price: parseFloat(button.data("price")),
    };

    console.log("Selected schedule data:", scheduleData);

    // Validate schedule selection
    if (!scheduleData.day || !scheduleData.startTime || !scheduleData.endTime) {
      alert("Invalid schedule data");
      return;
    }

    // Check if schedule is already selected
    if (
      isScheduleSelected(
        scheduleData.id,
        scheduleData.day,
        scheduleData.startTime,
        scheduleData.endTime
      )
    ) {
      alert(
        "This schedule time slot is already selected. Please choose a different time."
      );
      return;
    }

    // Add to selected schedules tracking
    if (scheduleData.id) {
      if (!selectedSchedules.has(scheduleData.id)) {
        selectedSchedules.set(scheduleData.id, []);
      }
      selectedSchedules.get(scheduleData.id).push({
        startTime: scheduleData.startTime,
        endTime: scheduleData.endTime,
      });
    }

    selectedPrograms.push(scheduleData);
    console.log("Updated selected programs:", selectedPrograms);
    updateProgramsSummary();
    updateTotalAmount();

    // Close the modal
    $("#scheduleModal").modal("hide");
  });

  // Handle program removal
  $(document).on("click", ".remove-program", function () {
    const index = $(this).data("index");
    if (index >= 0 && index < selectedPrograms.length) {
      const program = selectedPrograms[index];
      // Remove from selected schedules tracking
      if (program.id) {
        const schedule = selectedSchedules.get(program.id);
        if (schedule) {
          selectedSchedules.set(
            program.id,
            schedule.filter(
              (slot) =>
                slot.startTime !== program.startTime ||
                slot.endTime !== program.endTime
            )
          );
          if (selectedSchedules.get(program.id).length === 0) {
            selectedSchedules.delete(program.id);
          }
        }
      }
      selectedPrograms.splice(index, 1);
      updateProgramsSummary();
      updateTotalAmount();
    }
  });

  // Helper function to format date nicely
  function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
    });
  }

  // Function to update programs summary
  function updateProgramsSummary() {
    const programsContainer = $("#selectedProgramsContainer");
    let html = "";
    totalProgramsFee = 0; // Reset total

    // Rebuild array to ensure clean indices
    selectedPrograms = selectedPrograms.filter((program) => program !== null);

    selectedPrograms.forEach((program, index) => {
      // Skip invalid program data
      if (
        !program ||
        !program.id ||
        !program.coach_program_type_id ||
        !program.program ||
        !program.coach ||
        !program.day ||
        !program.startTime ||
        !program.endTime ||
        !program.price ||
        isNaN(program.price)
      ) {
        return;
      }

      const schedules = generateProgramDates(
        $("#membership_start_date").val(),
        calculateEndDate(
          $("#membership_start_date").val(),
          $('input[name="membership_plan"]:checked').data("duration"),
          $('input[name="membership_plan"]:checked').data("duration-type")
        ),
        program.day,
        program.startTime,
        program.endTime,
        program.price
      );

      totalProgramsFee += program.price * schedules.length;

      html += `
                <div class="summary-row" data-index="${index}" data-program-id="${
        program.id
      }">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">${program.program}</h5>
                        <button type="button" class="btn btn-sm btn-danger remove-program">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <p class="mb-1">Coach: ${program.coach}</p>
                    <p class="mb-1">Schedule: ${program.day} ${
        program.startTime
      } - ${program.endTime}</p>
                    <p class="mb-1">Price per Session: ₱${program.price}</p>
                    <p class="mb-1">Number of Sessions: ${schedules.length}</p>
                    <p class="mb-1">Total: ₱${(
                      program.price * schedules.length
                    ).toFixed(2)}</p>
                </div>
            `;
    });

    programsContainer.html(html || "<p>No programs selected</p>");
    updateTotalAmount();
  }

  // Update review information
  function updateReviewInformation() {
    // Account Information
    $("#review-username").text($("#username").val());

    // Personal Information
    const fullName = [
      $("#first_name").val(),
      $("#middle_name").val(),
      $("#last_name").val(),
    ]
      .filter(Boolean)
      .join(" ");

    $("#review-name").text(fullName);
    $("#review-sex").text(
      $('input[name="sex"]:checked').val() || "Not selected"
    );
    $("#review-birthdate").text($("#birthdate").val() || "Not selected");
    $("#review-contact").text($("#contact").val() || "Not selected");

    // Membership Plan Review
    const selectedPlan = $('input[name="membership_plan"]:checked');
    if (selectedPlan.length) {
      const planName = selectedPlan.data("name");
      const duration = selectedPlan.data("duration");
      const durationType = selectedPlan.data("duration-type");
      const price = parseFloat(selectedPlan.data("price"));
      const startDate = $("#membership_start_date").val();

      // Update plan summary
      $("#selectedPlan").html(`
                <p>Plan: ${planName}</p>
                <p>Duration: ${duration} ${durationType}</p>
                <p>Price: ₱${price.toFixed(2)}</p>
                <p>Start Date: ${startDate || "Not selected"}</p>
            `);

      // Update review section
      $("#review-membership").show();
      $("#review-plan").text(planName);
      $("#review-duration").text(duration + " " + durationType);
      $("#review-start-date").text(startDate);
      $("#review-end-date").text(
        calculateEndDate(startDate, duration, durationType)
      );
      $(".review-price").text("₱ " + price.toFixed(2));
      $("#review-membership-fee").text("₱" + window.registrationFee.toFixed(2));
    } else {
      $("#review-membership").hide();
    }

    // Programs Review
    const reviewProgramsContainer = $("#review-programs");
    let reviewHtml = "";
    let totalProgramsFee = 0;

    selectedPrograms.forEach((program, index) => {
      // Skip invalid program data
      if (
        !program ||
        !program.id ||
        !program.program ||
        !program.coach ||
        !program.day ||
        !program.startTime ||
        !program.endTime ||
        !program.price ||
        isNaN(program.price)
      ) {
        return;
      }

      const schedules = generateProgramDates(
        $("#membership_start_date").val(),
        calculateEndDate(
          $("#membership_start_date").val(),
          selectedPlan.data("duration"),
          selectedPlan.data("duration-type")
        ),
        program.day,
        program.startTime,
        program.endTime,
        program.price
      );

      reviewHtml += `<div class="program-item d-flex justify-content-between align-items-start mb-2">
                  <div>
                    <div class="program-title">${program.program}</div>
                    <div class="program-details">
                      Type: ${program.type.charAt(0).toUpperCase() + program.type.slice(1)} Program<br>
                      Coach: ${program.coach}<br>
                      Schedule: Every ${program.day}, ${program.startTime} - ${program.endTime}<br>
                      Dates: ${schedules.map((s) => formatDate(s.date)).join(", ")}<br>
                      Price: ₱${parseFloat(program.price).toFixed(2)} × ${schedules.length} = ₱${(program.price * schedules.length).toFixed(2)}
                    </div>
                  </div>
                  <button type="button" class="btn btn-link text-danger remove-program-review p-0 ms-2 mt-1" title="Remove" style="font-size: 1.2rem; align-self: flex-start;">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
            `;

      totalProgramsFee += program.price * schedules.length;
    });

    reviewProgramsContainer.html(
      reviewHtml || '<p class="text-muted">No programs selected</p>'
    );
    $(".review-programs-fee").text(" ₱ " + totalProgramsFee.toFixed(2));
    $("#review-programs").toggle(selectedPrograms.length > 0);

    // Rental Services Review
    $("#review-rentals-list").empty();
    let totalRentalsFee = 0;
    $('input[name="rental_services[]"]:checked').each(function () {
      const rental = {
        name: $(this).data("name"),
        duration: $(this).data("duration"),
        durationType: $(this).data("duration-type"),
        price: parseFloat($(this).data("price")),
      };
      const startDate = new Date().toISOString().split("T")[0];
      const endDate = calculateEndDate(
        startDate,
        rental.duration,
        rental.durationType
      );

      const rentalHtml = `<div class="rental-item review-rental d-flex justify-content-between align-items-start mb-2" data-rental-id="${$(this).val()}">
                      <div>
                        <div class="program-title">${rental.name}</div>
                        <div class="program-details">
                          Duration: ${rental.duration} ${rental.durationType}<br>
                          Start Date: ${startDate}<br>
                          End Date: ${endDate}<br>
                          Price: ₱${rental.price.toFixed(2)}
                        </div>
                      </div>
                      <button type="button" class="btn btn-link text-danger remove-rental-review p-0 ms-2 mt-1" title="Remove" style="font-size: 1.2rem; align-self: flex-start;">
                        <i class="fas fa-times"></i>
                      </button>
                    </div>
                  `;
      $("#review-rentals-list").append(rentalHtml);
      totalRentalsFee += rental.price;
    });
    $(".review-rentals-fee").text(" ₱ " + totalRentalsFee.toFixed(2));
    $("#review-rentals").toggle(
      $('input[name="rental_services[]"]:checked').length > 0
    );

    let registrationReviewText = "₱ " + window.registrationFee.toFixed(2);
     if (window.registrationDuration > 0 && window.registrationDurationType) {
        let durationTypeDisplay = (window.registrationDuration === 1) ? window.registrationDurationType.replace(/s$/, '') : window.registrationDurationType;
        registrationReviewText += ` (${window.registrationDuration} ${durationTypeDisplay})`;
     }
     // Update all places showing registration fee in review
     $('.review-registration-fee').text(registrationReviewText);
    updateTotalAmount();
  }

  // Function to update rental services summary
  function updateRentalServicesSummary() {
    $(".rental-services-summary").empty();

    $('input[name="rental_services[]"]:checked').each(function () {
      const rentalId = $(this).val();
      const rentalName = $(this).data("name");
      const rentalPrice = parseFloat($(this).data("price"));
      const rentalDuration = $(this).data("duration");
      const rentalDurationType = $(this).data("duration-type");

      const rentalHtml = `
  <div class="summary-row d-flex justify-content-between align-items-start mb-2" data-type="rental" data-rental-id="${rentalId}">
    <div>
      <h5 class="mb-0">${rentalName}</h5>
      <div class="details">
        <p class="mb-1"><strong>Duration:</strong> ${rentalDuration} ${rentalDurationType}</p>
        <p class="mb-1"><strong>Amount:</strong> ₱${rentalPrice.toFixed(2)}</p>
      </div>
    </div>
    <button type="button" class="btn btn-link text-danger remove-rental p-0 ms-2 mt-1" style="font-size: 1.2rem; align-self: flex-start;">
      <i class="fas fa-times"></i>
    </button>
  </div>
`;

      $(".rental-services-summary").append(rentalHtml);
    });
  }

  // Handle rental service card selection
  $(document).on("click", ".rental-option", function () {
    const checkbox = $(this).find('input[type="checkbox"]');
    checkbox.prop("checked", !checkbox.prop("checked"));
    $(this).toggleClass("selected");
    updateTotalAmount();
    updateRentalServicesSummary();
  });

  // Handle program removal in review phase
  $(document).on("click", ".remove-program-review", function () {
    const programDiv = $(this).closest(".program-item");
    const index = selectedPrograms.findIndex(
      (program) => program.program === programDiv.find(".program-title").text()
    );

    // Remove program from array
    if (typeof index !== "undefined") {
      selectedPrograms = selectedPrograms.filter((_, i) => i !== index);

      // Remove from UI
      programDiv.remove();

      // Update program cards and summary
      updateProgramsSummary();
      updateTotalAmount();

      // Hide section if no programs left
      if (selectedPrograms.length === 0) {
        $("#review-programs").hide();
      }
    }
  });

  // Handle rental removal in review phase
  $(document).on("click", ".remove-rental-review", function () {
    const rentalDiv = $(this).closest(".review-rental");
    const rentalId = rentalDiv.data("rental-id");

    // Uncheck the checkbox
    const checkbox = $(`input[name="rental_services[]"][value="${rentalId}"]`);
    checkbox.prop("checked", false);

    // Remove selected class from rental card
    checkbox.closest(".rental-option").removeClass("selected");

    // Remove from UI
    rentalDiv.remove();

    // Calculate total rental fees
    let totalRentalsFee = 0;
    $('input[name="rental_services[]"]:checked').each(function () {
      totalRentalsFee += parseFloat($(this).data("price")) || 0;
    });

    // Update rental fees display everywhere
    $(".rental-amount, .total-rentals-fee, .review-rentals-fee").text(
      "₱ " + totalRentalsFee.toFixed(2)
    );

    // Hide rentals fee row if no rentals
    if (totalRentalsFee === 0) {
      $(".rental-fee-row").hide();
    }

    // Update summary and totals
    updateRentalServicesSummary();
    updateTotalAmount();

    // Hide section if no rentals left
    if ($(".review-rental").length === 0) {
      $("#review-rentals-section").hide();
    }
  });

  // Form submission handler
  $("#memberForm").on("submit", function (e) {
    e.preventDefault();

    // Validate username and password before submission
    const username = $("#username").val();
    const password = $("#password").val();

    if (!username || !password) {
      alert("Please enter both username and password to complete registration");
      return;
    }

    // Validate membership plan selection
    const selectedPlan = $('input[name="membership_plan"]:checked');
    if (!selectedPlan.length) {
      alert("Please select a membership plan");
      return;
    }

    const membershipStartDate = $("#membership_start_date").val();
    if (!membershipStartDate) {
      alert("Please select a start date for the membership plan");
      return;
    }

    // Validate program data before submission
    const validPrograms = selectedPrograms.filter((program) => {
      if (
        !program ||
        !program.id ||
        !program.coach_program_type_id ||
        !program.program ||
        !program.coach ||
        !program.day ||
        !program.startTime ||
        !program.endTime ||
        !program.price ||
        isNaN(program.price)
      ) {
        console.error("Invalid program data:", program);
        return false;
      }
      return true;
    });

    if (selectedPrograms.length !== validPrograms.length) {
      alert(
        "Some selected programs have invalid data. Please try removing and re-adding them."
      );
      return;
    }

    const formData = new FormData(this);
    formData.append("action", "add_member");

    // Calculate end date
    const duration = selectedPlan.data("duration");
    const durationType = selectedPlan.data("duration-type");
    const membershipEndDate = calculateEndDate(
      membershipStartDate,
      duration,
      durationType
    );

    // Generate program schedules with validated data
    const programsWithSchedules = validPrograms.map((program) => {
      const schedules = generateProgramDates(
        membershipStartDate,
        membershipEndDate,
        program.day,
        program.startTime,
        program.endTime,
        program.price
      );

      return {
        id: program.id,
        type: program.type,
        coach_program_type_id: program.coach_program_type_id,
        schedules: schedules,
      };
    });

    formData.append("selected_programs", JSON.stringify(programsWithSchedules));
    formData.append("membership_start_date", membershipStartDate);
    formData.append("membership_end_date", membershipEndDate);

    // Log form data for debugging
    console.log("=== FORM SUBMISSION DEBUG ===");
    for (let pair of formData.entries()) {
      console.log(pair[0] + ":", pair[0] === "password" ? "[HIDDEN]" : pair[1]);
    }

    // Submit form
    $.ajax({
      url: BASE_URL + "/admin/pages/members/add_member.php",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        console.log("Raw server response:", response);
        try {
          // Parse response if it's a string
          if (typeof response === "string") {
            response = JSON.parse(response);
          }

          if (response.success) {
            Swal.fire({
              icon: "success",
              title: "Success",
              text: "Member registration successful!",
            }).then(() => {
              window.location.href = BASE_URL + "/admin/members_new";
            });
            window.location.href = BASE_URL + "/admin/members_new";
          } else {
            alert(
              "Error: " + (response.message || "Failed to register member")
            );
          }
        } catch (e) {
          console.error("Error parsing response:", e);
          console.error("Raw response:", response);
          alert(
            "An error occurred while processing the server response. Check the console for details."
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", error);
        console.error("Status:", status);
        console.error("Response:", xhr.responseText);
        console.error(
          "URL used:",
          BASE_URL + "/admin/pages/members/add_member.php"
        );
        alert(
          "An error occurred while processing your request. Check the console for details."
        );
      },
    });
  });

  // Function to generate program dates
  function generateProgramDates(
    startDate,
    endDate,
    dayOfWeek,
    startTime,
    endTime,
    price
  ) {
    const schedules = [];
    const start = new Date(startDate);
    const end = new Date(endDate);

    // Map day names to numbers (0 = Sunday, 6 = Saturday)
    const dayMap = {
      Sunday: 0,
      Monday: 1,
      Tuesday: 2,
      Wednesday: 3,
      Thursday: 4,
      Friday: 5,
      Saturday: 6,
    };

    const targetDay = dayMap[dayOfWeek];

    // Iterate through each day in the date range
    for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
      // Check if current day matches the target day of week
      if (d.getDay() === targetDay) {
        schedules.push({
          date: d.toISOString().split("T")[0],
          day: dayOfWeek,
          start_time: startTime,
          end_time: endTime,
          amount: price,
        });
      }
    }

    return schedules;
  }

  // Function to generate random numbers of specified length
  function generateRandomNumbers(length) {
    let result = "";
    for (let i = 0; i < length; i++) {
      result += Math.floor(Math.random() * 10);
    }
    return result;
  }

  // Function to generate default credentials
  function generateDefaultCredentials() {
    const firstName = $("#first_name").val().toLowerCase();
    if (firstName) {
      // Generate username: firstname + 4 random numbers
      const randomNum = generateRandomNumbers(4);
      const username = firstName + randomNum;

      // Generate password: 6 random numbers
      const password = generateRandomNumbers(6);

      // Set the values
      $("#username").val(username);
      $("#password").val(password);

      // Update the review section
      $("#review-username").text(username);
      $("#review-password").text("******");
    }
  }

  // Function to calculate end date based on start date and duration
  function calculateEndDate(startDate, duration, durationType) {
    if (!startDate) return "";

    try {
      const start = new Date(startDate);
      if (isNaN(start.getTime())) {
        console.error("Invalid start date:", startDate);
        return "";
      }

      const durationNum = parseInt(duration);
      if (isNaN(durationNum)) {
        console.error("Invalid duration:", duration);
        return "";
      }

      const end = new Date(start);
      switch (durationType.toLowerCase()) {
        case "day":
        case "days":
          end.setDate(end.getDate() + durationNum);
          break;
        case "month":
        case "months":
          end.setMonth(end.getMonth() + durationNum);
          break;
        case "year":
        case "years":
          end.setFullYear(end.getFullYear() + durationNum);
          break;
        default:
          console.error("Invalid duration type:", durationType);
          return "";
      }

      // Format date as YYYY-MM-DD
      return end.toISOString().split("T")[0];
    } catch (error) {
      console.error("Error calculating end date:", error);
      return "";
    }
  }

  // Update membership plan selection with end date
  $('input[name="membership_plan"]').change(function () {
    if ($(this).is(":checked")) {
      const planName = $(this).data("name");
      const duration = $(this).data("duration");
      const durationType = $(this).data("duration-type");
      const price = parseFloat($(this).data("price"));
      const startDate = $("#membership_start_date").val();

      // Update summary section
      $('.summary-row[data-type="membership"]').show();
      $(".membership-plan-name").text(planName);
      $(".membership-duration").text(duration + " " + durationType);
      $(".membership-start-date").text(startDate);
      $(".membership-end-date").text(
        calculateEndDate(startDate, duration, durationType)
      );
      $(".membership-amount").text(price.toFixed(2));

      // Update review section
      $("#review-membership").show();
      $("#review-plan").text(planName);
      $("#review-duration").text(duration + " " + durationType);
      $("#review-start-date").text(startDate);
      $("#review-end-date").text(
        calculateEndDate(startDate, duration, durationType)
      );
      $(".review-price").text("₱ " + price.toFixed(2));
      $("#review-membership-fee").text("₱" + window.registrationFee.toFixed(2));
    } else {
      $('.summary-row[data-type="membership"]').hide();
      $("#review-membership").hide();
    }

    updateTotalAmount();
  });

  // Update membership dates when start date changes
  $("#membership_start_date").change(function () {
    const selectedPlan = $('input[name="membership_plan"]:checked');
    if (selectedPlan.length) {
      const startDate = $(this).val();
      const duration = selectedPlan.data("duration");
      const durationType = selectedPlan.data("duration-type");
      const endDate = calculateEndDate(startDate, duration, durationType);

      if (endDate) {
        $(".membership-start-date").text(startDate);
        $(".membership-end-date").text(endDate);
        $("#review-start-date").text(startDate);
        $("#review-end-date").text(endDate);
      }
    }
  });

  // Membership Summary Minimize/Maximize Logic (mirroring renew_member.js)
  function setExpanded(state) {
    const $summary = $('.membership-summary');
    const $showBtn = $('#show-summary-btn');
    const $toggleBtn = $('#toggle-summary-btn');
    const $toggleIcon = $('#toggle-summary-icon');
    const $summaryContent = $('#membership-summary-content');
    if (state) {
      $summary.show();
      $showBtn.hide();
      $summaryContent.show();
      $toggleIcon && $toggleIcon.css('transform', 'rotate(0deg)');
      localStorage.setItem('summaryMinimized', 'false');
    } else {
      $summary.hide();
      $showBtn.show();
      localStorage.setItem('summaryMinimized', 'true');
    }
  }

  function restoreSummaryState() {
    const minimized = localStorage.getItem('summaryMinimized');
    setExpanded(minimized !== 'true');
  }

  $('#toggle-summary-btn').on('click', function() {
    setExpanded(false);
  });
  $('#show-summary-btn').on('click', function() {
    setExpanded(true);
  });
  // Always show summary as open on page load (match renew_member.js)
  localStorage.setItem('summaryMinimized', 'false');
  restoreSummaryState();

// Handle phase navigation
  function showPhase(phaseNumber) {
    // Hide all phases
    $(".phase").hide();

    // Show the selected phase
    $(`#phase${phaseNumber}`).show();

    // Update navigation pills
    $(".nav-link").removeClass("active");
    $(`.nav-link[data-phase="${phaseNumber}"]`).addClass("active");

    // Update progress indicators
    $(".step").removeClass("active completed");
    $(`.step:lt(${phaseNumber})`).addClass("completed");
    $(`.step:eq(${phaseNumber - 1})`).addClass("active");

    // Generate default credentials when reaching phase 4
    if (phaseNumber === 4) {
      generateDefaultCredentials();
      // Hide the membership summary section in phase 4
      $(".membership-summary").hide();
      $('#show-summary-btn').hide();
    } else {
      // Restore summary minimized/maximized state from localStorage
      restoreSummaryState();
    }

    // Update buttons
    updateButtons(phaseNumber);

    // Update current phase
    currentPhase = phaseNumber;
  }

  // Update button visibility based on phase
  function updateButtons(phaseNumber) {
    // Hide all buttons first
    $("#prevBtn, #nextBtn, #reviewBtn, #submitBtn").hide();

    // Show/hide buttons based on phase
    if (phaseNumber > 1) {
      $("#prevBtn").show();
    }

    if (phaseNumber < 3) {
      $("#nextBtn").show();
    } else if (phaseNumber === 3) {
      $("#nextBtn").hide();
      $("#reviewBtn").show();
    } else if (phaseNumber === 4) {
      $("#prevBtn").hide();
      $("#submitBtn").show();
    }
  }

  // Initialize form
  $(document).ready(function () {
    // Listen for changes to first name and regenerate credentials if in phase 4
    $("#first_name").on("change", function () {
      if (currentPhase === 4) {
        generateDefaultCredentials();
      }
    });

    // Show first phase initially
    showPhase(1);

    // Handle next button click for phase 1
    $("#nextBtn").click(function () {
      const currentPhase = parseInt(
        $(".phase:visible").attr("id").replace("phase", "")
      );

      // Phase 1 validation
      if (currentPhase === 1) {
        // Validate required fields
        const firstName = $("#first_name").val();
        const lastName = $("#last_name").val();
        const sex = $('input[name="sex"]:checked').val();
        const birthdate = $("#birthdate").val();
        const contact = $("#contact").val();

        // Remove any existing validation styles
        $(".is-invalid").removeClass("is-invalid");

        let isValid = true;
        let missingFields = [];

        if (!firstName) {
          $("#first_name").addClass("is-invalid");
          missingFields.push("First Name");
          isValid = false;
        }

        if (!lastName) {
          $("#last_name").addClass("is-invalid");
          missingFields.push("Last Name");
          isValid = false;
        }

        if (!sex) {
          $('input[name="sex"]').addClass("is-invalid");
          missingFields.push("Sex");
          isValid = false;
        }

        if (!birthdate) {
          $("#birthdate").addClass("is-invalid");
          missingFields.push("Birth Date");
          isValid = false;
        } else {
          const selectedDate = new Date(birthdate);
          const today = new Date();
          today.setHours(0, 0, 0, 0); // Reset time part for accurate date comparison

          // Calculate the date 15 years ago from today
          const minBirthdate = new Date(today);
          minBirthdate.setFullYear(today.getFullYear() - 15);

          if (selectedDate >= today) {
            $("#birthdate").addClass("is-invalid");
            $("#birthdate")
              .next(".invalid-feedback")
              .text("Birth date cannot be today or in the future");
            isValid = false;
          } else if (selectedDate > minBirthdate) {
            $("#birthdate").addClass("is-invalid");
            $("#birthdate")
              .next(".invalid-feedback")
              .text("Member must be at least 15 years old");
            isValid = false;
          } else {
            $("#birthdate").removeClass("is-invalid");
          }
        }

        if (!contact) {
          $("#contact").addClass("is-invalid");
          missingFields.push("Contact Number");
          isValid = false;
        } else {
          const contactRegex = /^09\d{9}$/;
          if (!contactRegex.test(contact)) {
            $("#contact").addClass("is-invalid");
            $("#contact")
              .next(".invalid-feedback")
              .text("Please enter a valid 11-digit contact number");
            isValid = false;
          } else {
            $("#contact").removeClass("is-invalid");
          }
        }

        if (!isValid) {
          return;
        }
      }

      // Phase 2 validation
      if (currentPhase === 2) {
        // Check if a membership plan is selected
        if (!$('input[name="membership_plan"]:checked').length) {
          alert("Please select a membership plan");
          return;
        }

        // Check if start date is selected
        if (!$("#membership_start_date").val()) {
          alert("Please select a start date for the membership plan");
          return;
        }
      }

      showPhase(currentPhase + 1);
    });

    // Handle previous button
    $("#prevBtn").click(function () {
      const currentPhase = parseInt(
        $(".phase:visible").attr("id").replace("phase", "")
      );
      showPhase(currentPhase - 1);
    });

    // Handle review button
    $("#reviewBtn").click(function () {
      updateReviewInformation();
      showPhase(4);
    });

    // Handle back to programs button
    $("#backToProgramsBtn").click(function () {
      showPhase(3);
    });
  });
});

// Function to handle membership plan selection
function selectMembershipPlan(card) {
  // Remove selected class from all cards
  document
    .querySelectorAll(".membership-option")
    .forEach((c) => c.classList.remove("selected"));

  // Add selected class to clicked card
  card.classList.add("selected");

  // Find and check the radio button inside the card
  const radio = card.querySelector('input[type="radio"]');
  radio.checked = true;

  // Trigger any existing change handlers
  $(radio).trigger("change");
}
