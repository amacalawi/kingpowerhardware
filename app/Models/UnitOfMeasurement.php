<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitOfMeasurement extends Model
{
    protected $guarded = ['id'];

    protected $table = 'unit_of_measurements';
    
    public $timestamps = false;

    public function all_uom_selectpicker()
    {	
    	$uomz = self::where(['is_active' => 1])->orderBy('id', 'asc')->get();

        $uoms = array();
        $uoms[] = array('' => 'select a uom');
        foreach ($uomz as $uom) {
            $uoms[] = array(
                $uom->id => $uom->code
            );
        }

        $uomz = array();
        foreach($uoms as $uom) {
            foreach($uom as $key => $val) {
                $uomz[$key] = $val;
            }
        }

        return $uomz;
    }
}
