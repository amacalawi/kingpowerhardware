<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleModule extends Model
{
    protected $guarded = ['id'];

    protected $table = 'roles_modules';
    
    public $timestamps = false;
}
