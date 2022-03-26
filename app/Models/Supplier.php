<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $guarded = ['id'];

    protected $table = 'suppliers';
    
    public $timestamps = false;

    public function all_suppliers_selectpicker()
    {	
    	$supplierz = self::where(['is_active' => 1])->orderBy('id', 'asc')->get();

        $suppliers = array();
        $suppliers[] = array('' => 'select a supplier');
        foreach ($supplierz as $supplier) {
            $suppliers[] = array(
                $supplier->id => $supplier->name
            );
        }

        $supplierz = array();
        foreach($suppliers as $supplier) {
            foreach($supplier as $key => $val) {
                $supplierz[$key] = $val;
            }
        }

        return $supplierz;
    }
}
