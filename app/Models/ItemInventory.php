<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemInventory extends Model
{
    protected $guarded = ['id'];

    protected $table = 'items_inventory';
    
    public $timestamps = false;

    public function item()
    {   
        return $this->belongsTo('App\Models\Item', 'item_id', 'id');
    }

    public function branch()
    {   
        return $this->belongsTo('App\Models\Branch', 'branch_id', 'id');
    }

    public function find_item_inventory($branchID, $itemID)
    {
        return self::where(['branch_id' => $branchID, 'item_id' => $itemID, 'is_active' => 1])->first()->quantity;
    }
}
