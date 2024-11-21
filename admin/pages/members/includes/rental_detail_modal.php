<div class="modal fade" id="rentalDetailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Rental Service Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="rentalDetailContent" class="rental-details">
                    <input type="hidden" class="rental-id">
                    <h5 class="rental-name mb-3"></h5>
                    <div class="rental-description mb-3"></div>
                    <div class="available-slots mb-3">
                        <strong>Available Slots:</strong>
                        <span class="rental-slots"></span>
                    </div>
                    <div class="rental-duration mb-3">
                        <strong>Duration:</strong>
                        <span class="rental-duration-value"></span>
                    </div>
                    <div class="rental-price font-weight-bold text-primary mb-3"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="addRentalDetailBtn">Add to Membership</button>
            </div>
        </div>
    </div>
</div>