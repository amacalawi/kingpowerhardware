<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryLinePosting extends Model
{
    protected $guarded = ['id'];

    protected $table = 'delivery_lines_posting';
    
    public $timestamps = false;
}
