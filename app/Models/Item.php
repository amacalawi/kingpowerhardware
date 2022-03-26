<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $guarded = ['id'];

    protected $table = 'items';
    
    public $timestamps = false;

    public function inventory()
    {   
        return $this->hasMany('App\Models\ItemInventory', 'item_id', 'id')
        ->whereIn(
            'branch_id', explode(',', trim((new User)->select(['assignment'])->where('id', Auth::user()->id)->first()->assignment))
        );
    }

    public function uom()
    {   
        return $this->belongsTo('App\Models\UnitOfMeasurement', 'uom_id', 'id');
    }

    public function category()
    {   
        return $this->belongsTo('App\Models\ItemCategory', 'item_category_id', 'id');
    }

    public function all_item_selectpicker()
    {	
    	$itemz = self::where(['is_active' => 1])->orderBy('id', 'asc')->get();

        $items = array();
        $items[] = array('' => 'select an item');
        foreach ($itemz as $item) {
            $items[] = array(
                $item->id => $item->name
            );
        }

        $itemz = array();
        foreach($items as $item) {
            foreach($item as $key => $val) {
                $itemz[$key] = $val;
            }
        }

        return $itemz;
    }
}
