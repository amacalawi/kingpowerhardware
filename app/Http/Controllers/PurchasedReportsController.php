<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseOrderType;
use Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\File;
// use App\Components\FlashMessages;
// use App\Helper\Helper;

class PurchasedReportsController extends Controller
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
        $po_types = (new PurchaseOrderType)->all_po_type_selectpicker();
        $branches = (new Branch)->all_branches_selectpicker();
        $types = ['summary' => 'Summary', 'detailed' => 'Detailed'];
        $suppliers = (new Supplier)->all_suppliers_selectpicker();
        $statuses = ['' => 'Select a status', 'prepared' => 'Prepared', 'posted' => 'Posted'];
        $orderby = ['asc' => 'Ascending', 'desc' => 'Descending'];
        return view('modules/reports/purchased-reports/manage')->with(compact('suppliers', 'orderby', 'statuses', 'menus', 'po_types', 'branches', 'types'));
    }

    public function search(Request $request)
    {   
        $orderby      = $request->get('orderby');  
        $keywords     = $request->get('keywords');
        $dateFrom     = $request->get('dateFrom');  
        $dateTo       = $request->get('dateTo');  
        $type         = $request->get('type');  
        $branch       = $request->get('branch');
        $supplier     = $request->get('supplier');  
        $po_type      = $request->get('po_type');  
        $status       = $request->get('status');  
        $cur_page     = null != $request->post('page') ? $request->post('page') : 1;
        $per_page     = 5 == -1 ? 0 : 5;
        $page         = $cur_page !== null ? $cur_page : 1;
        $start_from   = ($page-1) * $per_page;

        $previous_btn = true;
        $next_btn = true;
        $value = 0;
        $first_btn = true;
        $pagess = 0;
        $last_btn = true;

        $msg = "";
        
        $msg .= '<div class="table-responsive">';
        $msg .= '<table class="table align-middle table-row-dashed fs-6 gy-5" id="customerTable">';
        $msg .= '<thead>';
            if ($type == 'summary') {
                $msg .= '<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">';
                $msg .= '<th class="text-center">Transaction Date</th>';
                $msg .= '<th class="text-center">PO No</th>';
                $msg .= '<th class="">Branch</th>';
                $msg .= '<th class="">Supplier</th>';
                $msg .= '<th class="">Type</th>';
                $msg .= '<th class="text-center">Total Amount</th>';
                $msg .= '<th class="text-center">Satus</th>';
                $msg .= '</tr>';
            } else {
                $msg .= '<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">';
                $msg .= '<th class="text-center">Transaction Date</th>';
                $msg .= '<th class="text-center">DR No</th>';
                $msg .= '<th class="">Branch</th>';
                $msg .= '<th class="">Supplier</th>';
                $msg .= '<th class="">Type</th>';
                $msg .= '<th class="">Items</th>';
                $msg .= '<th class="text-center">Qty</th>';
                $msg .= '<th class="text-center">UOM</th>';
                $msg .= '<th class="text-center">SRP</th>';
                $msg .= '<th class="text-center">Total Amount</th>';
                $msg .= '<th class="text-center">Satus</th>';
                $msg .= '</tr>';
            }
        $msg .= '</thead>';
        $msg .= '<tbody class="fw-bold text-gray-600">';
        
        $query = $this->get_line_items($per_page, $start_from, $dateFrom, $dateTo, $type, $branch, $supplier, $po_type, $status, $orderby, $keywords);
        $count = $this->get_page_count($dateFrom, $dateTo, $type, $branch, $supplier, $po_type, $status, $orderby, $keywords);
        $no_of_paginations = ceil($count / $per_page);
        $assets = url('assets/media/illustrations/work.png');

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
                if ($type == 'summary') {
                    $totalAmt = number_format(floor(($row->totalAmt*100))/100,2);
                    $msg .= '<tr data-row-id="'.$row->id.'" data-row-doc="'.$row->poNo.'">';
                    $msg .= '<td class="text-center">'.$row->transDate.'</td>';
                    $msg .= '<td class="text-center">'.$row->poNo.'</td>';
                    $msg .= '<td>'.$row->branch.'</td>';
                    $msg .= '<td>'.$row->customer.'</td>';
                    $msg .= '<td>'.$row->po_type.'</td>';
                    $msg .= '<td class="text-right">'.$totalAmt.'</td>';
                    $msg .= '<td class="text-center">'.$row->status.'</td>';
                    $msg .= '</tr>';
                } else {
                    $totalAmt = number_format(floor(($row->total_amount*100))/100,2);
                    $msg .= '<tr data-row-id="'.$row->line_id.'" data-row-doc="'.$row->poNo.'">';
                    $msg .= '<td class="text-center">'.$row->transDate.'</td>';
                    $msg .= '<td class="text-center">'.$row->poNo.'</td>';
                    $msg .= '<td>'.$row->branch.'</td>';
                    $msg .= '<td>'.$row->customer.'</td>';
                    $msg .= '<td>'.$row->po_type.'</td>';
                    $msg .= '<td>'.$row->item.'</td>';
                    $msg .= '<td class="text-center">'.$row->quantity.'</td>';
                    $msg .= '<td class="text-center">'.$row->uom.'</td>';
                    $msg .= '<td class="text-center">'.$row->srp.'</td>';
                    $msg .= '<td class="text-right">'.$totalAmt.'</td>';
                    $msg .= '<td class="text-center">'.$row->status.'</td>';
                    $msg .= '</tr>';
                }
            }
        }
        $msg .= '</tbody>';
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

        $msg .= '<div class="row"><div class="col-sm-6 pl-5"><div class="dataTables_paginate paging_simple_numbers" id="kt_purchase_orders_table_paginate"><ul class="pagination" style="margin-bottom: 0;">';

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

    public function get_line_items($limit, $start_from, $dateFrom = '', $dateTo = '', $type, $branch = '', $supplier = '', $po_type = '', $status = '', $orderby, $keywords = '')
    {   
        $dateFrom2 = date('Y-m-d', strtotime($dateFrom)).' 00:00:01';
        $dateTo2   = date('Y-m-d', strtotime($dateTo)).' 23:59:59';

        if ($type == 'summary') {
            $res = PurchaseOrder::select([
                'purchase_orders.id as id',
                'branches.name as branch',
                'suppliers.name as customer',
                'purchase_orders_types.name as po_type',
                'purchase_orders.po_no as poNo',
                'purchase_orders.created_at as transDate',
                'purchase_orders.total_amount as totalAmt',
                'purchase_orders.status as status'
            ])
            ->leftJoin('purchase_orders_types', function($join)
            {
                $join->on('purchase_orders_types.id', '=', 'purchase_orders.purchase_order_type_id');
            })
            ->leftJoin('branches', function($join)
            {
                $join->on('branches.id', '=', 'purchase_orders.branch_id');
            })
            ->leftJoin('suppliers', function($join)
            {
                $join->on('suppliers.id', '=', 'purchase_orders.supplier_id');
            })
            ->where(function($q) use ($keywords) {
                if (!empty($keywords)) {
                    $q->where('purchase_orders.po_no', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders.total_amount', 'like', '%' . $keywords . '%')
                    ->orWhere('suppliers.name', 'like', '%' . $keywords . '%')
                    ->orWhere('branches.name', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_types.name', 'like', '%' . $keywords . '%');
                }
            })
            ->where(function($q) use ($dateFrom, $dateTo, $dateFrom2, $dateTo2) {
                if (!empty($dateFrom) && !empty($dateTo)) {
                    $q->where('purchase_orders.created_at', '>=', $dateFrom2)
                        ->where('purchase_orders.created_at', '<=', $dateTo2);
                } else if (!empty($dateFrom) && empty($dateTo)) {
                    $q->where('purchase_orders.created_at', '=', $dateFrom);
                } else if (empty($dateFrom) && !empty($dateTo)) {
                    $q->where('purchase_orders.created_at', '=', $dateTo);
                }
            })
            ->where(function($q) use ($supplier){
                if ($supplier != '') {
                    $q->where('suppliers.id', '=',  $supplier);
                }
            })
            ->where(function($q) use ($po_type){
                if ($po_type != '') {
                    $q->where('purchase_orders_types.id', '=',  $po_type);
                }
            })
            ->where(function($q) use ($branch){
                if ($branch != '') {
                    $q->where('branches.id', '=',  $branch);
                }
            })
            ->where(function($q) use ($status){
                if ($status != '') {
                    $q->where("purchase_orders.status", $status);
                }
            })
            ->where('purchase_orders.status', '!=', 'draft')
            ->where('purchase_orders.is_active', 1)
            ->skip($start_from)->take($limit)
            ->orderBy('purchase_orders.id', $orderby)
            ->get();

            return $res->map(function($del) {
                return (object) [
                    'id' => $del->id,
                    'transDate' => date('d-M-Y', strtotime($del->transDate)),
                    'totalAmt' => $del->totalAmt,
                    'poNo' => $del->poNo,
                    'status' => $del->status,
                    'po_type' => $del->po_type,
                    'branch' => $del->branch,
                    'customer' => $del->customer
                ];
            });
        } else {
            $res = PurchaseOrderLine::select([
                'purchase_orders.id as id',
                'branches.name as branch',
                'suppliers.name as customer',
                'purchase_orders_types.name as po_type',
                'purchase_orders.po_no as poNo',
                'purchase_orders.created_at as transDate',
                'purchase_orders.total_amount as totalAmt',
                'purchase_orders_lines.status as status',
                'purchase_orders_lines.id as lineID',
                'items.name as itemName',
                'items.code as itemCode',
                'purchase_orders_lines.quantity as quantity',
                'unit_of_measurements.code as uom',
                'purchase_orders_lines.srp as srp',
                'purchase_orders_lines.total_amount as total_amount',
                'purchase_orders_lines.posted_quantity as posted_quantity',
            ])
            ->leftJoin('items', function($join)
            {
                $join->on('items.id', '=', 'purchase_orders_lines.item_id');
            })
            ->leftJoin('unit_of_measurements', function($join)
            {
                $join->on('unit_of_measurements.id', '=', 'purchase_orders_lines.uom_id');
            })
            ->leftJoin('purchase_orders', function($join)
            {
                $join->on('purchase_orders.id', '=', 'purchase_orders_lines.purchase_order_id');
            })
            ->leftJoin('purchase_orders_types', function($join)
            {
                $join->on('purchase_orders_types.id', '=', 'purchase_orders.purchase_order_type_id');
            })
            ->leftJoin('branches', function($join)
            {
                $join->on('branches.id', '=', 'purchase_orders.branch_id');
            })
            ->leftJoin('suppliers', function($join)
            {
                $join->on('suppliers.id', '=', 'purchase_orders.supplier_id');
            })
            ->where(function($q) use ($keywords) {
                if (!empty($keywords)) {
                    $q->where('purchase_orders.po_no', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders.total_amount', 'like', '%' . $keywords . '%')
                    ->orWhere('suppliers.name', 'like', '%' . $keywords . '%')
                    ->orWhere('branches.name', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_types.name', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_lines.srp', 'like', '%' . $keywords . '%')
                    ->orWhere('unit_of_measurements.code', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_lines.quantity', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_lines.total_amount', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_lines.discount1', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_lines.discount2', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_lines.plus', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_lines.posted_quantity', 'like', '%' . $keywords . '%')
                    ->orWhere('items.code', 'like', '%' . $keywords . '%')
                    ->orWhere('items.name', 'like', '%' . $keywords . '%')
                    ->orWhere('items.description', 'like', '%' . $keywords . '%');
                }
            })
            ->where(function($q) use ($dateFrom, $dateTo, $dateFrom2, $dateTo2) {
                if (!empty($dateFrom) && !empty($dateTo)) {
                    $q->where('purchase_orders.created_at', '>=', $dateFrom2)
                        ->where('purchase_orders.created_at', '<=', $dateTo2);
                } else if (!empty($dateFrom) && empty($dateTo)) {
                    $q->where('purchase_orders.created_at', '=', $dateFrom);
                } else if (empty($dateFrom) && !empty($dateTo)) {
                    $q->where('purchase_orders.created_at', '=', $dateTo);
                }
            })
            ->where(function($q) use ($supplier){
                if ($supplier != '') {
                    $q->where('suppliers.id', '=',  $supplier);
                }
            })
            ->where(function($q) use ($po_type){
                if ($po_type != '') {
                    $q->where('purchase_orders_types.id', '=',  $po_type);
                }
            })
            ->where(function($q) use ($branch){
                if ($branch != '') {
                    $q->where('branches.id', '=',  $branch);
                }
            })
            ->where(function($q) use ($status){
                if ($status != '') {
                    $q->where("purchase_orders_lines.status", $status);
                }
            })
            ->where('purchase_orders.status', '!=', 'draft')
            ->where('purchase_orders_lines.is_active', 1)
            ->skip($start_from)->take($limit)
            ->orderBy('purchase_orders_lines.id', $orderby)
            ->get();

            return $res->map(function($del) use ($status) {
                return (object) [
                    'id' => $del->id,
                    'transDate' => date('d-M-Y', strtotime($del->transDate)),
                    'totalAmt' => $del->totalAmt,
                    'poNo' => $del->poNo,
                    'status' => $del->status,
                    'po_type' => $del->po_type,
                    'branch' => $del->branch,
                    'customer' => $del->customer,
                    'item' => $del->itemCode.' - '.$del->itemName,
                    'quantity' => ($status == 'posted') ? $del->posted_quantity : $del->quantity,
                    'uom' => $del->uom,
                    'srp' => $del->srp,
                    'total_amount' => $del->total_amount,
                    'line_id' => $del->lineID
                ];
            });
        }
    }

    public function get_page_count($dateFrom, $dateTo, $type, $branch, $supplier, $po_type, $status, $orderby, $keywords= '')
    {
        $dateFrom2 = date('Y-m-d', strtotime($dateFrom)).' 00:00:01';
        $dateTo2   = date('Y-m-d', strtotime($dateTo)).'23:59:59';
        if ($type == 'summary') {
            $res = PurchaseOrder::select([
                'purchase_orders.id as id',
                'branches.name as branch',
                'suppliers.name as customer',
                'purchase_orders_types.name as po_type',
                'purchase_orders.po_no as poNo',
                'purchase_orders.created_at as transDate',
                'purchase_orders.total_amount as totalAmt',
                'purchase_orders.status as status'
            ])
            ->leftJoin('purchase_orders_types', function($join)
            {
                $join->on('purchase_orders_types.id', '=', 'purchase_orders.purchase_order_type_id');
            })
            ->leftJoin('branches', function($join)
            {
                $join->on('branches.id', '=', 'purchase_orders.branch_id');
            })
            ->leftJoin('suppliers', function($join)
            {
                $join->on('suppliers.id', '=', 'purchase_orders.supplier_id');
            })
            ->where(function($q) use ($keywords) {
                if (!empty($keywords)) {
                    $q->where('purchase_orders.po_no', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders.total_amount', 'like', '%' . $keywords . '%')
                    ->orWhere('suppliers.name', 'like', '%' . $keywords . '%')
                    ->orWhere('branches.name', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_types.name', 'like', '%' . $keywords . '%');
                }
            })
            ->where(function($q) use ($dateFrom, $dateTo, $dateFrom2, $dateTo2) {
                if (!empty($dateFrom) && !empty($dateTo)) {
                    $q->where('purchase_orders.created_at', '>=', $dateFrom2)
                        ->where('purchase_orders.created_at', '<=', $dateTo2);
                } else if (!empty($dateFrom) && empty($dateTo)) {
                    $q->where('purchase_orders.created_at', '=', $dateFrom);
                } else if (empty($dateFrom) && !empty($dateTo)) {
                    $q->where('purchase_orders.created_at', '=', $dateTo);
                }
            })
            ->where(function($q) use ($supplier){
                if ($supplier != '') {
                    $q->where('suppliers.id', '=',  $supplier);
                }
            })
            ->where(function($q) use ($po_type){
                if ($po_type != '') {
                    $q->where('purchase_orders_types.id', '=',  $po_type);
                }
            })
            ->where(function($q) use ($branch){
                if ($branch != '') {
                    $q->where('branches.id', '=',  $branch);
                }
            })
            ->where(function($q) use ($status){
                if ($status != '') {
                    $q->where("purchase_orders.status", $status);
                }
            })
            ->where('purchase_orders.status', '!=', 'draft')
            ->where('purchase_orders.is_active', 1)
            ->orderBy('purchase_orders.id', $orderby)
            ->count();
        } else {
            $res = PurchaseOrderLine::select([
                'purchase_orders.id as id',
                'branches.name as branch',
                'suppliers.name as customer',
                'purchase_orders_types.name as po_type',
                'purchase_orders.po_no as poNo',
                'purchase_orders.created_at as transDate',
                'purchase_orders.total_amount as totalAmt',
                'purchase_orders.status as status',
                'purchase_orders_lines.id as lineID',
                'items.name as itemName',
                'items.code as itemCode',
                'purchase_orders_lines.quantity as quantity',
                'unit_of_measurements.code as uom',
                'purchase_orders_lines.srp as srp',
                'purchase_orders_lines.total_amount as total_amount',
                'purchase_orders_lines.discount1 as disc1',
                'purchase_orders_lines.discount2 as disc2',
                'purchase_orders_lines.plus as plus',
                'purchase_orders_lines.posted_quantity as posted_quantity',
            ])
            ->leftJoin('items', function($join)
            {
                $join->on('items.id', '=', 'purchase_orders_lines.item_id');
            })
            ->leftJoin('unit_of_measurements', function($join)
            {
                $join->on('unit_of_measurements.id', '=', 'purchase_orders_lines.uom_id');
            })
            ->leftJoin('purchase_orders', function($join)
            {
                $join->on('purchase_orders.id', '=', 'purchase_orders_lines.purchase_order_id');
            })
            ->leftJoin('purchase_orders_types', function($join)
            {
                $join->on('purchase_orders_types.id', '=', 'purchase_orders.purchase_order_type_id');
            })
            ->leftJoin('branches', function($join)
            {
                $join->on('branches.id', '=', 'purchase_orders.branch_id');
            })
            ->leftJoin('suppliers', function($join)
            {
                $join->on('suppliers.id', '=', 'purchase_orders.supplier_id');
            })
            ->where(function($q) use ($keywords) {
                if (!empty($keywords)) {
                    $q->where('purchase_orders.po_no', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders.total_amount', 'like', '%' . $keywords . '%')
                    ->orWhere('suppliers.name', 'like', '%' . $keywords . '%')
                    ->orWhere('branches.name', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_types.name', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_lines.srp', 'like', '%' . $keywords . '%')
                    ->orWhere('unit_of_measurements.code', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_lines.quantity', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_lines.total_amount', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_lines.discount1', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_lines.discount2', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_lines.plus', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_lines.posted_quantity', 'like', '%' . $keywords . '%')
                    ->orWhere('items.code', 'like', '%' . $keywords . '%')
                    ->orWhere('items.name', 'like', '%' . $keywords . '%')
                    ->orWhere('items.description', 'like', '%' . $keywords . '%');
                }
            })
            ->where(function($q) use ($dateFrom, $dateTo, $dateFrom2, $dateTo2) {
                if (!empty($dateFrom) && !empty($dateTo)) {
                    $q->where('purchase_orders.created_at', '>=', $dateFrom2)
                        ->where('purchase_orders.created_at', '<=', $dateTo2);
                } else if (!empty($dateFrom) && empty($dateTo)) {
                    $q->where('purchase_orders.created_at', '=', $dateFrom);
                } else if (empty($dateFrom) && !empty($dateTo)) {
                    $q->where('purchase_orders.created_at', '=', $dateTo);
                }
            })
            ->where(function($q) use ($supplier){
                if ($supplier != '') {
                    $q->where('suppliers.id', '=',  $supplier);
                }
            })
            ->where(function($q) use ($po_type){
                if ($po_type != '') {
                    $q->where('purchase_orders_types.id', '=',  $po_type);
                }
            })
            ->where(function($q) use ($branch){
                if ($branch != '') {
                    $q->where('branches.id', '=',  $branch);
                }
            })
            ->where(function($q) use ($status){
                if ($status != '') {
                    $q->where("purchase_orders_lines.status", $status);
                }
            })
            ->where('purchase_orders.status', '!=', 'draft')
            ->where('purchase_orders_lines.is_active', 1)
            ->orderBy('purchase_orders_lines.id', $orderby)
            // ->groupBy('purchase_orders_lines.id')
            ->count();
        }

        return $res;
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