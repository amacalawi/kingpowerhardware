<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $guarded = ['id'];

    protected $table = 'invoices';
    
    public $timestamps = false;

    public function all_invoice_selectpicker()
    {	
    	$invoicez = self::where(['is_active' => 1])->orderBy('id', 'asc')->get();

        $invoices = array();
        $invoices[] = array('' => 'select an invoice');
        foreach ($invoicez as $invoice) {
            $invoices[] = array(
                $invoice->id => $invoice->name
            );
        }

        $invoicez = array();
        foreach($invoices as $invoice) {
            foreach($invoice as $key => $val) {
                $invoicez[$key] = $val;
            }
        }

        return $invoicez;
    }
}
