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

function updateCartDisplay(cart) {
  const cartBody = document.querySelector(".cart-body");
  if (!cartBody) {
    console.error("Cart body element not found");
    return;
  }

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
                                <p class="price mb-1 fw">₱${parseFloat(
                                  walkin.price
                                ).toFixed(2)}</p>
                                <p class="text-muted small mb-0">Date: ${formatDate(
                                  walkin.date
                                )}</p>
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
    html += "</div>";
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
                                <p class="price mb-1">₱${parseFloat(
                                  membership.price
                                ).toFixed(2)}</p>
                                <p class="text-muted mb-0">Validity: ${
                                  membership.validity
                                }</p>
                                <p class="text-muted mb-0">Start: ${formatDate(
                                  membership.start_date
                                )}</p>
                                <p class="text-muted mb-0">End: ${formatDate(
                                  membership.end_date
                                )}</p>
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

  // Programs section
  if (cart.programs && cart.programs.length > 0) {
    cart.programs.forEach((program, index) => {
      html += `
                <div class="cart-item">
                    <div class="item-details">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-1">${program.program_name}</p>
                                <p class="price mb-1">₱${parseFloat(
                                  program.price
                                ).toFixed(2)}</p>
                                <p class="text-muted mb-0">Coach: ${program.coach_name}</p>
                                <p class="text-muted mb-0">Day: ${program.day}</p>
                                <p class="text-muted mb-0">Date: ${formatDate(program.session_date)}</p>
                                <p class="text-muted mb-0">Time: ${program.start_time} - ${program.end_time}</p>
                            </div>
                            <button class="remove-item" 
                                    onclick="removeFromCart('program', ${index})">
                                <i class="fas fa-times"></i>
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
                                <p class="price mb-1">₱${parseFloat(
                                  rental.price
                                ).toFixed(2)}</p>
                                <p class="text-muted mb-0">Start: ${formatDate(
                                  rental.start_date
                                )}</p>
                                <p class="text-muted mb-0">End: ${formatDate(
                                  rental.end_date
                                )}</p>
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

  // Registration Fee section
  if (cart.registration_fee) {
    html += `
            <div class="cart-section">
                <h6 class="fw-bold mb-3">One-time Fees</h6>
                <div class="cart-item">
                    <div class="item-details">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-1">${
                                  cart.registration_fee.name
                                }</p>
                                <p class="price mb-1">₱${parseFloat(
                                  cart.registration_fee.price
                                ).toFixed(2)}</p>
                                <p class="text-muted mb-0">One-time registration fee for new members</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
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

function availServices() {
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
