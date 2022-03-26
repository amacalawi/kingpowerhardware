<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryLine extends Model
{
    protected $guarded = ['id'];

    protected $table = 'delivery_lines';
    
    public $timestamps = false;

    public function item()
    {   
        return $this->belongsTo('App\Models\Item', 'item_id', 'id');
    }

    public function delivery()
    {   
        return $this->belongsTo('App\Models\Delivery', 'delivery_id', 'id');
    }
}
