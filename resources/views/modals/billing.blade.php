<div class="modal bg-white fade" tabindex="-2" data-bs-backdrop="static" data-bs-keyboard="false" id="billingModal">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content shadow-none">
            <div class="modal-header">
                <h5 class="modal-title">New Invoice</h5>
                <div class="card-toolbar">
                    <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-6 border-0">
                        <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
							<!--begin::Svg Icon | path: icons/duotone/Navigation/Close.svg-->
							<span class="svg-icon svg-icon-1">
								<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
									<g transform="translate(12.000000, 12.000000) rotate(-45.000000) translate(-12.000000, -12.000000) translate(4.000000, 4.000000)" fill="#000000">
										<rect fill="#000000" x="0" y="7" width="16" height="2" rx="1"></rect>
										<rect fill="#000000" opacity="0.5" transform="translate(8.000000, 8.000000) rotate(-270.000000) translate(-8.000000, -8.000000)" x="0" y="7" width="16" height="2" rx="1"></rect>
									</g>
								</svg>
							</span>
							<!--end::Svg Icon-->
						</div>
                    </ul>
                </div>
            </div>

            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-4 me-pe-10">
                        @include('forms.billing')
                    </div>
                    <div class="col-sm-8 me-ps-10">
                        @include('forms.billing-line')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>