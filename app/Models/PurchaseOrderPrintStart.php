<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderPrintStart extends Model
{
    protected $guarded = ['id'];

    protected $table = 'purchase_orders_print_start';
    
    public $timestamps = false;

    public function get_po_no($branch)
    {
        $res = self::where(['branch_id' => $branch])->orderBy('id', 'asc')->get();
        
        $drNo = 'PO-';
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
