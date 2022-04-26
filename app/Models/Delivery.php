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

    public function get_delivery_doc_no($branch)
    {   
        $year = date('Y');
        $yearPrefix = substr($year, -2);
        $count = self::where(['branch_id' => $branch])->where('created_at', 'like', '%' . $year . '%')->count();
        
        $drNo = 'DR-'.$yearPrefix;
        if ($count < 9) {
            $drNo .= '0000'.($count + 1);
        } else if ($count < 99) {
            $drNo .= '000'.($count + 1);
        } else if ($count < 999) {
            $drNo .= '00'.($count + 1);
        } else if ($count < 9999) {
            $drNo .= '0'.($count + 1);
        } else {
            $drNo .= ($count + 1);
        }
        
        return $drNo;
    }
}
