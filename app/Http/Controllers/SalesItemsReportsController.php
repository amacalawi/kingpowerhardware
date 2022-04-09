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
use App\Models\Delivery;
use App\Models\DeliveryLine;
use App\Models\DeliveryLinePosting;
use Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\File;
// use App\Components\FlashMessages;
// use App\Helper\Helper;

class SalesItemsReportsController extends Controller
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
        return view('modules/reports/sales-items-reports/manage')->with(compact('categories', 'orderby', 'items', 'menus', 'branches'));
    }

    public function search(Request $request)
    {   
        $orderby      = $request->get('orderby');  
        $keywords     = $request->get('keywords');
        $dateFrom     = $request->get('dateFrom');  
        $dateTo       = $request->get('dateTo');  
        $branch       = $request->get('branch');
        $category     = $request->get('category');  
        $item         = $request->get('item');  
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

        $msg = "";
        
        $msg .= '<div class="table-responsive">';
        $msg .= '<table class="table align-middle table-row-dashed fs-6 gy-5" id="customerTable">';
        $msg .= '<thead>';
        $msg .= '<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">';
        $msg .= '<th class="text-center">Transaction Date</th>';
        $msg .= '<th class="">Branch</th>';
        $msg .= '<th class="">Category</th>';
        $msg .= '<th class="">Items</th>';
        $msg .= '<th class="text-center">Qty</th>';
        $msg .= '<th class="text-center">UOM</th>';
        $msg .= '<th class="text-center">SRP</th>';
        $msg .= '<th class="text-center">DISC 1</th>';
        $msg .= '<th class="text-center">DISC 2</th>';
        $msg .= '<th class="text-center">PLUS</th>';
        $msg .= '<th class="text-right">Total Amount</th>';
        $msg .= '<th class="text-center">IS SPECIAL</th>';
        $msg .= '</tr>';
        $msg .= '</thead>';
        $msg .= '<tbody class="fw-bold text-gray-600">';
        
        $query = $this->get_line_items($per_page, $start_from, $dateFrom, $dateTo, $branch, $category, $item, $orderby, $keywords);
        $count = $this->get_page_count($dateFrom, $dateTo, $branch, $category, $item, $orderby, $keywords);
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
                $totalAmt = number_format(floor(($row->totalAmt*100))/100,2);
                $msg .= '<tr">';
                $msg .= '<td class="text-center">'.$row->transDate.'</td>';
                $msg .= '<td>'.$row->branch.'</td>';
                $msg .= '<td>'.$row->category.'</td>';
                $msg .= '<td>'.$row->item.'</td>';
                $msg .= '<td class="text-center">'.$row->quantity.'</td>';
                $msg .= '<td class="text-center">'.$row->uom.'</td>';
                $msg .= '<td class="text-center">'.$row->srp.'</td>';
                $msg .= '<td class="text-center">'.$row->disc1.'</td>';
                $msg .= '<td class="text-center">'.$row->disc2.'</td>';
                $msg .= '<td class="text-center">'.$row->plus.'</td>';
                $msg .= '<td class="text-right">'.$totalAmt.'</td>';
                $msg .= '<td class="text-center">'.$row->is_special.'</td>';
                $msg .= '</tr>';
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

    public function get_line_items($limit, $start_from, $dateFrom = '', $dateTo = '', $branch = '', $category = '', $item = '', $orderby, $keywords = '')
    {   
        $dateFrom2 = date('Y-m-d', strtotime($dateFrom));
        $dateTo2   = date('Y-m-d', strtotime($dateTo));

        $res = DeliveryLinePosting::select([
            'delivery_lines_posting.id as id',
            'delivery_lines_posting.delivery_line_id as lineId',
            'delivery_lines_posting.date_delivered as transDate',
            'delivery_lines.uom as uom',
            'items.code as itemCode',
            'items.name as itemName',
            'items_category.name as itemCategory',
            'delivery_lines.is_special as is_special',
            'delivery_lines.srp as srp',
            'delivery_lines.quantity as prepQuantity',
            'delivery_lines_posting.quantity as quantity',
            'delivery_lines.total_amount as totalAmt',
            'delivery_lines.discount1 as disc1',
            'delivery_lines.discount2 as disc2',
            'delivery_lines.plus as plus',
            'branches.name as branch'
        ])
        ->leftJoin('delivery_lines', function($join)
        {
            $join->on('delivery_lines.id', '=', 'delivery_lines_posting.delivery_line_id');
        })
        ->leftJoin('items', function($join)
        {
            $join->on('items.id', '=', 'delivery_lines.item_id');
        })
        ->leftJoin('items_category', function($join)
        {
            $join->on('items_category.id', '=', 'items.item_category_id');
        })
        ->leftJoin('delivery', function($join)
        {
            $join->on('delivery.id', '=', 'delivery_lines.delivery_id');
        })
        ->leftJoin('branches', function($join)
        {
            $join->on('branches.id', '=', 'delivery.branch_id');
        })
        ->where(function($q) use ($keywords) {
            if (!empty($keywords)) {
                $q->where('delivery_lines.discount1', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.discount2', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.plus', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.uom', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.srp', 'like', '%' . $keywords . '%')
                ->orWhere('items.name', 'like', '%' . $keywords . '%')
                ->orWhere('items.code', 'like', '%' . $keywords . '%')
                ->orWhere('branches.name', 'like', '%' . $keywords . '%')
                ->orWhere('items_category.name', 'like', '%' . $keywords . '%');
            }
        })
        ->where(function($q) use ($dateFrom, $dateTo, $dateFrom2, $dateTo2) {
            if (!empty($dateFrom) && !empty($dateTo)) {
                $q->where('delivery_lines_posting.date_delivered', '>=', $dateFrom2)
                    ->where('delivery_lines_posting.date_delivered', '<=', $dateTo2);
            } else if (!empty($dateFrom) && empty($dateTo)) {
                $q->where('delivery_lines_posting.date_delivered', '=', $dateFrom);
            } else if (empty($dateFrom) && !empty($dateTo)) {
                $q->where('delivery_lines_posting.date_delivered', '=', $dateTo);
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
        ->where(function($q) use ($branch){
            if ($branch != '') {
                $q->where('branches.id', '=',  $branch);
            }
        })
        ->where('delivery_lines_posting.is_active', 1)
        ->skip($start_from)->take($limit)
        ->orderBy('delivery_lines_posting.id', $orderby)
        ->get();
        
        return $res->map(function($del) {
            $srpVal = floatval($del->totalAmt) / floatval($del->prepQuantity);
            $totalAmt = floatval($del->quantity) * floatval($srpVal);
            return (object) [
                'id' => $del->id,
                'lineId' => $del->lineId,
                'transDate' => date('d-M-Y', strtotime($del->transDate)),
                'branch' => $del->branch,
                'uom' => $del->uom,
                'item' => $del->itemCode.' - '.$del->itemName,
                'category' => $del->itemCategory,
                'srp' => $del->srp,
                'disc1' => $del->disc1 ? $del->disc1.'%' : '',
                'disc2' => $del->disc2 ? $del->disc2.'%' : '',
                'plus' => $del->plus ? $del->plus.'%' : '',
                'prepQuantity' => $del->prepQuantity,
                'quantity' => $del->quantity,
                'is_special' => ($del->is_special > 0) ? 'Yes' : 'No',
                'totalAmt' => floatval($totalAmt)
            ];
        });
    }

    public function get_page_count($dateFrom = '', $dateTo = '', $branch = '', $category = '', $item = '', $orderby, $keywords = '')
    {   
        $dateFrom2 = date('Y-m-d', strtotime($dateFrom));
        $dateTo2   = date('Y-m-d', strtotime($dateTo));
        $res = DeliveryLinePosting::select([
            'delivery_lines_posting.id as id',
            'delivery_lines_posting.delivery_line_id as lineId',
            'delivery_lines_posting.date_delivered as transDate',
            'delivery_lines.uom as uom',
            'items.code as itemCode',
            'items.name as itemName',
            'items_category.name as itemCategory',
            'delivery_lines.is_special as is_special',
            'delivery_lines.srp as srp',
            'delivery_lines.quantity as prepQuantity',
            'delivery_lines_posting.quantity as quantity',
            'delivery_lines.total_amount as totalAmt',
            'delivery_lines.discount1 as disc1',
            'delivery_lines.discount2 as disc2',
            'delivery_lines.plus as plus',
            'branches.name as branch'
        ])
        ->leftJoin('delivery_lines', function($join)
        {
            $join->on('delivery_lines.id', '=', 'delivery_lines_posting.delivery_line_id');
        })
        ->leftJoin('items', function($join)
        {
            $join->on('items.id', '=', 'delivery_lines.item_id');
        })
        ->leftJoin('items_category', function($join)
        {
            $join->on('items_category.id', '=', 'items.item_category_id');
        })
        ->leftJoin('delivery', function($join)
        {
            $join->on('delivery.id', '=', 'delivery_lines.delivery_id');
        })
        ->leftJoin('branches', function($join)
        {
            $join->on('branches.id', '=', 'delivery.branch_id');
        })
        ->where(function($q) use ($keywords) {
            if (!empty($keywords)) {
                $q->where('delivery_lines.discount1', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.discount2', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.plus', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.uom', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.srp', 'like', '%' . $keywords . '%')
                ->orWhere('items.name', 'like', '%' . $keywords . '%')
                ->orWhere('items.code', 'like', '%' . $keywords . '%')
                ->orWhere('branches.name', 'like', '%' . $keywords . '%')
                ->orWhere('items_category.name', 'like', '%' . $keywords . '%');
            }
        })
        ->where(function($q) use ($dateFrom, $dateTo, $dateFrom2, $dateTo2) {
            if (!empty($dateFrom) && !empty($dateTo)) {
                $q->where('delivery_lines_posting.date_delivered', '>=', $dateFrom2)
                    ->where('delivery_lines_posting.date_delivered', '<=', $dateTo2);
            } else if (!empty($dateFrom) && empty($dateTo)) {
                $q->where('delivery_lines_posting.date_delivered', '=', $dateFrom);
            } else if (empty($dateFrom) && !empty($dateTo)) {
                $q->where('delivery_lines_posting.date_delivered', '=', $dateTo);
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
        ->where(function($q) use ($branch){
            if ($branch != '') {
                $q->where('branches.id', '=',  $branch);
            }
        })
        ->where('delivery_lines_posting.is_active', 1)
        ->orderBy('delivery_lines_posting.id', $orderby)
        ->count();

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