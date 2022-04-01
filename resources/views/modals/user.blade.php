<!--begin::Modal - users - Add-->
<div class="modal fade" id="userModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Form-->
            <form class="form" action="{{ url('/auth/components/users/store')}}" id="user_Form" method="post">
                <!--begin::Modal header-->
                <div class="modal-header" id="user_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bolder">Add a User</h2>
                    <!--end::Modal title-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body py-10 px-lg-17">
                    <!--begin::Scroll-->
                    <div class="scroll-y me-n7 pe-7" id="kt_modal_add_user_scroll" data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_add_user_header" data-kt-scroll-wrappers="#kt_modal_add_user_scroll" data-kt-scroll-offset="300px">
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
                                {{ Form::label('name', 'Name', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::text($name = 'name', $value = '', 
                                    $attributes = array(
                                        'id' => 'name',
                                        'class' => 'form-control form-control-solid'
                                    )) 
                                }}
                            </div>
                            <div class="col-xl-6">
                                {{ Form::label('type', 'Role', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{
                                    Form::select('type', $roles, '', ['data-control' => 'select2', 'data-placeholder' => 'select a role', 'data-dropdown-parent' => '#userModal', 'id' => 'role_id', 'class' => 'form-select form-select-solid fw-bolder'])
                                }}
                            </div>
                        </div>
                        <div class="fv-row row mb-4">
                            <div class="col-xl-12">
                                {{ Form::label('assignment', 'Assignment', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{
                                    Form::select('assignment[]', $branches, '', ['data-control' => 'select2', 'data-placeholder' => 'select an assignment', 'data-dropdown-parent' => '#userModal', 'id' => 'assignment', 'multiple' => 'multiple', 'class' => 'form-select form-select-solid fw-bolder'])
                                }}
                            </div>
                        </div>
                        <div class="fv-row row mb-4">
                            <div class="col-xl-6">
                                {{ Form::label('username', 'Username', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::text($name = 'username', $value = '', 
                                    $attributes = array(
                                        'id' => 'username',
                                        'class' => 'form-control form-control-solid'
                                    )) 
                                }}
                            </div>
                            <div class="col-xl-6">
                                {{ Form::label('email', 'Email', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::text($name = 'email', $value = '', 
                                    $attributes = array(
                                        'id' => 'email',
                                        'class' => 'form-control form-control-solid'
                                    )) 
                                }}
                            </div>
                        </div>
                        <div class="fv-row row mb-4">
                            <div class="col-xl-6">
                                <label for="password" class="w-100 required fs-6 fw-bold mb-2">Password 
                                    <span class="pull-right"><i class="la la-eye"></i></span>
                                </label>
                                <input id="password" class="form-control form-control-solid" name="password" type="password" value="">
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                            </div>
                            <div class="col-xl-6">
                                <label for="password" class="w-100 required fs-6 fw-bold mb-2">Confirm Password 
                                    <span class="pull-right"><i class="la la-eye"></i></span>
                                </label>
                                <input id="confirm_password" class="form-control form-control-solid" name="confirm_password" type="password" value="">
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="fv-row row mb-4">
                            <div class="col-xl-6">
                                {{ Form::label('secret_question_id', 'Secret Question', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{
                                    Form::select('secret_question_id', $secrets, '', ['data-control' => 'select2', 'data-placeholder' => 'select a secret question', 'data-dropdown-parent' => '#userModal', 'id' => 'secret_question_id', 'class' => 'form-select form-select-solid fw-bolder'])
                                }}
                            </div>
                            <div class="col-xl-6">
                                <label for="secret_password" class="w-100 required fs-6 fw-bold mb-2">Secret Password 
                                    <span class="pull-right"><i class="la la-eye"></i></span>
                                </label>
                                <input id="secret_password" class="form-control form-control-solid" name="secret_password" type="password" value="">
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                            </div>
                        </div>

                    </div>
                    <!--end::Scroll-->
                </div>
                <!--end::Modal body-->
                <!--begin::Modal footer-->
                <div class="modal-footer flex-center">
                    <!--begin::Button-->
                    <button type="reset" id="userModalCancel" class="btn btn-white me-3">Discard</button>
                    <!--end::Button-->
                    <!--begin::Button-->
                    <button type="submit" id="userModalSubmit" class="btn btn-primary">
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
<!--end::Modal - users - Add-->