<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\TransferItem;
use App\Models\TransferItemLine;
use Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\File;
use App\Exports\TransferItemReportExport;
use Maatwebsite\Excel\Facades\Excel;
// use App\Components\FlashMessages;
// use App\Helper\Helper;

class TransferItemReportsController extends Controller
{   
    // use FlashMessages;
    private $models;

    public function __construct()
    {   
        date_default_timezone_set('Asia/Manila');
        $this->middleware('auth');
    }

    public function index(Request $request)
    {   
        // $this->is_permitted(1);    
        $menus = $this->load_menus();
        $branches = (new Branch)->all_branches_selectpicker();
        $items = (new Item)->all_item_selectpicker();
        $categories = (new ItemCategory)->all_item_category_selectpicker();
        $orderby = ['asc' => 'Ascending', 'desc' => 'Descending'];
        $statuses = ['' => 'Select a status', 'prepared' => 'Prepared', 'partial' => 'Partial', 'posted' => 'Posted'];
        return view('modules/reports/transfer-item-reports/manage')->with(compact('statuses', 'categories', 'orderby', 'items', 'menus', 'branches'));
    }

    public function search(Request $request)
    {   
        $orderby      = $request->get('orderby');  
        $keywords     = $request->get('keywords');
        $dateFrom     = $request->get('dateFrom');  
        $dateTo       = $request->get('dateTo');  
        $branchFrom   = $request->get('branchFram');
        $branchTo     = $request->get('branchTo');
        $category     = $request->get('category');  
        $item         = $request->get('item');  
        $status       = $request->get('status');  
        $cur_page     = null != $request->post('page') ? $request->post('page') : 1;
        $per_page     = 10 == -1 ? 0 : 10;
        $page         = $cur_page !== null ? $cur_page : 1;
        $start_from   = ($page-1) * $per_page;

        $previous_btn = true;
        $next_btn = true;
        $value = 0;
        $first_btn = true;
        $pagess = 0;
        $last_btn = true;

        $query = $this->get_line_items($per_page, $start_from, $dateFrom, $dateTo, $branchFrom, $branchTo, $category, $item, $status, $orderby, $keywords);
        $count = $this->get_page_count($dateFrom, $dateTo, $branchFrom, $branchTo, $category, $item, $status, $orderby, $keywords);
        $sumAmt = $this->get_page_amount($dateFrom, $dateTo, $branchFrom, $branchTo, $category, $item, $status, $orderby, $keywords);
        $no_of_paginations = ceil($count / $per_page);
        $assets = url('assets/media/illustrations/work.png');

        $msg = "";
        
        $msg .= '<div class="table-responsive">';
        $msg .= '<table data-row-count="'.$count.'" class="table align-middle table-row-dashed fs-6 gy-5" id="transferItemReportTable">';
        $msg .= '<thead>';
        $msg .= '<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">';
        $msg .= '<th class="text-center">Transaction Date</th>';
        $msg .= '<th class="text-center">Trans No</th>';
        $msg .= '<th class="text-center">Branch From</th>';
        $msg .= '<th class="text-center">Branch To</th>';
        $msg .= '<th class="text-center">Category</th>';
        $msg .= '<th class="">Items</th>';
        $msg .= '<th class="text-center">UOM</th>';
        $msg .= '<th class="text-center">Qty</th>';
        $msg .= '<th class="text-center">SRP</th>';
        $msg .= '<th class="text-center">PLUS</th>';
        $msg .= '<th class="text-center">DISC1</th>';
        $msg .= '<th class="text-center">DISC2</th>';
        $msg .= '<th class="text-center">STATUS</th>';
        $msg .= '<th class="text-center">Total</th>';
        $msg .= '</tr>';
        $msg .= '</thead>';
        $msg .= '<tbody class="fw-bold text-gray-600">';

        if($count <= 0)
        {
            $msg .= '<tr>';
            $msg .= '<td colspan="20" class="text-center">there are no data has been displayed.<br/><br/><br/>';
            $msg .= '<img class="mw-100 mh-200px" alt="" src="'.$assets.'">';
            $msg .= '</td>';
            $msg .= '<tr>';
        } 
        else 
        {
            foreach ($query as $row)
            {   
                $totalAmt = number_format(floor(($row->totalAmt*100))/100,2);
                $plus = ($row->plus > 0) ? $row->plus.'%' : '';
                $disc1 = ($row->disc1 > 0) ? $row->disc1.'%' : '';
                $disc2 = ($row->disc2 > 0) ? $row->disc2.'%' : '';
                $msg .= '<tr">';
                $msg .= '<td class="text-center">'.$row->transDate.'</td>';
                $msg .= '<td class="text-center">'.$row->transNo.'</td>';
                $msg .= '<td class="text-center">'.$row->branchFrom.'</td>';
                $msg .= '<td class="text-center">'.$row->branchTo.'</td>';
                $msg .= '<td class="text-center">'.$row->category.'</td>';
                $msg .= '<td>'.$row->item.'</td>';
                $msg .= '<td class="text-center">'.$row->uom.'</td>';
                $msg .= '<td class="text-center">'.$row->quantity.'</td>';
                $msg .= '<td class="text-center">'.$row->srp.'</td>';
                $msg .= '<td class="text-center">'.$plus.'</td>';
                $msg .= '<td class="text-center">'.$disc1.'</td>';
                $msg .= '<td class="text-center">'.$disc2.'</td>';
                $msg .= '<td class="text-center">'.$row->status.'</td>';
                $msg .= '<td class="text-right">'.$totalAmt.'</td>';
                $msg .= '</tr>';
            }
        }
        $msg .= '</tbody>';
        $msg .= '<tfoot>';
        $msg .= '<tr class="fs-5">';
        $msg .= '<td class="text-right" colspan="13"><strong>TOTAL AMOUNT:</strong></td>';
        $msg .= '<td class="text-right text-danger"><strong>'.number_format(floor(($sumAmt*100))/100,2).'</strong></td>';
        $msg .= '</tr>';
        $msg .= '</tfoot>';
        $msg .= '</table>';
        $msg .= '</div>';

        if ($cur_page >= 5) {
            $start_loop = $cur_page - 2;
            if ($no_of_paginations > $cur_page + 2)
                $end_loop = $cur_page + 2;
            else if ($cur_page <= $no_of_paginations && $cur_page > $no_of_paginations - 6) {
                $start_loop = $no_of_paginations - 4;
                $end_loop = $no_of_paginations;
            } else {
                $end_loop = $no_of_paginations;
            }
        } else {
            $start_loop = 1;
            if ($no_of_paginations > 5)
                $end_loop = 5;
            else
                $end_loop = $no_of_paginations;
        }

        $msg .= '<div class="row"><div class="col-sm-6 pl-5"><div class="dataTables_paginate paging_simple_numbers" id="kt_delivery_table_paginate"><ul class="pagination" style="margin-bottom: 0;">';

        // FOR ENABLING THE PREVIOUS BUTTON
        if ($previous_btn && $cur_page > 1) {
            $pre = $cur_page - 1;
            $msg .= '<li class="paginate_button page-item" p="'.$pre.'">';
            $msg .= '<a href="javascript:;" aria-label="Previous" class="page-link">';
            $msg .= '<i class="la la-angle-left"></i>';
            $msg .= '</a>';
            $msg .= '</li>';
        } else if ($previous_btn) {
            $msg .= '<li class="paginate_button page-item disabled">';
            $msg .= '<a href="javascript:;" aria-label="Previous" class="page-link">';
            $msg .= '<i class="la la-angle-left"></i>';
            $msg .= '</a>';
            $msg .= '</li>';
        }
        for ($i = $start_loop; $i <= $end_loop; $i++) {

            if ($cur_page == $i)
                $msg .= '<li class="paginate_button page-item active" p="'.$i.'"><a href="javascript:;" class="page-link">'.$i.'</a></li>';
            else
                $msg .= '<li class="paginate_button page-item ping" p="'.$i.'"><a href="javascript:;" class="page-link">'.$i.'</a></li>';
        }

        // TO ENABLE THE NEXT BUTTON
        if ($next_btn && $cur_page < $no_of_paginations) {
            $nex = $cur_page + 1;
            $msg .= '<li class="paginate_button page-item" p="'.$nex.'">';
            $msg .= '<a href="javascript:;" aria-label="Next" class="page-link">';
            $msg .= '<i class="la la-angle-right"></i>';
            $msg .= '</a>';
            $msg .= '</li>';
        } else if ($next_btn) {
            $msg .= '<li class="paginate_button page-item disabled">';
            $msg .= '<a href="javascript:;" aria-label="Next" class="page-link">';
            $msg .= '<i class="la la-angle-right"></i>';
            $msg .= '</a>';
            $msg .= '</li>';
        }

        $msg .= '</ul></div></div>';

        $show = ($per_page < $count) ? (($per_page * $cur_page) <= $count) ? ($per_page * $cur_page) : $count : $count;  
        $cur_page = ($cur_page <= 1) ?  ($count != 0) ? $cur_page : $count : (($cur_page - 1) * $per_page) + 1;

        $total_string = '<div class="infos">Showing '. $cur_page .' to '.$show.' of '.$count.' entries</div>';
        $msg .= '<div class="col-sm-6 text-right pr-5">'.$total_string.'</div><div class="clearfix"></div></div>';
        echo $msg;
    }

    public function get_line_items($limit, $start_from, $dateFrom = '', $dateTo= '', $branchFrom= '', $branchTo = '', $category = '', $item = '', $status = '', $orderby, $keywords = '')
    {   
        $dateFrom2 = date('Y-m-d', strtotime($dateFrom));
        $dateTo2   = date('Y-m-d', strtotime($dateTo));

        $res = TransferItemLine::select([
            'transfer_items_lines.id as id',
            'transfer_items_lines.created_at as transDate',
            'items.name as itemName',
            'items.code as itemCode',
            'items_category.name as itemCategory',
            'transfer_items_lines.quantity as quantity',
            'unit_of_measurements.code as uom',
            'transfer_items_lines.srp as srp',
            'transfer_items_lines.total_amount as total_amount',
            'transfer_items_lines.posted_quantity as posted_quantity',
            'transfer_items_lines.discount1 as disc1',
            'transfer_items_lines.discount2 as disc2',
            'transfer_items_lines.plus as plus',
            'bra1.name as branchFrom',
            'bra2.name as branchTo',
            'transfer_items.transfer_no as transNo',
            'transfer_items_lines.status as status',
        ])
        ->leftJoin('items', function($join)
        {
            $join->on('items.id', '=', 'transfer_items_lines.item_id');
        })
        ->leftJoin('unit_of_measurements', function($join)
        {
            $join->on('unit_of_measurements.id', '=', 'transfer_items_lines.uom_id');
        })
        ->leftJoin('items_category', function($join)
        {
            $join->on('items_category.id', '=', 'items.item_category_id');
        })
        ->leftJoin('transfer_items', function($join)
        {
            $join->on('transfer_items.id', '=', 'transfer_items_lines.transfer_item_id');
        })
        ->leftJoin('branches as bra1', function($join)
        {
            $join->on('bra1.id', '=', 'transfer_items.transfer_from');
        })
        ->leftJoin('branches as bra2', function($join)
        {
            $join->on('bra2.id', '=', 'transfer_items.transfer_to');
        })
        ->where(function($q) use ($keywords) {
            if (!empty($keywords)) {
                $q->where('transfer_items_lines.srp', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.quantity', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.total_amount', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.posted_quantity', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.discount1', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.discount2', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.plus', 'like', '%' . $keywords . '%')
                  ->orWhere('unit_of_measurements.code', 'like', '%' . $keywords . '%')
                  ->orWhere('items.code', 'like', '%' . $keywords . '%')
                  ->orWhere('items.name', 'like', '%' . $keywords . '%')
                  ->orWhere('bra1.name', 'like', '%' . $keywords . '%')
                  ->orWhere('bra2.name', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items.transfer_no', 'like', '%' . $keywords . '%')
                  ->orWhere('items_category.name', 'like', '%' . $keywords . '%');
            }
        })
        ->where(function($q) use ($dateFrom, $dateTo, $dateFrom2, $dateTo2) {
            if (!empty($dateFrom) && !empty($dateTo)) {
                $q->where('transfer_items_lines.created_at', '>=', $dateFrom2)
                    ->where('transfer_items_lines.created_at', '<=', $dateTo2);
            } else if (!empty($dateFrom) && empty($dateTo)) {
                $q->where('transfer_items_lines.created_at', '=', $dateFrom);
            } else if (empty($dateFrom) && !empty($dateTo)) {
                $q->where('transfer_items_lines.created_at', '=', $dateTo);
            }
        })
        ->where(function($q) use ($item){
            if ($item != '') {
                $q->where('items.id', '=',  $item);
            }
        })
        ->where(function($q) use ($category){
            if ($category != '') {
                $q->where('items_category.id', '=',  $category);
            }
        })
        ->where(function($q) use ($branchFrom){
            if ($branchFrom != '') {
                $q->where('bra1.id', '=',  $branchFrom);
            }
        })
        ->where(function($q) use ($branchTo){
            if ($branchTo != '') {
                $q->where('bra2.id', '=',  $branchTo);
            }
        })
        ->where(function($q) use ($status){
            if ($status != '') {
                $q->where("transfer_items_lines.status", $status);
            }
        })
        ->where('transfer_items_lines.is_active', 1)
        ->skip($start_from)->take($limit)
        ->orderBy('transfer_items_lines.id', $orderby)
        ->get();
        
        return $res->map(function($trans) use ($status) {
            if ($status == 'partial') { 
                $srpVal = floatval($trans->total_amount) / floatval($trans->quantity);
                $totalAmt = floatval($trans->posted_quantity) * floatval($srpVal);
            } else {
                $totalAmt = $trans->total_amount;
            }
            return (object) [
                'id' => $trans->id,
                'transNo' => $trans->transNo,
                'transDate' => date('d-M-Y', strtotime($trans->transDate)),
                'branchFrom' => $trans->branchFrom,
                'branchTo' => $trans->branchTo,
                'category' => $trans->itemCategory,
                'item' => $trans->itemCode.' - '.$trans->itemName,
                'uom' => $trans->uom,
                'quantity' => ($status == 'partial') ? $trans->posted_quantity : $trans->quantity,
                'srp' => $trans->srp,
                'disc1' => $trans->disc1,
                'disc2' => $trans->disc2,
                'plus' => $trans->plus,
                'status' => ($status == 'partial' || $status == 'posted') ? $trans->status : 'prepared',
                'totalAmt' => floatval($totalAmt)
            ];
        });
    }

    public function get_page_count($dateFrom = '', $dateTo= '', $branchFrom= '', $branchTo = '', $category = '', $item = '', $status = '', $orderby, $keywords = '')
    {   
        $dateFrom2 = date('Y-m-d', strtotime($dateFrom));
        $dateTo2   = date('Y-m-d', strtotime($dateTo));
        $res = TransferItemLine::select([
            'transfer_items_lines.id as id',
            'transfer_items_lines.created_at as transDate',
            'items.name as itemName',
            'items.code as itemCode',
            'items_category.name as itemCategory',
            'transfer_items_lines.quantity as quantity',
            'unit_of_measurements.code as uom',
            'transfer_items_lines.srp as srp',
            'transfer_items_lines.total_amount as total_amount',
            'transfer_items_lines.posted_quantity as posted_quantity',
            'transfer_items_lines.discount1 as disc1',
            'transfer_items_lines.discount2 as disc2',
            'transfer_items_lines.plus as plus',
            'bra1.name as branchFrom',
            'bra2.name as branchTo',
            'transfer_items.transfer_no as transNo',
            'transfer_items_lines.status as status',
        ])
        ->leftJoin('items', function($join)
        {
            $join->on('items.id', '=', 'transfer_items_lines.item_id');
        })
        ->leftJoin('unit_of_measurements', function($join)
        {
            $join->on('unit_of_measurements.id', '=', 'transfer_items_lines.uom_id');
        })
        ->leftJoin('items_category', function($join)
        {
            $join->on('items_category.id', '=', 'items.item_category_id');
        })
        ->leftJoin('transfer_items', function($join)
        {
            $join->on('transfer_items.id', '=', 'transfer_items_lines.transfer_item_id');
        })
        ->leftJoin('branches as bra1', function($join)
        {
            $join->on('bra1.id', '=', 'transfer_items.transfer_from');
        })
        ->leftJoin('branches as bra2', function($join)
        {
            $join->on('bra2.id', '=', 'transfer_items.transfer_to');
        })
        ->where(function($q) use ($keywords) {
            if (!empty($keywords)) {
                $q->where('transfer_items_lines.srp', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.quantity', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.total_amount', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.posted_quantity', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.discount1', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.discount2', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.plus', 'like', '%' . $keywords . '%')
                  ->orWhere('unit_of_measurements.code', 'like', '%' . $keywords . '%')
                  ->orWhere('items.code', 'like', '%' . $keywords . '%')
                  ->orWhere('items.name', 'like', '%' . $keywords . '%')
                  ->orWhere('bra1.name', 'like', '%' . $keywords . '%')
                  ->orWhere('bra2.name', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items.transfer_no', 'like', '%' . $keywords . '%')
                  ->orWhere('items_category.name', 'like', '%' . $keywords . '%');
            }
        })
        ->where(function($q) use ($dateFrom, $dateTo, $dateFrom2, $dateTo2) {
            if (!empty($dateFrom) && !empty($dateTo)) {
                $q->where('transfer_items_lines.created_at', '>=', $dateFrom2)
                    ->where('transfer_items_lines.created_at', '<=', $dateTo2);
            } else if (!empty($dateFrom) && empty($dateTo)) {
                $q->where('transfer_items_lines.created_at', '=', $dateFrom);
            } else if (empty($dateFrom) && !empty($dateTo)) {
                $q->where('transfer_items_lines.created_at', '=', $dateTo);
            }
        })
        ->where(function($q) use ($item){
            if ($item != '') {
                $q->where('items.id', '=',  $item);
            }
        })
        ->where(function($q) use ($category){
            if ($category != '') {
                $q->where('items_category.id', '=',  $category);
            }
        })
        ->where(function($q) use ($branchFrom){
            if ($branchFrom != '') {
                $q->where('bra1.id', '=',  $branchFrom);
            }
        })
        ->where(function($q) use ($branchTo){
            if ($branchTo != '') {
                $q->where('bra2.id', '=',  $branchTo);
            }
        })
        ->where(function($q) use ($status){
            if ($status != '') {
                $q->where("transfer_items_lines.status", $status);
            }
        })
        ->where('transfer_items_lines.is_active', 1)
        ->orderBy('transfer_items_lines.id', $orderby)
        ->count();

        return $res;
    }

    public function get_page_amount($dateFrom = '', $dateTo= '', $branchFrom= '', $branchTo = '', $category = '', $item = '', $status = '', $orderby, $keywords = '')
    {   
        $dateFrom2 = date('Y-m-d', strtotime($dateFrom));
        $dateTo2   = date('Y-m-d', strtotime($dateTo));
        $res = TransferItemLine::select([
            'transfer_items_lines.id as id',
            'transfer_items_lines.created_at as transDate',
            'items.name as itemName',
            'items.code as itemCode',
            'items_category.name as itemCategory',
            'transfer_items_lines.quantity as quantity',
            'unit_of_measurements.code as uom',
            'transfer_items_lines.srp as srp',
            'transfer_items_lines.total_amount as total_amount',
            'transfer_items_lines.posted_quantity as posted_quantity',
            'transfer_items_lines.discount1 as disc1',
            'transfer_items_lines.discount2 as disc2',
            'transfer_items_lines.plus as plus',
            'bra1.name as branchFrom',
            'bra2.name as branchTo',
            'transfer_items.transfer_no as transNo',
            'transfer_items_lines.status as status',
        ])
        ->leftJoin('items', function($join)
        {
            $join->on('items.id', '=', 'transfer_items_lines.item_id');
        })
        ->leftJoin('unit_of_measurements', function($join)
        {
            $join->on('unit_of_measurements.id', '=', 'transfer_items_lines.uom_id');
        })
        ->leftJoin('items_category', function($join)
        {
            $join->on('items_category.id', '=', 'items.item_category_id');
        })
        ->leftJoin('transfer_items', function($join)
        {
            $join->on('transfer_items.id', '=', 'transfer_items_lines.transfer_item_id');
        })
        ->leftJoin('branches as bra1', function($join)
        {
            $join->on('bra1.id', '=', 'transfer_items.transfer_from');
        })
        ->leftJoin('branches as bra2', function($join)
        {
            $join->on('bra2.id', '=', 'transfer_items.transfer_to');
        })
        ->where(function($q) use ($keywords) {
            if (!empty($keywords)) {
                $q->where('transfer_items_lines.srp', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.quantity', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.total_amount', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.posted_quantity', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.discount1', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.discount2', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.plus', 'like', '%' . $keywords . '%')
                  ->orWhere('unit_of_measurements.code', 'like', '%' . $keywords . '%')
                  ->orWhere('items.code', 'like', '%' . $keywords . '%')
                  ->orWhere('items.name', 'like', '%' . $keywords . '%')
                  ->orWhere('bra1.name', 'like', '%' . $keywords . '%')
                  ->orWhere('bra2.name', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items.transfer_no', 'like', '%' . $keywords . '%')
                  ->orWhere('items_category.name', 'like', '%' . $keywords . '%');
            }
        })
        ->where(function($q) use ($dateFrom, $dateTo, $dateFrom2, $dateTo2) {
            if (!empty($dateFrom) && !empty($dateTo)) {
                $q->where('transfer_items_lines.created_at', '>=', $dateFrom2)
                    ->where('transfer_items_lines.created_at', '<=', $dateTo2);
            } else if (!empty($dateFrom) && empty($dateTo)) {
                $q->where('transfer_items_lines.created_at', '=', $dateFrom);
            } else if (empty($dateFrom) && !empty($dateTo)) {
                $q->where('transfer_items_lines.created_at', '=', $dateTo);
            }
        })
        ->where(function($q) use ($item){
            if ($item != '') {
                $q->where('items.id', '=',  $item);
            }
        })
        ->where(function($q) use ($category){
            if ($category != '') {
                $q->where('items_category.id', '=',  $category);
            }
        })
        ->where(function($q) use ($branchFrom){
            if ($branchFrom != '') {
                $q->where('bra1.id', '=',  $branchFrom);
            }
        })
        ->where(function($q) use ($branchTo){
            if ($branchTo != '') {
                $q->where('bra2.id', '=',  $branchTo);
            }
        })
        ->where(function($q) use ($status){
            if ($status != '') {
                $q->where("transfer_items_lines.status", $status);
            }
        })
        ->where('transfer_items_lines.is_active', 1)
        ->orderBy('transfer_items_lines.id', $orderby)
        ->get();

        $totalAmt = 0;
        if ($status == 'partial') {
            foreach ($res as $trans) {
                $srpVal = floatval($trans->total_amount) / floatval($trans->quantity);
                $totalAmt += floatval($trans->posted_quantity) * floatval($srpVal);
            }
        } else {
            foreach ($res as $trans) {
                $totalAmt += floatval($trans->total_amount);
            }
        }

        return $totalAmt;
    }

    public function export(Request $request)
    {
        return Excel::download(new TransferItemReportExport($request), 'transfer_item_report_'.time().'.xlsx');
    }

    public function audit_logs($entity, $entity_id, $description, $data, $timestamp, $user)
    {
        $auditLogs = AuditLog::create([
            'entity' => $entity,
            'entity_id' => $entity_id,
            'description' => $description,
            'data' => json_encode($data),
            'created_at' => $timestamp,
            'created_by' => $user
        ]);

        return true;
    }
}