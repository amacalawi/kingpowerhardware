<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    protected $guarded = ['id'];

    protected $table = 'banks';
    
    public $timestamps = false;

    public function all_bank_selectpicker()
    {	
    	$bankz = self::where(['is_active' => 1])->orderBy('id', 'asc')->get();

        $banks = array();
        $banks[] = array('' => 'select an bank');
        foreach ($bankz as $bank) {
            $banks[] = array(
                $bank->id => $bank->bank_name
            );
        }

        $bankz = array();
        foreach($banks as $bank) {
            foreach($bank as $key => $val) {
                $bankz[$key] = $val;
            }
        }

        return $bankz;
    }
}
