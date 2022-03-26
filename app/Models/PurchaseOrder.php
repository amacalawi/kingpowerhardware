<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $guarded = ['id'];

    protected $table = 'purchase_orders';
    
    public $timestamps = false;
}
