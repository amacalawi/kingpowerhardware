<div class="stepper stepper-links d-flex flex-column" id="kt_create_account_stepper">
    <div class="stepper-nav py-5 mb-5">
        <!--begin::Step 1-->
        <div steps="1" class="stepper-item current" data-kt-stepper-element="nav">
            <h3 class="stepper-title">Transfer Details</h3>
        </div>
        <!--end::Step 1-->
        <!--begin::Step 2-->
        <div steps="2" class="stepper-item" data-kt-stepper-element="nav">
            <h3 class="stepper-title">Item Details</h3>
        </div>
        <!--end::Step 2-->
    </div>
    <div steps="1" class="current" data-kt-stepper-element="content" style="min-height: 356px">
        {{ Form::open(array(
            'url' => 'auth/items/transfer-items/store',
            'method' => 'POST',
            'id' => 'transferItemForm',
            'class' => 'w-100'
            )) 
        }}  
            <div class="fv-row row mb-4 hidden">
                <div class="col-sm-6">
                    {{ Form::label('transfer_item_id', 'ID', ['class' => 'fs-6 fw-bold mb-2']) }}
                    {{ 
                        Form::text($name = 'transfer_item_id', $value = '', 
                        $attributes = array(
                            'id' => 'transfer_item_id',
                            'class' => 'form-control form-control-solid',
                            'disabled' => 'disabled'
                        )) 
                    }}
                </div>
            </div>
            <div class="fv-row row mb-4">
                <div class="col-sm-12">
                    {{ Form::label('transfer_no', 'Transfer No', ['class' => 'fs-6 fw-bold mb-2']) }}
                    {{ 
                        Form::text($name = 'transfer_no', $value = '', 
                        $attributes = array(
                            'id' => 'transfer_no',
                            'class' => 'form-control form-control-solid',
                            'disabled' => 'disabled'
                        )) 
                    }}
                </div>
            </div>
            <div class="fv-row row mb-4">
                <div class="col-sm-6">
                    {{ Form::label('transfer_from', 'Transfer From', ['class' => 'required fs-6 fw-bold mb-2']) }}
                    {{
                        Form::select('transfer_from', $branches1, '', ['data-control' => 'select2', 'data-placeholder' => 'select a transfer from', 'data-dropdown-parent' => '#transferItemModal', 'id' => 'transfer_from', 'class' => 'form-select form-select-solid fw-bolder'])
                    }}
                </div>
                <div class="col-sm-6">
                    {{ Form::label('transfer_to', 'Transfer To', ['class' => 'required fs-6 fw-bold mb-2']) }}
                    {{
                        Form::select('transfer_to', $branches2, '', ['data-control' => 'select2', 'data-placeholder' => 'select a transfer to', 'data-dropdown-parent' => '#transferItemModal', 'id' => 'transfer_to', 'class' => 'form-select form-select-solid fw-bolder'])
                    }}
                </div>
            </div>
            <div class="fv-row row mb-4">
                <div class="col-sm-12">
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
        {{ Form::close() }}
    </div>
    <div steps="2" class="" data-kt-stepper-element="content" style="min-height: 356px">
            {{ Form::open(array(
                'url' => 'auth/items/transfer-items/store-line-item',
                'method' => 'POST',
                'id' => 'transferItemLineForm',
                'class' => 'w-100'
                )) 
            }} 
            <div class="fv-row row mb-4 hidden">
                <div class="col-sm-6">
                    {{ Form::label('transfer_item_line_id', 'Line ID', ['class' => 'fs-6 fw-bold mb-2']) }}
                    {{ 
                        Form::text($name = 'transfer_item_line_id', $value = '', 
                        $attributes = array(
                            'id' => 'transfer_item_line_id',
                            'class' => 'form-control form-control-solid',
                            'disabled' => 'disabled'
                        )) 
                    }}
                </div>
            </div> 
            <div class="fv-row row mb-4">
                <div class="col-sm-6">
                    {{ Form::label('item_id', 'Item', ['class' => 'required fs-6 fw-bold mb-2']) }}
                    {{
                        Form::select('item_id', $items, '', ['data-control' => 'select2', 'data-placeholder' => 'select an item', 'data-dropdown-parent' => '#transferItemModal', 'id' => 'item_id', 'class' => 'form-select form-select-solid fw-bolder'])
                    }}
                </div>
                <div class="col-sm-6">
                    {{ Form::label('uom_id', 'Unit Of Measurement', ['class' => 'required fs-6 fw-bold mb-2']) }}
                    {{
                        Form::select('uom_id', $uoms, '', ['data-control' => 'select2', 'data-placeholder' => 'select a uom', 'data-dropdown-parent' => '#transferItemModal', 'id' => 'uom_id', 'class' => 'form-select form-select-solid fw-bolder'])
                    }}
                </div>
            </div>
            <div class="fv-row row mb-4">
                <div class="col-sm-6">
                    {{ Form::label('qty', 'Quantity', ['class' => 'required fs-6 fw-bold mb-2']) }}
                    {{ 
                        Form::text($name = 'qty', $value = '', 
                        $attributes = array(
                            'id' => 'qty',
                            'class' => 'numeric form-control form-control-solid',
                        )) 
                    }}
                </div>
                <div class="col-sm-6">
                    {{ Form::label('based_quantity', 'Quantity On-Hand', ['class' => 'fs-6 fw-bold mb-2']) }}
                    {{ 
                        Form::text($name = 'based_quantity', $value = '', 
                        $attributes = array(
                            'id' => 'based_quantity',
                            'class' => 'numeric form-control form-control-solid',
                            'disabled' => 'disabled'
                        )) 
                    }}
                </div>
            </div>
            <div class="fv-row row mb-4">
                <div class="col-sm-6">
                    {{ Form::label('srp', 'SRP', ['class' => 'fs-6 fw-bold mb-2']) }}
                    {{ 
                        Form::text($name = 'srp', $value = '', 
                        $attributes = array(
                            'id' => 'srp',
                            'class' => 'numeric form-control form-control-solid',
                            'disabled' => 'disabled'
                        )) 
                    }}
                </div>
                <div class="col-sm-6">
                    {{ Form::label('total_amount', 'Total Amount', ['class' => 'fs-6 fw-bold mb-2']) }}
                    {{ 
                        Form::text($name = 'total_amount', $value = '', 
                        $attributes = array(
                            'id' => 'total_amountx',
                            'class' => 'form-control form-control-solid',
                            'disabled' => 'disabled'
                        )) 
                    }}
                </div>
            </div>
            <div class="fv-row row">
                <div class="col-sm-12 text-center mt-4">
                <button type="button" class="add-item-btn btn btn-lg btn-primary">
                    <span class="indicator-label">
                    <!--begin::Svg Icon -->
                    <span class="svg-icon svg-icon-4 ms-1 me-0"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                            <rect fill="#000000" x="4" y="11" width="16" height="2" rx="1"/>
                            <rect fill="#000000" opacity="0.5" transform="translate(12.000000, 12.000000) rotate(-270.000000) translate(-12.000000, -12.000000) " x="4" y="11" width="16" height="2" rx="1"/>
                    </svg></span>
                    <!--end::Svg Icon-->
                    Add Item</span>
                    <span class="indicator-progress">Please wait...
                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                </button>
                </div>
            </div>
        {{ Form::close() }}
    </div>
</div>
<div id="prev-next-btn-holder" class="d-flex flex-stack pt-5">
    <!--begin::Wrapper-->
    <div class="mr-2">
        <button id="prev-btn" type="button" class="hidden prev-next-btn prev btn btn-lg btn-light-primary me-3" data-kt-stepper-action="previous">
        <!--begin::Svg Icon | path: icons/duotone/Navigation/Left-2.svg-->
        <span class="svg-icon svg-icon-4 me-1">
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                    <polygon points="0 0 24 0 24 24 0 24" />
                    <rect fill="#000000" opacity="0.3" transform="translate(15.000000, 12.000000) scale(-1, 1) rotate(-90.000000) translate(-15.000000, -12.000000)" x="14" y="7" width="2" height="10" rx="1" />
                    <path d="M3.7071045,15.7071045 C3.3165802,16.0976288 2.68341522,16.0976288 2.29289093,15.7071045 C1.90236664,15.3165802 1.90236664,14.6834152 2.29289093,14.2928909 L8.29289093,8.29289093 C8.67146987,7.914312 9.28105631,7.90106637 9.67572234,8.26284357 L15.6757223,13.7628436 C16.0828413,14.136036 16.1103443,14.7686034 15.7371519,15.1757223 C15.3639594,15.5828413 14.7313921,15.6103443 14.3242731,15.2371519 L9.03007346,10.3841355 L3.7071045,15.7071045 Z" fill="#000000" fill-rule="nonzero" transform="translate(9.000001, 11.999997) scale(-1, -1) rotate(90.000000) translate(-9.000001, -11.999997)" />
                </g>
            </svg>
        </span>
        <!--end::Svg Icon-->Back</button>
    </div>
    <!--end::Wrapper-->
    <!--begin::Wrapper-->
    <div>
        <button id="next-btn" type="button" class="prev-next-btn next btn btn-lg btn-primary" data-kt-stepper-action="next">Next
        <!--begin::Svg Icon | path: icons/duotone/Navigation/Right-2.svg-->
        <span class="svg-icon svg-icon-4 ms-1 me-0">
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                    <polygon points="0 0 24 0 24 24 0 24" />
                    <rect fill="#000000" opacity="0.5" transform="translate(8.500000, 12.000000) rotate(-90.000000) translate(-8.500000, -12.000000)" x="7.5" y="7.5" width="2" height="9" rx="1" />
                    <path d="M9.70710318,15.7071045 C9.31657888,16.0976288 8.68341391,16.0976288 8.29288961,15.7071045 C7.90236532,15.3165802 7.90236532,14.6834152 8.29288961,14.2928909 L14.2928896,8.29289093 C14.6714686,7.914312 15.281055,7.90106637 15.675721,8.26284357 L21.675721,13.7628436 C22.08284,14.136036 22.1103429,14.7686034 21.7371505,15.1757223 C21.3639581,15.5828413 20.7313908,15.6103443 20.3242718,15.2371519 L15.0300721,10.3841355 L9.70710318,15.7071045 Z" fill="#000000" fill-rule="nonzero" transform="translate(14.999999, 11.999997) scale(1, -1) rotate(90.000000) translate(-14.999999, -11.999997)" />
                </g>
            </svg>
        </span>
        <!--end::Svg Icon--></button>
    </div>
    <!--end::Wrapper-->
</div>