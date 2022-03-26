<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemTransfer extends Model
{
    protected $guarded = ['id'];

    protected $table = 'items_transfer';
    
    public $timestamps = false;

    public function trans()
    {   
        return $this->belongsTo('App\Models\ItemTransaction', 'transaction_id', 'id');
    }

    public function branch()
    {   
        return $this->belongsTo('App\Models\Branch', 'branch_id', 'id');
    }
}
