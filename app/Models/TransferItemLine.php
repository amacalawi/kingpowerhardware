<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferItemLine extends Model
{
    protected $guarded = ['id'];

    protected $table = 'transfer_items_lines';
    
    public $timestamps = false;

    public function item()
    {   
        return $this->belongsTo('App\Models\Item', 'item_id', 'id');
    }

    public function transfer_item()
    {   
        return $this->belongsTo('App\Models\TransferItem', 'transfer_item_id', 'id');
    }
}
