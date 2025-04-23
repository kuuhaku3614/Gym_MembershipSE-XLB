// renew_member.js
// Adapted from add_member.js for the renewal process. Phase 1 logic is removed.

$(document).ready(function () {
  let currentPhase = 2;
  let selectedPrograms = [];
  let selectedRentals = [];
  showPhase(currentPhase);

  // Phase navigation
  $(".nav-link").on("click", function (e) {
    e.preventDefault();
    const phase = parseInt($(this).data("phase"));
    showPhase(phase);
  });

  $("#nextBtn").on("click", function () {
    if (validatePhase(currentPhase)) {
      if (currentPhase < 4) {
        currentPhase++;
        showPhase(currentPhase);
      }
    }
  });

  $("#prevBtn").on("click", function () {
    if (currentPhase > 2) {
      currentPhase--;
      showPhase(currentPhase);
    }
  });

  $("#reviewBtn").on("click", function () {
    if (validatePhase(currentPhase)) {
      updateReviewInformation();
      showPhase(4);
    }
  });

  // Handle Back to Programs button in phase 4
  $(document).on("click", "#backToProgramsBtn", function () {
    showPhase(3);
  });

  // Handle Renew Membership submission in phase 4
  $(document).on("click", "#renewSubmitBtn", function () {
    // Collect all checked rental IDs
    selectedRentals = [];
    $('input[name="rental_services[]"]:checked').each(function () {
      selectedRentals.push($(this).val());
    });
    "Selected rentals before submit:", selectedRentals;
    // Collect all renewal data
    // Prepare selected programs with schedules for backend
    var plan = $('input[name="membership_plan"]:checked');
    var startDate = $("#membership_start_date").val();
    var endDate = null;
    if (plan.length && startDate) {
      var duration = plan.data("duration");
      var durationType = plan.data("duration-type");
      endDate = calculateEndDate(startDate, duration, durationType);
    }
    var programsWithSchedules = (selectedPrograms || []).map(function (
      program
    ) {
      // Only generate schedules if all needed fields are present
      if (
        program &&
        program.day &&
        program.startTime &&
        program.endTime &&
        program.price &&
        startDate &&
        endDate
      ) {
        var schedules = generateProgramDates(
          startDate,
          endDate,
          program.day,
          program.startTime,
          program.endTime,
          program.price,
          program.type,
          program.id
        );
        return Object.assign({}, program, { schedules: schedules });
      }
      return program;
    });
    var data = {
      action: "renew_member",
      member_id: $("#member_id").val(),
      membership_plan: $('input[name="membership_plan"]:checked').val(),
      membership_start_date: startDate,
      selected_programs: JSON.stringify(programsWithSchedules),
      selected_rentals: JSON.stringify(selectedRentals || []),
    };

    $.ajax({
      url: BASE_URL + "/admin/pages/members/renew_member.php",
      type: "POST",
      data: data,
      dataType: "json",
      success: function (response) {
        if (response.success) {
          Swal.fire({
            icon: "success",
            title: "Success",
            text: "Member registration successful!",
          }).then(() => {
            window.location.href = BASE_URL + "/admin/members_new";
          });
        } else {
          alert("Renewal failed: " + (response.message || "Unknown error"));
        }
      },
      error: function (xhr) {
        alert("An error occurred while renewing membership.");
        "AJAX error:",
          {
            status: xhr.status,
            statusText: xhr.statusText,
            responseText: xhr.responseText,
            requestPayload: data,
          };
      },
    });
  });

  // Update review information for phase 4 (mirrors add_member.js)
  function updateReviewInformation() {
    // Membership Plan
    const selectedPlan = $('input[name="membership_plan"]:checked');
    if (selectedPlan.length) {
      const planName = selectedPlan.data("name");
      const duration = selectedPlan.data("duration");
      const durationType = selectedPlan.data("duration-type");
      const price = parseFloat(selectedPlan.data("price"));
      const startDate = $("#membership_start_date").val();
      const endDate = calculateEndDate(startDate, duration, durationType);
      $("#review-plan").text(planName);
      $("#review-duration").text(duration + " " + durationType);
      $("#review-start-date").text(startDate);
      $("#review-end-date").text(endDate);
      $(".review-price").text("₱ " + price.toFixed(2));
      $(".review-registration-fee").text(
        "₱ " + (window.registrationFee || 0).toFixed(2)
      );
    } else {
      $(
        "#review-plan, #review-duration, #review-start-date, #review-end-date"
      ).text("");
      $(".review-price, .review-registration-fee").text("₱ 0.00");
    }

    // Programs
    let programsHtml = "";
    let totalPrograms = 0;
    selectedPrograms.forEach(function (program, index) {
      if (!program || !program.program) return;
      // Calculate session dates
      const plan = $('input[name="membership_plan"]:checked');
      const startDate = $("#membership_start_date").val();
      let sessionDates = [];
      let numSessions = 1;
      if (plan.length && startDate) {
        const duration = plan.data("duration");
        const durationType = plan.data("duration-type");
        const endDate = calculateEndDate(startDate, duration, durationType);
        sessionDates = generateProgramDates(
          startDate,
          endDate,
          program.day,
          program.startTime,
          program.endTime,
          program.price
        ).map((s) => s.date);
        numSessions = sessionDates.length;
      }
      const total = program.price * numSessions;
      totalPrograms += total;
      programsHtml += `<div class="program-item mb-2" data-index="${index}">
                <div class="program-title">${program.program}</div>
                <div class="program-details">
                    Coach: ${program.coach}<br>
                    Schedule: ${program.day} ${program.startTime} - ${
        program.endTime
      }<br>
                    Price per Session: ₱${program.price}<br>
                    Number of Sessions: ${numSessions}<br>
                    <strong>Session Dates:</strong> ${
                      sessionDates.length ? sessionDates.join(", ") : "N/A"
                    }<br>
                    Total: ₱${total.toFixed(2)}
                </div>
                <button type="button" class="btn btn-link text-danger remove-program-review p-0" title="Remove" style="font-size: 1.2rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>`;
    });
    $("#review-programs-list").html(
      programsHtml || "<p>No programs selected</p>"
    );
    $(".review-programs-fee").text("₱ " + totalPrograms.toFixed(2));

    // Attach remove handler for review phase
    $(".remove-program-review")
      .off("click")
      .on("click", function () {
        const index = $(this).closest(".program-item").data("index");
        if (typeof index !== "undefined") {
          selectedPrograms.splice(index, 1);
          updateReviewInformation();
          updateProgramsSummary();
        }
      });

    // Rentals
    let rentalsHtml = "";
    let totalRentals = 0;
    $('input[name="rental_services[]"]:checked').each(function () {
      const rentalName = $(this).data("name");
      const rentalPrice = parseFloat($(this).data("price"));
      const rentalDuration = $(this).data("duration");
      const rentalDurationType = $(this).data("duration-type");
      totalRentals += rentalPrice;
      rentalsHtml += `<div class="rental-item review-rental mb-2" data-rental-id="${$(
        this
      ).val()}">
                <div class="program-title">${rentalName}</div>
                <div class="program-details">
                    Duration: ${rentalDuration} ${rentalDurationType}<br>
                    Amount: ₱${rentalPrice.toFixed(2)}
                </div>
                <button type="button" class="btn btn-link text-danger remove-rental-review p-0" title="Remove" style="font-size: 1.2rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>`;
    });
    $("#review-rentals-list").html(rentalsHtml || "<p>No rentals selected</p>");
    $(".review-rentals-fee").text("₱ " + totalRentals.toFixed(2));

    // Attach remove handler for review phase
    $(".remove-rental-review")
      .off("click")
      .on("click", function () {
        const rentalId = $(this).closest(".review-rental").data("rental-id");
        $(`input[name='rental_services[]'][value='${rentalId}']`).prop(
          "checked",
          false
        );
        selectedRentals = selectedRentals.filter((id) => id != rentalId);
        updateReviewInformation();
        updateRentalServicesSummary();
        updateTotalAmount();
      });
  }

  // Membership plan selection
  $(".membership-option").on("click", function () {
    $(".membership-option").removeClass("selected");
    $(this).addClass("selected");
    $(this).find('input[type="radio"]').prop("checked", true);
    updateTotalAmount();
  });

  // Global function for inline onclick
  window.selectMembershipPlan = function (el) {
    $(".membership-option").removeClass("selected");
    $(el).addClass("selected");
    $(el).find('input[type="radio"]').prop("checked", true);
    updateSummaryMembershipPlan();
    updateTotalAmount();
  };

  // Update summary section when a plan is selected
  function updateSummaryMembershipPlan() {
    const selectedPlan = $('input[name="membership_plan"]:checked');
    if (selectedPlan.length) {
      const planName = selectedPlan.data("name");
      const duration = selectedPlan.data("duration");
      const durationType = selectedPlan.data("duration-type");
      const price = parseFloat(selectedPlan.data("price"));
      const startDate = $("#membership_start_date").val();
      const endDate = calculateEndDate(startDate, duration, durationType);

      // Update summary section as HTML block (like add_member.js)
      $("#selectedPlan").html(`
                <p>Plan: ${planName}</p>
                <p>Duration: ${duration} ${durationType}</p>
                <p>Price: ₱${price.toFixed(2)}</p>
                <p>Start Date: ${startDate || "Not selected"}</p>
                <p>End Date: ${endDate || "Not calculated"}</p>
            `);
      $('.summary-row[data-type="membership"]').show();
    } else {
      $("#selectedPlan").empty();
      $('.summary-row[data-type="membership"]').hide();
    }
  }

  // Also update summary when radio changes or date changes
  $('input[name="membership_plan"]').on("change", function () {
    updateSummaryMembershipPlan();
  });
  $("#membership_start_date").on("change", function () {
    updateSummaryMembershipPlan();
  });

  // Show summary if plan is preselected on load
  updateSummaryMembershipPlan();

  // --- PHASE 3: Programs - Handle Coach Selection (Show Schedules) ---
  // Track selected programs and schedules for renewal
  // Use the local selectedPrograms defined above
  let selectedSchedules = new Map();
  let totalProgramsFee = 0;

  // Function to update programs summary (mirrors add_member.js)
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
      // Calculate number of sessions based on membership plan duration
      const plan = $('input[name="membership_plan"]:checked');
      const startDate = $("#membership_start_date").val();
      let numSessions = 1;
      if (plan.length && startDate) {
        // Estimate end date
        const duration = plan.data("duration");
        const durationType = plan.data("duration-type");
        const endDate = calculateEndDate(startDate, duration, durationType);
        numSessions = generateProgramDates(
          startDate,
          endDate,
          program.day,
          program.startTime,
          program.endTime,
          program.price
        ).length;
      }
      const total = program.price * numSessions;
      totalProgramsFee += total;
      html += `
                <div class="summary-row mb-2" data-index="${index}" data-program-id="${
        program.id
      }">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${program.program}</strong><br>
                            Coach: ${program.coach}<br>
                            Schedule: ${program.day} ${program.startTime} - ${
        program.endTime
      }<br>
                            Price per Session: ₱${program.price}<br>
                            Number of Sessions: ${numSessions}<br>
                            Total: ₱${total.toFixed(2)}
                        </div>
                        <button type="button" class="btn btn-link text-danger remove-program p-0" title="Remove" style="font-size: 1.2rem;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
    });
    programsContainer.html(html || "<p>No programs selected</p>");
    updateTotalAmount();

    // Attach remove handler for summary section
    $(".remove-program")
      .off("click")
      .on("click", function (e) {
        e.preventDefault();
        const index = $(this)
          .closest(".summary-row, .program-item")
          .data("index");
        if (typeof index !== "undefined") {
          selectedPrograms.splice(index, 1);
          updateProgramsSummary();
          updateReviewInformation();
        }
      });
  }

  // Helper: Generate program dates (copied from add_member.js)
  // Accept scheduleId and type for propagation
  function generateProgramDates(
    startDate,
    endDate,
    dayOfWeek,
    startTime,
    endTime,
    price,
    programType = null,
    programId = null
  ) {
    const schedules = [];
    const start = new Date(startDate);
    const end = new Date(endDate);
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
    for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
      if (d.getDay() === targetDay) {
        const scheduleObj = {
          date: d.toISOString().split("T")[0],
          day: dayOfWeek,
          start_time: startTime,
          end_time: endTime,
          amount: price,
          coach_group_schedule_id: programType === "group" ? programId : null,
          coach_personal_schedule_id:
            programType === "personal" ? programId : null,
        };
        schedules.push(scheduleObj);
      }
    }
    return schedules;
  }

  // Utility: Check if a schedule is already selected (prevent duplicates/conflicts)
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

  // Handle schedule selection from modal
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
      // Explicitly set schedule ID field for backend
      coach_group_schedule_id:
        button.data("type") === "group" ? button.data("id") : null,
      coach_personal_schedule_id:
        button.data("type") === "personal" ? button.data("id") : null,
    };

    // Validate schedule selection
    if (!scheduleData.day || !scheduleData.startTime || !scheduleData.endTime) {
      alert("Invalid schedule data");
      return;
    }
    // Prevent duplicate/conflicting schedules
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
    // Track selected schedules
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
    updateProgramsSummary();
    updateTotalAmount();
    // Close the modal reliably
    var modalEl = document.getElementById("scheduleModal");
    if (modalEl) {
      var bsModal =
        bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
      bsModal.hide();
    }
    $("#scheduleModal").removeClass("show").hide(); // fallback
  });

  // --- END schedule selection logic ---
  $(document).on("change", ".program-coach", function () {
    const coachProgramTypeId = $(this).val();
    if (!coachProgramTypeId) return;

    const programCard = $(this).closest(".program-card");
    const programName = programCard.find(".card-title").text().trim();
    const programType = programCard.data("program-type");

    // Reset dropdown to default immediately
    $(this).val("").find("option:first").prop("selected", true);

    // Store current program card for reference
    $("#scheduleModal").data("current-program-card", programCard);

    let scheduleModal = new bootstrap.Modal(
      document.getElementById("scheduleModal")
    );

    // Pre-fetch data before showing modal
    $.ajax({
      url: `${BASE_URL}/admin/pages/members/renew_member.php`,
      method: "GET",
      data: {
        action: "get_schedule",
        coach_program_type_id: coachProgramTypeId,
      },
      dataType: "json",
      success: function (response) {
        const tableBody = $("#scheduleTableBody");
        const tableHead = $("#scheduleTableHead");
        const programDesc = $("#programDesc");
        tableBody.empty();
        programDesc.empty();

        if (response.success && response.data && response.data.length > 0) {
          // Different headers for personal and group schedules
          if (response.program_type === "personal") {
            // For personal training, group time slots by day, duration, and price
            const scheduleGroups = {};
            response.data.forEach((schedule) => {
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
            const rows = Object.values(scheduleGroups).map((group) => {
              const timeButtons = group.slots
                .map(
                  (slot) => `
                                <button class="btn btn-primary btn-sm select-schedule m-1" type="button"
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
                                </button>
                            `
                )
                .join("");
              return `
                                <tr>
                                    <td>${group.day}</td>
                                    <td>${group.duration}</td>
                                    <td>${group.coach}</td>
                                    <td>₱${group.price}</td>
                                    <td>${timeButtons}</td>
                                </tr>
                            `;
            });
            tableHead.html(`
                            <tr>
                                <th>Day</th>
                                <th>Duration (mins)</th>
                                <th>Coach</th>
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
                                <td>${schedule.coach_name}</td>
                                <td>₱${schedule.price}</td>
                                <td>
                                    <button class="btn btn-primary btn-sm select-schedule" type="button"
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
            );
            tableHead.html(`
                            <tr>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Subscribers</th>
                                <th>Coach</th>
                                <th>Price</th>
                                <th>Action</th>
                            </tr>
                        `);
            tableBody.html(rows.join(""));
          }
          programDesc.text(
            `${programName} (${response.program_type_description || ""})`
          );
        } else if (response.message) {
          tableBody.html(
            `<tr><td colspan="6" class="text-center">${response.message}</td></tr>`
          );
          programDesc.text(programName);
        } else {
          tableBody.html(
            '<tr><td colspan="6" class="text-center">No schedules found.</td></tr>'
          );
          programDesc.text(programName);
        }
        scheduleModal.show();
      },
      error: function (xhr, status, error) {
        $("#scheduleTableBody").html(
          '<tr><td colspan="6" class="text-center">Failed to load schedules</td></tr>'
        );
        $("#programDesc").hide();
        scheduleModal.show();
      },
    });
  });

  // Rental service selection
  $(".rental-option").on("click", function () {
    $(this).toggleClass("selected");
    const checkbox = $(this).find('input[type="checkbox"]');
    checkbox.prop("checked", !checkbox.prop("checked"));
    updateRentalServicesSummary();
    updateTotalAmount();
  });

  // Update total and summary on plan or rental change
  $('input[name="membership_plan"], .rental-service-checkbox').on(
    "change",
    function () {
      updateRentalServicesSummary();
      updateTotalAmount();
    }
  );

  // Update rental services summary (mirrors add_member.js)
  function updateRentalServicesSummary() {
    const summaryContainer = $(".rental-services-summary");
    summaryContainer.empty();
    $('input[name="rental_services[]"]:checked').each(function () {
      const rentalId = $(this).val();
      const rentalName = $(this).data("name");
      const rentalPrice = parseFloat($(this).data("price"));
      const rentalDuration = $(this).data("duration");
      const rentalDurationType = $(this).data("duration-type");
      const rentalHtml = `
                <div class="summary-row" data-type="rental" data-rental-id="${rentalId}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${rentalName}</strong><br>
                            Duration: ${rentalDuration} ${rentalDurationType}<br>
                            Amount: ₱${rentalPrice.toFixed(2)}
                        </div>
                        <button type="button" class="btn btn-link text-danger remove-rental p-0" title="Remove" style="font-size: 1.2rem;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
      summaryContainer.append(rentalHtml);
    });

    // Attach remove handler for summary section
    $(".remove-rental")
      .off("click")
      .on("click", function () {
        const rentalId = $(this)
          .closest(".summary-row, .rental-item")
          .data("rental-id");
        // Uncheck the checkbox
        $(`input[name='rental_services[]'][value='${rentalId}']`).prop(
          "checked",
          false
        );
        // Remove from selectedRentals array
        selectedRentals = selectedRentals.filter((id) => id != rentalId);
        updateRentalServicesSummary();
        updateReviewInformation();
        updateTotalAmount();
      });
  }

  // Handle rental service removal (mirrors add_member.js)
  $(document).on("click", ".remove-rental", function () {
    const rentalId = $(this).closest(".summary-row").data("rental-id");
    const checkbox = $(`input[name="rental_services[]"][value="${rentalId}"]`);
    // Uncheck the checkbox
    checkbox.prop("checked", false);
    // Remove selected class from the rental card
    checkbox.closest(".rental-option").removeClass("selected");
    // Remove from summary
    $(this).closest(".summary-row").remove();
    // Update totals and summary
    updateRentalServicesSummary();
    updateTotalAmount();
  });

  // Form submission
  $("#renewMemberForm").on("submit", function (e) {
    e.preventDefault();
    if (!validatePhase(4)) return;
    const formData = $(this).serialize();
    $.ajax({
      url: "",
      type: "POST",
      data: formData,
      dataType: "json",
      success: function (response) {
        if (response.success) {
          alert("Membership renewed successfully!");
          window.location.href =
            BASE_URL + "/admin/pages/members/members_new.php";
        } else {
          alert("Error: " + response.message);
        }
      },
      error: function () {
        alert("An error occurred while processing the renewal.");
      },
    });
  });

  // Utility: Show the correct phase
  function showPhase(phaseNumber) {
    $(".phase").hide();
    $("#phase" + phaseNumber).show();
    $(".nav-link").removeClass("active");
    $('.nav-link[data-phase="' + phaseNumber + '"]').addClass("active");
    // Show/hide navigation buttons
    // Hide previous button in phase 4
    if (phaseNumber === 4) {
      $("#prevBtn").hide();
    } else {
      $("#prevBtn").toggle(phaseNumber > 2);
    }
    // Hide next button on phase 3, show review button only
    if (phaseNumber === 3) {
      $("#nextBtn").hide();
      $("#reviewBtn").show();
    } else {
      $("#nextBtn").toggle(phaseNumber < 4);
      $("#reviewBtn").toggle(phaseNumber === 3);
    }
    // Hide summary in phase 4, show otherwise
    if (phaseNumber === 4) {
      updateReviewInformation();
      $(".membership-summary").hide();
    } else {
      $(".membership-summary").show();
    }
  }

  // Utility: Validate current phase
  function validatePhase(phaseNumber) {
    // Phase 2: Membership Plan
    if (phaseNumber === 2) {
      if (!$('input[name="membership_plan"]:checked').length) {
        alert("Please select a membership plan.");
        return false;
      }
      if (!$("#membership_start_date").val()) {
        alert("Please select a start date.");
        return false;
      }
    }
    // Phase 3: Programs (optional, can add validation if needed)
    // Phase 4: Review (just before submit)
    return true;
  }

  // Utility: Update total amount
  function updateTotalAmount() {
    let total = 0;
    const selectedPlan = $('input[name="membership_plan"]:checked');
    if (selectedPlan.length) {
      total += parseFloat(selectedPlan.data("price")) || 0;
    }
    $(".rental-service-checkbox:checked").each(function () {
      total += parseFloat($(this).data("price")) || 0;
    });
    $(".totalAmount").text("₱" + total.toFixed(2));
    $("#review-total-amount").text("₱" + total.toFixed(2));
  }

  // Initialize totals
  updateTotalAmount();

  // Expose functions globally for inline handlers
  window.updateRentalServicesSummary = updateRentalServicesSummary;
  window.updateTotalAmount = updateTotalAmount;
});
