<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Billing extends Model
{
    protected $guarded = ['id'];

    protected $table = 'billing';
    
    public $timestamps = false;
}
