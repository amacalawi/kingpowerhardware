<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemTransaction extends Model
{
    protected $guarded = ['id'];

    protected $table = 'items_transactions';
    
    public $timestamps = false;

    public function item()
    {   
        return $this->belongsTo('App\Models\Item', 'item_id', 'id');
    }

    public function branch()
    {   
        return $this->belongsTo('App\Models\Branch', 'branch_id', 'id');
    }

    public function issued()
    {   
        return $this->belongsTo('App\Models\User', 'issued_by', 'id');
    }

    public function received()
    {   
        return $this->belongsTo('App\Models\User', 'received_by', 'id');
    }
}
