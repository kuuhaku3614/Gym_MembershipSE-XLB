<style>
  #successModal .modal-content {
    border: none;
    border-radius: 10px;
  }

  #successModal .modal-header {
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
  }

  #successModal .fa-check-circle {
    animation: scaleIn 0.3s ease-in-out;
  }
  .modal-footer{
    display: flex;
    justify-content: center;
  }

  @keyframes scaleIn {
    0% {
      transform: scale(0);
    }
    100% {
      transform: scale(1);
    }
  }
</style>
<!-- Success Modal -->
<div
  class="modal fade"
  id="successModal"
  tabindex="-1"
  aria-labelledby="successModalLabel"
  aria-hidden="true"
>
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="successModalLabel">Success!</h5>
        <button
          type="button"
          class="btn-close btn-close-white"
          data-bs-dismiss="modal"
          aria-label="Close"
        ></button>
      </div>
      <div class="modal-body text-center">
        <i class="fas fa-check-circle text-success" style="font-size: 48px"></i>
        <p class="mt-3">Created successfully!</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" data-bs-dismiss="modal">
          Okay
        </button>
      </div>
    </div>
  </div>
</div>
<!-- Update Success Modal -->
<div
  class="modal fade"
  id="updateSuccessModal"
  tabindex="-1"
  aria-labelledby="updateSuccessModalLabel"
  aria-hidden="true"
>
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="updateSuccessModalLabel">Update Successful!</h5>
        <button
          type="button"
          class="btn-close btn-close-white"
          data-bs-dismiss="modal"
          aria-label="Close"
        ></button>
      </div>
      <div class="modal-body text-center">
        <i class="fas fa-check-circle text-info" style="font-size: 48px"></i>
        <p class="mt-3">Updated successfully!</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-info" data-bs-dismiss="modal">
          Okay
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>
