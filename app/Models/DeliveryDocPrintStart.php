<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryDocPrintStart extends Model
{
    protected $guarded = ['id'];

    protected $table = 'delivery_doc_print_start';
    
    public $timestamps = false;

    public function get_delivery_doc_no($branch)
    {
        $res = self::where(['branch_id' => $branch])->orderBy('id', 'asc')->get();
        
        $drNo = 'DR-';
        if ($res->count() > 0) {
            $count = $res->first()->print_start;
            
            if ($count < 9) {
                $drNo .= '0000'.($count + 1);
            } else if ($count < 99) {
                $drNo .= '000'.($count + 1);
            } else if ($count < 999) {
                $drNo .= '00'.($count + 1);
            } else if ($count < 9999) {
                $drNo .= '0'.($count + 1);
            } else {
                $drNo .= ($count + 1);
            }
        } else {
            $drNo .= '00001';
        }

        return $drNo;
    }
}
