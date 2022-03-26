<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderLine extends Model
{
    protected $guarded = ['id'];

    protected $table = 'purchase_orders_lines';
    
    public $timestamps = false;

    public function item()
    {   
        return $this->belongsTo('App\Models\Item', 'item_id', 'id');
    }

    public function purchase_order()
    {   
        return $this->belongsTo('App\Models\PurchaseOrder', 'purchase_order_id', 'id');
    }
}
