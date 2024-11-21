<div class="modal fade" id="programDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Program Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="font-weight-bold mb-3">Program Information</h6>
                        <div id="programDetailContent" class="program-details">
                            <input type="hidden" class="program-id">
                            <h5 class="program-name mb-3"></h5>
                            <div class="program-description mb-3"></div>
                            <div class="program-schedule mb-3"></div>
                            <div class="program-price font-weight-bold text-primary mb-3"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="font-weight-bold mb-3">Available Coaches</h6>
                        <select id="coachSelect" class="form-control mb-3"></select>
                        <div id="coachDetails" class="coach-details p-3 bg-light rounded">
                            <div class="coach-photo mb-3"></div>
                            <div class="coach-info"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="addProgramDetailBtn">Add to Membership</button>
            </div>
        </div>
    </div>
</div>