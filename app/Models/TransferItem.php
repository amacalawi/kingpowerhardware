<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferItem extends Model
{
    protected $guarded = ['id'];

    protected $table = 'transfer_items';
    
    public $timestamps = false;
}
