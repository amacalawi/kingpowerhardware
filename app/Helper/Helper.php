<?php

namespace App\Helper;
use Illuminate\Support\Facades\Auth;
use App\Models\UserRole;
use App\Models\RoleSubModule;
use App\Models\SubModule;
use App\Models\Module;
use App\Models\RoleModule;

class Helper
{
    public static function get_privileges()
    {   
        $role = (new UserRole)->where('user_id', Auth::user()->id)->first();

        if (request()->segment(2) !== null && request()->segment(3) == null) {
            $moduleID = Module::where('slug', request()->segment(2))->first()->id;
            $privileges = RoleModule::where(['role_id' => $role->id, 'module_id' => $moduleID, 'is_active' => 1])->get();
            if ($privileges->count() > 0) {
                return $privileges->first()->permissions;
            }
        } else {
            $subModuleID = SubModule::where('slug', request()->segment(3))->first()->id;
            $privileges = RoleSubModule::where(['role_id' => $role->id, 'sub_module_id' => $subModuleID, 'is_active' => 1])->get();
            if ($privileges->count() > 0) {
                return $privileges->first()->permissions;
            }
        }

        return '0,0,0,0';
    }
}