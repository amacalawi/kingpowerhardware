<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryAdjustment extends Model
{
    protected $guarded = ['id'];

    protected $table = 'inventory_adjustments';
    
    public $timestamps = false;
}
