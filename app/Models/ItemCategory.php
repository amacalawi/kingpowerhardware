<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemCategory extends Model
{
    protected $guarded = ['id'];

    protected $table = 'items_category';
    
    public $timestamps = false;

    public function all_item_category_selectpicker()
    {	
    	$categories = self::where(['is_active' => 1])->orderBy('id', 'asc')->get();

        $categorize = array();
        $categorize[] = array('' => 'select a category');
        foreach ($categories as $category) {
            $categorize[] = array(
                $category->id => $category->name
            );
        }

        $categories = array();
        foreach($categorize as $category) {
            foreach($category as $key => $val) {
                $categories[$key] = $val;
            }
        }

        return $categories;
    }
}
