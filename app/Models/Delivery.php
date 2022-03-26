<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $guarded = ['id'];

    protected $table = 'delivery';
    
    public $timestamps = false;

    public function agent()
    {   
        return $this->belongsTo('App\Models\User', 'agent_id', 'id');
    }

    public function branch()
    {   
        return $this->belongsTo('App\Models\Branch', 'branch_id', 'id');
    }

    public function customer()
    {   
        return $this->belongsTo('App\Models\Customer', 'customer_id', 'id');
    }

    public function payment_term()
    {   
        return $this->belongsTo('App\Models\PaymentTerms', 'customer_id', 'id');
    }
}
