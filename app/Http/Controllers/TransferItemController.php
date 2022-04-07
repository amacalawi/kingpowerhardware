<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\TransferItem;
use App\Models\TransferItemLine;
use App\Models\TransferItemLinePosting;
use App\Models\Item;
use App\Models\ItemInventory;
use App\Models\ItemTransaction;
use App\Models\UnitOfMeasurement;
use App\Models\User;
use App\Models\AuditLog;
use Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\File;
use PDF;

class TransferItemController extends Controller
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
        $branches1 = (new Branch)->all_branches_selectpicker(Auth::user()->id);
        $branches2 = (new Branch)->all_branches_selectpicker();
        $uoms = (new UnitOfMeasurement)->all_uom_selectpicker();
        $items = (new Item)->all_item_selectpicker();
        return view('modules/items/transfer-items/manage')->with(compact('menus', 'branches1', 'branches2', 'uoms', 'items'));
    }

    public function inactive(Request $request)
    {   
        // $this->is_permitted(1);    
        $menus = $this->load_menus();
        return view('modules/items/transfer-items/manage-inactive')->with(compact('menus', 'branches'));
    }

    public function store(Request $request)
    {   
        // $this->is_permitted(0);
        $timestamp = date('Y-m-d H:i:s');
        $transNo = $this->generate_trans_no();

        $transferItem = TransferItem::create([
            'transfer_from' => $request->transfer_from,
            'transfer_to' => $request->transfer_to,
            'transfer_no' => $transNo,
            'remarks' => $request->remarks,
            'created_at' => $timestamp,
            'created_by' => Auth::user()->id
        ]);

        if (!$transferItem) {
            throw new NotFoundHttpException();
        }

        $this->audit_logs('transfer_items', $transferItem->id, 'has inserted a new transfer items.', TransferItem::find($transferItem->id), $timestamp, Auth::user()->id);
        
        $data = array(
            'transfer_item_id' => $transferItem->id,
            'transfer_no' => $transNo,
            'title' => 'Well done!',
            'text' => 'The transfer item has been successfully stored.',
            'type' => 'success',
            'class' => 'btn-brand'
        );

        echo json_encode( $data ); exit();
    }

    public function find(Request $request, $id)
    {    
        $trans = TransferItem::find($id);

        if(!$trans) {
            throw new NotFoundHttpException();
        }

        $data = (object) array(
            'transfer_item_id' => $id,
            'transfer_no' => $trans->transfer_no,
            'transfer_from' => $trans->transfer_from,
            'transfer_to' => $trans->transfer_to,
            'remarks' => $trans->remarks,
        );

        return response()
        ->json([
            'status' => 'ok',
            'data' => $data
        ]);
    }

    public function update(Request $request, $id)
    {    
        // $this->is_permitted(2);
        $timestamp = date('Y-m-d H:i:s');
        $trans = TransferItem::find($id);

        if(!$trans) {
            throw new NotFoundHttpException();
        }

        $trans->remarks = $request->remarks;
        $trans->updated_at = $timestamp;
        $trans->updated_by = Auth::user()->id;

        if ($trans->update()) {
            $this->audit_logs('transfer_items', $id, 'has modified a transfer item.', TransferItem::find($id), $timestamp, Auth::user()->id);
            $data = array(
                'title' => 'Well done!',
                'text' => 'The transfer item has been successfully modified.',
                'type' => 'success',
                'class' => 'btn-brand'
            );
            echo json_encode( $data ); exit();
        }
    }

    public function all_active(Request $request)
    {   
        $keywords     = $request->get('keywords');  
        $cur_page     = null != $request->post('page') ? $request->post('page') : 1;
        $per_page     = $request->get('perPage') == -1 ? 0 : $request->get('perPage');
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
        $msg .= '<table class="table align-middle table-row-dashed fs-6 gy-5" id="transferItemTable">';
        $msg .= '<thead>';
            $msg .= '<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">';
            $msg .= '<th class="w-10px pe-2">';
            $msg .= '<div class="form-check form-check-sm form-check-custom form-check-solid me-3">';
            $msg .= '<input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_purchase_order_type_table .form-check-input" value="1" />';
            $msg .= '</div>';
            $msg .= '</th>';
            $msg .= '<th class="min-w-50px">Transfer No</th>';
            $msg .= '<th class="min-w-125px">From</th>';
            $msg .= '<th class="min-w-125px">To</th>';
            $msg .= '<th class="min-w-125px">Remarks</th>';
            $msg .= '<th class="text-center">Last Modified</th>';
            $msg .= '<th class="text-center min-w-70px">Actions</th>';
            $msg .= '</tr>';
        $msg .= '</thead>';
        $msg .= '<tbody class="fw-bold text-gray-600">';
        
        $query = $this->get_line_items($per_page, $start_from, $keywords, 1);
        $count = $this->get_page_count($keywords, 1);
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
                if ($row->status == 'open') {
                    $status = '<span class="badge badge-status badge-light-dark">'.$row->status.'</span>';
                } else if ($row->status == 'prepared') {
                    $status = '<span class="badge badge-status badge-light-warning">'.$row->status.'</span>';
                } else if ($row->status == 'partial'){
                    $status = '<span class="badge badge-status badge-light-primary">'.$row->status.'</span>';
                } else {
                    $status = '<span class="badge badge-status badge-light-success">'.$row->status.'</span>';
                } 
                $msg .= '<tr data-row-amount="'.$row->total_amount.'" data-row-id="'.$row->transId.'" data-row-trans="'.$row->transNo.'">';
                $msg .= '<td>';
                $msg .= '<div class="form-check form-check-sm form-check-custom form-check-solid">';
                $msg .= '<input class="form-check-input" type="checkbox" value="'.$row->transId.'" />';
                $msg .= '</div>';
                $msg .= '</td>';
                $msg .= '<td>';
                $msg .= '<a href="#" class="text-gray-800 text-hover-primary mb-1">'.$row->transNo.'</a>';
                $msg .= '</td>';
                $msg .= '<td>'.$row->transFrom.'</td>';
                $msg .= '<td>'.$row->transTo.'</td>';
                $msg .= '<td>'.$row->remarks.'</td>';
                $msg .= '<td class="text-right">₱'.$row->total_amount.'</td>';
                $msg .= '<td class="text-center">'.$status.'</td>';
                $msg .= '<td class="text-center">'.$row->modified_at.'</td>';
                $msg .= '<td class="text-center">';
                $msg .= '<a href="javascript:;" title="modify this" class="edit-btn btn btn-sm btn-light btn-active-light-primary">';
                $msg .= '<!--begin::Svg Icon | path: assets/media/icons/duotone/General/Edit.svg-->
                <span class="svg-icon svg-icon-muted svg-icon-2hx"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                        <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                        <rect x="0" y="0" width="24" height="24"/>
                        <path d="M7.10343995,21.9419885 L6.71653855,8.03551821 C6.70507204,7.62337518 6.86375628,7.22468355 7.15529818,6.93314165 L10.2341093,3.85433055 C10.8198957,3.26854411 11.7696432,3.26854411 12.3554296,3.85433055 L15.4614112,6.9603121 C15.7369117,7.23581259 15.8944065,7.6076995 15.9005637,7.99726737 L16.1199293,21.8765672 C16.1330212,22.7048909 15.4721452,23.3869929 14.6438216,23.4000848 C14.6359205,23.4002097 14.6280187,23.4002721 14.6201167,23.4002721 L8.60285976,23.4002721 C7.79067946,23.4002721 7.12602744,22.7538546 7.10343995,21.9419885 Z" fill="#000000" fill-rule="nonzero" transform="translate(11.418039, 13.407631) rotate(-135.000000) translate(-11.418039, -13.407631) "/>
                    </g>
                </svg></span>
                <!--end::Svg Icon--></a>';
                $msg .= '</td>';
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

        $msg .= '<div class="row"><div class="col-sm-6 pl-5"><div class="dataTables_paginate paging_simple_numbers" id="kt_purchase_order_type_table_paginate"><ul class="pagination" style="margin-bottom: 0;">';

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

    public function get_line_items($limit, $start_from, $keywords = '')
    {
        if (!empty($keywords)) {
            $res = TransferItem::select([
                'transfer_items.id as id',
                'transfer_items.transfer_no',
                'bra1.name as transFrom',
                'bra2.name as transTo',
                'transfer_items.remarks as remarks',
                'transfer_items.total_amount as total_amount',
                'transfer_items.status as status',
                'transfer_items.created_at',
                'transfer_items.updated_at'
            ])
            ->leftJoin('branches as bra1', function($join)
            {
                $join->on('bra1.id', '=', 'transfer_items.transfer_from');
            })
            ->leftJoin('branches as bra2', function($join)
            {
                $join->on('bra2.id', '=', 'transfer_items.transfer_to');
            })
            ->where(function($q) use ($keywords) {
                $q->where('bra1.name', 'like', '%' . $keywords . '%')
                  ->orWhere('bra2.name', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items.transfer_no', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items.remarks', 'like', '%' . $keywords . '%');
            })
            ->skip($start_from)->take($limit)
            ->orderBy('transfer_items.id', 'desc')
            ->get();
        } else {
            $res = TransferItem::select([
                'transfer_items.id as id',
                'transfer_items.transfer_no',
                'bra1.name as transFrom',
                'bra2.name as transTo',
                'transfer_items.remarks as remarks',
                'transfer_items.total_amount as total_amount',
                'transfer_items.status as status',
                'transfer_items.created_at',
                'transfer_items.updated_at'
            ])
            ->leftJoin('branches as bra1', function($join)
            {
                $join->on('bra1.id', '=', 'transfer_items.transfer_from');
            })
            ->leftJoin('branches as bra2', function($join)
            {
                $join->on('bra2.id', '=', 'transfer_items.transfer_to');
            })
            ->skip($start_from)->take($limit)
            ->orderBy('transfer_items.id', 'desc')
            ->get();
        }

        return $res->map(function($trans) {
            return (object) [
                'transId' => $trans->id,
                'transNo' => $trans->transfer_no,
                'transFrom' => $trans->transFrom,
                'transTo' => $trans->transTo,
                'remarks' => $trans->remarks,
                'total_amount' => $trans->total_amount,
                'status' => $trans->status,
                'modified_at' => ($trans->updated_at !== NULL) ? date('d-M-Y', strtotime($trans->updated_at)).'<br/>'. date('h:i A', strtotime($trans->updated_at)) : date('d-M-Y', strtotime($trans->created_at)).'<br/>'. date('h:i A', strtotime($trans->created_at))
            ];
        });
    }

    public function get_page_count($keywords = '')
    {
        if (!empty($keywords)) {
            $res = TransferItem::select([
                'transfer_items.id as id',
                'transfer_items.transfer_no',
                'bra1.name as transFrom',
                'bra2.name as transTo',
                'transfer_items.remarks as remarks',
                'transfer_items.created_at',
                'transfer_items.updated_at'
            ])
            ->leftJoin('branches as bra1', function($join)
            {
                $join->on('bra1.id', '=', 'transfer_items.transfer_from');
            })
            ->leftJoin('branches as bra2', function($join)
            {
                $join->on('bra2.id', '=', 'transfer_items.transfer_to');
            })
            ->where(function($q) use ($keywords) {
                $q->where('bra1.name', 'like', '%' . $keywords . '%')
                  ->orWhere('bra2.name', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items.transfer_no', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items.remarks', 'like', '%' . $keywords . '%');
            })
            ->orderBy('transfer_items.id', 'desc')
            ->count();
        } else {
            $res = TransferItem::select([
                'transfer_items.id as id',
                'transfer_items.transfer_no',
                'bra1.name as transFrom',
                'bra2.name as transTo',
                'transfer_items.remarks as remarks',
                'transfer_items.created_at',
                'transfer_items.updated_at'
            ])
            ->leftJoin('branches as bra1', function($join)
            {
                $join->on('bra1.id', '=', 'transfer_items.transfer_from');
            })
            ->leftJoin('branches as bra2', function($join)
            {
                $join->on('bra2.id', '=', 'transfer_items.transfer_to');
            })
            ->orderBy('transfer_items.id', 'desc')
            ->count();
        }

        return $res;
    }

    public function all_inactive(Request $request)
    {   
        $keywords     = $request->get('keywords');  
        $cur_page     = null != $request->post('page') ? $request->post('page') : 1;
        $per_page     = $request->get('perPage') == -1 ? 0 : $request->get('perPage');
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
        $msg .= '<table class="table align-middle table-row-dashed fs-6 gy-5" id="transferItemTypeTable">';
        $msg .= '<thead>';
            $msg .= '<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">';
            $msg .= '<th class="w-10px pe-2">';
            $msg .= '<div class="form-check form-check-sm form-check-custom form-check-solid me-3">';
            $msg .= '<input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_purchase_order_type_table .form-check-input" value="1" />';
            $msg .= '</div>';
            $msg .= '</th>';
            $msg .= '<th class="min-w-50px">Code</th>';
            $msg .= '<th class="min-w-125px">Name</th>';
            $msg .= '<th class="min-w-125px">Description</th>';
            $msg .= '<th class="text-center">Last Modified</th>';
            $msg .= '<th class="text-center min-w-70px">Actions</th>';
            $msg .= '</tr>';
        $msg .= '</thead>';
        $msg .= '<tbody class="fw-bold text-gray-600">';
        
        $query = $this->get_line_items($per_page, $start_from, $keywords, 0);
        $count = $this->get_page_count($keywords, 0);
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
                $msg .= '<tr data-row-id="'.$row->id.'" data-row-code="'.$row->code.'">';
                $msg .= '<td>';
                $msg .= '<div class="form-check form-check-sm form-check-custom form-check-solid">';
                $msg .= '<input class="form-check-input" type="checkbox" value="'.$row->id.'" />';
                $msg .= '</div>';
                $msg .= '</td>';
                $msg .= '<td>';
                $msg .= '<a href="#" class="text-gray-800 text-hover-primary mb-1">'.$row->code.'</a>';
                $msg .= '</td>';
                $msg .= '<td>'.$row->name.'</td>';
                $msg .= '<td>'.$row->description.'</td>';
                $msg .= '<td class="text-center">'.$row->modified_at.'</td>';
                $msg .= '<td class="text-center">';
                $msg .= '<a href="javascript:;" title="modify this" class="restore-btn btn btn-sm btn-light btn-active-light-info">';
                $msg .= '<!--begin::Svg Icon | path: assets/media/icons/duotone/Text/Undo.svg-->
                <span class="svg-icon svg-icon-muted svg-icon-2hx"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                        <rect x="0" y="0" width="24" height="24"/>
                        <path d="M21.4451171,17.7910156 C21.4451171,16.9707031 21.6208984,13.7333984 19.0671874,11.1650391 C17.3484374,9.43652344 14.7761718,9.13671875 11.6999999,9 L11.6999999,4.69307548 C11.6999999,4.27886191 11.3642135,3.94307548 10.9499999,3.94307548 C10.7636897,3.94307548 10.584049,4.01242035 10.4460626,4.13760526 L3.30599678,10.6152626 C2.99921905,10.8935795 2.976147,11.3678924 3.2544639,11.6746702 C3.26907199,11.6907721 3.28437331,11.7062312 3.30032452,11.7210037 L10.4403903,18.333467 C10.7442966,18.6149166 11.2188212,18.596712 11.5002708,18.2928057 C11.628669,18.1541628 11.6999999,17.9721616 11.6999999,17.7831961 L11.6999999,13.5 C13.6531249,13.5537109 15.0443703,13.6779456 16.3083984,14.0800781 C18.1284272,14.6590944 19.5349747,16.3018455 20.5280411,19.0083314 L20.5280247,19.0083374 C20.6363903,19.3036749 20.9175496,19.5 21.2321404,19.5 L21.4499999,19.5 C21.4499999,19.0068359 21.4451171,18.2255859 21.4451171,17.7910156 Z" fill="#000000" fill-rule="nonzero"/>
                    </g>
                </svg></span>
                <!--end::Svg Icon--></a>';
                $msg .= '</td>';
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

        $msg .= '<div class="row"><div class="col-sm-6 pl-5"><div class="dataTables_paginate paging_simple_numbers" id="kt_purchase_order_type_table_paginate"><ul class="pagination" style="margin-bottom: 0;">';

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

    public function all_active_lines(Request $request)
    {   
        $keywords     = $request->get('keywords'); 
        $transferItemID   = $request->get('id');   
        $cur_page     = null != $request->post('page') ? $request->post('page') : 1;
        $per_page     = $request->get('perPage') == -1 ? 0 : $request->get('perPage');
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
        $msg .= '<table class="table align-middle table-row-dashed fs-8 mt-5" id="transferItemLineTable">';
        $msg .= '<thead>';
            $msg .= '<tr class="text-start text-gray-400 fw-bolder fs-8 text-uppercase gs-0">';
            $msg .= '<th class="w-10px pe-2">';
            $msg .= '<div class="form-check form-check-sm form-check-custom form-check-solid me-3">';
            $msg .= '<input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_customers_table .form-check-input" value="1" />';
            $msg .= '</div>';
            $msg .= '</th>';
            $msg .= '<th class="min-w-100px text-left">Item Description</th>';
            $msg .= '<th class="min-w-50px text-center">Qty</th>';
            $msg .= '<th class="min-w-50px text-center">UOM</th>';
            $msg .= '<th class="min-w-50px text-center">SRP</th>';
            $msg .= '<th class="min-w-50px text-center">Total</th>';
            $msg .= '<th class="min-w-50px text-center">Posted</th>';
            $msg .= '<th class="text-center min-w-70px">Actions</th>';
            $msg .= '</tr>';
        $msg .= '</thead>';
        $msg .= '<tbody class="fw-bold text-gray-600">';
        
        $query = $this->get_transfer_item_line_items($per_page, $start_from, $keywords, $transferItemID);
        $count = $this->get_transfer_item_line_page_count($keywords, $transferItemID);
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
                $item = $row->itemCode.' - '.$row->itemName;
                $bg = ($row->posted_quantity > 0) ? (floatval($row->quantity) === floatval($row->posted_quantity)) ? 'bg-light-success' : 'bg-light-warning' : '';
                $disabled = (floatval($row->quantity) === floatval($row->posted_quantity)) ? 'disabled="disabled"' : '';
                $msg .= '<tr class="'.$bg.'" data-row-id="'.$row->id.'" data-row-item="'.$item.'" data-row-qty="'.$row->quantity.'" data-row-posted="'.$row->posted_quantity.'">';
                $msg .= '<td>';
                $msg .= '<div class="form-check form-check-sm form-check-custom form-check-solid">';
                $msg .= '<input class="form-check-input" type="checkbox" value="'.$row->id.'" '.$disabled.'/>';
                $msg .= '</div>';
                $msg .= '</td>';
                $msg .= '<td class="min-w-50px text-left">'.$item.'</td>';
                $msg .= '<td class="min-w-50px text-center">'.$row->quantity.'</td>';
                $msg .= '<td class="min-w-50px text-center">'.$row->uom.'</td>';
                $msg .= '<td class="min-w-50px text-center">'.$row->srp.'</td>';
                $msg .= '<td class="min-w-50px text-center">'.$row->total_amount.'</td>';
                $msg .= '<td class="min-w-50px text-center">'.(($row->posted_quantity > 0) ? $row->posted_quantity : '' ).'</td>';
                $msg .= '<td class="text-center">';
                $msg .= '<a href="javascript:;" title="retract this" class="remove-btn btn btn-sm btn-light btn-active-light-danger">';
                $msg .= '<!--begin::Svg Icon | path: assets/media/icons/duotone/Design/Eraser.svg-->
                <span class="svg-icon svg-icon-muted svg-icon-2hx"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                        <rect x="0" y="0" width="24" height="24"/>
                        <path d="M8.43296491,7.17429118 L9.40782327,7.85689436 C9.49616631,7.91875282 9.56214077,8.00751728 9.5959027,8.10994332 C9.68235021,8.37220548 9.53982427,8.65489052 9.27756211,8.74133803 L5.89079566,9.85769242 C5.84469033,9.87288977 5.79661753,9.8812917 5.74809064,9.88263369 C5.4720538,9.8902674 5.24209339,9.67268366 5.23445968,9.39664682 L5.13610134,5.83998177 C5.13313425,5.73269078 5.16477113,5.62729274 5.22633424,5.53937151 C5.384723,5.31316892 5.69649589,5.25819495 5.92269848,5.4165837 L6.72910242,5.98123382 C8.16546398,4.72182424 10.0239806,4 12,4 C16.418278,4 20,7.581722 20,12 C20,16.418278 16.418278,20 12,20 C7.581722,20 4,16.418278 4,12 L6,12 C6,15.3137085 8.6862915,18 12,18 C15.3137085,18 18,15.3137085 18,12 C18,8.6862915 15.3137085,6 12,6 C10.6885336,6 9.44767246,6.42282109 8.43296491,7.17429118 Z" fill="#000000" fill-rule="nonzero"/>
                    </g>
                </svg></span>
                <!--end::Svg Icon--></a>';
                $msg .= '<a href="javascript:;" title="post this" class="post-btn btn btn-sm btn-light btn-active-light-success">';
                $msg .= '<!--begin::Svg Icon | path: assets/media/icons/duotone/Design/Edit.svg-->
                <span class="svg-icon svg-icon-muted svg-icon-2hx"><svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                        <path d="M12.2674799,18.2323597 L12.0084872,5.45852451 C12.0004303,5.06114792 12.1504154,4.6768183 12.4255037,4.38993949 L15.0030167,1.70195304 L17.5910752,4.40093695 C17.8599071,4.6812911 18.0095067,5.05499603 18.0083938,5.44341307 L17.9718262,18.2062508 C17.9694575,19.0329966 17.2985816,19.701953 16.4718324,19.701953 L13.7671717,19.701953 C12.9505952,19.701953 12.2840328,19.0487684 12.2674799,18.2323597 Z" fill="#000000" fill-rule="nonzero" transform="translate(14.701953, 10.701953) rotate(-135.000000) translate(-14.701953, -10.701953) "/>
                        <path d="M12.9,2 C13.4522847,2 13.9,2.44771525 13.9,3 C13.9,3.55228475 13.4522847,4 12.9,4 L6,4 C4.8954305,4 4,4.8954305 4,6 L4,18 C4,19.1045695 4.8954305,20 6,20 L18,20 C19.1045695,20 20,19.1045695 20,18 L20,13 C20,12.4477153 20.4477153,12 21,12 C21.5522847,12 22,12.4477153 22,13 L22,18 C22,20.209139 20.209139,22 18,22 L6,22 C3.790861,22 2,20.209139 2,18 L2,6 C2,3.790861 3.790861,2 6,2 L12.9,2 Z" fill="#000000" fill-rule="nonzero" opacity="0.3"/>
                </svg></span>
                <!--end::Svg Icon--></a>';
                $msg .= '</td>';
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

        $msg .= '<div class="row"><div class="col-sm-6 pl-5"><div class="dataTables_paginate paging_simple_numbers" id="kt_customers_table_paginate"><ul class="pagination" style="margin-bottom: 0;">';

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

    public function get_transfer_item_line_items($limit, $start_from, $keywords = '', $transferItemID)
    {
        if (!empty($keywords)) {
            $res = TransferItemLine::select([
                'transfer_items_lines.id as lineID',
                'items.name as itemName',
                'items.code as itemCode',
                'transfer_items_lines.quantity as quantity',
                'unit_of_measurements.code as uom',
                'transfer_items_lines.srp as srp',
                'transfer_items_lines.total_amount as total_amount',
                'transfer_items_lines.posted_quantity as posted_quantity',
            ])
            ->leftJoin('items', function($join)
            {
                $join->on('items.id', '=', 'transfer_items_lines.item_id');
            })
            ->leftJoin('unit_of_measurements', function($join)
            {
                $join->on('unit_of_measurements.id', '=', 'transfer_items_lines.uom_id');
            })
            ->where([
                'transfer_items_lines.transfer_item_id' => $transferItemID, 
                'transfer_items_lines.is_active' => 1
            ])
            ->where(function($q) use ($keywords) {
                $q->where('transfer_items_lines.srp', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.uom', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.quantity', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.total_amount', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.posted_quantity', 'like', '%' . $keywords . '%')
                  ->orWhere('unit_of_measurements.code', 'like', '%' . $keywords . '%')
                  ->orWhere('items.code', 'like', '%' . $keywords . '%')
                  ->orWhere('items.name', 'like', '%' . $keywords . '%')
                  ->orWhere('items.description', 'like', '%' . $keywords . '%');
            })
            ->skip($start_from)->take($limit)
            ->orderBy('transfer_items_lines.id', 'asc')
            ->get();
        } else {
            $res = TransferItemLine::select([
                'transfer_items_lines.id as lineID',
                'items.name as itemName',
                'items.code as itemCode',
                'transfer_items_lines.quantity as quantity',
                'unit_of_measurements.code as uom',
                'transfer_items_lines.srp as srp',
                'transfer_items_lines.total_amount as total_amount',
                'transfer_items_lines.posted_quantity as posted_quantity',
            ])
            ->leftJoin('items', function($join)
            {
                $join->on('items.id', '=', 'transfer_items_lines.item_id');
            })
            ->leftJoin('unit_of_measurements', function($join)
            {
                $join->on('unit_of_measurements.id', '=', 'transfer_items_lines.uom_id');
            })
            ->where([
                'transfer_items_lines.transfer_item_id' => $transferItemID, 
                'transfer_items_lines.is_active' => 1
            ])
            ->skip($start_from)->take($limit)
            ->orderBy('transfer_items_lines.id', 'asc')
            ->get();
        }

        return $res->map(function($trans) {
            return (object) [
                'id' => $trans->lineID,
                'itemName' => $trans->itemName,
                'itemCode' => $trans->itemCode,
                'quantity' => $trans->quantity,
                'srp' => $trans->srp,
                'uom' => $trans->uom,
                'total_amount' => $trans->total_amount,
                'posted_quantity' => $trans->posted_quantity,
                'modified_at' => ($trans->updated_at !== NULL) ? date('d-M-Y', strtotime($trans->updated_at)).'<br/>'. date('h:i A', strtotime($trans->updated_at)) : date('d-M-Y', strtotime($trans->created_at)).'<br/>'. date('h:i A', strtotime($trans->created_at))
            ];
        });
    }

    public function get_transfer_item_line_page_count($keywords = '', $transferItemID)
    {
        if (!empty($keywords)) {
            $res = TransferItemLine::select([
                'transfer_items_lines.id as lineID',
                'items.name as itemName',
                'items.code as itemCode',
                'transfer_items_lines.quantity as quantity',
                'unit_of_measurements.code as uom',
                'transfer_items_lines.srp as srp',
                'transfer_items_lines.total_amount as total_amount',
                'transfer_items_lines.posted_quantity as posted_quantity',
            ])
            ->leftJoin('items', function($join)
            {
                $join->on('items.id', '=', 'transfer_items_lines.item_id');
            })
            ->leftJoin('unit_of_measurements', function($join)
            {
                $join->on('unit_of_measurements.id', '=', 'transfer_items_lines.uom_id');
            })
            ->where([
                'transfer_items_lines.transfer_item_id' => $transferItemID, 
                'transfer_items_lines.is_active' => 1
            ])
            ->where(function($q) use ($keywords) {
                $q->where('transfer_items_lines.srp', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.uom', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.quantity', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.total_amount', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.posted_quantity', 'like', '%' . $keywords . '%')
                  ->orWhere('unit_of_measurements.code', 'like', '%' . $keywords . '%')
                  ->orWhere('items.name', 'like', '%' . $keywords . '%')
                  ->orWhere('items.code', 'like', '%' . $keywords . '%')
                  ->orWhere('items.description', 'like', '%' . $keywords . '%');
            })
            ->orderBy('transfer_items_lines.id', 'asc')
            ->count();
        } else {
            $res = TransferItemLine::select([
                'transfer_items_lines.id as lineID',
                'items.name as itemName',
                'items.code as itemCode',
                'transfer_items_lines.quantity as quantity',
                'unit_of_measurements.code as uom',
                'transfer_items_lines.srp as srp',
                'transfer_items_lines.total_amount as total_amount',
                'transfer_items_lines.posted_quantity as posted_quantity',
            ])
            ->leftJoin('items', function($join)
            {
                $join->on('items.id', '=', 'transfer_items_lines.item_id');
            })
            ->leftJoin('unit_of_measurements', function($join)
            {
                $join->on('unit_of_measurements.id', '=', 'transfer_items_lines.uom_id');
            })
            ->where([
                'transfer_items_lines.transfer_item_id' => $transferItemID, 
                'transfer_items_lines.is_active' => 1
            ])
            ->orderBy('transfer_items_lines.id', 'asc')
            ->count();
        }

        return $res;
    }


    public function remove(Request $request, $id)
    {   
        // $this->is_permitted(3);
        $timestamp = date('Y-m-d H:i:s');
        $trans = TransferItem::where([
            'id' => $id,
        ])
        ->update([
            'updated_at' => $timestamp,
            'updated_by' => Auth::user()->id,
            'is_active' => 0
        ]);
        $this->audit_logs('purchase_orders_types', $id, 'has removed a purchase order type.', TransferItem::find($id), $timestamp, Auth::user()->id);
        
        $data = array(
            'title' => 'Well done!',
            'text' => 'The purchase order type has been successfully removed.',
            'type' => 'success',
            'class' => 'btn-brand'
        );

        echo json_encode( $data ); exit();
    }

    public function restore(Request $request, $id)
    {   
        // $this->is_permitted(3);
        $timestamp = date('Y-m-d H:i:s');
        $trans = TransferItem::where([
            'id' => $id,
        ])
        ->update([
            'updated_at' => $timestamp,
            'updated_by' => Auth::user()->id,
            'is_active' => 1
        ]);
        $this->audit_logs('purchase_orders_types', $id, 'has restored a purchase order type.', TransferItem::find($id), $timestamp, Auth::user()->id);
        
        $data = array(
            'title' => 'Well done!',
            'text' => 'The purchase order type has been successfully restored.',
            'type' => 'success',
            'class' => 'btn-brand'
        );

        echo json_encode( $data ); exit();
    }

    public function generate_trans_no()
    {
        $count = TransferItem::count();
        if ($count < 9) {
            return 'TR-0000'.($count + 1);
        } else if ($count < 99) {
            return 'TR-000'.($count + 1);
        } else if ($count < 999) {
            return 'TR-00'.($count + 1);
        } else if ($count < 9999) {
            return 'TR-0'.($count + 1);
        } else if ($count < 99999) {
            return 'TR-'.($count + 1);
        }
    }

    public function get_item_info(Request $request, $itemID, $branchID)
    {
        $branch = (new Branch)->where('id', $branchID)->first();
        $item = (new Item)->with([
            'uom' =>  function($q) { 
                $q->select(['id', 'code']);
            }
        ])
        ->where('id', $itemID)->first();
        $inventory = (new ItemInventory)->where([
            'branch_id' => $branchID,
            'item_id' => $itemID
        ])->first();
        
        $srp = ($branch->is_srp > 0) ? $item->srp2 : $item->srp;
        $data = array(
            'is_srp' => $branch->is_srp,
            'srp' => $srp,
            'uom_id' => $item->uom->id,
            'based_quantity' => $inventory->quantity
        );

        echo json_encode( $data ); exit();
    }

    public function export(Request $request)
    {   
        $fileName = 'purchase_order_type_'.time().'.csv';

        $purchase_order_type = TransferItem::select([
            'transfer_items.id', 
            'transfer_items.code', 
            'transfer_items.name', 
            'transfer_items.description'
        ])
        ->where('transfer_items.is_active', 1)
        ->orderBy('transfer_items.id', 'asc')
        ->get();

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('Code', 'Name', 'Description');

        $callback = function() use($purchase_order_type, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($purchase_order_type as $trans) {
                $row['code']      = $trans->code;
                $row['name']      = $trans->name;
                $row['desc']      = $trans->description;
                fputcsv($file, array($row['code'], $row['name'], $row['desc']));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {   
        // $this->is_permitted(0);
        foreach($_FILES as $file)
        {   
            $row = 0; $timestamp = date('Y-m-d H:i:s');
            if (($files = fopen($file['tmp_name'], "r")) !== FALSE) 
            {
                while (($data = fgetcsv($files, 3000, ",")) !== FALSE) 
                {
                    $row++; 
                    if ($row > 1) 
                    {  
                        $exist = TransferItem::where('code', $data[0])->get();
                        if ($exist->count() > 0) {
                            $trans = TransferItem::find($exist->first()->id);
                            $trans->code = $data[0];
                            $trans->name = $data[1];
                            $trans->description = $data[2];
                            $trans->updated_at = $timestamp;
                            $trans->updated_by = Auth::user()->id;

                            if ($trans->update()) {
                                $this->audit_logs('purchase_orders_types', $exist->first()->id, 'has modified a purchase order type.', TransferItem::find($exist->first()->id), $timestamp, Auth::user()->id);
                            }
                        } else {
                            $res = TransferItem::count();
                            $trans = TransferItem::create([
                                'code' => $data[0],
                                'name' => $data[1],
                                'description' => $data[2],
                                'created_at' => $timestamp,
                                'created_by' => Auth::user()->id
                            ]);
                    
                            if (!$trans) {
                                throw new NotFoundHttpException();
                            }
                        
                            $this->audit_logs('purchase_orders_types', $trans->id, 'has inserted a new purchase order type.', TransferItem::find($trans->id), $timestamp, Auth::user()->id);
                        }
                    } // close for if $row > 1 condition   
                }
                fclose($files);
            }
        }

        $data = array(
            'message' => 'success'
        );

        echo json_encode( $data );

        exit();
    }

    public function store_line_item(Request $request, $transferItemID)
    {   
        // $this->is_permitted(0);
        $timestamp = date('Y-m-d H:i:s');

        $transferItemLine = TransferItemLine::create([
            'transfer_item_id' => $transferItemID,
            'item_id' => $request->item_id,
            'uom_id' => $request->uom_id,
            'quantity' => $request->qty,
            'srp' => $request->get('srp'),
            'total_amount' => $request->get('total_amount'),
            'created_at' => $timestamp,
            'created_by' => Auth::user()->id
        ]);

        if (!$transferItemLine) {
            throw new NotFoundHttpException();
        }
        $this->audit_logs('transfer_items_lines', $transferItemLine->id, 'has inserted a new transfer item line.', TransferItemLine::find($transferItemLine->id), $timestamp, Auth::user()->id);
        
        $transferItem = TransferItem::find($transferItemID);
        $inventory = (new ItemInventory)->where([
            'branch_id' => $transferItem->transfer_from,
            'item_id' => $request->item_id
        ])->first();
        $inventoryQty = $inventory->quantity;
        $holdQuantity = floatval($inventory->hold) + floatval($request->qty);
        $qtyLeft = floatval($inventoryQty) - floatval($request->qty);
        $updateInventory = (new ItemInventory)->where('id', $inventory->id)
        ->update([
            'quantity' => $qtyLeft,
            'hold' => $holdQuantity,
            'updated_at' => $timestamp,
            'updated_by' => Auth::user()->id
        ]);

        $transaction = ItemTransaction::create([
            'item_id' => $transferItemLine->item_id,
            'branch_id' => $transferItem->transfer_from,
            'transaction' => 'Transfer Item',
            'based_quantity' => $inventoryQty,
            'issued_quantity' => $request->qty,
            'left_quantity' => $qtyLeft,
            'srp' => $request->get('srp'),
            'total_amount' => $request->get('total_amount'),
            'issued_by' => Auth::user()->id,
            'received_by' => Auth::user()->id,
            'remarks' => 'Item transfer from Transfer Item ('.$transferItem->transfer_no.')',
            'created_at' => $timestamp,
            'created_by' => Auth::user()->id
        ]);

        if (!$transaction) {
            throw new NotFoundHttpException();
        }

        $this->audit_logs('items_transactions', $transaction->id, 'has inserted a new item transaction.', ItemTransaction::find($transaction->id), $timestamp, Auth::user()->id);

        $result = TransferItemLine::where(['transfer_item_id' => $transferItemID, 'is_active' => 1])->get();
        $status = 'open'; $totalAmount = 0;
        if ($result->count() > 0) {
            $prepCounter = 0; $postCounter = 0;
            foreach ($result as $res) {
                $prepCounter++;
                $totalAmount += floatval($res->total_amount);
                if ($res->status == 'posted') {
                    $postCounter++;
                }
            }

            if ($prepCounter == $postCounter) {
                $status = 'completed';
            } else {
                $status = 'partial';
            }
        } 
        
        $transferItem = TransferItem::where([
            'id' => $transferItemID,
        ])
        ->update([
            'status' => $status,
            'total_amount' => $totalAmount,
            'updated_at' => $timestamp,
            'updated_by' => Auth::user()->id
        ]);

        $data = array(
            'total_amount' => number_format(floor(($totalAmount*100))/100,2),
            'title' => 'Well done!',
            'text' => 'The item has been added.',
            'type' => 'success',
            'class' => 'btn-brand'
        );

        echo json_encode( $data ); exit();
    }

    public function find_line(Request $request, $id)
    {    
        $res = TransferItemLine::where(['id' => $id])->first();
        $transLines = (object) array(
            'transfer_item_line_id' => $id,
            'item_id' => $res->item_id,
            'uom_id' => $res->uom_id,
            'qty' => $res->quantity,
            'srp' => $res->srp,
            'total_amount' => $res->total_amount
        );

        return response()
        ->json([
            'status' => 'ok',
            'data' => $transLines
        ]);
    }

    public function find_line_item(Request $request, $id)
    {    
        $res = TransferItemLine::with([
            'item' =>  function($q) { 
                $q->select(['id', 'code', 'name']);
            },
            'transfer_item' =>  function($q) { 
                $q->select(['id', 'transfer_to']);
            }
        ])
        ->where(['id' => $id])
        ->first();

        $transferItem = (object) array(
            'transfer_item_line_id' => $id,
            'transfer_item_idx' => $res->transfer_item_id,
            'item_name' => $res->item->code.' - '.$res->item->name,
            'available_qty' => (new ItemInventory)->find_item_inventory($res->transfer_item->transfer_to, $res->item->id),
            'for_posting' => (floatval($res->quantity) - floatval($res->posted_quantity))
        );

        return response()
        ->json([
            'status' => 'ok',
            'data' => $transferItem
        ]);
    }
    
    public function post_line_item(Request $request, $id)
    {   
        $timestamp = date('Y-m-d H:i:s');
        $transferItemLine = TransferItemLine::find($id);

        if(!$transferItemLine) {
            throw new NotFoundHttpException();
        }

        $posting = TransferItemLinePosting::create([
            'transfer_item_line_id' => $id,
            'quantity' => $request->qty_to_post,
            'date_received' => date('Y-m-d', strtotime($request->date_received)),
            'created_at' => $timestamp,
            'created_by' => Auth::user()->id
        ]);

        if (!$posting) {
            throw new NotFoundHttpException();
        }
        
        $transferItem = TransferItem::where('id', $transferItemLine->transfer_item_id)->first();
        $inventory = ItemInventory::where(['item_id' => $transferItemLine->item_id, 'branch_id' => $transferItem->transfer_to, 'is_active' => 1])->get();
        $inventory2 = ItemInventory::where(['item_id' => $transferItemLine->item_id, 'branch_id' => $transferItem->transfer_from, 'is_active' => 1])->get();
        if ($inventory->count() > 0) {
            $inventory = $inventory->first();
            $inventory2 = $inventory2->first();
            $inventoryQty = $inventory->quantity;
            $quantity = floatval($request->qty_to_post); 
            $qtyLeft   = floatval($inventoryQty) + floatval($quantity);
            ItemInventory::where('id', $inventory->id)->update(['quantity' => $qtyLeft]);
            $holdLeft  = floatval($inventory2->hold) - floatval($quantity);
            ItemInventory::where('id', $inventory2->id)->update(['hold' => $qtyLeft]);

            $transaction = ItemTransaction::create([
                'item_id' => $transferItemLine->item_id,
                'branch_id' => $transferItem->transfer_to,
                'transaction' => 'Receiving',
                'based_quantity' => $inventoryQty,
                'issued_quantity' => $quantity,
                'left_quantity' => $qtyLeft,
                'srp' => $transferItemLine->srp,
                'total_amount' => $transferItemLine->total_amount,
                'issued_by' => Auth::user()->id,
                'received_by' => Auth::user()->id,
                'remarks' => 'Item receiving from Transfer Item ('.$transferItem->transfer_no.')',
                'created_at' => $timestamp,
                'created_by' => Auth::user()->id
            ]);
    
            if (!$transaction) {
                throw new NotFoundHttpException();
            }
    
            $this->audit_logs('items_transactions', $transaction->id, 'has inserted a new item transaction.', ItemTransaction::find($transaction->id), $timestamp, Auth::user()->id);

            $postedQuantity = floatval($transferItemLine->posted_quantity) + floatval($quantity);
            $transferItemLine->posted_quantity = $postedQuantity;
            if (floatval($postedQuantity) == floatval($transferItemLine->quantity)) {
                $transferItemLine->status = 'posted';
            }
            $transferItemLine->updated_at = $timestamp;
            $transferItemLine->updated_by = Auth::user()->id;

            if ($transferItemLine->update()) {
                $this->audit_logs('transfer_items_lines', $id, 'has posted a quantity('.$quantity.') on transfer item line.', TransferItemLine::find($id), $timestamp, Auth::user()->id);
            }

            $result = TransferItemLine::where(['transfer_item_id' => $transferItem->id, 'is_active' => 1])->get();
            $status = 'open'; $totalAmount = 0;
            if ($result->count() > 0) {
                $prepCounter = 0; $postCounter = 0;
                foreach ($result as $res) {
                    $prepCounter++;
                    $totalAmount += floatval($res->total_amount);
                    if ($res->status == 'posted') {
                        $postCounter++;
                    }
                }

                if ($prepCounter == $postCounter) {
                    $status = 'completed';
                } else {
                    $status = 'partial';
                }
            } 
            
            $transferItem = TransferItem::where([
                'id' => $transferItem->id,
            ])
            ->update([
                'status' => $status,
                'total_amount' => $totalAmount,
                'updated_at' => $timestamp,
                'updated_by' => Auth::user()->id
            ]);
        }

        $data = array(
            'title' => 'Well done!',
            'text' => 'The quantity has been posted.',
            'type' => 'success',
            'class' => 'btn-brand'
        );

        echo json_encode( $data ); exit();
    }

    public function remove_line_item(Request $request, $id)
    {   
        // $this->is_permitted(3);
        $timestamp = date('Y-m-d H:i:s');

        $transferItemLine = TransferItemLine::find($id);
        $transferItem = TransferItem::find($transferItemLine->transfer_item_id);

        if ($transferItemLine->posted_quantity > 0) {
            $quantity = floatval($transferItemLine->quantity) - floatval($transferItemLine->posted_quantity);
            $inventory = ItemInventory::where(['item_id' => $transferItemLine->item_id, 'branch_id' => $transferItem->transfer_from, 'is_active' => 1])->get();
            if ($inventory->count() > 0) {
                $inventory    = $inventory->first();
                $inventoryQty = $inventory->quantity;
                $qtyLeft      = floatval($inventoryQty) + floatval($quantity);
                $holdLeft     = floatval($inventory->hold) - floatval($quantity);
                $res = ItemInventory::where('id', $inventory->id)
                ->update([
                    'quantity' => $qtyLeft,
                    'hold' => $holdLeft,
                    'updated_at' => $timestamp,
                    'updated_by' => Auth::user()->id,
                ]);
            }

            $transferItemLine->quantity = $quantity;
            $transferItemLine->total_amount = floatval(floatval($transferItemLine->srp) * floatval($quantity));
            $transferItemLine->updated_at = $timestamp;
            $transferItemLine->updated_by = Auth::user()->id;

            $transaction = ItemTransaction::create([
                'item_id' => $transferItemLine->item_id,
                'branch_id' => $transferItem->transfer_from,
                'transaction' => 'Receiving',
                'based_quantity' => $inventoryQty,
                'issued_quantity' => $quantity,
                'left_quantity' => $qtyLeft,
                'srp' => $transferItemLine->srp,
                'total_amount' => floatval(floatval($transferItemLine->srp) * floatval($quantity)),
                'issued_by' => Auth::user()->id,
                'received_by' => Auth::user()->id,
                'remarks' => 'Item retracted from Transfer Item ('.$transferItem->transfer_no.')',
                'created_at' => $timestamp,
                'created_by' => Auth::user()->id
            ]);
    
            if (!$transaction) {
                throw new NotFoundHttpException();
            }
    
            $this->audit_logs('items_transactions', $transaction->id, 'has inserted a new item transaction.', ItemTransaction::find($transaction->id), $timestamp, Auth::user()->id);

            if ($transferItemLine->update()) {
                $this->audit_logs('transfer_items_lines', $id, 'has retracted a quantity('.$quantity.') on transfer item line.', TransferItemLine::find($id), $timestamp, Auth::user()->id);
            }
        } else {       
            $transLine = TransferItemLine::where([
                'id' => $id,
            ])
            ->update([
                'updated_at' => $timestamp,
                'updated_by' => Auth::user()->id,
                'is_active' => 0
            ]);
            $this->audit_logs('transfer_items_lines', $id, 'has removed a transfer item line.', TransferItemLine::find($id), $timestamp, Auth::user()->id);
        
            $inventory = ItemInventory::where(['item_id' => $transferItemLine->item_id, 'branch_id' => $transferItem->transfer_from, 'is_active' => 1])->get();
            if ($inventory->count() > 0) {
                $inventory = $inventory->first();
                $inventoryQty = $inventory->quantity;
                $qtyLeft   = floatval($inventory->quantity) + floatval($transferItemLine->quantity);
                $holdLeft  = floatval($inventory->hold) - floatval($transferItemLine->quantity);
                $res = ItemInventory::where('id', $inventory->id)
                ->update([
                    'quantity' => $qtyLeft,
                    'hold' => $holdLeft,
                    'updated_at' => $timestamp,
                    'updated_by' => Auth::user()->id,
                ]);

                $quantity = $transferItemLine->quantity;
                $transaction = ItemTransaction::create([
                    'item_id' => $transferItemLine->item_id,
                    'branch_id' => $transferItem->transfer_from,
                    'transaction' => 'Receiving',
                    'based_quantity' => $inventoryQty,
                    'issued_quantity' => $quantity,
                    'left_quantity' => $qtyLeft,
                    'srp' => $transferItemLine->srp,
                    'total_amount' => floatval(floatval($transferItemLine->srp) * floatval($quantity)),
                    'issued_by' => Auth::user()->id,
                    'received_by' => Auth::user()->id,
                    'remarks' => 'Item retracted from Transfer Item ('.$transferItem->transfer_no.')',
                    'created_at' => $timestamp,
                    'created_by' => Auth::user()->id
                ]);
        
                if (!$transaction) {
                    throw new NotFoundHttpException();
                }
        
                $this->audit_logs('items_transactions', $transaction->id, 'has inserted a new item transaction.', ItemTransaction::find($transaction->id), $timestamp, Auth::user()->id);
            }
        }  

        $result = TransferItemLine::where([
            'transfer_item_id' => $transferItemLine->trasnfer_item_id, 
            'is_active' => 1
        ])->get();
        $status = 'open'; $totalAmount = 0;
        if ($result->count() > 0) {
            $prepCounter = 0; $postCounter = 0;
            foreach ($result as $res) {
                $prepCounter++;
                $totalAmount += floatval($res->total_amount);
                if ($res->status == 'posted') {
                    $postCounter++;
                }
            }

            if ($prepCounter == $postCounter) {
                $status = 'completed';
            } else {
                $status = 'partial';
            }
        } 
        
        $transferItem = TransferItem::where([
            'id' => $transferItemLine->transfer_item_id,
        ])
        ->update([
            'status' => $status,
            'total_amount' => $totalAmount,
            'updated_at' => $timestamp,
            'updated_by' => Auth::user()->id
        ]);

        $data = array(
            'total_amount' => number_format(floor(($totalAmount*100))/100,2),
            'title' => 'Well done!',
            'text' => 'The line item has been successfully retracted.',
            'type' => 'success',
            'class' => 'btn-brand'
        );

        echo json_encode( $data ); exit();
    }

    public function preview(Request $request)
    {   
        $trans = TransferItem::select([
            'transfer_items.id as transId',
            'transfer_items.transfer_no as transNo',
            'bra1.name as transFrom',
            'bra2.name as transTo',
            'transfer_items.remarks as remarks',
            'transfer_items.total_amount as total_amount',
            'transfer_items.status as status',
            'transfer_items.created_at as transDate',
            'transfer_items.created_by as transCreated',
            'bra2.dr_header as drHeader',
            'bra2.dr_address as drAddress'
        ])        
        ->leftJoin('branches as bra1', function($join)
        {
            $join->on('bra1.id', '=', 'transfer_items.transfer_from');
        })
        ->leftJoin('branches as bra2', function($join)
        {
            $join->on('bra2.id', '=', 'transfer_items.transfer_to');
        })
        ->where([
            'transfer_no' => $request->get('tr_no')
        ])->first();

        $lineItems = TransferItemLine::select([
            'transfer_items_lines.id as lineID',
            'items.name as itemName',
            'items.code as itemCode',
            'transfer_items_lines.quantity as quantity',
            'unit_of_measurements.code as uom',
            'transfer_items_lines.srp as srp',
            'transfer_items_lines.total_amount as total_amount',
            'transfer_items_lines.posted_quantity as posted_quantity',
        ])
        ->leftJoin('items', function($join)
        {
            $join->on('items.id', '=', 'transfer_items_lines.item_id');
        })
        ->leftJoin('unit_of_measurements', function($join)
        {
            $join->on('unit_of_measurements.id', '=', 'transfer_items_lines.uom_id');
        })
        ->where([
            'transfer_items_lines.transfer_item_id' => $trans->transId, 
            'transfer_items_lines.is_active' => 1
        ])
        ->get();

        PDF::SetMargins(10, 0, 10, false);
        PDF::SetAutoPageBreak(true, 0);
        PDF::SetTitle('Transfer Items ('.$request->get('tr_no').')');
        PDF::AddPage('P', 'LETTER');
        $tbl = '<div style="font-size:10pt">&nbsp;</div>';
        $tbl .= '<table id="heaer-table" width="100%" cellspacing="0" cellpadding="0" border="0" style="font-size: 9px;">
            <thead>
                <tr>
                    <td align="center"><p style="font-size: 22px">King Power Wholesaling Materials</p></td>
                </tr>
                <tr>
                    <td align="center"><p style="font-size: 9px"></p></td>
                </tr>
                <tr>
                    <td align="center" style="font-size: 11px">TRANSFER ITEMS</td>
                </tr>
            </thead>
            </table>';
        PDF::writeHTML($tbl, false, false, false, false, '');

        $tbl = '<div style="font-size:15pt">&nbsp;</div>';
        $tbl .= '<table>';
        $tbl .= '<tbody>';
        $tbl .= '<tr>';
        $tbl .= '<td width="65%">';
        $tbl .= '<table width="100%" cellspacing="0" cellpadding="1" border="0" style="font-size: 9px;">
        <thead>
            <tr>
                <td align="right" width="22%"><strong>FROM BRANCH:&nbsp;&nbsp;</strong></td>
                <td align="left" width="78%" style="border-bottom-width:0.1px;">'.$trans->transFrom.'</td>
            </tr>
            <tr>
                <td align="right" width="22%"><div style="font-size:5pt">&nbsp;</div><strong>TO BRANCH:&nbsp;&nbsp;</strong></td>
                <td align="left" width="78%" style="border-bottom-width:0.1px;"><div style="font-size:5pt">&nbsp;</div>'.$trans->transTo.'</td>
            </tr>
            <tr>
                <td align="right" width="22%"><div style="font-size:5pt">&nbsp;</div><strong>REMARKS:&nbsp;&nbsp;</strong></td>
                <td align="left" width="78%" style="height:39px;border-bottom-width:0.1px;"><div style="font-size:5pt">&nbsp;</div>'.$trans->remarks.'</td>
            </tr>
        </thead>
        </table>';
        $tbl .= '</td>';
        $tbl .= '<td width="35%">';
        $tbl .= '<table width="100%" cellspacing="0" cellpadding="1" border="0" style="font-size: 9px;">
        <thead>
            <tr>
                <td align="right" width="25%"><strong>TR#:&nbsp;&nbsp;</strong></td>
                <td align="left" width="75%" style="border-bottom-width:0.1px;">'.$trans->transNo.'</td>
            </tr>
            <tr>
                <td align="right" width="25%"><div style="font-size:5pt">&nbsp;</div><strong>DATE:&nbsp;&nbsp;</strong></td>
                <td align="left" width="75%" style="border-bottom-width:0.1px;"><div style="font-size:5pt">&nbsp;</div>'.date('d-M-Y', strtotime($trans->transDate)).'</td>
            </tr>
        </thead>
        </table>';
        $tbl .= '</td>';
        $tbl .= '</tr>';
        $tbl .= '</tbody>';
        $tbl .= '</table>';
        PDF::writeHTML($tbl, false, false, false, false, '');

        $tbl = '<div style="font-size:15pt">&nbsp;</div><table width="100%" cellspacing="0" cellpadding="2" border="0" style="border-bottom-width:0.1px;font-size: 9px;">
        <thead>
            <tr>
                <td rowspan="1" align="center" width="12%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;"><strong>QTY</strong></td>
                <td rowspan="1" align="center" width="7%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;"><strong>UOM</strong></td>
                <td rowspan="1" align="center" width="45%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;"><strong>ITEM DESCRIPTION</strong></td>
                <td rowspan="1" align="center" width="15%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;"><strong>PRICE</strong></td>
                <td rowspan="1" align="center" width="21%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;"><strong>AMOUNT</strong></td>
            </tr>
            <tr>
                <td align="center" width="12%" style="height: 527px;border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;"></td>
                <td align="center" width="7%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;"></td>
                <td align="center" width="45%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;"></td>
                <td align="center" width="15%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;"></td>
                <td align="center" width="21%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;"></td>
            </tr>
        </thead>
        <tbody>';
        
        $tbl .= '</tbody>';
        $tbl .= '<tfoot>';
        $tbl .= '<tr>';
            $tbl .= '<td align="right" colspan="7" style="border-top-width:0.1px;border-left-width:0.1px;border-right-width:0.1px; font-size:10px" width="79%"><strong>TOTAL AMOUNT</strong></td>';
            $tbl .= '<td align="right" style="border-top-width:0.1px;border-left-width:0.1px;border-right-width:0.1px;font-size:10px" width="21%"></td>';
        $tbl .= '</tr>';
        $tbl .= '</tfoot>';
        $tbl .= '</table>';
        PDF::writeHTML($tbl, false, false, false, false, '');

        $tbl = '<div style="font-size:15pt">&nbsp;</div><table width="100%" cellspacing="0" cellpadding="0" border="0" style="font-size: 9px;">
        <thead>
            <tr>
                <td align="center" width="25%" style="border-bottom-width:0.1px;">'.ucwords(Auth::user()->name).'</td>
                <td align="center" width="12.5%"></td>
                <td align="center" width="25%" style="border-bottom-width:0.1px;"></td>
                <td align="center" width="12.5%"></td>
                <td align="center" width="25%" style="border-bottom-width:0.1px;">'.ucwords((new User)->where('id', $trans->transCreated)->first()->name).'</td>
            </tr>
            <tr>
                <td align="center" width="25%"><strong>Printed By</strong></td>
                <td align="center" width="12.5%"></td>
                <td align="center" width="25%"><strong>Approved By</strong></td>
                <td align="center" width="12.5%"></td>
                <td align="center" width="25%"><strong>Prepared By</strong></td>
            </tr>
        </thead>
        </table>';
        PDF::writeHTML($tbl, false, false, false, false, '');

        $totalAmt = 0;
        PDF::SetXY(10, 68);
        $tbl = '<table width="100%" cellspacing="0" cellpadding="2" border="0" style="font-size: 10px;">
        <tbody>';
        foreach ($lineItems as $line) {
            if ($request->get('document') == 'preparation') {
                $totalAmt += floatval($line->total_amount);
                $total = number_format(floor(($line->total_amount*100))/100,2);
                $srp = number_format(floor(($line->srp*100))/100,2);
            } else {
                $srpVal = floatval($line->total_amount) / floatval($line->quantity);
                $amount = floatval($line->posted_quantity) * floatval($srpVal);
                $total = number_format(floor(($amount*100))/100,2);
                $totalAmt += floatval($amount);
                $srp = number_format(floor(($line->srp*100))/100,2);
            }
            $tbl .= '<tr>';
            $tbl .= '<td align="center" width="12%">'.$line->quantity.'</td>';
            $tbl .= '<td align="center" width="7%">'.$line->uom.'</td>';
            $tbl .= '<td align="left" width="45%">'.$line->itemCode.' - '.$line->itemName.'</td>';
            $tbl .= '<td align="right" width="15%">'.$srp.'</td>';
            $tbl .= '<td align="right" width="21%">'.$total.'</td>';
            $tbl .= '</tr>';
        }
        $tbl .=' </tbody>
        </table>';
        PDF::writeHTML($tbl, false, false, false, false, '');

        PDF::SetXY(10, 252.8);
        $tbl = '<table width="100%" cellspacing="0" cellpadding="2" border="0" style="font-size: 9px;">
        <tbody>
        <tr>
        <td width="79%">&nbsp;</td>
        <td width="21%" align="right" style="font-size: 10px"><strong>'.number_format(floor(($totalAmt*100))/100,2).'</strong></td>
        </tr>
        </tbody>
        </table>';
        PDF::writeHTML($tbl, false, false, false, false, '');

        PDF::Output('preview.pdf');
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