<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $guarded = ['id'];

    protected $table = 'customers';
    
    public $timestamps = false;

    public function agent()
    {   
        return $this->belongsTo('App\Models\User', 'agent_id', 'id');
    }

    public function all_customer_selectpicker()
    {	
    	$customerz = self::where(['is_active' => 1])->orderBy('id', 'asc')->get();

        $customers = array();
        $customers[] = array('' => 'select a customer');
        foreach ($customerz as $customer) {
            $customers[] = array(
                $customer->id => $customer->name
            );
        }

        $customerz = array();
        foreach($customers as $customer) {
            foreach($customer as $key => $val) {
                $customerz[$key] = $val;
            }
        }

        return $customerz;
    }
}
