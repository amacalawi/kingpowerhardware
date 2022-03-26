<!--begin::Modal - suppliers - Add-->
<div class="modal fade" id="supplierModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Form-->
            <form class="form" action="{{ url('/auth/components/suppliers/store')}}" id="supplier_Form" method="post">
                <!--begin::Modal header-->
                <div class="modal-header" id="supplier_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bolder">Add a Supplier</h2>
                    <!--end::Modal title-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body py-10 px-lg-17">
                    <!--begin::Scroll-->
                    <div class="scroll-y me-n7 pe-7" id="kt_modal_add_supplier_scroll" data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_add_supplier_header" data-kt-scroll-wrappers="#kt_modal_add_supplier_scroll" data-kt-scroll-offset="300px">
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
                        
                        <div class="fv-row row mb-4">
                            <div class="col-xl-6">
                                {{ Form::label('code', 'Code', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::text($name = 'code', $value = '', 
                                    $attributes = array(
                                        'id' => 'code',
                                        'class' => 'form-control form-control-solid'
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
                        
                        <div class="fv-row row mb-4">
                            <div class="col-sm-12">
                                {{ Form::label('payment_terms_id', 'Payment Terms', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{
                                    Form::select('payment_terms_id', $terms, '', ['data-control' => 'select2', 'data-placeholder' => 'select a payment term', 'data-dropdown-parent' => '#supplierModal', 'id' => 'payment_terms_id', 'class' => 'form-select form-select-solid fw-bolder'])
                                }}
                            </div>
                        </div>
                        <div class="fv-row row mb-4">
                            <div class="col-xl-6">
                                {{ Form::label('contact_person', 'Contact Person', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::text($name = 'contact_person', $value = '', 
                                    $attributes = array(
                                        'id' => 'contact_person',
                                        'class' => 'form-control form-control-solid'
                                    )) 
                                }}
                            </div>
                            <div class="col-xl-6">
                                {{ Form::label('contact_no', 'Contact No', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::text($name = 'contact_no', $value = '', 
                                    $attributes = array(
                                        'id' => 'contact_no',
                                        'class' => 'numeric-only form-control form-control-solid'
                                    )) 
                                }}
                            </div>
                        </div>
                        
                        <div class="fv-row row mb-4">
                            <div class="col-sm-12">
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
                        </div>


                    </div>
                    <!--end::Scroll-->
                </div>
                <!--end::Modal body-->
                <!--begin::Modal footer-->
                <div class="modal-footer flex-center">
                    <!--begin::Button-->
                    <button type="reset" id="supplierModalCancel" class="btn btn-white me-3">Discard</button>
                    <!--end::Button-->
                    <!--begin::Button-->
                    <button type="submit" id="supplierModalSubmit" class="btn btn-primary">
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
<!--end::Modal - suppliers - Add-->