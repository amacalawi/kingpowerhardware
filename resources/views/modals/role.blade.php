<!--begin::Modal - role - Add-->
<div class="modal fade" id="roleModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Modal header-->
            <div class="modal-header bg-primary c-white" id="billing_line_header">
                <!--begin::Modal title-->
                <h2 class="fw-normal">Add Role</h2>
            </div>
            <!--end::Modal header-->
            <form class="form" action="{{ url('/auth/components/roles/store')}}" id="roleForm" method="post">
            <!--begin::Modal body-->
            <div class="modal-body">
                <div class="card">
                    <div class="card-body pt-0 ps-1 pe-1">
                        <div class="fv-row row mb-4 hidden">
                            <div class="col-xl-6">
                                {{ Form::label('role_id', 'ID', ['class' => 'required fs-6 fw-bold mb-2']) }}
                                {{ 
                                    Form::text($name = 'role_id', $value = '', 
                                    $attributes = array(
                                        'id' => 'role_id',
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
                        <div class="separator d-flex flex-center my-8">
                        </div>
                        <a href="javascript:;" class="check-roles" value="checkall">[ Check All ]</a>
                        <a href="javascript:;" class="check-roles" value="uncheckall">[ Uncheck All ]</a>
                        <!--begin::Accordion-->
                        <div class="accordion" id="kt_accordion_1">
                            <div class="row">
                                @foreach ($modules as $modulex)
                                    @foreach ($modulex['modules'] as $module)
                                    <div class="col-sm-6 my-3">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="kt_accordion_1_header_2">
                                                <button class="w-100 accordion-buttons fs-4 fw-bold" type="button" >
                                                    <div class="form-check form-check-custom form-check-solid">
                                                        <input class="form-check-input" type="checkbox" value="{{ $module['id'] }}" name="modules[]"/>
                                                        <label class="form-check-label" for="flexCheckDefault">
                                                            {{ $module['name'] }}
                                                        </label>
                                                    </div>
                                                    <span class="flex-fill text-right collapsed" data-bs-toggle="collapse" data-bs-target="#module_{{ $module['id'] }}" aria-expanded="false" aria-controls="module_{{ $module['id'] }}">
                                                        <i class="la la-plus fs-2"></i>
                                                    </span>
                                                </button>
                                            </h2>
                                            <div id="module_{{ $module['id'] }}" class="accordion-collapse collapse" aria-labelledby="kt_accordion_1_header_2">
                                                <div class="accordion-body">
                                                    <div class="d-flex">
                                                        <div class="form-check flex-fill form-check-custom form-check-solid">
                                                            <input module="{{ $module['id'] }}" class="form-check-input" type="checkbox" value="1" name="crudx[{{ $module['id'] }}][0]"/>
                                                            <label class="form-check-label" for="flexCheckbox30">
                                                                create
                                                            </label>
                                                        </div>
                                                        <div class="form-check flex-fill form-check-custom form-check-solid">
                                                            <input module="{{ $module['id'] }}" class="form-check-input" type="checkbox" value="1" name="crudx[{{ $module['id'] }}][1]"/>
                                                            <label class="form-check-label" for="flexCheckbox30">
                                                                read
                                                            </label>
                                                        </div>
                                                        <div class="form-check flex-fill form-check-custom form-check-solid">
                                                            <input module="{{ $module['id'] }}" class="form-check-input" type="checkbox" value="1" name="crudx[{{ $module['id'] }}][2]"/>
                                                            <label class="form-check-label" for="flexCheckbox30">
                                                                update
                                                            </label>
                                                        </div>
                                                        <div class="form-check flex-fill -check-custom form-check-solid">
                                                            <input module="{{ $module['id'] }}" class="form-check-input" type="checkbox" value="1" name="crudx[{{ $module['id'] }}][3]"/>
                                                            <label class="form-check-label" for="flexCheckbox30">
                                                                delete
                                                            </label>
                                                        </div>
                                                    </div>
                                                    @if (array_key_exists('sub_modules', $modulex)) 
                                                        <div class="separator d-flex flex-center my-5">
                                                            <span class="text-uppercase bg-white fs-7 fw-bold text-gray-400 px-3">SUB MODULES</span>
                                                        </div>
                                                        @foreach ($modulex['sub_modules'][$module['id']] as $sub_module)
                                                            <div class="d-flex form-check form-check-custom form-check-solid my-2">
                                                                <input module="{{ $module['id'] }}" class="form-check-input" type="checkbox" value="{{ $sub_module['id'] }}" name="sub_modules[]"/>
                                                                <label class="form-check-label" for="flexCheckDefault">
                                                                    {{ $sub_module['name'] }}
                                                                </label>
                                                                <div class="flex-fill text-right">
                                                                    <a href="javascript:;" class="toggle-crud">
                                                                        <i class="la la-plus"></i>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                            <div class="toggle-crud-info hidden">
                                                                <div class="d-flex ms-7">
                                                                    <div class="form-check flex-fill form-check-custom form-check-solid">
                                                                        <input submodule="{{ $sub_module['id'] }}" class="form-check-input" type="checkbox" value="1" name="crud[{{ $sub_module['id'] }}][0]"/>
                                                                        <label class="form-check-label" for="flexCheckbox30">
                                                                            create
                                                                        </label>
                                                                    </div>
                                                                    <div class="form-check flex-fill form-check-custom form-check-solid">
                                                                        <input submodule="{{ $sub_module['id'] }}" class="form-check-input" type="checkbox" value="1" name="crud[{{ $sub_module['id'] }}][1]"/>
                                                                        <label class="form-check-label" for="flexCheckbox30">
                                                                            read
                                                                        </label>
                                                                    </div>
                                                                    <div class="form-check flex-fill form-check-custom form-check-solid">
                                                                        <input submodule="{{ $sub_module['id'] }}" class="form-check-input" type="checkbox" value="1" name="crud[{{ $sub_module['id'] }}][2]"/>
                                                                        <label class="form-check-label" for="flexCheckbox30">
                                                                            update
                                                                        </label>
                                                                    </div>
                                                                    <div class="form-check flex-fill -check-custom form-check-solid">
                                                                        <input submodule="{{ $sub_module['id'] }}" class="form-check-input" type="checkbox" value="1" name="crud[{{ $sub_module['id'] }}][3]"/>
                                                                        <label class="form-check-label" for="flexCheckbox30">
                                                                            delete
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>
                        <!--end::Accordion-->

                    </div>
                </div>
                <div class="fv-row row">
                    <div class="col-sm-12 text-center mt-3 mb-3">
                        <!--begin::Button-->
                        <button type="reset" id="roleCancel" class="btn btn-white me-3">Discard</button>
                        <!--end::Button-->
                        <!--begin::Button-->
                        <button type="submit" id="roleSubmit" class="btn btn-primary">
                            <span class="indicator-label">Submit</span>
                            <span class="indicator-progress">Please wait...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </div>
            </div>
            <!--end::Modal body-->
            </form>
        </div>
    </div>
</div>
<!--end::Modal - role - Add-->