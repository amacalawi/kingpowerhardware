<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $guarded = ['id'];

    protected $table = 'roles';
    
    public $timestamps = false;

    public function all_roles_selectpicker()
    {	
    	$rolez = self::where('id', '!=', 1)->where(['is_active' => 1])->orderBy('id', 'asc')->get();

        $roles = array();
        $roles[] = array('' => 'select a role');
        foreach ($rolez as $role) {
            $roles[] = array(
                $role->id => $role->name
            );
        }

        $rolez = array();
        foreach($roles as $role) {
            foreach($role as $key => $val) {
                $rolez[$key] = $val;
            }
        }

        return $rolez;
    }
}
