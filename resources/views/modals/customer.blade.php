<!--begin::Modal - Customers - Add-->
<div class="modal fade" id="customerModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Form-->
            <form class="form" action="{{ url('/auth/components/customers/store')}}" id="customer_form" method="post">
                <!--begin::Modal header-->
                <div class="modal-header" id="customer_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bolder">Add a Customer</h2>
                    <!--end::Modal title-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body py-10 px-lg-17">
                    <!--begin::Scroll-->
                    <div class="scroll-y me-n7 pe-7" id="kt_modal_add_customer_scroll" data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_add_customer_header" data-kt-scroll-wrappers="#kt_modal_add_customer_scroll" data-kt-scroll-offset="300px">
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
                        <!--end::Input group-->

                        <!--begin::Input group-->
                        <div class="fv-row row mb-2">
                            <div class="col-sm-6">
                                {{ Form::label('description', 'Company Name', ['class' => 'fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::text($name = 'description', $value = '', 
                                    $attributes = array(
                                        'id' => 'description',
                                        'class' => 'form-control form-control-solid'
                                    )) 
                                }}
                            </div>
                            <div class="col-sm-6">
                                {{ Form::label('agent_id', 'Agent', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{
                                    Form::select('agent_id', $agents, '', ['data-control' => 'select2', 'data-placeholder' => 'select an agent...', 'data-dropdown-parent' => '#customerModal', 'id' => 'agent_id', 'class' => 'form-select form-select-solid fw-bolder'])
                                }}
                            </div>
                        </div>
                        <!--end::Input group-->

                        <!--begin::Input group-->
                        <div class="fv-row row mb-2">
                            <!--begin::Label-->
                            <div class="col-sm-6">
                                {{ Form::label('email', 'Email', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::email($name = 'email', $value = '', 
                                    $attributes = array(
                                        'id' => 'email',
                                        'class' => 'form-control form-control-solid'
                                    )) 
                                }}
                            </div>

                            <div class="col-sm-6">
                                {{ Form::label('mobile_no', 'Mobile No.', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::text($name = 'mobile_no', $value = '', 
                                    $attributes = array(
                                        'id' => 'mobile_no',
                                        'class' => 'form-control form-control-solid'
                                    )) 
                                }}
                            </div>
                        </div>
                        <div class="d-flex flex-column mb-2 fv-row">
                            {{ Form::label('address', 'Address', ['class' => 'fs-6 fw-bold mb-2']) }}
                            {{ 
                                Form::textarea($name = 'address', $value = '', 
                                $attributes = array(
                                    'id' => 'address',
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
                    <button type="reset" id="customerModalCancel" class="btn btn-white me-3">Discard</button>
                    <!--end::Button-->
                    <!--begin::Button-->
                    <button type="submit" id="customerModalSubmit" class="btn btn-primary">
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
<!--end::Modal - Customers - Add-->