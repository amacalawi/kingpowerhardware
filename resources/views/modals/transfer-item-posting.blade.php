<div class="modal bg-shadow fade" tabindex="-2" id="transferItemPostingModal" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow-none">
            <div class="modal-header">
                <h5 class="modal-title">Transfer Item Posting</h5>
                <div class="card-toolbar">
                    <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-6 border-0">
                    </ul>
                </div>
            </div>
            <div class="modal-body">
                @include('forms.transfer-item-posting')
            </div>

            <div class="modal-footer flex-center">
                <!--begin::Button-->
                <button type="button" id="transferItemPostingModalSubmit" class="btn btn-light-success">
                    <span class="indicator-label">POST</span>
                    <span class="indicator-progress">Please wait...
                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                </button>
                <!--end::Button-->
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">CANCEL</button>
            </div>
        </div>
    </div>
</div>