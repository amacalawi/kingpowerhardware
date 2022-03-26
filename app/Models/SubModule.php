<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubModule extends Model
{
    protected $guarded = ['id'];

    protected $table = 'sub_modules';
    
    public $timestamps = false;
}
