<!--begin::Modal - items - Add-->
<div class="modal fade" id="itemModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Form-->
            <form class="form" action="{{ url('/auth/items/listing/store')}}" id="item_form" method="post">
                <!--begin::Modal header-->
                <div class="modal-header" id="item_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bolder">Add a item</h2>
                    <!--end::Modal title-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body py-10 px-lg-17">
                    <!--begin::Scroll-->
                    <div class="scroll-y me-n7 pe-7" id="kt_modal_add_item_scroll" data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_add_item_header" data-kt-scroll-wrappers="#kt_modal_add_item_scroll" data-kt-scroll-offset="300px">
                        <!--begin::Input group-->
                        <div class="fv-row row mb-2 hidden">
                            <div class="col-xl-6">
                                {{ 
                                    Form::text($name = 'method', $value = 'add', 
                                    $attributes = array(
                                        'id' => 'method',
                                        'class' => 'form-control form-control-solid',
                                        'disabled' => 'disabled'
                                    )) 
                                }}
                            </div>
                            <div class="col-xl-6">
                                {{ 
                                    Form::text($name = 'id', $value = '', 
                                    $attributes = array(
                                        'id' => 'id',
                                        'class' => 'form-control form-control-solid',
                                        'disabled' => 'disabled'
                                    )) 
                                }}
                            </div>
                        </div>
                        <div class="fv-row row mb-2">
                            <div class="col-xl-6">
                                {{ Form::label('item_category_id', 'Product Category', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{
                                    Form::select('item_category_id', $categories, '', ['data-control' => 'select2', 'data-placeholder' => 'select a category', 'data-dropdown-parent' => '#itemModal', 'class' => 'form-select form-select-solid fw-bolder'])
                                }}
                            </div>
                            <div class="col-xl-6">
                                {{ Form::label('uom_id', 'Unit of Measurement', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{
                                    Form::select('uom_id', $unit_of_measurements, '', ['data-control' => 'select2', 'data-placeholder' => 'select a uom', 'data-dropdown-parent' => '#itemModal', 'class' => 'form-select form-select-solid fw-bolder'])
                                }}
                            </div>
                        </div>
                        <div class="fv-row row mb-2">
                            <div class="col-xl-6">
                                {{ Form::label('code', 'Code', ['class' => 'fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::text($name = 'code', $value = '', 
                                    $attributes = array(
                                        'id' => 'code',
                                        'class' => 'form-control form-control-solid',
                                        'disabled' => 'disabled'
                                    )) 
                                }}
                            </div>
                            <div class="col-xl-6">
                                {{ Form::label('name', 'Name', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::text($name = 'name', $value = '', 
                                    $attributes = array(
                                        'id' => 'name',
                                        'class' => 'form-control form-control-solid'
                                    )) 
                                }}
                            </div>
                        </div>
                        <!--end::Input group-->
                        
                        <div class="d-flex flex-column mb-2 fv-row">
                            {{ Form::label('description', 'Description', ['class' => 'fs-6 fw-bold mb-2']) }}
                            {{ 
                                Form::textarea($name = 'description', $value = '', 
                                $attributes = array(
                                    'id' => 'description',
                                    'class' => 'form-control form-control-solid',
                                    'rows' => 2
                                )) 
                            }}
                        </div>

                        <!--begin::Input group-->
                        <div class="fv-row row mb-2">
                            <!--begin::Label-->
                            <div class="col-sm-4">
                                {{ Form::label('srp', 'SRP', ['class' => 'fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::text($name = 'srp', $value = '', 
                                    $attributes = array(
                                        'id' => 'srp',
                                        'class' => 'numeric form-control form-control-solid'
                                    )) 
                                }}
                            </div>
                            <div class="col-sm-4">
                                {{ Form::label('srp2', 'SRP 2', ['class' => 'fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::text($name = 'srp2', $value = '', 
                                    $attributes = array(
                                        'id' => 'srp2',
                                        'class' => 'numeric form-control form-control-solid'
                                    )) 
                                }}
                            </div>
                            <div class="col-sm-4">
                                {{ Form::label('reorder_level', 'Re-order Level', ['class' => 'fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::text($name = 'reorder_level', $value = '', 
                                    $attributes = array(
                                        'id' => 'reorder_level',
                                        'class' => 'numeric form-control form-control-solid'
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
                    <button type="reset" id="itemModalCancel" class="btn btn-white me-3">Discard</button>
                    <!--end::Button-->
                    <!--begin::Button-->
                    <button type="submit" id="itemModalSubmit" class="btn btn-primary">
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