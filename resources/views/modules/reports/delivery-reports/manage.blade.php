@extends('layouts.app')

@section('content')
    <div class="row gy-5 g-xl-8">
        <!--begin::Card-->
        <div class="card">
            <!--begin::Card header-->
            <div class="card-header border-0 pt-6">
                <!--begin::Card title-->
                <div class="card-title">
                    <h5>Filter Information</h5>
                </div>
            </div>
            <!--end::Card header-->
            <!--begin::Card body-->
            <div id="delivery-reports-parent" class="card-body pt-0">
                <div class="fv-row row mb-4">
                    <div class="col-sm-4">
                        {{ Form::label('dateFrom', 'Date From', ['class' => 'fs-6 fw-bold mb-2']) }}
                        {{ 
                            Form::text($name = 'dateFrom', $value = '', 
                            $attributes = array(
                                'id' => 'dateFrom',
                                'class' => 'form-control form-control-solid'
                            )) 
                        }}
                    </div>
                    <div class="col-sm-4">
                        {{ Form::label('dateTo', 'Date To', ['class' => 'fs-6 fw-bold mb-2']) }}
                        {{ 
                            Form::text($name = 'dateTo', $value = '', 
                            $attributes = array(
                                'id' => 'dateTo',
                                'class' => 'form-control form-control-solid'
                            )) 
                        }}
                    </div>
                    <div class="col-sm-4">
                        {{ Form::label('type', 'Type', ['class' => 'required fs-6 fw-bold mb-2']) }}
                        {{
                            Form::select('type', $types, '', ['data-control' => 'select2', 'data-placeholder' => 'select a type', 'data-dropdown-parent' => '#delivery-reports-parent', 'id' => 'type', 'class' => 'form-select form-select-solid fw-bolder'])
                        }}
                    </div>
                </div>
                <div class="fv-row row mb-4">
                    <div class="col-sm-4">
                        {{ Form::label('branch_id', 'Branch', ['class' => 'fs-6 fw-bold mb-2']) }}
                        {{
                            Form::select('branch_id', $branches, '', ['data-control' => 'select2', 'data-placeholder' => 'select a branch', 'data-dropdown-parent' => '#delivery-reports-parent', 'id' => 'branch_id', 'class' => 'form-select form-select-solid fw-bolder'])
                        }}
                    </div>
                    <div class="col-sm-4">
                        {{ Form::label('customer_id', 'Customer', ['class' => 'fs-6 fw-bold mb-2']) }}
                        {{
                            Form::select('customer_id', $customers, '', ['data-control' => 'select2', 'data-placeholder' => 'select a customer', 'data-dropdown-parent' => '#delivery-reports-parent', 'id' => 'customer_id', 'class' => 'form-select form-select-solid fw-bolder'])
                        }}
                    </div>
                    <div class="col-sm-4">
                        {{ Form::label('agent_id', 'Agent', ['class' => 'fs-6 fw-bold mb-2']) }}
                        {{
                            Form::select('agent_id', $agents, '', ['data-control' => 'select2', 'data-placeholder' => 'select an agent', 'data-dropdown-parent' => '#delivery-reports-parent', 'id' => 'agent_id', 'class' => 'form-select form-select-solid fw-bolder'])
                        }}
                    </div>
                </div>
                <div class="fv-row row mb-4">
                    <div class="col-sm-4">
                        {{ Form::label('status', 'Status', ['class' => 'fs-6 fw-bold mb-2']) }}
                        {{
                            Form::select('status', $statuses, '', ['data-control' => 'select2', 'data-placeholder' => 'select an status', 'data-dropdown-parent' => '#delivery-reports-parent', 'id' => 'status', 'class' => 'form-select form-select-solid fw-bolder'])
                        }}
                    </div>
                    <div class="col-sm-4">
                        {{ Form::label('order_by', 'Order By', ['class' => 'fs-6 fw-bold mb-2']) }}
                        {{
                            Form::select('order_by', $orderby, '', ['data-control' => 'select2', 'data-placeholder' => 'select an status', 'data-dropdown-parent' => '#delivery-reports-parent', 'id' => 'order_by', 'class' => 'form-select form-select-solid fw-bolder'])
                        }}
                    </div>
                    <div class="col-sm-4">
                        {{ Form::label('keywords', 'Keywords', ['class' => 'fs-6 fw-bold mb-2']) }}
                        {{ 
                            Form::text($name = 'keywords', $value = '', 
                            $attributes = array(
                                'id' => 'keywords',
                                'class' => 'form-control form-control-solid'
                            )) 
                        }}
                    </div>
                </div>
                <div class="fv-row row mb-4 mt-7 text-center">
                    <div class="col-sm-12 text-center">
                        <button type="button" class="btn btn-primary btn-search">
                            <span class="indicator-label">
                                <span class="svg-icon svg-icon-muted svg-icon-2hx"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                        <rect x="0" y="0" width="24" height="24"/>
                                        <path d="M14.2928932,16.7071068 C13.9023689,16.3165825 13.9023689,15.6834175 14.2928932,15.2928932 C14.6834175,14.9023689 15.3165825,14.9023689 15.7071068,15.2928932 L19.7071068,19.2928932 C20.0976311,19.6834175 20.0976311,20.3165825 19.7071068,20.7071068 C19.3165825,21.0976311 18.6834175,21.0976311 18.2928932,20.7071068 L14.2928932,16.7071068 Z" fill="#000000" fill-rule="nonzero" opacity="0.3"/>
                                        <path d="M11,16 C13.7614237,16 16,13.7614237 16,11 C16,8.23857625 13.7614237,6 11,6 C8.23857625,6 6,8.23857625 6,11 C6,13.7614237 8.23857625,16 11,16 Z M11,18 C7.13400675,18 4,14.8659932 4,11 C4,7.13400675 7.13400675,4 11,4 C14.8659932,4 18,7.13400675 18,11 C18,14.8659932 14.8659932,18 11,18 Z" fill="#000000" fill-rule="nonzero"/>
                                    </g>
                                </svg></span>
                            SEARCH</span>
                            <span class="indicator-progress">Please wait...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </div>
            </div>
            <!--end::Card body-->
        </div>
        <!--end::Card-->
        <!--begin::Card-->
        <div class="card">
            <!--begin::Card body-->
            <div class="card-body pt-0">
                <div id="datatable-result"></div>
            </div>
            <!--end::Card body-->
        </div>
        <!--end::Card-->
    </div>
@endsection

@push('styles')
@endpush
@push('scripts')
    <script src="{{ asset('js/datatables/delivery-report.js') }}" type="text/javascript"></script>
@endpush
