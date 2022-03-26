<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleSubModule extends Model
{
    protected $guarded = ['id'];

    protected $table = 'roles_sub_modules';
    
    public $timestamps = false;
}
