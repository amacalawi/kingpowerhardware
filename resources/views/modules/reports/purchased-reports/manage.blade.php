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
            <form target="_blank" class="form ms-5 me-5" action="{{ url('/auth/reports/purchased-reports/export')}}" id="purchasedReportform" method="GET">
                <div id="purchased-reports-parent" class="card-body pt-0">
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
                                Form::select('type', $types, '', ['data-control' => 'select2', 'data-placeholder' => 'select a type', 'data-dropdown-parent' => '#purchased-reports-parent', 'id' => 'type', 'class' => 'form-select form-select-solid fw-bolder'])
                            }}
                        </div>
                    </div>
                    <div class="fv-row row mb-4">
                        <div class="col-sm-4">
                            {{ Form::label('branch_id', 'Branch', ['class' => 'fs-6 fw-bold mb-2']) }}
                            {{
                                Form::select('branch_id', $branches, '', ['data-control' => 'select2', 'data-placeholder' => 'select a branch', 'data-dropdown-parent' => '#purchased-reports-parent', 'id' => 'branch_id', 'class' => 'form-select form-select-solid fw-bolder'])
                            }}
                        </div>
                        <div class="col-sm-4">
                            {{ Form::label('supplier_id', 'Supplier', ['class' => 'fs-6 fw-bold mb-2']) }}
                            {{
                                Form::select('supplier_id', $suppliers, '', ['data-control' => 'select2', 'data-placeholder' => 'select a supplier', 'data-dropdown-parent' => '#purchased-reports-parent', 'id' => 'supplier_id', 'class' => 'form-select form-select-solid fw-bolder'])
                            }}
                        </div>
                        <div class="col-sm-4">
                            {{ Form::label('purchase_order_type_id', 'Purchase Type', ['class' => 'fs-6 fw-bold mb-2']) }}
                            {{
                                Form::select('purchase_order_type_id', $po_types, '', ['data-control' => 'select2', 'data-placeholder' => 'select a purchase type', 'data-dropdown-parent' => '#purchased-reports-parent', 'id' => 'purchase_order_type_id', 'class' => 'form-select form-select-solid fw-bolder'])
                            }}
                        </div>
                    </div>
                    <div class="fv-row row mb-4">
                        <div class="col-sm-4">
                            {{ Form::label('status', 'Status', ['class' => 'fs-6 fw-bold mb-2']) }}
                            {{
                                Form::select('status', $statuses, '', ['data-control' => 'select2', 'data-placeholder' => 'select an status', 'data-dropdown-parent' => '#purchased-reports-parent', 'id' => 'status', 'class' => 'form-select form-select-solid fw-bolder'])
                            }}
                        </div>
                        <div class="col-sm-4">
                            {{ Form::label('order_by', 'Order By', ['class' => 'fs-6 fw-bold mb-2']) }}
                            {{
                                Form::select('order_by', $orderby, '', ['data-control' => 'select2', 'data-placeholder' => 'select an status', 'data-dropdown-parent' => '#purchased-reports-parent', 'id' => 'order_by', 'class' => 'form-select form-select-solid fw-bolder'])
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
                            <button type="button" class="min-w-150px btn btn-light-primary btn-search">
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

                            <button type="button" class="min-w-150px btn btn-light-success ms-4 btn-export">
                            <span class="svg-icon svg-icon-muted svg-icon-2hx">
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                        <rect x="0" y="0" width="24" height="24" />
                                        <path d="M17,8 C16.4477153,8 16,7.55228475 16,7 C16,6.44771525 16.4477153,6 17,6 L18,6 C20.209139,6 22,7.790861 22,10 L22,18 C22,20.209139 20.209139,22 18,22 L6,22 C3.790861,22 2,20.209139 2,18 L2,9.99305689 C2,7.7839179 3.790861,5.99305689 6,5.99305689 L7.00000482,5.99305689 C7.55228957,5.99305689 8.00000482,6.44077214 8.00000482,6.99305689 C8.00000482,7.54534164 7.55228957,7.99305689 7.00000482,7.99305689 L6,7.99305689 C4.8954305,7.99305689 4,8.88848739 4,9.99305689 L4,18 C4,19.1045695 4.8954305,20 6,20 L18,20 C19.1045695,20 20,19.1045695 20,18 L20,10 C20,8.8954305 19.1045695,8 18,8 L17,8 Z" fill="#000000" fill-rule="nonzero" opacity="0.3" />
                                        <rect fill="#000000" opacity="0.3" transform="translate(12.000000, 8.000000) scale(1, -1) rotate(-180.000000) translate(-12.000000, -8.000000)" x="11" y="2" width="2" height="12" rx="1" />
                                        <path d="M12,2.58578644 L14.2928932,0.292893219 C14.6834175,-0.0976310729 15.3165825,-0.0976310729 15.7071068,0.292893219 C16.0976311,0.683417511 16.0976311,1.31658249 15.7071068,1.70710678 L12.7071068,4.70710678 C12.3165825,5.09763107 11.6834175,5.09763107 11.2928932,4.70710678 L8.29289322,1.70710678 C7.90236893,1.31658249 7.90236893,0.683417511 8.29289322,0.292893219 C8.68341751,-0.0976310729 9.31658249,-0.0976310729 9.70710678,0.292893219 L12,2.58578644 Z" fill="#000000" fill-rule="nonzero" transform="translate(12.000000, 2.500000) scale(1, -1) translate(-12.000000, -2.500000)" />
                                    </g>
                                </svg>
                            </span>EXPORT</button>
                        </div>
                    </div>
                </div>
            </form>
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

@push('scripts')
    <script src="{{ asset('js/datatables/purchased-report.js') }}" type="text/javascript"></script>
@endpush
