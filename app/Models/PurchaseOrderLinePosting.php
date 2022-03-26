<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderLinePosting extends Model
{
    protected $guarded = ['id'];

    protected $table = 'purchase_orders_lines_posting';
    
    public $timestamps = false;

    public function purchase_order_line()
    {   
        return $this->belongsTo('App\Models\PurchaseOrder', 'purchase_order_line_id', 'id');
    }
}
