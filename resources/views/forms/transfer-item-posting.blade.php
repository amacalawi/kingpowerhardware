{{ Form::open(array(
    'url' => 'auth/items/transfer-items/post-line-item',
    'method' => 'POST',
    'id' => 'transferItemPostingForm',
    )) 
}}
<div class="fv-row row mb-2 hidden">
    <div class="col-sm-12 text-center">
        {{ Form::label('transfer_item_line_id', 'ID', ['class' => 'fs-6 fw-bold mb-2']) }}
        {{ 
            Form::text($name = 'transfer_item_line_id', $value = '', 
            $attributes = array(
                'id' => 'transfer_item_line_id',
                'class' => 'text-center form-control form-control-solid',
                'disabled' => 'disabled'
            )) 
        }}
    </div>
    <div class="col-sm-12 text-center">
        {{ Form::label('transfer_item_idx', 'ID', ['class' => 'fs-6 fw-bold mb-2']) }}
        {{ 
            Form::text($name = 'transfer_item_idx', $value = '', 
            $attributes = array(
                'id' => 'transfer_item_idx',
                'class' => 'text-center form-control form-control-solid',
                'disabled' => 'disabled'
            )) 
        }}
    </div>
</div>
<div class="fv-row row mb-2">
    <div class="col-sm-12 text-center">
        {{ Form::label('item_name', 'Item Description', ['class' => 'fs-6 fw-bold mb-2']) }}
        {{ 
            Form::text($name = 'item_name', $value = '', 
            $attributes = array(
                'id' => 'item_name',
                'class' => 'text-center form-control form-control-solid',
                'disabled' => 'disabled'
            )) 
        }}
    </div>
</div>
<div class="fv-row row mb-2">
    <div class="col-sm-12 text-center">
        {{ Form::label('available_qty', 'Quantity On-Hand', ['class' => 'fs-6 fw-bold mb-2']) }}
        {{ 
            Form::text($name = 'available_qty', $value = '', 
            $attributes = array(
                'id' => 'available_qty',
                'class' => 'text-center form-control form-control-solid',
                'disabled' => 'disabled'
            )) 
        }}
    </div>
</div>
<div class="fv-row row mb-2">
    <div class="col-sm-12 text-center">
        {{ Form::label('for_posting', 'For Posting', ['class' => 'fs-6 fw-bold mb-2']) }}
        {{ 
            Form::text($name = 'for_posting', $value = '', 
            $attributes = array(
                'id' => 'for_posting',
                'class' => 'text-center form-control form-control-solid',
                'disabled' => 'disabled'
            )) 
        }}
    </div>
</div>
<div class="fv-row row mb-2">
    <div class="col-sm-12 text-center">
        {{ Form::label('date_received', 'Date Received', ['class' => 'required fs-6 fw-bold mb-2']) }}
        {{ 
            Form::text($name = 'date_received', $value = '', 
            $attributes = array(
                'id' => 'date_received',
                'class' => 'text-center form-control form-control-solid border-danger'
            )) 
        }}
    </div>
</div>
<div class="fv-row row mb-2">
    <div class="col-sm-12 text-center">
        {{ Form::label('qty_to_post', 'Quantity to Post', ['class' => 'required fs-6 fw-bold mb-2']) }}
        {{ 
            Form::text($name = 'qty_to_post', $value = '', 
            $attributes = array(
                'id' => 'qty_to_post',
                'class' => 'numeric-only text-center form-control form-control-solid border-danger'
            )) 
        }}
    </div>
</div>
{{ Form::close() }}