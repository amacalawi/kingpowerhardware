<!--begin::Modal - paymentLinees - Add-->
<div class="modal fade" id="paymentLineModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Form-->
            <form class="form" action="{{ url('/auth/billing/store-payment-line')}}" id="paymentLine_form" method="post">
                <!--begin::Modal header-->
                <div class="modal-header" id="paymentLine_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bolder">Add a Payment</h2>
                    <!--end::Modal title-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body py-10 px-lg-17">
                    <!--begin::Scroll-->
                    <div class="scroll-y me-n7 pe-7" id="kt_modal_add_paymentLine_scroll" data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_add_paymentLine_header" data-kt-scroll-wrappers="#kt_modal_add_paymentLine_scroll" data-kt-scroll-offset="300px">

                        <div class="fv-row row mb-4 hidden">
                            <div class="col-xl-6">
                                {{ Form::label('payment_id', 'Payment ID', ['class' => 'fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::text($name = 'payment_id', $value = '', 
                                    $attributes = array(
                                        'id' => 'payment_id',
                                        'class' => 'form-control form-control-solid',
                                        'disabled' => 'disabled'
                                    )) 
                                }}
                            </div>
                        </div>
                        <div class="fv-row row mb-4">
                            <div class="col-xl-6">
                                {{ Form::label('payment_type_id', 'Payment Type', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{
                                    Form::select('payment_type_id', $types, '', ['data-control' => 'select2', 'data-placeholder' => 'select a payment type', 'data-dropdown-parent' => '#paymentLineModal', 'id' => 'payment_type_id', 'class' => 'form-select form-select-solid fw-bolder'])
                                }}
                            </div>
                            <div class="col-xl-6">
                                {{ Form::label('bank_id', 'Bank', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{
                                    Form::select('bank_id', $banks, '', ['data-control' => 'select2', 'data-placeholder' => 'select a bank', 'data-dropdown-parent' => '#paymentLineModal', 'id' => 'bank_id', 'class' => 'form-select form-select-solid fw-bolder'])
                                }}
                            </div>
                        </div>
                            
                        <div class="fv-row row mb-4">
                            <div class="col-sm-6 hidden">
                                {{ Form::label('bank_name', 'Bank Name', ['class' => 'fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::text($name = 'bank_name', $value = '', 
                                    $attributes = array(
                                        'id' => 'bank_name',
                                        'class' => 'form-control form-control-solid',
                                        'disabled' => 'disabled'
                                    )) 
                                }}
                            </div>
                            <div class="col-sm-6">
                                {{ Form::label('bank_no', 'Bank No', ['class' => 'fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::text($name = 'bank_no', $value = '', 
                                    $attributes = array(
                                        'id' => 'bank_no',
                                        'class' => 'form-control form-control-solid',
                                        'disabled' => 'disabled'
                                    )) 
                                }}
                            </div>
                            <div class="col-sm-6">
                                {{ Form::label('bank_account', 'Bank Account', ['class' => 'fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::text($name = 'bank_account', $value = '', 
                                    $attributes = array(
                                        'id' => 'bank_account',
                                        'class' => 'form-control form-control-solid',
                                        'disabled' => 'disabled'
                                    )) 
                                }}
                            </div>
                        </div>
                        <div class="fv-row row mb-4">
                            <div class="col-sm-6">
                                {{ Form::label('cheque_no', 'Cheque No', ['class' => 'fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::text($name = 'cheque_no', $value = '', 
                                    $attributes = array(
                                        'id' => 'cheque_no',
                                        'class' => 'form-control form-control-solid',
                                        'disabled' => 'disabled'
                                    )) 
                                }}
                            </div>
                            <div class="col-sm-6">
                                {{ Form::label('cheque_date', 'Cheque Date', ['class' => 'fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::text($name = 'cheque_date', $value = '', 
                                    $attributes = array(
                                        'id' => 'cheque_date',
                                        'class' => 'form-control form-control-solid',
                                        'disabled' => 'disabled'
                                    )) 
                                }}
                            </div>
                        </div>
                        <div class="fv-row row mb-4">
                            <div class="col-sm-12">
                                {{ Form::label('amount', 'Amount', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::text($name = 'amount', $value = '', 
                                    $attributes = array(
                                        'id' => 'amount',
                                        'class' => 'numeric form-control form-control-solid',
                                    )) 
                                }}
                            </div>
                        </div>
                        <div class="fv-row row mb-4">
                            <div class="col-sm-12">
                                {{ Form::label('external_doc', 'External Document', ['class' => 'fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::textarea($name = 'external_doc', $value = '', 
                                    $attributes = array(
                                        'id' => 'external_doc',
                                        'class' => 'form-control form-control-solid',
                                        'rows' => 2
                                    )) 
                                }}
                            </div>
                        </div>

                    </div>
                    <!--end::Scroll-->
                </div>
                <!--end::Modal body-->
                <!--begin::Modal footer-->
                <div class="modal-footer flex-center">
                    <!--begin::Button-->
                    <button type="reset" id="paymentLineModalCancel" class="btn btn-white me-3">Discard</button>
                    <!--end::Button-->
                    <!--begin::Button-->
                    <button type="submit" id="paymentLineModalSubmit" class="btn btn-primary">
                        <span class="indicator-label">Submit</span>
                        <span class="indicator-progress">Please wait...
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                    <!--end::Button-->
                </div>
                <!--end::Modal footer-->
            </form>
            <!--end::Form-->
        </div>
    </div>
</div>
<!--end::Modal - paymentLinees - Add-->