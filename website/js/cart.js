// Remove all sessions in a program schedule group
function removeProgramGroupFromCart(groupKey) {
  const formData = new URLSearchParams();
  formData.append("action", "remove_group");
  formData.append("type", "program");
  formData.append("groupKey", groupKey);

  fetch("services/cart_handler.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
      "Cache-Control": "no-cache",
    },
    body: formData.toString(),
    credentials: "same-origin",
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`Network response was not ok: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      if (data.success) {
        if (data.data && data.data.cart) {
          updateCartDisplay(data.data.cart);
        } else {
          loadCart();
        }
      } else {
        loadCart();
        const message = data.data && data.data.message
          ? data.data.message
          : "Failed to remove program group";
        console.error("Server error:", message);
      }
    })
    .catch((error) => {
      console.error("Error removing program group:", error);
      loadCart();
      alert("Failed to remove all sessions in this schedule");
    });
}

// Function to show alerts
function showAlert(type, message) {
  const alertContainer = document.querySelector(".alert-container");
  if (!alertContainer) {
    console.error("Alert container not found");
    return;
  }

  const alertDiv = document.createElement("div");
  alertDiv.className = `alert alert-${
    type === "error" ? "danger" : "success"
  } alert-dismissible fade show`;
  alertDiv.role = "alert";
  alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

  alertContainer.appendChild(alertDiv);

  // Auto dismiss after 5 seconds
  setTimeout(() => {
    alertDiv.remove();
  }, 5000);
}

// Function to fetch registration fee details
function fetchRegistrationFeeDetails() {
  return fetch("services/registration_fee_handler.php", {
    method: "GET",
    headers: {
      "Cache-Control": "no-cache",
    },
    credentials: "same-origin",
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`Network response was not ok: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      if (data.success) {
        return data.data.registration;
      } else {
        console.error("Error fetching registration fee:", data.data.message);
        return null;
      }
    })
    .catch((error) => {
      console.error("Error fetching registration fee details:", error);
      return null;
    });
}

// Function to format duration text based on duration and type
function formatDuration(duration, durationType) {
  if (!duration || !durationType) return "";
  
  // Convert duration type ID to readable text
  let typeText = "";
  switch (durationType) {
    case 1:
      typeText = duration > 1 ? "days" : "day";
      break;
    case 2:
      typeText = duration > 1 ? "months" : "month";
      break;
    case 3:
      typeText = duration > 1 ? "years" : "year";
      break;
    default:
      typeText = "unknown";
  }
  
  return `${duration} ${typeText}`;
}

// Function to update the registration fee section in the cart display
function updateRegistrationFeeDisplay(cart, registrationDetails) {
  // Only update if there's a registration fee in the cart
  if (!cart.registration_fee || !registrationDetails) return "";
  
  const durationText = formatDuration(
    registrationDetails.duration, 
    registrationDetails.duration_type_id
  );
  
  return `
    <div class="cart-section">
      <h6 class="fw-bold mb-3">One-time Fees</h6>
      <div class="cart-item">
        <div class="item-details">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="mb-1">${cart.registration_fee.name}</p>
              <p class="price mb-1">₱${parseFloat(cart.registration_fee.price).toFixed(2)}</p>
              <p class="text-muted mb-0">One-time registration fee for new members</p>
              ${durationText ? `<p class="text-muted mb-0">Valid for: ${durationText}</p>` : ""}
            </div>
          </div>
        </div>
      </div>
    </div>
  `;
}

// Updated updateCartDisplay function to incorporate registration fee details
function updateCartDisplay(cart) {
  const cartBody = document.querySelector(".cart-body");
  if (!cartBody) {
    console.error("Cart body element not found");
    return;
  }

  // First fetch registration fee details if needed
  if (cart.registration_fee) {
    fetchRegistrationFeeDetails().then(registrationDetails => {
      renderCartWithRegistrationDetails(cart, registrationDetails);
    });
  } else {
    renderCartWithRegistrationDetails(cart, null);
  }
  
  function renderCartWithRegistrationDetails(cart, registrationDetails) {
    let html = "";

    // Walk-in section
    if (cart.walkins && cart.walkins.length > 0) {
      cart.walkins.forEach((walkin, index) => {
        html += `
          <div class="cart-item">
            <div class="item-details">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="mb-1">Walk-in Service</h6>
                  <p class="price mb-1 fw">₱${parseFloat(walkin.price).toFixed(2)}</p>
                  <p class="text-muted small mb-0">Date: ${formatDate(walkin.date)}</p>
                </div>
                <button class="remove-item" 
                  onclick="removeFromCart('walkin', ${index})">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </div>
        `;
      });
    }

    // Memberships section
    if (cart.memberships && cart.memberships.length > 0) {
      cart.memberships.forEach((membership, index) => {
        html += `
          <div class="cart-item">
            <div class="item-details">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <p class="mb-1">${membership.name}</p>
                  <p class="price mb-1">₱${parseFloat(membership.price).toFixed(2)}</p>
                  <p class="text-muted mb-0">Validity: ${membership.validity}</p>
                  <p class="text-muted mb-0">Start: ${formatDate(membership.start_date)}</p>
                  <p class="text-muted mb-0">End: ${formatDate(membership.end_date)}</p>
                </div>
                <button class="remove-item" 
                  onclick="removeFromCart('membership', ${index})">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </div>
        `;
      });
    }

    // Programs section (grouped by schedule)
    if (cart.programs && cart.programs.length > 0) {
      // Group program sessions by shared schedule properties
      const groupedPrograms = {};
      cart.programs.forEach((program, index) => {
        // Use a composite key of schedule properties except date
        const key = [
          program.program_id,
          program.program_name,
          program.coach_id,
          program.coach_name,
          program.day,
          program.start_time,
          program.end_time,
          program.price
        ].join('|');
        if (!groupedPrograms[key]) {
          groupedPrograms[key] = {
            ...program,
            session_dates: [],
            indices: [],
            groupKey: key
          };
        }
        groupedPrograms[key].session_dates.push(program.session_date);
        groupedPrograms[key].indices.push(index);
      });

      // Render each group as one card with all dates listed
      Object.values(groupedPrograms).forEach((group) => {
        html += `
          <div class="cart-item">
            <div class="item-details">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <p class="mb-1">${group.program_name}</p>
                  <p class="price mb-1">₱${parseFloat(group.price).toFixed(2)}</p>
                  <p class="text-muted mb-0">Coach: ${group.coach_name}</p>
                  <p class="text-muted mb-0">Day: ${group.day}</p>
                  <p class="text-muted mb-0">Time: ${group.start_time} - ${group.end_time}</p>
                  <div class="mt-2">
                    <span class="fw-bold">Session Dates:</span>
                    <ul class="mb-1 ps-4">
                      ${group.session_dates.map((date, i) => `
                        <li class="d-flex align-items-center justify-content-between">
                          <span>${formatDate(date)}</span>
                          <button class="btn btn-sm btn-link text-danger p-0 ms-2" title="Remove this session" onclick="removeFromCart('program', ${group.indices[i]})"><i class="fas fa-times"></i></button>
                        </li>
                      `).join('')}
                    </ul>
                  </div>
                </div>
                <button class="remove-item ms-3" title="Remove all sessions in this schedule"
                  onclick="removeProgramGroupFromCart('${group.groupKey}')">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
          </div>
        `;
      });
    }

    // Rentals section
    if (cart.rentals && cart.rentals.length > 0) {
      cart.rentals.forEach((rental, index) => {
        html += `
          <div class="cart-item">
            <div class="item-details">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <p class="mb-1">${rental.name}</p>
                  <p class="price mb-1">₱${parseFloat(rental.price).toFixed(2)}</p>
                  <p class="text-muted mb-0">Start: ${formatDate(rental.start_date)}</p>
                  <p class="text-muted mb-0">End: ${formatDate(rental.end_date)}</p>
                </div>
                <button class="remove-item" 
                  onclick="removeFromCart('rental', ${index})">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </div>
        `;
      });
    }

    // Registration Fee section with updated duration information
    if (cart.registration_fee) {
      html += updateRegistrationFeeDisplay(cart, registrationDetails);
    }

    // Cart summary
    html += `
      <div class="cart-summary mt-3">
        <div class="d-flex justify-content-between align-items-center">
          <span class="fw">Total:</span>
          <span class="fw">₱${parseFloat(cart.total).toFixed(2)}</span>
        </div>
        ${
          cart.total > 0
            ? `
            <div class="d-grid gap-2 mt-3">
              <button class="btn btn-primary" onclick="availServices()">
                Avail Services
              </button>
              <button class="btn btn-outline-secondary" onclick="clearCart()">
                Clear Cart
              </button>
            </div>
          `
            : `
            <p class="text-center text-muted mt-3">Your cart is empty</p>
          `
        }
      </div>
    `;

    cartBody.innerHTML = html;
  }
}

function removeFromCart(type, id) {
  const formData = new URLSearchParams();
  formData.append("action", "remove");
  formData.append("type", type);
  formData.append("index", id.toString());  // Already fixed from your previous change

  fetch("services/cart_handler.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
      "Cache-Control": "no-cache",
    },
    body: formData.toString(),
    credentials: "same-origin",
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`Network response was not ok: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      console.log("Response from removeFromCart:", data);  // Debug logging
      
      // Check if we need to load the cart manually regardless of success
      if (data.success) {
        // If there's cart data in the response, use it
        if (data.data && data.data.cart) {
          updateCartDisplay(data.data.cart);
        } else {
          // If successful but no cart data, reload the cart
          loadCart();
        }
      } else {
        // Even on error, try reloading the cart
        console.warn("Server reported error but attempting to reload cart anyway");
        loadCart();
        
        // Also show the error
        const message = data.data && data.data.message 
          ? data.data.message 
          : "Failed to remove item";
        console.error("Server error:", message);
      }
    })
    .catch((error) => {
      console.error("Error removing item:", error);
      // Even on JavaScript error, try reloading the cart
      loadCart();
      alert("Failed to remove item from cart");
    });
}

function loadCart() {
  fetch("services/cart_handler.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
      "Cache-Control": "no-cache",
    },
    body: "action=get",
    credentials: "same-origin",
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`Network response was not ok: ${response.status}`);
      }
      return response.text();
    })
    .then((text) => {
      if (!text.trim()) {
        throw new Error("Empty response received");
      }
      try {
        return JSON.parse(text);
      } catch (e) {
        console.error("Parse error:", e);
        throw new Error("Invalid JSON response");
      }
    })
    .then((response) => {
      if (!response) {
        throw new Error("No response data");
      }

      if (response.success) {
        if (response.data && response.data.cart) {
          updateCartDisplay(response.data.cart);
        } else if (response.data && response.data.message) {
          updateCartDisplay({
            memberships: [],
            programs: [],
            rentals: [],
            total: 0,
          });
        }
      } else {
        const message =
          response.data && response.data.message
            ? response.data.message
            : "Unknown error";
        console.error("Server error:", message);
        // Removed the redirect check and just update cart display
        updateCartDisplay({
          memberships: [],
          programs: [],
          rentals: [],
          total: 0,
        });
      }
    })
    .catch((error) => {
      console.error("Error loading cart:", error);
      updateCartDisplay({
        memberships: [],
        programs: [],
        rentals: [],
        total: 0,
      });
    });
}

function clearCart() {
  // Create the confirmation modal HTML
  const confirmModalHTML = `
    <div class="modal fade" id="clearCartModal" tabindex="-1" aria-labelledby="clearCartModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-0">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title" id="clearCartModalLabel">Confirm Clear Cart</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            Are you sure you want to clear your cart?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger" id="clearCartModalYesBtn">Yes</button>
          </div>
        </div>
      </div>
    </div>
  `;

  // Add the confirmation modal HTML to the body
  document.body.insertAdjacentHTML("beforeend", confirmModalHTML);

  // Show the confirmation modal
  const confirmModal = new bootstrap.Modal(
    document.getElementById("clearCartModal")
  );
  confirmModal.show();

  // Add event listener to the Yes button
  document
    .getElementById("clearCartModalYesBtn")
    .addEventListener("click", function () {
      confirmModal.hide();
      console.log("Clearing cart...");
      const formData = new URLSearchParams();
      formData.append("action", "clear");

      fetch("services/cart_handler.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          "Cache-Control": "no-cache",
        },
        body: formData.toString(),
        credentials: "same-origin",
      })
        .then((response) => {
          if (!response.ok) {
            console.error("Server responded with status:", response.status);
            throw new Error(`Server error: ${response.status}`);
          }
          return response.json();
        })
        .then((data) => {
          console.log("Clear cart response:", data);
          if (data.success) {
            updateCartDisplay(data.data.cart);
          } else {
            const message =
              data.data && data.data.message
                ? data.data.message
                : "Failed to clear cart";
            console.error("Clear cart failed:", message);
            alert(message);
          }
        })
        .catch((error) => {
          console.error("Error clearing cart:", error);
          alert("Failed to clear cart. Please try again or refresh the page.");
        });
    });
}

// Modified function to check walk-in dates against active membership
function checkWalkInAgainstMembership(walkIns, activeMembership) {
  // If no active membership or no walk-ins, no conflict exists
  if (!activeMembership || !walkIns || walkIns.length === 0) {
    return {
      conflict: false
    };
  }
  
  // Convert membership dates to Date objects
  const membershipStart = new Date(activeMembership.start_date);
  const membershipEnd = new Date(activeMembership.end_date);
  
  // Find any walk-in dates that fall within the active membership period
  const conflictingDates = walkIns.filter(walkin => {
    const walkinDate = new Date(walkin.date);
    return walkinDate >= membershipStart && walkinDate <= membershipEnd;
  });
  
  return {
    conflict: conflictingDates.length > 0,
    dates: conflictingDates,
    membershipStart: activeMembership.start_date,
    membershipEnd: activeMembership.end_date
  };
}

// Function to show membership-walkin conflict warning
function showMembershipWalkinConflictWarning(conflictData) {
  // Format the dates for display
  const formattedDates = conflictData.dates.map(item => formatDate(item.date)).join(", ");
  const membershipStart = formatDate(conflictData.membershipStart);
  const membershipEnd = formatDate(conflictData.membershipEnd);
  
  // Create the warning modal HTML
  const warningModalHTML = `
    <div class="modal fade" id="membershipWalkinConflictModal" tabindex="-1" aria-labelledby="membershipWalkinConflictModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-0">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title" id="membershipWalkinConflictModalLabel">Membership Conflict</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="d-flex align-items-center mb-3">
              <i class="bi bi-exclamation-circle-fill text-danger me-3" style="font-size: 2rem;"></i>
              <div>
                <p class="mb-1">You cannot avail walk-in services for these dates:</p>
                <p class="fw-bold mb-3">${formattedDates}</p>
                <p class="mb-0">These dates fall within your active membership period (${membershipStart} - ${membershipEnd}).</p>
                <p class="mt-2">Members should use their membership benefits for gym access during this period.</p>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  `;

  // Add the warning modal HTML to the body
  document.body.insertAdjacentHTML("beforeend", warningModalHTML);

  // Show the warning modal
  const warningModal = new bootstrap.Modal(
    document.getElementById("membershipWalkinConflictModal")
  );
  warningModal.show();
}

function availServices() {
  // First check if there are any pending transactions
  checkPendingTransactions()
    .then(hasPendingTransactions => {
      if (hasPendingTransactions) {
        // If there are pending transactions, show warning and stop
        showPendingTransactionWarning();
      } else {
        // No pending transactions, now check cart content
        fetch("services/cart_handler.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            "Cache-Control": "no-cache",
          },
          body: "action=get",
          credentials: "same-origin",
        })
          .then(response => response.json())
          .then(cartResponse => {
            if (cartResponse.success && cartResponse.data.cart) {
              const cart = cartResponse.data.cart;
              
              // Check if the cart contains a membership plan
              const hasMembershipInCart = cart.memberships && cart.memberships.length > 0;
              // Check if the cart contains a walk-in service
              const hasWalkinInCart = cart.walkins && cart.walkins.length > 0;
              
              // First check for active membership regardless of what's in the cart
              checkActiveMembership()
                .then(activeMembershipData => {
                  // If there are walk-ins in the cart and the user has an active membership,
                  // check for conflicts between walk-in dates and membership period
                  if (hasWalkinInCart && activeMembershipData) {
                    const conflictResult = checkWalkInAgainstMembership(cart.walkins, activeMembershipData);
                    
                    if (conflictResult.conflict) {
                      // There's a conflict, show warning and stop
                      showMembershipWalkinConflictWarning(conflictResult);
                      return;
                    }
                  }
                  
                  // Continue with existing validation logic
                  if (hasWalkinInCart) {
                    // Check if the user already has a walk-in for the dates in cart
                    checkWalkInRecords(cart.walkins)
                      .then(walkInCheckResult => {
                        if (walkInCheckResult.duplicate) {
                          // User has an existing walk-in for at least one date, show warning
                          showDuplicateWalkInWarning(walkInCheckResult.dates);
                        } else if (hasMembershipInCart && activeMembershipData) {
                          // No duplicate walk-ins, but user is adding new membership while having active one
                          showActiveMembershipWarning(activeMembershipData);
                        } else {
                          // No conflicts, proceed
                          validateAndProceed();
                        }
                      })
                      .catch(error => {
                        console.error("Error checking walk-in records:", error);
                        if (hasMembershipInCart && activeMembershipData) {
                          showActiveMembershipWarning(activeMembershipData);
                        } else {
                          validateAndProceed();
                        }
                      });
                  } else if (hasMembershipInCart && activeMembershipData) {
                    // No walk-in in cart, but user is adding new membership while having active one
                    showActiveMembershipWarning(activeMembershipData);
                  } else {
                    // No conflicts, proceed directly
                    validateAndProceed();
                  }
                })
                .catch(error => {
                  console.error("Error checking active membership:", error);
                  // Proceed with regular validation if membership check fails
                  if (hasWalkinInCart) {
                    checkWalkInRecords(cart.walkins)
                      .then(walkInCheckResult => {
                        if (walkInCheckResult.duplicate) {
                          showDuplicateWalkInWarning(walkInCheckResult.dates);
                        } else {
                          validateAndProceed();
                        }
                      })
                      .catch(error => {
                        console.error("Error checking walk-in records:", error);
                        validateAndProceed();
                      });
                  } else {
                    validateAndProceed();
                  }
                });
            } else {
              // Handle error from cart fetch
              console.error("Failed to get cart data:", cartResponse);
              validateAndProceed();
            }
          })
          .catch(error => {
            console.error("Error getting cart:", error);
            validateAndProceed();
          });
      }
    })
    .catch(error => {
      console.error("Error checking pending transactions:", error);
      validateAndProceed();
    });
}

// Function to check for existing walk-in records for dates in cart
function checkWalkInRecords(walkIns) {
  // Store all the dates we need to check
  const dates = walkIns.map(walkin => walkin.date);
  
  // If no dates, return immediately
  if (!dates.length) {
    return Promise.resolve({ duplicate: false });
  }
  
  // Create an array of promises for each date check
  const checkPromises = dates.map(date => {
    const formData = new URLSearchParams();
    formData.append("date", date);
    
    return fetch("services/walkin_check.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: formData.toString(),
      credentials: "same-origin",
    })
    .then(response => {
      if (!response.ok) {
        throw new Error(`Network response was not ok: ${response.status}`);
      }
      return response.json();
    })
    .then(data => {
      return {
        date: date,
        hasWalkinForDate: data.data.hasWalkinForDate,
        walkInRecord: data.data.walkInRecord
      };
    });
  });
  
  // Check all dates and return the result
  return Promise.all(checkPromises)
    .then(results => {
      // Find any dates with existing walk-ins
      const duplicateDates = results.filter(result => result.hasWalkinForDate);
      
      return {
        duplicate: duplicateDates.length > 0,
        dates: duplicateDates
      };
    });
}

// Function to show duplicate walk-in warning
function showDuplicateWalkInWarning(duplicateDates) {
  // Format the dates for display
  const formattedDates = duplicateDates.map(item => formatDate(item.date)).join(", ");
  
  // Create the warning modal HTML
  const warningModalHTML = `
    <div class="modal fade" id="duplicateWalkInModal" tabindex="-1" aria-labelledby="duplicateWalkInModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-0">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title" id="duplicateWalkInModalLabel">Duplicate Walk-In Entry</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="d-flex align-items-center mb-3">
              <i class="bi bi-exclamation-circle-fill text-danger me-3" style="font-size: 2rem;"></i>
              <div>
                <p class="mb-1">You already have a walk-in record for the following date(s):</p>
                <p class="fw-bold mb-3">${formattedDates}</p>
                <p class="mb-0">You cannot have multiple walk-in entries for the same date. Please remove the duplicate entries from your cart.</p>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  `;

  // Add the warning modal HTML to the body
  document.body.insertAdjacentHTML("beforeend", warningModalHTML);

  // Show the warning modal
  const warningModal = new bootstrap.Modal(
    document.getElementById("duplicateWalkInModal")
  );
  warningModal.show();
}

// Add this function to check for active memberships
function checkActiveMembership() {
  return fetch("services/membership_check.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    credentials: "same-origin",
  })
    .then(response => {
      if (!response.ok) {
        throw new Error(`Network response was not ok: ${response.status}`);
      }
      return response.json();
    })
    .then(data => {
      console.log("Active membership check:", data);
      return data.data; // Return the membership data if exists
    });
}

// Add this function to show the active membership warning
function showActiveMembershipWarning(membershipData) {
  // Create the warning modal HTML
  const warningModalHTML = `
    <div class="modal fade" id="activeMembershipModal" tabindex="-1" aria-labelledby="activeMembershipModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-0">
          <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title" id="activeMembershipModalLabel">Active Membership Found</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="d-flex align-items-center mb-3">
              <i class="bi bi-exclamation-triangle-fill text-warning me-3" style="font-size: 2rem;"></i>
              <div>
                <p class="mb-1">You already have an active membership that expires on ${formatDate(membershipData.end_date)}.</p>
                <p class="mb-0">Purchasing a new membership now will only be valid after your current membership expires.</p>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="proceedAnywayBtn">Proceed Anyway</button>
          </div>
        </div>
      </div>
    </div>
  `;

  // Add the warning modal HTML to the body
  document.body.insertAdjacentHTML("beforeend", warningModalHTML);

  // Show the warning modal
  const warningModal = new bootstrap.Modal(
    document.getElementById("activeMembershipModal")
  );
  warningModal.show();

  // Add event listener to the "Proceed Anyway" button
  document
    .getElementById("proceedAnywayBtn")
    .addEventListener("click", function () {
      warningModal.hide();
      validateAndProceed();
    });
}

function checkPendingTransactions() {
  return fetch("services/transaction_check.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    credentials: "same-origin",
  })
    .then(response => {
      if (!response.ok) {
        throw new Error(`Network response was not ok: ${response.status}`);
      }
      return response.json();
    })
    .then(data => {
      console.log("Pending transaction check:", data);
      return data.hasPendingTransactions;
    });
}

function showPendingTransactionWarning() {
  // Create the warning modal HTML
  const warningModalHTML = `
    <div class="modal fade" id="pendingTransactionModal" tabindex="-1" aria-labelledby="pendingTransactionModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-0">
          <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title" id="pendingTransactionModalLabel">Pending Transaction</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="d-flex align-items-center mb-3">
              <i class="bi bi-exclamation-triangle-fill text-warning me-3" style="font-size: 2rem;"></i>
              <p class="mb-0">You have pending transactions awaiting approval from admin/staff.</p>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  `;

  // Add the warning modal HTML to the body
  document.body.insertAdjacentHTML("beforeend", warningModalHTML);

  // Show the warning modal
  const warningModal = new bootstrap.Modal(
    document.getElementById("pendingTransactionModal")
  );
  warningModal.show();
}

function validateAndProceed() {
  fetch("services/cart_handler.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: "action=validate",
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success && data.data.showConfirm) {
        proceedWithAvailing();
      } else {
        alert(
          data.data.message || "An error occurred while validating your cart."
        );
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("An error occurred while processing your request.");
    });
}

// Add this HTML for the modal to your page
function addSuccessModal() {
  const modalHTML = `
  <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content p-0 ">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title" id="successModalLabel">Success!</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center">
          <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
          <h4 class="mt-3">Transaction Successful</h4>
          <p>Your services have been successfully availed.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-success" id="successModalContinueBtn">Continue</button>
        </div>
      </div>
    </div>
  </div>
  `;

  // Add the modal HTML to the body
  document.body.insertAdjacentHTML("beforeend", modalHTML);
}

// Make sure to call this function when the page loads
document.addEventListener("DOMContentLoaded", function () {
  addSuccessModal();
});

// Updated proceedWithAvailing function
function proceedWithAvailing() {
  // Create the confirmation modal HTML
  const confirmModalHTML = `
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-0">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="confirmModalLabel">Confirm Avail Services</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            Are you sure you want to avail these services?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="confirmModalYesBtn">Yes</button>
          </div>
        </div>
      </div>
    </div>
  `;

  // Add the confirmation modal HTML to the body
  document.body.insertAdjacentHTML("beforeend", confirmModalHTML);

  // Show the confirmation modal
  const confirmModal = new bootstrap.Modal(
    document.getElementById("confirmModal")
  );
  confirmModal.show();

  // Add event listener to the Yes button
  document
    .getElementById("confirmModalYesBtn")
    .addEventListener("click", function () {
      confirmModal.hide();
      fetch("services/checkout_handler.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Show the success modal instead of redirecting immediately
            const successModal = new bootstrap.Modal(
              document.getElementById("successModal")
            );
            successModal.show();

            // Add event listener to the continue button
            document
              .getElementById("successModalContinueBtn")
              .addEventListener("click", function () {
                successModal.hide();
                window.location.href = "../website/profile.php";
              });

            // Also redirect after a short delay if user doesn't click the button
            setTimeout(function () {
              if (
                document
                  .getElementById("successModal")
                  .classList.contains("show")
              ) {
                successModal.hide();
                window.location.href = "../website/profile.php";
              }
            }, 5000); // 5 seconds delay
          } else {
            alert(data.data.message || "An error occurred during checkout.");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("An error occurred while processing your request.");
        });
    });
}

function formatDate(dateString) {
  if (!dateString) return "N/A";
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return "Invalid Date";

  const month = (date.getMonth() + 1).toString().padStart(2, "0");
  const day = date.getDate().toString().padStart(2, "0");
  const year = date.getFullYear();

  return `${month}/${day}/${year}`;
}

// Initialize cart when page loads
document.addEventListener("DOMContentLoaded", loadCart);