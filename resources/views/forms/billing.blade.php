{{ Form::open(array(
    'url' => 'auth/billing/store',
    'method' => 'POST',
    'id' => 'billingForm',
    )) 
}}
<h4 class="fs-1 text-gray-800 w-bolder mb-6">Invoice Details</h4>
<div class="fv-row row mb-2 hidden">
    <div class="col-sm-6">
        {{ Form::label('billing_id', 'Billing ID', ['class' => 'fs-6 fw-bold mb-2']) }}
        {{ 
            Form::text($name = 'billing_id', $value = '', 
            $attributes = array(
                'id' => 'billing_id',
                'class' => 'form-control form-control-solid',
                'disabled' => 'disabled'
            )) 
        }}
    </div>
</div>
<div class="fv-row row mb-2">
    <div class="col-sm-6">
        {{ Form::label('branch_id', 'Branch', ['class' => 'required fs-6 fw-bold mb-2']) }}
        {{
            Form::select('branch_id', $branches, '', ['data-control' => 'select2', 'data-placeholder' => 'select a branch', 'data-dropdown-parent' => '#billingModal', 'id' => 'branch_id', 'class' => 'form-select form-select-solid fw-bolder'])
        }}
    </div>
    <div class="col-sm-6">
        {{ Form::label('invoice_id', 'Invoice Type', ['class' => 'required fs-6 fw-bold mb-2']) }}
        {{
            Form::select('invoice_id', $invoices, '', ['data-control' => 'select2', 'data-placeholder' => 'select an invoice type', 'data-dropdown-parent' => '#billingModal', 'id' => 'invoice_id', 'class' => 'form-select form-select-solid fw-bolder'])
        }}
    </div>
</div>
<div class="fv-row row mb-2">
    <div class="col-sm-6">
        {{ Form::label('customer_id', 'Customer', ['class' => 'required fs-6 fw-bold mb-2']) }}
        {{
            Form::select('customer_id', $customers, '', ['data-control' => 'select2', 'data-placeholder' => 'select a customer', 'data-dropdown-parent' => '#billingModal', 'id' => 'customer_id', 'class' => 'form-select form-select-solid fw-bolder'])
        }}
    </div>
    <div class="col-sm-6">
        {{ Form::label('invoice_no', 'Invoice No', ['class' => 'fs-6 fw-bold mb-2']) }}
        {{ 
            Form::text($name = 'invoice_no', $value = '', 
            $attributes = array(
                'id' => 'invoice_no',
                'class' => 'form-control form-control-solid',
                'disabled' => 'disabled'
            )) 
        }}
    </div>
</div>
<div class="fv-row row mb-2">
    <div class="col-sm-6">
        {{ Form::label('agent_id', 'Agent', ['class' => 'required fs-6 fw-bold mb-2']) }}
        {{
            Form::select('agent_id', $agents, '', ['data-control' => 'select2', 'data-placeholder' => 'select an agent', 'data-dropdown-parent' => '#billingModal', 'id' => 'agent_id', 'class' => 'form-select form-select-solid fw-bolder'])
        }}
    </div>
    <div class="col-sm-6">
        {{ Form::label('payment_terms_id', 'Payment Terms', ['class' => 'required fs-6 fw-bold mb-2']) }}
        {{
            Form::select('payment_terms_id', $terms, '', ['data-control' => 'select2', 'data-placeholder' => 'select a payment terms', 'data-dropdown-parent' => '#billingModal', 'id' => 'payment_terms_id', 'class' => 'form-select form-select-solid fw-bolder'])
        }}
    </div>
</div>
<div class="fv-row row mb-2">
    <div class="col-sm-6">
        {{ Form::label('invoice_date', 'Invoice date', ['class' => 'required fs-6 fw-bold mb-2']) }}
        {{ 
            Form::text($name = 'invoice_date', $value = '', 
            $attributes = array(
                'id' => 'invoice_date',
                'class' => 'form-control form-control-solid',
            )) 
        }}
    </div>
    <div class="col-sm-6">
        {{ Form::label('due_date', 'Due date', ['class' => 'fs-6 fw-bold mb-2']) }}
        {{ 
            Form::text($name = 'due_date', $value = '', 
            $attributes = array(
                'id' => 'due_date',
                'class' => 'form-control form-control-solid',
                'disabled' => 'disabled'
            )) 
        }}
    </div>
</div>
<div class="fv-row row mb-2">
    <div class="col-sm-6">
        {{ Form::label('countered_date', 'Countered date', ['class' => 'fs-6 fw-bold mb-2']) }}
        {{ 
            Form::text($name = 'countered_date', $value = '', 
            $attributes = array(
                'id' => 'countered_date',
                'class' => 'form-control form-control-solid',
            )) 
        }}
    </div>
    <div class="col-sm-6">
        {{ Form::label('countered_by', 'Countered By', ['class' => 'fs-6 fw-bold mb-2']) }}
        {{ 
            Form::text($name = 'countered_by', $value = '', 
            $attributes = array(
                'id' => 'countered_by',
                'class' => 'form-control form-control-solid',
            )) 
        }}
    </div>
</div>
<div class="fv-row row mb-2">
    <div class="col-sm-12">
        {{ Form::label('instructions', 'Instructions', ['class' => 'fs-6 fw-bold mb-2']) }}
        {{ 
            Form::textarea($name = 'instructions', $value = '', 
            $attributes = array(
                'id' => 'instructions',
                'class' => 'form-control form-control-solid',
                'rows' => 2
            )) 
        }}
    </div>
</div>
<div class="fv-row row">
    <div class="col-sm-12 text-center mt-2">
    <button type="button" class="save-billing-btn btn btn-lg btn-primary">
        <span class="indicator-label">
        <!--begin::Svg Icon -->
        <span class="svg-icon svg-icon-4 ms-1 me-0"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
            <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                <polygon points="0 0 24 0 24 24 0 24"/>
                <path d="M17,4 L6,4 C4.79111111,4 4,4.7 4,6 L4,18 C4,19.3 4.79111111,20 6,20 L18,20 C19.2,20 20,19.3 20,18 L20,7.20710678 C20,7.07449854 19.9473216,6.94732158 19.8535534,6.85355339 L17,4 Z M17,11 L7,11 L7,4 L17,4 L17,11 Z" fill="#000000" fill-rule="nonzero"/>
                <rect fill="#000000" opacity="0.3" x="12" y="4" width="3" height="5" rx="0.5"/>
            </g>
        </svg></span>
        <!--end::Svg Icon-->
        Save Changes</span>
        <span class="indicator-progress">Please wait...
        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
    </button>
    </div>
</div>


{{ Form::close() }}