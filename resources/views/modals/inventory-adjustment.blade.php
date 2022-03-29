<!--begin::Modal - purchase-order-types - Add-->
<div class="modal fade" id="inventoryAdjustmentModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Form-->
            <form class="form" action="{{ url('/auth/items/inventory-adjustment/store')}}" id="inventoryAdjustment_Form" method="post">
                <!--begin::Modal header-->
                <div class="modal-header" id="inventoryAdjustment_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bolder">Add Inventory Adustment</h2>
                    <!--end::Modal title-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body py-10 px-lg-17">
                    <!--begin::Scroll-->
                    <div class="scroll-y me-n7 pe-7" id="kt_modal_add_inventoryAdjustment_scroll" data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_add_inventoryAdjustment_header" data-kt-scroll-wrappers="#kt_modal_add_inventoryAdjustment_scroll" data-kt-scroll-offset="300px">
                        <!--begin::Input group-->
                        <div class="fv-row row mb-4">
                            <div class="col-xl-6">
                                {{ Form::label('category', 'Category', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{
                                    Form::select('category', $categories, '', ['data-control' => 'select2', 'data-placeholder' => 'select a category...', 'data-dropdown-parent' => '#inventoryAdjustmentModal', 'id' => 'category', 'class' => 'form-select form-select-solid fw-bolder'])
                                }}
                            </div>
                            <div class="col-xl-6">
                                {{ Form::label('branch_id', 'Branch', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{
                                    Form::select('branch_id', $branches, '', ['data-control' => 'select2', 'data-placeholder' => 'select a branch...', 'data-dropdown-parent' => '#inventoryAdjustmentModal', 'id' => 'branch_id', 'class' => 'form-select form-select-solid fw-bolder'])
                                }}
                            </div>
                        </div>
                        
                        <div class="fv-row row mb-4">
                            <div class="col-xl-6">
                                {{ Form::label('item_id', 'Item', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{
                                    Form::select('item_id', $items, '', ['data-control' => 'select2', 'data-placeholder' => 'select an item...', 'data-dropdown-parent' => '#inventoryAdjustmentModal', 'id' => 'item_id', 'class' => 'form-select form-select-solid fw-bolder'])
                                }}
                            </div>
                            <div class="col-xl-6">
                                {{ Form::label('uom', 'UOM', ['class' => 'fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::text($name = 'uom', $value = '', 
                                    $attributes = array(
                                        'id' => 'uom',
                                        'class' => 'form-control form-control-solid',
                                        'disabled' => 'disabled'
                                    )) 
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
                                        'class' => 'numeric form-control form-control-solid',
                                        'disabled' => 'disabled'
                                    )) 
                                }}
                            </div>
                            <div class="col-xl-6">
                                {{ Form::label('quantity', 'Quantity Issued', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::text($name = 'quantity', $value = '', 
                                    $attributes = array(
                                        'id' => 'quantity',
                                        'class' => 'numeric form-control form-control-solid'
                                    )) 
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


                    </div>
                    <!--end::Scroll-->
                </div>
                <!--end::Modal body-->
                <!--begin::Modal footer-->
                <div class="modal-footer flex-center">
                    <!--begin::Button-->
                    <button type="reset" id="inventoryAdjustmentModalCancel" data-bs-dismiss="modal" class="btn btn-white me-3">Discard</button>
                    <!--end::Button-->
                    <!--begin::Button-->
                    <button type="submit" id="inventoryAdjustmentModalSubmit" class="btn btn-primary">
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
<!--end::Modal - purchase-order-types - Add-->