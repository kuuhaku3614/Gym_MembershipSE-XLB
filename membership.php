<!DOCTYPE html>
<html>
<head>
    <!-- Bootstrap CSS and Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .receipt-header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px dashed #ddd;
        }
        
        .receipt-body {
            padding: 20px;
        }
        
        .receipt-footer {
            text-align: center;
            padding: 20px 0;
            border-top: 2px dashed #ddd;
        }
        
        .receipt-item {
            margin-bottom: 15px;
        }
        
        .receipt-item-label {
            font-weight: bold;
            color: #666;
        }
        
        .member-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .total-amount {
            font-size: 1.5rem;
            font-weight: bold;
            color: #0d6efd;
        }
    </style>
</head>
<body>
    <!-- Button trigger modal -->
    <button class="btn btn-primary btn-sm view-details">
        <i class="bi bi-receipt me-1"></i> View Receipt
    </button>

    <!-- Modal -->
    <div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="receiptModalLabel">Transaction Receipt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="receipt-header">
                        <div class="member-photo-container mb-3">
                            <img src="" alt="Member Photo" class="member-photo" id="memberPhoto">
                        </div>
                        <h4 id="memberName"></h4>
                        <p class="text-muted mb-0" id="transactionId"></p>
                        <p class="text-muted" id="paymentDate"></p>
                    </div>
                    
                    <div class="receipt-body">
                        <div class="row">
                            <div class="col-12 mb-4">
                                <h5 class="text-primary">Membership Details</h5>
                                <div class="receipt-item">
                                    <div class="receipt-item-label">Plan Name</div>
                                    <div id="planName"></div>
                                </div>
                                <div class="receipt-item">
                                    <div class="receipt-item-label">Duration</div>
                                    <div id="membershipDuration"></div>
                                </div>
                                <div class="receipt-item">
                                    <div class="receipt-item-label">Status</div>
                                    <div id="membershipStatus"></div>
                                </div>
                                <div class="receipt-item">
                                    <div class="receipt-item-label">Price</div>
                                    <div id="membershipPrice"></div>
                                </div>
                            </div>
                            
                            <div class="col-12 mb-4" id="programSection">
                                <h5 class="text-primary">Program Subscription</h5>
                                <div class="receipt-item">
                                    <div class="receipt-item-label">Program Name</div>
                                    <div id="programName"></div>
                                </div>
                                <div class="receipt-item">
                                    <div class="receipt-item-label">Duration</div>
                                    <div id="programDuration"></div>
                                </div>
                                <div class="receipt-item">
                                    <div class="receipt-item-label">Price</div>
                                    <div id="programPrice"></div>
                                </div>
                            </div>
                            
                            <div class="col-12 mb-4" id="rentalSection">
                                <h5 class="text-primary">Rental Service</h5>
                                <div class="receipt-item">
                                    <div class="receipt-item-label">Service Name</div>
                                    <div id="rentalServiceName"></div>
                                </div>
                                <div class="receipt-item">
                                    <div class="receipt-item-label">Duration</div>
                                    <div id="rentalDuration"></div>
                                </div>
                                <div class="receipt-item">
                                    <div class="receipt-item-label">Price</div>
                                    <div id="rentalPrice"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="receipt-footer">
                            <div class="receipt-item">
                                <div class="receipt-item-label">Total Amount</div>
                                <div class="total-amount" id="totalAmount"></div>
                            </div>
                            <div class="receipt-item">
                                <div class="receipt-item-label">Processed by</div>
                                <div id="staffName"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const viewDetailsButtons = document.querySelectorAll('.view-details');
            const receiptModal = new bootstrap.Modal(document.getElementById('receiptModal'));
            
            viewDetailsButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // In a real application, you would fetch the data for the specific transaction
                    // Here's an example of how to populate the modal with the data
                    const transactionData = {
                        transaction_id: "TRX-001",
                        total_amount: 250.00,
                        payment_date: "2024-03-24",
                        staff_name: "John Doe",
                        member_name: "Jane Smith",
                        member_photo: "/api/placeholder/100/100", // placeholder image
                        membership_start_date: "2024-03-24",
                        membership_end_date: "2025-03-23",
                        membership_status: "Active",
                        plan_name: "Premium Annual",
                        program_name: "Yoga Basics",
                        program_duration: "12",
                        program_duration_type: "Weeks",
                        program_price: 150.00,
                        rental_service_name: "Yoga Mat",
                        rental_duration: "1",
                        rental_duration_type: "Month",
                        rental_price: 25.00,
                        membership_price: 75.00
                    };
                    
                    // Populate the modal with data
                    document.getElementById('memberPhoto').src = transactionData.member_photo;
                    document.getElementById('memberName').textContent = transactionData.member_name;
                    document.getElementById('transactionId').textContent = `Transaction ID: ${transactionData.transaction_id}`;
                    document.getElementById('paymentDate').textContent = `Payment Date: ${new Date(transactionData.payment_date).toLocaleDateString()}`;
                    document.getElementById('planName').textContent = transactionData.plan_name;
                    document.getElementById('membershipDuration').textContent = `${transactionData.membership_start_date} to ${transactionData.membership_end_date}`;
                    document.getElementById('membershipStatus').textContent = transactionData.membership_status;
                    document.getElementById('membershipPrice').textContent = `$${transactionData.membership_price.toFixed(2)}`;
                    
                    // Program details
                    if (transactionData.program_name) {
                        document.getElementById('programName').textContent = transactionData.program_name;
                        document.getElementById('programDuration').textContent = `${transactionData.program_duration} ${transactionData.program_duration_type}`;
                        document.getElementById('programPrice').textContent = `$${transactionData.program_price.toFixed(2)}`;
                        document.getElementById('programSection').style.display = 'block';
                    } else {
                        document.getElementById('programSection').style.display = 'none';
                    }
                    
                    // Rental details
                    if (transactionData.rental_service_name) {
                        document.getElementById('rentalServiceName').textContent = transactionData.rental_service_name;
                        document.getElementById('rentalDuration').textContent = `${transactionData.rental_duration} ${transactionData.rental_duration_type}`;
                        document.getElementById('rentalPrice').textContent = `$${transactionData.rental_price.toFixed(2)}`;
                        document.getElementById('rentalSection').style.display = 'block';
                    } else {
                        document.getElementById('rentalSection').style.display = 'none';
                    }
                    
                    document.getElementById('totalAmount').textContent = `$${transactionData.total_amount.toFixed(2)}`;
                    document.getElementById('staffName').textContent = transactionData.staff_name;
                    
                    receiptModal.show();
                });
            });
        });
    </script>
</body>
</html>