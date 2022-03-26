<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Hash;

class Branch extends Model
{
    protected $guarded = ['id'];

    protected $table = 'branches';
    
    public $timestamps = false;

    public function all_branches()
    {	
    	$branchez = self::where(['is_active' => 1])->orderBy('id', 'asc')->get();
        $id = array();
        foreach ($branchez as $branch) {
            if ($branch->activation_code == hash('md5', $branch->code.'-branch-'.$branch->id)) {
                $id[] = $branch->id;
            }
        }
        $branches = self::whereIn('id', $id)->orderBy('id', 'asc')->get();
        return $branches;
    }

    public function all_branches_selectpicker($user)
    {	
    	$branchez = self::whereIn('id', 
            explode(',', trim((new User)->select(['assignment'])->where('id', $user)->first()->assignment))
        )
        ->where(['is_active' => 1])->orderBy('id', 'asc')->get();

        $id = array();
        foreach ($branchez as $branch) {
            if ($branch->activation_code == hash('md5', $branch->code.'-branch-'.$branch->id)) {
                $id[] = $branch->id;
            }
        }

        $results = self::whereIn('id', $id)->orderBy('id', 'asc')->get();
        $branches = array();
        $branches[] = array('' => 'select a branch');
        foreach ($results as $res) {
            $branches[] = array(
                $res->id => $res->name
            );
        }

        $branchs = array();
        foreach($branches as $branch) {
            foreach($branch as $key => $val) {
                $branchs[$key] = $val;
            }
        }

        return $branchs;
    }
}
