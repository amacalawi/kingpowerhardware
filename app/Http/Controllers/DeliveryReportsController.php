<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\Delivery;
use App\Models\DeliveryLine;
use Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\File;
// use App\Components\FlashMessages;
// use App\Helper\Helper;

class DeliveryReportsController extends Controller
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
        $agents = (new User)->all_agents_selectpicker();
        $branches = (new Branch)->all_branches_selectpicker();
        $types = ['summary' => 'Summary', 'detailed' => 'Detailed'];
        $customers = (new Customer)->all_customer_selectpicker();
        $statuses = ['' => 'Select a status', 'prepared' => 'Prepared', 'posted' => 'Posted', 'billed' => 'Billed'];
        $orderby = ['asc' => 'Ascending', 'desc' => 'Descending'];
        return view('modules/reports/delivery-reports/manage')->with(compact('customers', 'orderby', 'statuses', 'menus', 'agents', 'branches', 'types'));
    }

    public function search(Request $request)
    {   
        $orderby      = $request->get('orderby');  
        $keywords     = $request->get('keywords');
        $dateFrom     = $request->get('dateFrom');  
        $dateTo       = $request->get('dateTo');  
        $type         = $request->get('type');  
        $branch       = $request->get('branch');
        $customer     = $request->get('customer');  
        $agent        = $request->get('agent');  
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
                $msg .= '<th class="text-center">DR No</th>';
                $msg .= '<th class="">Branch</th>';
                $msg .= '<th class="">Customer</th>';
                $msg .= '<th class="">Agent</th>';
                $msg .= '<th class="text-center">Total Amount</th>';
                $msg .= '<th class="text-center">Satus</th>';
                $msg .= '</tr>';
            } else {
                $msg .= '<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">';
                $msg .= '<th class="text-center">Transaction Date</th>';
                $msg .= '<th class="text-center">DR No</th>';
                $msg .= '<th class="">Branch</th>';
                $msg .= '<th class="">Customer</th>';
                $msg .= '<th class="">Agent</th>';
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
        
        $query = $this->get_line_items($per_page, $start_from, $dateFrom, $dateTo, $type, $branch, $customer, $agent, $status, $orderby, $keywords);
        $count = $this->get_page_count($dateFrom, $dateTo, $type, $branch, $customer, $agent, $status, $orderby, $keywords);
        $no_of_paginations = ceil($count / $per_page);
        $assets = url('assets/media/illustrations/work.png');

        if($count <= 0)
        {
            $msg .= '<tr>';
            $msg .= '<td colspan="8" class="text-center">there are no data has been displayed.<br/><br/><br/>';
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
                    $msg .= '<tr data-row-id="'.$row->id.'" data-row-doc="'.$row->docNo.'">';
                    $msg .= '<td class="text-center">'.$row->transDate.'</td>';
                    $msg .= '<td class="text-center">'.$row->docNo.'</td>';
                    $msg .= '<td>'.$row->branch.'</td>';
                    $msg .= '<td>'.$row->customer.'</td>';
                    $msg .= '<td>'.$row->agent.'</td>';
                    $msg .= '<td class="text-right">'.$totalAmt.'</td>';
                    $msg .= '<td class="text-center">'.$row->status.'</td>';
                    $msg .= '</tr>';
                } else {
                    $totalAmt = number_format(floor(($row->total_amount*100))/100,2);
                    $msg .= '<tr data-row-id="'.$row->line_id.'" data-row-doc="'.$row->docNo.'">';
                    $msg .= '<td class="text-center">'.$row->transDate.'</td>';
                    $msg .= '<td class="text-center">'.$row->docNo.'</td>';
                    $msg .= '<td>'.$row->branch.'</td>';
                    $msg .= '<td>'.$row->customer.'</td>';
                    $msg .= '<td>'.$row->agent.'</td>';
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

    public function get_line_items($limit, $start_from, $dateFrom = '', $dateTo = '', $type, $branch = '', $customer = '', $agent = '', $status = '', $orderby, $keywords = '')
    {   
        $dateFrom2 = date('Y-m-d', strtotime($dateFrom)).' 00:00:01';
        $dateTo2   = date('Y-m-d', strtotime($dateTo)).' 23:59:59';

        if ($type == 'summary') {
            $res = Delivery::select([
                'delivery.id as id',
                'branches.name as branch',
                'customers.name as customer',
                'users.name as agent',
                'delivery.delivery_doc_no as docNo',
                'delivery.created_at as transDate',
                'delivery.total_amount as totalAmt',
                'delivery.status as status'
            ])
            ->leftJoin('users', function($join)
            {
                $join->on('users.id', '=', 'delivery.agent_id');
            })
            ->leftJoin('branches', function($join)
            {
                $join->on('branches.id', '=', 'delivery.branch_id');
            })
            ->leftJoin('customers', function($join)
            {
                $join->on('customers.id', '=', 'delivery.customer_id');
            })
            ->where(function($q) use ($keywords) {
                if (!empty($keywords)) {
                    $q->where('delivery.delivery_doc_no', 'like', '%' . $keywords . '%')
                    ->orWhere('delivery.total_amount', 'like', '%' . $keywords . '%')
                    ->orWhere('customers.name', 'like', '%' . $keywords . '%')
                    ->orWhere('branches.name', 'like', '%' . $keywords . '%')
                    ->orWhere('users.name', 'like', '%' . $keywords . '%');
                }
            })
            ->where(function($q) use ($dateFrom, $dateTo, $dateFrom2, $dateTo2) {
                if (!empty($dateFrom) && !empty($dateTo)) {
                    $q->where('delivery.created_at', '>=', $dateFrom2)
                        ->where('delivery.created_at', '<=', $dateTo2);
                } else if (!empty($dateFrom) && empty($dateTo)) {
                    $q->where('delivery.created_at', '=', $dateFrom);
                } else if (empty($dateFrom) && !empty($dateTo)) {
                    $q->where('delivery.created_at', '=', $dateTo);
                }
            })
            ->where(function($q) use ($customer){
                if ($customer != '') {
                    $q->where('customers.id', '=',  $customer);
                }
            })
            ->where(function($q) use ($agent){
                if ($agent != '') {
                    $q->where('users.id', '=',  $agent);
                }
            })
            ->where(function($q) use ($branch){
                if ($branch != '') {
                    $q->where('branches.id', '=',  $branch);
                }
            })
            ->where(function($q) use ($status){
                if ($status != '') {
                    $q->where("delivery.status", $status);
                }
            })
            ->where('delivery.status', '!=', 'draft')
            ->where('delivery.is_active', 1)
            ->skip($start_from)->take($limit)
            ->orderBy('delivery.id', $orderby)
            // ->groupBy('delivery.id')
            ->get();

            return $res->map(function($del) {
                return (object) [
                    'id' => $del->id,
                    'transDate' => date('d-M-Y', strtotime($del->transDate)),
                    'totalAmt' => $del->totalAmt,
                    'docNo' => $del->docNo,
                    'status' => $del->status,
                    'agent' => $del->agent,
                    'branch' => $del->branch,
                    'customer' => $del->customer
                ];
            });
        } else {
            $res = DeliveryLine::select([
                'delivery.id as id',
                'branches.name as branch',
                'customers.name as customer',
                'users.name as agent',
                'delivery.delivery_doc_no as docNo',
                'delivery.created_at as transDate',
                'delivery.total_amount as totalAmt',
                'delivery_lines.status as status',
                'delivery_lines.id as lineID',
                'items.name as itemName',
                'items.code as itemCode',
                'delivery_lines.quantity as quantity',
                'delivery_lines.uom as uom',
                'delivery_lines.srp as srp',
                'delivery_lines.total_amount as total_amount',
                'delivery_lines.discount1 as disc1',
                'delivery_lines.discount2 as disc2',
                'delivery_lines.plus as plus',
                'delivery_lines.posted_quantity as posted_quantity',
            ])
            ->leftJoin('items', function($join)
            {
                $join->on('items.id', '=', 'delivery_lines.item_id');
            })
            ->leftJoin('delivery', function($join)
            {
                $join->on('delivery.id', '=', 'delivery_lines.delivery_id');
            })
            ->leftJoin('users', function($join)
            {
                $join->on('users.id', '=', 'delivery.agent_id');
            })
            ->leftJoin('branches', function($join)
            {
                $join->on('branches.id', '=', 'delivery.branch_id');
            })
            ->leftJoin('customers', function($join)
            {
                $join->on('customers.id', '=', 'delivery.customer_id');
            })
            ->where(function($q) use ($keywords) {
                if (!empty($keywords)) {
                    $q->where('delivery.delivery_doc_no', 'like', '%' . $keywords . '%')
                    ->orWhere('delivery.total_amount', 'like', '%' . $keywords . '%')
                    ->orWhere('customers.name', 'like', '%' . $keywords . '%')
                    ->orWhere('branches.name', 'like', '%' . $keywords . '%')
                    ->orWhere('users.name', 'like', '%' . $keywords . '%')
                    ->orWhere('delivery_lines.srp', 'like', '%' . $keywords . '%')
                    ->orWhere('delivery_lines.uom', 'like', '%' . $keywords . '%')
                    ->orWhere('delivery_lines.quantity', 'like', '%' . $keywords . '%')
                    ->orWhere('delivery_lines.total_amount', 'like', '%' . $keywords . '%')
                    ->orWhere('delivery_lines.discount1', 'like', '%' . $keywords . '%')
                    ->orWhere('delivery_lines.discount2', 'like', '%' . $keywords . '%')
                    ->orWhere('delivery_lines.plus', 'like', '%' . $keywords . '%')
                    ->orWhere('delivery_lines.posted_quantity', 'like', '%' . $keywords . '%')
                    ->orWhere('items.code', 'like', '%' . $keywords . '%')
                    ->orWhere('items.name', 'like', '%' . $keywords . '%')
                    ->orWhere('items.description', 'like', '%' . $keywords . '%');
                }
            })
            ->where(function($q) use ($dateFrom, $dateTo, $dateFrom2, $dateTo2) {
                if (!empty($dateFrom) && !empty($dateTo)) {
                    $q->where('delivery.created_at', '>=', $dateFrom2)
                        ->where('delivery.created_at', '<=', $dateTo2);
                } else if (!empty($dateFrom) && empty($dateTo)) {
                    $q->where('delivery.created_at', '=', $dateFrom);
                } else if (empty($dateFrom) && !empty($dateTo)) {
                    $q->where('delivery.created_at', '=', $dateTo);
                }
            })
            ->where(function($q) use ($customer){
                if ($customer != '') {
                    $q->where('customers.id', '=',  $customer);
                }
            })
            ->where(function($q) use ($agent){
                if ($agent != '') {
                    $q->where('users.id', '=',  $agent);
                }
            })
            ->where(function($q) use ($branch){
                if ($branch != '') {
                    $q->where('branches.id', '=',  $branch);
                }
            })
            ->where(function($q) use ($status){
                if ($status != '') {
                    $q->where("delivery_lines.status", $status);
                }
            })
            ->where('delivery.status', '!=', 'draft')
            ->where('delivery_lines.is_active', 1)
            ->skip($start_from)->take($limit)
            ->orderBy('delivery_lines.id', $orderby)
            // ->groupBy('delivery_lines.id')
            ->get();

            return $res->map(function($del) use ($status) {
                return (object) [
                    'id' => $del->id,
                    'transDate' => date('d-M-Y', strtotime($del->transDate)),
                    'totalAmt' => $del->totalAmt,
                    'docNo' => $del->docNo,
                    'status' => $del->status,
                    'agent' => $del->agent,
                    'branch' => $del->branch,
                    'customer' => $del->customer,
                    'item' => $del->itemCode.' - '.$del->itemName,
                    'quantity' => ($status == 'posted') ? $del->posted_quantity : $del->quantity,
                    'uom' => $del->uom,
                    'srp' => $del->srp,
                    'total_amount' => $del->total_amount,
                    'disc1' => $del->disc1,
                    'disc2' => $del->disc2,
                    'plus' => $del->plus,
                    'line_id' => $del->lineID
                ];
            });
        }
    }

    public function get_page_count($dateFrom, $dateTo, $type, $branch, $customer, $agent, $status, $orderby, $keywords= '')
    {
        $dateFrom2 = date('Y-m-d', strtotime($dateFrom)).' 00:00:01';
        $dateTo2   = date('Y-m-d', strtotime($dateTo)).'23:59:59';
        if ($type == 'summary') {
            $res = Delivery::select([
                'delivery.id as id',
                'branches.name as branch',
                'customers.name as customer',
                'users.name as agent',
                'delivery.delivery_doc_no as docNo',
                'delivery.created_at as transDate',
                'delivery.total_amount as totalAmt',
                'delivery.status as status'
            ])
            ->leftJoin('users', function($join)
            {
                $join->on('users.id', '=', 'delivery.agent_id');
            })
            ->leftJoin('branches', function($join)
            {
                $join->on('branches.id', '=', 'delivery.branch_id');
            })
            ->leftJoin('customers', function($join)
            {
                $join->on('customers.id', '=', 'delivery.customer_id');
            })
            ->where(function($q) use ($keywords) {
                if (!empty($keywords)) {
                    $q->where('delivery.delivery_doc_no', 'like', '%' . $keywords . '%')
                    ->orWhere('delivery.total_amount', 'like', '%' . $keywords . '%')
                    ->orWhere('customers.name', 'like', '%' . $keywords . '%')
                    ->orWhere('branches.name', 'like', '%' . $keywords . '%')
                    ->orWhere('users.name', 'like', '%' . $keywords . '%');
                }
            })
            ->where(function($q) use ($dateFrom, $dateTo, $dateFrom2, $dateTo2) {
                if (!empty($dateFrom) && !empty($dateTo)) {
                    $q->where('delivery.created_at', '>=', $dateFrom2)
                        ->where('delivery.created_at', '<=', $dateTo2);
                } else if (!empty($dateFrom) && empty($dateTo)) {
                    $q->where('delivery.created_at', '=', $dateFrom);
                } else if (empty($dateFrom) && !empty($dateTo)) {
                    $q->where('delivery.created_at', '=', $dateTo);
                }
            })
            ->where(function($q) use ($customer){
                if ($customer != '') {
                    $q->where('customers.id', '=',  $customer);
                }
            })
            ->where(function($q) use ($agent){
                if ($agent != '') {
                    $q->where('users.id', '=',  $agent);
                }
            })
            ->where(function($q) use ($branch){
                if ($branch != '') {
                    $q->where('branches.id', '=',  $branch);
                }
            })
            ->where(function($q) use ($status){
                if ($status != '') {
                    $q->where("delivery.status", $status);
                }
            })
            ->where('delivery.status', '!=', 'draft')
            ->where('delivery.is_active', 1)
            ->orderBy('delivery.id', $orderby)
            ->count();
        } else {
            $res = DeliveryLine::select([
                'delivery.id as id',
                'branches.name as branch',
                'customers.name as customer',
                'users.name as agent',
                'delivery.delivery_doc_no as docNo',
                'delivery.created_at as transDate',
                'delivery.total_amount as totalAmt',
                'delivery.status as status',
                'delivery_lines.id as lineID',
                'items.name as itemName',
                'items.code as itemCode',
                'delivery_lines.quantity as quantity',
                'delivery_lines.uom as uom',
                'delivery_lines.srp as srp',
                'delivery_lines.total_amount as total_amount',
                'delivery_lines.discount1 as disc1',
                'delivery_lines.discount2 as disc2',
                'delivery_lines.plus as plus',
                'delivery_lines.posted_quantity as posted_quantity',
            ])
            ->leftJoin('items', function($join)
            {
                $join->on('items.id', '=', 'delivery_lines.item_id');
            })
            ->leftJoin('delivery', function($join)
            {
                $join->on('delivery.id', '=', 'delivery_lines.delivery_id');
            })
            ->leftJoin('users', function($join)
            {
                $join->on('users.id', '=', 'delivery.agent_id');
            })
            ->leftJoin('branches', function($join)
            {
                $join->on('branches.id', '=', 'delivery.branch_id');
            })
            ->leftJoin('customers', function($join)
            {
                $join->on('customers.id', '=', 'delivery.customer_id');
            })
            ->where(function($q) use ($keywords) {
                if (!empty($keywords)) {
                    $q->where('delivery.delivery_doc_no', 'like', '%' . $keywords . '%')
                    ->orWhere('delivery.total_amount', 'like', '%' . $keywords . '%')
                    ->orWhere('customers.name', 'like', '%' . $keywords . '%')
                    ->orWhere('branches.name', 'like', '%' . $keywords . '%')
                    ->orWhere('users.name', 'like', '%' . $keywords . '%')
                    ->orWhere('delivery_lines.srp', 'like', '%' . $keywords . '%')
                    ->orWhere('delivery_lines.uom', 'like', '%' . $keywords . '%')
                    ->orWhere('delivery_lines.quantity', 'like', '%' . $keywords . '%')
                    ->orWhere('delivery_lines.total_amount', 'like', '%' . $keywords . '%')
                    ->orWhere('delivery_lines.discount1', 'like', '%' . $keywords . '%')
                    ->orWhere('delivery_lines.discount2', 'like', '%' . $keywords . '%')
                    ->orWhere('delivery_lines.plus', 'like', '%' . $keywords . '%')
                    ->orWhere('delivery_lines.posted_quantity', 'like', '%' . $keywords . '%')
                    ->orWhere('items.code', 'like', '%' . $keywords . '%')
                    ->orWhere('items.name', 'like', '%' . $keywords . '%')
                    ->orWhere('items.description', 'like', '%' . $keywords . '%');
                }
            })
            ->where(function($q) use ($dateFrom, $dateTo, $dateFrom2, $dateTo2) {
                if (!empty($dateFrom) && !empty($dateTo)) {
                    $q->where('delivery.created_at', '>=', $dateFrom2)
                        ->where('delivery.created_at', '<=', $dateTo2);
                } else if (!empty($dateFrom) && empty($dateTo)) {
                    $q->where('delivery.created_at', '=', $dateFrom);
                } else if (empty($dateFrom) && !empty($dateTo)) {
                    $q->where('delivery.created_at', '=', $dateTo);
                }
            })
            ->where(function($q) use ($customer){
                if ($customer != '') {
                    $q->where('customers.id', '=',  $customer);
                }
            })
            ->where(function($q) use ($agent){
                if ($agent != '') {
                    $q->where('users.id', '=',  $agent);
                }
            })
            ->where(function($q) use ($branch){
                if ($branch != '') {
                    $q->where('branches.id', '=',  $branch);
                }
            })
            ->where(function($q) use ($status){
                if ($status != '') {
                    $q->where("delivery_lines.status", $status);
                }
            })
            ->where('delivery.status', '!=', 'draft')
            ->where('delivery_lines.is_active', 1)
            ->orderBy('delivery_lines.id', $orderby)
            // ->groupBy('delivery_lines.id')
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