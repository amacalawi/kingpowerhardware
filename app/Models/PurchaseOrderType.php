<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderType extends Model
{
    protected $guarded = ['id'];

    protected $table = 'purchase_orders_types';
    
    public $timestamps = false;

    public function all_po_type_selectpicker()
    {	
    	$typez = self::where(['is_active' => 1])->orderBy('id', 'asc')->get();

        $types = array();
        $types[] = array('' => 'select a type');
        foreach ($typez as $type) {
            $types[] = array(
                $type->id => $type->code
            );
        }

        $typez = array();
        foreach($types as $type) {
            foreach($type as $key => $val) {
                $typez[$key] = $val;
            }
        }

        return $typez;
    }
}
