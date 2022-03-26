<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{	
    protected $guarded = ['id'];

    protected $table = 'audit_logs';
    
    public $timestamps = false;
}

