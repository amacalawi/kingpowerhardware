<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTerm extends Model
{
    protected $guarded = ['id'];

    protected $table = 'payment_terms';
    
    public $timestamps = false;

    public function all_payment_term_selectpicker()
    {	
    	$payment_termz = self::where(['is_active' => 1])->orderBy('id', 'asc')->get();

        $payment_terms = array();
        $payment_terms[] = array('' => 'select a payment term');
        foreach ($payment_termz as $payment_term) {
            $payment_terms[] = array(
                $payment_term->id => $payment_term->name
            );
        }

        $payment_termz = array();
        foreach($payment_terms as $payment_term) {
            foreach($payment_term as $key => $val) {
                $payment_termz[$key] = $val;
            }
        }

        return $payment_termz;
    }
}
