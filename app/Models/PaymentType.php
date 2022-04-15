<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentType extends Model
{
    protected $guarded = ['id'];

    protected $table = 'payment_types';
    
    public $timestamps = false;

    public function all_payment_type_selectpicker()
    {	
    	$payment_typez = self::where(['is_active' => 1])->orderBy('id', 'asc')->get();

        $payment_types = array();
        $payment_types[] = array('' => 'select an payment_type');
        foreach ($payment_typez as $payment_type) {
            $payment_types[] = array(
                $payment_type->id => $payment_type->code
            );
        }

        $payment_typez = array();
        foreach($payment_types as $payment_type) {
            foreach($payment_type as $key => $val) {
                $payment_typez[$key] = $val;
            }
        }

        return $payment_typez;
    }
}
