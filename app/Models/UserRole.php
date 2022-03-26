<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Module;
use App\Models\SubModule;
use App\Models\RoleModule;
use App\Models\RoleSubModule;

class UserRole extends Model
{
    protected $guarded = ['id'];

    protected $table = 'users_roles';
    
    public $timestamps = false;

    public function load_menus($user)
    {   
        $menus = array(); $increment = 0;
        $userRole = self::where('user_id', $user)->first();

        $userModules = RoleModule::select(['modules.id', 'modules.name', 'modules.slug'])
        ->join('modules', function($join2)
        {
            $join2->on('modules.id', '=', 'roles_modules.module_id');
        })
        ->where([
            'roles_modules.role_id' => $userRole->role_id,
            'roles_modules.is_active' => 1,
            'modules.is_active' => 1
        ])
        ->orderBy('modules.order', 'asc')->get();
            
        $modulars = array();
        foreach ($userModules as $userModule) {
            $menus[$increment]['modules'][$userModule->id] = array(
                'id' => $userModule->id,
                'name' => $userModule->name,
                'slug' => $userModule->slug,
                'icon' => ''
            );

            $moduleID = $userModule->id;
            $userSubModules = RoleSubModule::select(['sub_modules.module_id', 'sub_modules.id', 'sub_modules.name', 'sub_modules.slug', 'sub_modules.icon'])
            ->join('sub_modules', function($join2)
            {
                $join2->on('sub_modules.id', '=', 'roles_sub_modules.sub_module_id');
            })
            ->where([
                'roles_sub_modules.role_id' => $userRole->role_id,
                'roles_sub_modules.is_active' => 1,
                'sub_modules.is_active' => 1
            ])
            ->whereIn('roles_sub_modules.sub_module_id', SubModule::select('id')->where(['module_id' => $moduleID, 'is_active' => 1])->orderBy('order', 'asc'))
            ->orderBy('sub_modules.order', 'asc')->get();

            foreach ($userSubModules as $userSubModule) {
                $menus[$increment]['sub_modules'][$moduleID][$userSubModule->id] = array(
                    'id' => $userSubModule->id,
                    'name' => $userSubModule->name,
                    'slug' => $userSubModule->slug,
                    'icon' => $userSubModule->icon
                );
            }

            $increment++;
        }

        return $menus;
    }
}
