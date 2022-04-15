<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SubModule;

class Module extends Model
{
    protected $guarded = ['id'];

    protected $table = 'modules';
    
    public $timestamps = false;

    public function all_modules()
    {
        $modules = self::where('is_active', 1)->orderBy('order', 'asc')->get();
        $increment = 0;
        $modulex = array();
        foreach ($modules as $module) {

            $modulex[$increment]['modules'][$module->id] = array(
                'id' => $module->id,
                'name' => $module->name,
                'slug' => $module->slug,
                'icon' => $module->icon
            );

            $sub_modules = SubModule::where([
                'module_id' => $module->id,
                'is_active' => 1
            ])->orderBy('order', 'asc')->get();
                
            foreach ($sub_modules as $sub_module) {
                $modulex[$increment]['sub_modules'][$module->id][$sub_module->id] = array(
                    'id' => $sub_module->id,
                    'name' => $sub_module->name,
                    'slug' => $sub_module->slug,
                    'icon' => $sub_module->icon
                );
            }
            
            // $modulex[$increment] = array(
            //     'id' => $module->id,
            //     'name' => $module->name,
            //     'slug' => $module->slug,
            // );

            // $sub_modules = SubModule::where([
            //     'module_id' => $module->id,
            //     'is_active' => 1
            // ])->orderBy('order', 'asc')->get();
                
            // foreach ($sub_modules as $sub_module) {
            //     $modulex[$increment]['sub_modules'][$module->id] = array(
            //         'id' => $sub_module->id,
            //         'name' => $sub_module->name,
            //         'slug' => $sub_module->slug,
            //         'icon' => $sub_module->icon
            //     );
            // }

            $increment++;
        }

        return $modulex;
    }
}
