<!--begin::Modal - payment-terms - Add-->
<div class="modal fade" id="importPaymentTermModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Modal header-->
            <div class="modal-header bg-primary c-white" id="PaymentTerm_header">
                <!--begin::Modal title-->
                <h2 class="fw-normal">Import Payment Term</h2>
                <!--end::Modal title-->
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <!--begin::Svg Icon | path: icons/duotone/Navigation/Close.svg-->
                    <i class="las la-times text-white" style="font-size:20px;"></i>
                    <!--end::Svg Icon-->
                </div>
            </div>
            <!--end::Modal header-->
            <!--begin::Modal body-->
            <div class="modal-body">
                <form method="POST" action="{{ url('/auth/components/payment-terms/import')}}" class="dropzone dz-clickable" id="import-payment-term-dropzone">
                    @csrf
                    <div class="dz-default dz-message"><span>Drop files here to upload</span></div>
                </form>
            </div>
            <!--end::Modal body-->
        </div>
    </div>
</div>
<!--end::Modal - payment-terms - Add-->