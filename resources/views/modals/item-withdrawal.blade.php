<!--begin::Modal - items - Add-->
<div class="modal fade" id="itemWithdrawalModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Form-->
            <form class="form" action="{{ url('/auth/items/listing/store-withdrawal')}}" id="itemWithdrawalForm" method="post">
                <!--begin::Modal header-->
                <div class="modal-header" id="item_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bolder">Item Withdrawal (<span class="item-code"></span>)<span class="item-id hidden"></span></h2>
                    <!--end::Modal title-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body py-10 px-lg-17">
                    <!--begin::Scroll-->
                    <div class="scroll-y me-n7 pe-7" id="kt_modal_add_item_scroll" data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_add_item_header" data-kt-scroll-wrappers="#kt_modal_add_item_scroll" data-kt-scroll-offset="300px">
                        <!--begin::Input group-->
                        <div class="fv-row row mb-4">
                            <div class="col-xl-6">
                                {{ Form::label('transaction', 'Transaction', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{
                                    Form::select('transaction', $withdrawalTrans, '', ['data-control' => 'select2', 'data-placeholder' => 'select a transaction', 'data-dropdown-parent' => '#itemWithdrawalModal', 'class' => 'form-select form-select-solid fw-bolder'])
                                }}
                            </div>
                            <div class="col-xl-6">
                                {{ Form::label('branch_id', 'Branch', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{
                                    Form::select('branch_id', $branches, '', ['data-control' => 'select2', 'data-placeholder' => 'select a branch', 'data-dropdown-parent' => '#itemWithdrawalModal', 'class' => 'form-select form-select-solid fw-bolder'])
                                }}
                            </div>
                        </div>
                        <div class="fv-row row mb-4 hidden">
                            <div class="col-xl-12">
                                {{ Form::label('transfer_to', 'Transfer To', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{
                                    Form::select('transfer_to', $branches, '', ['data-control' => 'select2', 'data-placeholder' => 'select a branch', 'data-dropdown-parent' => '#itemWithdrawalModal', 'class' => 'form-select form-select-solid fw-bolder'])
                                }}
                            </div>
                        </div>
                        <div class="fv-row row mb-4">
                            <div class="col-xl-6">
                                {{ Form::label('issued_by', 'Issued By', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{
                                    Form::select('issued_by', $users, '', ['data-control' => 'select2', 'data-placeholder' => 'select a user', 'data-dropdown-parent' => '#itemWithdrawalModal', 'class' => 'form-select form-select-solid fw-bolder'])
                                }}
                            </div>
                            <div class="col-xl-6">
                                {{ Form::label('received_by', 'Received By', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{
                                    Form::select('received_by', $users, '', ['data-control' => 'select2', 'data-placeholder' => 'select a user', 'data-dropdown-parent' => '#itemWithdrawalModal', 'class' => 'form-select form-select-solid fw-bolder'])
                                }}
                            </div>
                        </div>
                        <div class="fv-row row mb-4">
                            <div class="col-xl-6">
                                {{ Form::label('based_quantity', 'Based Quantity', ['class' => 'fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::text($name = 'based_quantity', $value = '', 
                                    $attributes = array(
                                        'id' => 'based_quantity',
                                        'class' => 'form-control form-control-solid',
                                        'disabled' => 'disabled',
                                    )) 
                                }}
                            </div>
                            <div class="col-xl-6">
                                {{ Form::label('issued_quantity', 'Quantity Release', ['class' => 'numeric required fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::text($name = 'issued_quantity', $value = '', 
                                    $attributes = array(
                                        'id' => 'issued_quantity',
                                        'class' => 'form-control form-control-solid'
                                    )) 
                                }}
                            </div>
                        </div>
                        <div class="fv-row row mb-4">
                            <div class="col-xl-6">
                                <div class="d-flex justify-content-between">
                                    {{ Form::label('srp', 'SRP', ['class' => 'fs-6 fw-bold mb-2']) }}
                                    <div class="form-check form-check-custom form-check-solid">
                                        <label class="form-check-label" for="flexCheckDefault">
                                            Set 0 SRP
                                        </label>
                                        <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault" style="margin-left: 10px; margin-top: -5px"/>
                                    </div>
                                </div>
                                {{ 
                                    Form::text($name = 'srp', $value = '', 
                                    $attributes = array(
                                        'id' => 'srp',
                                        'class' => 'form-control form-control-solid',
                                        'disabled' => 'disabled'
                                    )) 
                                }}
                            </div>
                            <div class="col-xl-6">
                                {{ Form::label('total_amount', 'Total Amount', ['class' => 'fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::text($name = 'total_amount', $value = '', 
                                    $attributes = array(
                                        'id' => 'total_amount',
                                        'class' => 'form-control form-control-solid',
                                        'disabled' => 'disabled'
                                    )) 
                                }}
                            </div>
                        </div>
                        
                        <div class="d-flex flex-column fv-row">
                            {{ Form::label('remarks', 'Remarks', ['class' => 'required fs-6 fw-bold mb-2']) }}
                            {{ 
                                Form::textarea($name = 'remarks', $value = '', 
                                $attributes = array(
                                    'id' => 'remarks',
                                    'class' => 'form-control form-control-solid',
                                    'rows' => 2
                                )) 
                            }}
                        </div>
                    </div>
                    <!--end::Scroll-->
                </div>
                <!--end::Modal body-->
                <!--begin::Modal footer-->
                <div class="modal-footer flex-center">
                    <!--begin::Button-->
                    <button type="reset" id="itemWithdrawalModalCancel" class="btn btn-white me-3">Discard</button>
                    <!--end::Button-->
                    <!--begin::Button-->
                    <button type="submit" id="itemWithdrawalModalSubmit" class="btn btn-primary">
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
<!--end::Modal - items - Add-->