<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferItemLinePosting extends Model
{
    protected $guarded = ['id'];

    protected $table = 'transfer_items_lines_posting';
    
    public $timestamps = false;
}
