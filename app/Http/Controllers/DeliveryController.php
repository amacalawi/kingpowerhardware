<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\DeliveryLine;
use App\Models\DeliveryDocPrintStart;
use App\Models\Item;
use App\Models\ItemInventory;
use App\Models\ItemTransaction;
use App\Models\PaymentTerm;
use App\Models\User;
use Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\File;
use PDF;
// use App\Components\FlashMessages;
// use App\Helper\Helper;

class DeliveryController extends Controller
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
        $branches = (new Branch)->all_branches_selectpicker(Auth::user()->id);
        $customers = (new Customer)->all_customer_selectpicker();
        $agents = (new User)->all_agents_selectpicker();
        $payment_terms = (new PaymentTerm)->all_payment_term_selectpicker();
        $items = (new Item)->all_item_selectpicker();
        return view('modules/delivery/manage')->with(compact('menus', 'items', 'branches', 'customers', 'agents', 'payment_terms'));
    }

    public function get_delivery_doc_no(Request $request, $branch)
    {
        $drNo = (new DeliveryDocPrintStart)->get_delivery_doc_no($branch);
        return $drNo;
    }

    public function get_customer_info(Request $request, $customer)
    {
        $res = (new Customer)->where('id', $customer)->first();
        $data = array(
            'contact_no' => $res->mobile_no,
            'address' => $res->address,
            'agent_id' => $res->agent_id
        );

        echo json_encode( $data ); exit();
    }

    public function get_item_srp(Request $request, $itemID, $branchID)
    {
        $branch = (new Branch)->where('id', $branchID)->first();
        $item = (new Item)->with([
            'uom' =>  function($q) { 
                $q->select(['id', 'code']);
            }
        ])
        ->where('id', $itemID)->first();
        
        $data = array(
            'srp' => ($branch->is_srp > 0) ? $item->srp2 : $item->srp,
            'uom' => $item->uom->code
        );

        echo json_encode( $data ); exit();
    }

    public function store(Request $request)
    {   
        // $this->is_permitted(0);
        $timestamp = date('Y-m-d H:i:s');
        $drNo = (new DeliveryDocPrintStart)->get_delivery_doc_no($request->branch_id);

        $delivery = Delivery::create([
            'branch_id' => $request->branch_id,
            'customer_id' => $request->customer_id,
            'payment_terms_id' => $request->payment_terms_id,
            'agent_id' => $request->agent_id,
            'delivery_doc_no' => $drNo,
            'contact_no' => $request->contact_no,
            'address' => $request->address,
            'remarks' => $request->remarks,
            'created_at' => $timestamp,
            'created_by' => Auth::user()->id
        ]);

        if (!$delivery) {
            throw new NotFoundHttpException();
        }

        $this->audit_logs('delivery', $delivery->id, 'has inserted a new delivery.', delivery::find($delivery->id), $timestamp, Auth::user()->id);
        $res = DeliveryDocPrintStart::where('branch_id', $request->branch_id)->update(['print_start' => intval(str_replace('DR-','',$drNo))]);

        $data = array(
            'delivery_id' => $delivery->id,
            'doc_no' => $drNo,
            'title' => 'Well done!',
            'text' => 'The delivery has been successfully stored.',
            'type' => 'success',
            'class' => 'btn-brand'
        );

        echo json_encode( $data ); exit();
    }

    public function find(Request $request, $id)
    {    
        $delivery = Delivery::find($id);

        if(!$delivery) {
            throw new NotFoundHttpException();
        }

        $delivery = (object) array(
            'delivery_doc_no' => $delivery->delivery_doc_no,
            'delivery_id' => $delivery->id,
            'branch_id' => $delivery->branch_id,
            'customer_id' => $delivery->customer_id,
            'payment_terms_id' => $delivery->payment_terms_id,
            'agent_id' => $delivery->agent_id,
            'contact_no' => $delivery->contact_no,
            'address' => $delivery->address,
            'remarks' => $delivery->remarks,
            'total_amount' => $delivery->total_amount
        );

        return response()
        ->json([
            'status' => 'ok',
            'data' => $delivery
        ]);
    }

    public function update(Request $request, $id)
    {    
        // $this->is_permitted(2);
        $timestamp = date('Y-m-d H:i:s');
        $delivery = Delivery::find($id);

        if(!$delivery) {
            throw new NotFoundHttpException();
        }

        // $delivery->branch_id = $request->branch_id;
        $delivery->customer_id = $request->customer_id;
        $delivery->payment_terms_id = $request->payment_terms_id;
        $delivery->agent_id = $request->agent_id;
        $delivery->contact_no = $request->contact_no;
        $delivery->address = $request->address;
        $delivery->remarks = $request->remarks;
        $delivery->updated_at = $timestamp;
        $delivery->updated_by = Auth::user()->id;

        if ($delivery->update()) {
            $data = array(
                'title' => 'Well done!',
                'text' => 'The delivery has been successfully modified.',
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
        $msg .= '<table class="table align-middle table-row-dashed fs-6 gy-5" id="deliveryTable">';
        $msg .= '<thead>';
            $msg .= '<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">';
            $msg .= '<th class="w-10px pe-2">';
            $msg .= '<div class="form-check form-check-sm form-check-custom form-check-solid me-3">';
            $msg .= '<input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_customers_table .form-check-input" value="1" />';
            $msg .= '</div>';
            $msg .= '</th>';
            $msg .= '<th class="min-w-50px text-center">Transaction Date</th>';
            $msg .= '<th class="min-w-50px">DR No</th>';
            $msg .= '<th class="min-w-100px">Branch</th>';
            $msg .= '<th class="min-w-150px">Customer</th>';
            $msg .= '<th class="min-w-150px">Agent</th>';
            $msg .= '<th class="min-w-100px text-right">Total Amount</th>';
            $msg .= '<th class="min-w-50px text-center">Status</th>';
            $msg .= '<th class="text-center">Last Modified</th>';
            $msg .= '<th class="text-center min-w-70px">Actions</th>';
            $msg .= '</tr>';
        $msg .= '</thead>';
        $msg .= '<tbody class="fw-bold text-gray-600">';
        
        $query = $this->get_line_items($per_page, $start_from, $keywords);
        $count = $this->get_page_count($per_page, $start_from, $keywords);
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
                if ($row->status == 'draft') {
                    $status = '<span class="badge badge-status badge-light-dark">'.$row->status.'</span>';
                } else if ($row->status == 'prepared') {
                    $status = '<span class="badge badge-status badge-light-warning">'.$row->status.'</span>';
                } else if ($row->status == 'posted') {
                    $status = '<span class="badge badge-status badge-light-success">'.$row->status.'</span>';
                } else {
                    $status = '<span class="badge badge-status badge-light-primary">'.$row->status.'</span>';
                }
                $msg .= '<tr data-row-amount="'.$row->total_amount.'" data-row-dr="'.$row->doc_no.'" data-row-id="'.$row->id.'">';
                $msg .= '<td>';
                $msg .= '<div class="form-check form-check-sm form-check-custom form-check-solid">';
                $msg .= '<input class="form-check-input" type="checkbox" value="'.$row->id.'" />';
                $msg .= '</div>';
                $msg .= '</td>';
                $msg .= '<td class="text-center">'.$row->transaction_date.'</td>';
                $msg .= '<td>'.$row->doc_no.'</td>';
                $msg .= '<td>'.$row->branch.'</td>';
                $msg .= '<td>'.$row->customer.'</td>';
                $msg .= '<td>'.$row->agent.'</td>';
                $msg .= '<td class="text-right">₱'.$row->total_amount.'</td>';
                $msg .= '<td class="text-center">'.$status.'</td>';
                $msg .= '<td class="text-center">'.$row->modified_at.'</td>';
                $msg .= '<td class="text-center">';
                $msg .= '<a href="javascript:;" title="modify this" class="edit-btn btn btn-sm btn-light btn-active-light-primary">';
                $msg .= '<!--begin::Svg Icon | path: assets/media/icons/duotone/Design/Edit.svg-->
                <span class="svg-icon svg-icon-muted svg-icon-2hx"><svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                        <path d="M8,17.9148182 L8,5.96685884 C8,5.56391781 8.16211443,5.17792052 8.44982609,4.89581508 L10.965708,2.42895648 C11.5426798,1.86322723 12.4640974,1.85620921 13.0496196,2.41308426 L15.5337377,4.77566479 C15.8314604,5.0588212 16,5.45170806 16,5.86258077 L16,17.9148182 C16,18.7432453 15.3284271,19.4148182 14.5,19.4148182 L9.5,19.4148182 C8.67157288,19.4148182 8,18.7432453 8,17.9148182 Z" fill="#000000" fill-rule="nonzero" transform="translate(12.000000, 10.707409) rotate(-135.000000) translate(-12.000000, -10.707409) "/>
                        <rect fill="#000000" opacity="0.3" x="5" y="20" width="15" height="2" rx="1"/>
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

    public function get_line_items($limit, $start_from, $keywords = '')
    {   
        $user = Auth::user()->id;
        if (!empty($keywords)) {
            $res = Delivery::select([
                'delivery.created_at',
                'delivery.updated_at',
                'delivery.id',
                'delivery.delivery_doc_no as doc_no',
                'users.name as agent',
                'customers.name as customer',
                'payment_terms.name as payment_terms',
                'branches.name as branch',
                'delivery.status as status',
                'delivery.total_amount as total_amount'
            ])
            ->leftJoin('users', function($join)
            {
                $join->on('users.id', '=', 'delivery.agent_id');
            })
            ->leftJoin('customers', function($join)
            {
                $join->on('customers.id', '=', 'delivery.customer_id');
            })
            ->leftJoin('branches', function($join)
            {
                $join->on('branches.id', '=', 'delivery.branch_id');
            })
            ->leftJoin('payment_terms', function($join)
            {
                $join->on('payment_terms.id', '=', 'delivery.payment_terms_id');
            })
            ->whereIn('branches.id', 
                explode(',', trim((new User)->select(['assignment'])->where('id', $user)->first()->assignment))
            )
            ->where('delivery.is_active', 1)
            ->where(function($q) use ($keywords) {
                $q->where('delivery.delivery_doc_no', 'like', '%' . $keywords . '%')
                  ->orWhere('delivery.delivery_doc_no', 'like', '%' . $keywords . '%')
                  ->orWhere('delivery.total_amount', 'like', '%' . $keywords . '%')
                  ->orWhere('customers.name', 'like', '%' . $keywords . '%')
                  ->orWhere('payment_terms.name', 'like', '%' . $keywords . '%')
                  ->orWhere('branches.name', 'like', '%' . $keywords . '%')
                  ->orWhere('users.name', 'like', '%' . $keywords . '%');
            })
            ->skip($start_from)->take($limit)
            ->orderBy('delivery.id', 'desc')
            ->get();
        } else {
            $res = Delivery::select([
                'delivery.created_at',
                'delivery.updated_at',
                'delivery.id',
                'delivery.delivery_doc_no as doc_no',
                'users.name as agent',
                'customers.name as customer',
                'payment_terms.name as payment_terms',
                'branches.name as branch',
                'delivery.status as status',
                'delivery.total_amount as total_amount'
            ])
            ->leftJoin('users', function($join)
            {
                $join->on('users.id', '=', 'delivery.agent_id');
            })
            ->leftJoin('customers', function($join)
            {
                $join->on('customers.id', '=', 'delivery.customer_id');
            })
            ->leftJoin('branches', function($join)
            {
                $join->on('branches.id', '=', 'delivery.branch_id');
            })
            ->leftJoin('payment_terms', function($join)
            {
                $join->on('payment_terms.id', '=', 'delivery.payment_terms_id');
            })
            ->whereIn('branches.id', 
                explode(',', trim((new User)->select(['assignment'])->where('id', $user)->first()->assignment))
            )
            ->where('delivery.is_active', 1)
            ->skip($start_from)->take($limit)
            ->orderBy('delivery.id', 'desc')
            ->get();
        }

        return $res->map(function($del) {
            return (object) [
                'id' => $del->id,
                'doc_no' => $del->doc_no,
                'agent' => $del->agent,
                'customer' => $del->customer,
                'payment_terms' => $del->payment_terms,
                'branch' => $del->branch,
                'total_amount' => number_format(floor(($del->total_amount*100))/100,2),
                'status' => $del->status,
                'agent' => (strlen($del->agent) > 0) ? $del->agent : '-',
                'transaction_date' => date('d-M-Y', strtotime($del->created_at)),
                'modified_at' => ($del->updated_at !== NULL) ? date('d-M-Y', strtotime($del->updated_at)).'<br/>'. date('h:i A', strtotime($del->updated_at)) : date('d-M-Y', strtotime($del->created_at)).'<br/>'. date('h:i A', strtotime($del->created_at))
            ];
        });
    }

    public function get_page_count($limit, $start_from, $keywords = '')
    {   
        $user = Auth::user()->id;
        if (!empty($keywords)) {
            $res = Delivery::select([
                'delivery.created_at',
                'delivery.updated_at',
                'delivery.id',
                'delivery.delivery_doc_no as doc_no',
                'users.name as agent',
                'customers.name as customer',
                'payment_terms.name as payment_terms',
                'branches.name as branch',
                'delivery.status as status',
                'delivery.total_amount as total_amount'
            ])
            ->leftJoin('users', function($join)
            {
                $join->on('users.id', '=', 'delivery.agent_id');
            })
            ->leftJoin('customers', function($join)
            {
                $join->on('customers.id', '=', 'delivery.customer_id');
            })
            
            ->leftJoin('branches', function($join)
            {
                $join->on('branches.id', '=', 'delivery.branch_id');
            })
            ->leftJoin('payment_terms', function($join)
            {
                $join->on('payment_terms.id', '=', 'delivery.payment_terms_id');
            })
            ->whereIn('branches.id', 
                explode(',', trim((new User)->select(['assignment'])->where('id', $user)->first()->assignment))
            )
            ->where('delivery.is_active', 1)
            ->where(function($q) use ($keywords) {
                $q->where('delivery.delivery_doc_no', 'like', '%' . $keywords . '%')
                ->orWhere('delivery.delivery_doc_no', 'like', '%' . $keywords . '%')
                ->orWhere('delivery.total_amount', 'like', '%' . $keywords . '%')
                ->orWhere('customers.name', 'like', '%' . $keywords . '%')
                ->orWhere('payment_terms.name', 'like', '%' . $keywords . '%')
                ->orWhere('branches.name', 'like', '%' . $keywords . '%')
                ->orWhere('users.name', 'like', '%' . $keywords . '%');
            })
            ->orderBy('delivery.id', 'desc')
            ->count();
        } else {
            $res = Delivery::select([
                'delivery.created_at',
                'delivery.updated_at',
                'delivery.id',
                'delivery.delivery_doc_no as doc_no',
                'users.name as agent',
                'customers.name as customer',
                'payment_terms.name as payment_terms',
                'branches.name as branch',
                'delivery.status as status',
                'delivery.total_amount as total_amount'
            ])
            ->leftJoin('users', function($join)
            {
                $join->on('users.id', '=', 'delivery.agent_id');
            })
            ->leftJoin('customers', function($join)
            {
                $join->on('customers.id', '=', 'delivery.customer_id');
            })
            
            ->leftJoin('branches', function($join)
            {
                $join->on('branches.id', '=', 'delivery.branch_id');
            })
            ->leftJoin('payment_terms', function($join)
            {
                $join->on('payment_terms.id', '=', 'delivery.payment_terms_id');
            })
            ->whereIn('branches.id', 
                explode(',', trim((new User)->select(['assignment'])->where('id', $user)->first()->assignment))
            )
            ->where('delivery.is_active', 1)
            ->orderBy('delivery.id', 'desc')
            ->count();
        }

        return $res;
    }

    public function all_active_lines(Request $request)
    {   
        $keywords     = $request->get('keywords'); 
        $deliveryID   = $request->get('id');   
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
        $msg .= '<table class="table align-middle table-row-dashed fs-8 mt-5" id="deliveryLineTable">';
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
            $msg .= '<th class="min-w-50px text-center">Plus</th>';
            $msg .= '<th class="min-w-50px text-center">Disc1</th>';
            $msg .= '<th class="min-w-50px text-center">Disc2</th>';
            $msg .= '<th class="min-w-50px text-center">Total</th>';
            $msg .= '<th class="min-w-50px text-center">Posted</th>';
            $msg .= '<th class="text-center min-w-70px">Actions</th>';
            $msg .= '</tr>';
        $msg .= '</thead>';
        $msg .= '<tbody class="fw-bold text-gray-600">';
        
        $query = $this->get_delivery_line_items($per_page, $start_from, $keywords, $deliveryID);
        $count = $this->get_delivery_page_count($per_page, $start_from, $keywords, $deliveryID);
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
                $msg .= '<td class="min-w-50px text-center">'.(($row->plus > 0) ? $row->plus.'%' : '' ).'</td>';
                $msg .= '<td class="min-w-50px text-center">'.(($row->disc1 > 0) ? $row->disc1.'%' : '' ).'</td>';
                $msg .= '<td class="min-w-50px text-center">'.(($row->disc2 > 0) ? $row->disc2.'%' : '' ).'</td>';
                $msg .= '<td class="min-w-50px text-center">'.$row->total_amount.'</td>';
                $msg .= '<td class="min-w-50px text-center">'.(($row->posted_quantity > 0) ? $row->posted_quantity : '' ).'</td>';
                $msg .= '<td class="text-center">';
                $msg .= '<a href="javascript:;" title="modify this" class="edit-item-btn btn btn-sm btn-light btn-active-light-primary">';
                $msg .= '<!--begin::Svg Icon | path: assets/media/icons/duotone/General/Edit.svg-->
                <span class="svg-icon svg-icon-muted svg-icon-2hx"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                        <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                        <rect x="0" y="0" width="24" height="24"/>
                        <path d="M7.10343995,21.9419885 L6.71653855,8.03551821 C6.70507204,7.62337518 6.86375628,7.22468355 7.15529818,6.93314165 L10.2341093,3.85433055 C10.8198957,3.26854411 11.7696432,3.26854411 12.3554296,3.85433055 L15.4614112,6.9603121 C15.7369117,7.23581259 15.8944065,7.6076995 15.9005637,7.99726737 L16.1199293,21.8765672 C16.1330212,22.7048909 15.4721452,23.3869929 14.6438216,23.4000848 C14.6359205,23.4002097 14.6280187,23.4002721 14.6201167,23.4002721 L8.60285976,23.4002721 C7.79067946,23.4002721 7.12602744,22.7538546 7.10343995,21.9419885 Z" fill="#000000" fill-rule="nonzero" transform="translate(11.418039, 13.407631) rotate(-135.000000) translate(-11.418039, -13.407631) "/>
                    </g>
                </svg></span>
                <!--end::Svg Icon--></a>';
                $msg .= '<a href="javascript:;" title="remove this" class="remove-btn btn btn-sm btn-light btn-active-light-danger">';
                $msg .= '<!--begin::Svg Icon | path: assets/media/icons/duotone/Design/Eraser.svg-->
                <span class="svg-icon svg-icon-muted svg-icon-2hx"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                        <rect x="0" y="0" width="24" height="24"/>
                        <path d="M6,8 L6,20.5 C6,21.3284271 6.67157288,22 7.5,22 L16.5,22 C17.3284271,22 18,21.3284271 18,20.5 L18,8 L6,8 Z" fill="#000000" fill-rule="nonzero"/>
                        <path d="M14,4.5 L14,4 C14,3.44771525 13.5522847,3 13,3 L11,3 C10.4477153,3 10,3.44771525 10,4 L10,4.5 L5.5,4.5 C5.22385763,4.5 5,4.72385763 5,5 L5,5.5 C5,5.77614237 5.22385763,6 5.5,6 L18.5,6 C18.7761424,6 19,5.77614237 19,5.5 L19,5 C19,4.72385763 18.7761424,4.5 18.5,4.5 L14,4.5 Z" fill="#000000" opacity="0.3"/>
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

    public function get_delivery_line_items($limit, $start_from, $keywords = '', $deliveryID)
    {
        if (!empty($keywords)) {
            $res = DeliveryLine::select([
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
            ->where([
                'delivery_lines.delivery_id' => $deliveryID, 
                'delivery_lines.is_active' => 1
            ])
            ->where(function($q) use ($keywords) {
                $q->where('delivery_lines.srp', 'like', '%' . $keywords . '%')
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
            })
            ->skip($start_from)->take($limit)
            ->orderBy('delivery_lines.id', 'asc')
            ->get();
        } else {
            $res = DeliveryLine::select([
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
            ->where([
                'delivery_lines.delivery_id' => $deliveryID, 
                'delivery_lines.is_active' => 1
            ])
            ->skip($start_from)->take($limit)
            ->orderBy('delivery_lines.id', 'asc')
            ->get();
        }

        return $res->map(function($del) {
            return (object) [
                'id' => $del->lineID,
                'itemName' => $del->itemName,
                'itemCode' => $del->itemCode,
                'quantity' => $del->quantity,
                'srp' => $del->srp,
                'uom' => $del->uom,
                'total_amount' => $del->total_amount,
                'disc1' => $del->disc1,
                'disc2' => $del->disc2,
                'plus' => $del->plus,
                'posted_quantity' => $del->posted_quantity,
                'modified_at' => ($del->updated_at !== NULL) ? date('d-M-Y', strtotime($del->updated_at)).'<br/>'. date('h:i A', strtotime($del->updated_at)) : date('d-M-Y', strtotime($del->created_at)).'<br/>'. date('h:i A', strtotime($del->created_at))
            ];
        });
    }

    public function get_delivery_page_count($limit, $start_from, $keywords = '', $deliveryID)
    {
        if (!empty($keywords)) {
            $res = DeliveryLine::select([
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
            ->where([
                'delivery_lines.delivery_id' => $deliveryID, 
                'delivery_lines.is_active' => 1
            ])
            ->where(function($q) use ($keywords) {
                $q->where('delivery_lines.srp', 'like', '%' . $keywords . '%')
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
            })
            ->skip($start_from)->take($limit)
            ->orderBy('delivery_lines.id', 'asc')
            ->count();
        } else {
            $res = DeliveryLine::select([
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
            ->where([
                'delivery_lines.delivery_id' => $deliveryID, 
                'delivery_lines.is_active' => 1
            ])
            ->orderBy('delivery_lines.id', 'asc')
            ->count();
        }

        return $res;
    }

    public function store_line_item(Request $request, $deliveryID)
    {   
        // $this->is_permitted(0);
        $timestamp = date('Y-m-d H:i:s');

        $deliveryLine = DeliveryLine::create([
            'delivery_id' => $deliveryID,
            'item_id' => $request->item_id,
            'uom' => $request->get('uom'),
            'is_special' => ($request->srp_special !== NULL) ? 1 : 0,
            'srp' => ($request->srp_special !== NULL) ? $request->srp_special : $request->get('srp'),
            'quantity' => $request->qty,
            'discount1' => $request->disc1,
            'discount2' => $request->disc2,
            'plus' => $request->plus,
            'total_amount' => $request->get('total_amount'),
            'created_at' => $timestamp,
            'created_by' => Auth::user()->id
        ]);

        if (!$deliveryLine) {
            throw new NotFoundHttpException();
        }

        $this->audit_logs('delivery', $deliveryLine->id, 'has inserted a new delivery.', DeliveryLine::find($deliveryLine->id), $timestamp, Auth::user()->id);

        $result = DeliveryLine::where(['delivery_id' => $deliveryID, 'is_active' => 1])->get();
        $status = 'draft'; $totalAmount = 0;
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
                $status = 'posted';
            } else {
                $status = 'prepared';
            }
        } 
        
        $delivery = Delivery::where([
            'id' => $deliveryID,
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

    public function remove(Request $request, $id)
    {   
        // $this->is_permitted(3);
        $timestamp = date('Y-m-d H:i:s');
        $delivery = Customer::where([
            'id' => $id,
        ])
        ->update([
            'updated_at' => $timestamp,
            'updated_by' => Auth::user()->id,
            'is_active' => 0
        ]);
        $this->audit_logs('customers', $id, 'has removed a customer.', Customer::find($id), $timestamp, Auth::user()->id);
        
        $data = array(
            'title' => 'Well done!',
            'text' => 'The customer has been successfully removed.',
            'type' => 'success',
            'class' => 'btn-brand'
        );

        echo json_encode( $data ); exit();
    }

    public function remove_line_item(Request $request, $id)
    {   
        // $this->is_permitted(3);
        $timestamp = date('Y-m-d H:i:s');
        $deliveryLine = DeliveryLine::where([
            'id' => $id,
        ])
        ->update([
            'updated_at' => $timestamp,
            'updated_by' => Auth::user()->id,
            'is_active' => 0
        ]);
        $this->audit_logs('delivery_lines', $id, 'has removed a delivery line.', DeliveryLine::find($id), $timestamp, Auth::user()->id);
        
        $deliveryID = DeliveryLine::where('id', $id)->first()->delivery_id;
        $result = DeliveryLine::where(['delivery_id' => $deliveryID, 'is_active' => 1])->get();
        $status = 'draft'; $totalAmount = 0;
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
                $status = 'posted';
            } else {
                $status = 'prepared';
            }
        } 
        
        $delivery = Delivery::where([
            'id' => $deliveryID,
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
            'text' => 'The line item has been successfully removed.',
            'type' => 'success',
            'class' => 'btn-brand'
        );

        echo json_encode( $data ); exit();
    }

    public function update_line_item(Request $request, $id)
    {    
        // $this->is_permitted(2);
        $timestamp = date('Y-m-d H:i:s');
        $deliveryLine = DeliveryLine::find($id);

        if(!$deliveryLine) {
            throw new NotFoundHttpException();
        }
        
        $deliveryLine->item_id = $request->item_id;
        $deliveryLine->uom = $request->get('uom');
        $deliveryLine->is_special = ($request->srp_special !== NULL) ? 1 : 0;
        $deliveryLine->srp = ($request->srp_special !== NULL) ? $request->srp_special : $request->get('srp');
        $deliveryLine->quantity = $request->qty;
        $deliveryLine->discount1 = $request->disc1;
        $deliveryLine->discount2 = $request->disc2;
        $deliveryLine->plus = $request->plus;
        $deliveryLine->total_amount = $request->get('total_amount');
        $deliveryLine->updated_at = $timestamp;
        $deliveryLine->updated_by = Auth::user()->id;

        if ($deliveryLine->update()) {
            $deliveryID = DeliveryLine::where('id', $id)->first()->delivery_id;
            $result = DeliveryLine::where(['delivery_id' => $deliveryID, 'is_active' => 1])->get();
            $status = 'draft'; $totalAmount = 0;
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
                    $status = 'posted';
                } else {
                    $status = 'prepared';
                }
            } 
            
            $delivery = Delivery::where([
                'id' => $deliveryID,
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
                'text' => 'The item has been successfully modified.',
                'type' => 'success',
                'class' => 'btn-brand'
            );
            echo json_encode( $data ); exit();
        }
    }

    public function restore(Request $request, $id)
    {   
        // $this->is_permitted(3);
        $timestamp = date('Y-m-d H:i:s');
        $delivery = Customer::where([
            'id' => $id,
        ])
        ->update([
            'updated_at' => $timestamp,
            'updated_by' => Auth::user()->id,
            'is_active' => 1
        ]);
        $this->audit_logs('customers', $id, 'has removed a customer.', Customer::find($id), $timestamp, Auth::user()->id);
        
        $data = array(
            'title' => 'Well done!',
            'text' => 'The customer has been successfully removed.',
            'type' => 'success',
            'class' => 'btn-brand'
        );

        echo json_encode( $data ); exit();
    }

    public function export(Request $request)
    {   
        $fileName = 'delivery_'.time().'.csv';

        $deliveries = Delivery::select([
            'delivery.created_at as transaction_date',
            'branches.name as branch',
            'customers.name as customer',
            'delivery.delivery_doc_no as doc_no',
            'payment_terms.name as terms',
            'users.name as agent',
            'delivery.total_amount as total',
            'delivery.status as status'
        ])
        ->join('customers', function($join)
        {
            $join->on('customers.id', '=', 'delivery.customer_id');
        })
        ->join('branches', function($join)
        {
            $join->on('branches.id', '=', 'delivery.branch_id');
        })
        ->join('payment_terms', function($join)
        {
            $join->on('payment_terms.id', '=', 'delivery.payment_terms_id');
        })
        ->join('users', function($join)
        {
            $join->on('users.id', '=', 'delivery.agent_id');
        })
        ->where('delivery.is_active', 1)
        ->orderBy('delivery.id', 'asc')
        ->get();

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('Transaction Date', 'DR No', 'Branch', 'Customer', 'Agent', 'Payment Terms', 'Total Amount', 'Status');

        $callback = function() use($deliveries, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($deliveries as $delivery) {
                $row['transaction'] = $delivery->transaction_date;
                $row['doc_no']      = $delivery->doc_no;
                $row['branch']      = $delivery->branch;
                $row['customer']    = $delivery->customer;
                $row['agent']       = $delivery->agent;
                $row['terms']       = $delivery->terms;
                $row['total']       = $delivery->total;
                $row['status']      = $delivery->status;
                fputcsv($file, array($row['transaction'], $row['doc_no'], $row['branch'], $row['customer'], $row['agent'], $row['terms'], $row['total'], $row['status']));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function find_line_item(Request $request, $id)
    {    
        $res = DeliveryLine::with([
            'item' =>  function($q) { 
                $q->select(['id', 'code', 'name']);
            },
            'delivery' =>  function($q) { 
                $q->select(['id', 'branch_id', 'customer_id', 'payment_terms_id', 'agent_id']);
            }
        ])
        ->where(['id' => $id])
        ->first();

        $delivery = (object) array(
            'delivery_line_id' => $id,
            'delivery_idx' => $res->delivery_id,
            'item_name' => $res->item->code.' - '.$res->item->name,
            'available_qty' => (new ItemInventory)->find_item_inventory($res->delivery->branch_id, $res->item->id),
            'for_posting' => (floatval($res->quantity) - floatval($res->posted_quantity))
        );

        return response()
        ->json([
            'status' => 'ok',
            'data' => $delivery
        ]);
    }

    public function find_line(Request $request, $id)
    {    
        $res = DeliveryLine::with([
            'item' =>  function($q) { 
                $q->select(['id', 'code', 'name']);
            },
            'delivery' =>  function($q) { 
                $q->select(['id', 'branch_id', 'customer_id', 'payment_terms_id', 'agent_id']);
            }
        ])
        ->where(['id' => $id])
        ->first();

        $delivery = (object) array(
            'delivery_line_id' => $id,
            'item_id' => $res->item_id,
            'qty' => $res->quantity,
            'srp_special' => ($res->is_special > 0) ? $res->srp : '',
            'plus' => $res->plus,
            'disc1' => $res->discount1,
            'disc2' => $res->discount2,
            'total_amount' => $res->total_amount
        );

        return response()
        ->json([
            'status' => 'ok',
            'data' => $delivery
        ]);
    }

    public function post_line_item(Request $request, $id)
    {   
        $timestamp = date('Y-m-d H:i:s');
        $deliveryLine = DeliveryLine::find($id);

        if(!$deliveryLine) {
            throw new NotFoundHttpException();
        }
        
        $delivery = Delivery::where('id', $deliveryLine->delivery_id)->first();
        $inventory = ItemInventory::where(['item_id' => $deliveryLine->item_id, 'branch_id' => $delivery->branch_id, 'is_active' => 1])->get();
        if ($inventory->count() > 0) {
            $inventory = $inventory->first();
            $quantity = (floatval($request->qty_to_post) <= floatval($inventory->quantity)) ? $request->qty_to_post : $inventory->quantity; 
            $qtyLeft   = floatval($inventory->quantity) - floatval($quantity);
            ItemInventory::where('id', $inventory->id)->update(['quantity' => $qtyLeft]);

            $transaction = ItemTransaction::create([
                'item_id' => $deliveryLine->item_id,
                'branch_id' => $delivery->branch_id,
                'transaction' => 'Withdrawal',
                'based_quantity' => $inventory->quantity,
                'issued_quantity' => $quantity,
                'left_quantity' => $qtyLeft,
                'srp' => $deliveryLine->srp,
                'total_amount' => $deliveryLine->total_amount,
                'issued_by' => Auth::user()->id,
                'received_by' => Auth::user()->id,
                'remarks' => 'Item withdrawal from '.$delivery->delivery_doc_no,
                'created_at' => $timestamp,
                'created_by' => Auth::user()->id
            ]);
    
            if (!$transaction) {
                throw new NotFoundHttpException();
            }
    
            $this->audit_logs('items_transactions', $transaction->id, 'has inserted a new item transaction.', ItemTransaction::find($transaction->id), $timestamp, Auth::user()->id);

            $postedQuantity = floatval($deliveryLine->posted_quantity) + floatval($quantity);
            $deliveryLine->posted_quantity = $postedQuantity;
            if (floatval($postedQuantity) == floatval($deliveryLine->quantity)) {
                $deliveryLine->status = 'posted';
            }
            $deliveryLine->updated_at = $timestamp;
            $deliveryLine->updated_by = Auth::user()->id;

            if ($deliveryLine->update()) {
                $this->audit_logs('delivery_lines', $id, 'has posted a quantity('.$quantity.') on delivery line.', DeliveryLine::find($id), $timestamp, Auth::user()->id);
            }

            $result = DeliveryLine::where(['delivery_id' => $delivery->id, 'is_active' => 1])->get();
            $status = 'draft'; $totalAmount = 0;
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
                    $status = 'posted';
                } else {
                    $status = 'prepared';
                }
            } 
            
            $delivery = Delivery::where([
                'id' => $delivery->id,
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

    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }

    public function preview(Request $request)
    {   
        $page = 0;
        $delivery = Delivery::select([
            'customers.name as customer',
            'payment_terms.name as terms',
            'users.name as agent',
            'delivery.address as address',
            'delivery.contact_no as contact',
            'delivery.created_at as transaction_date',
            'branches.dr_header as drHeader',
            'branches.dr_address as drAddress'
        ])
        ->leftJoin('branches', function($join)
        {
            $join->on('branches.id', '=', 'delivery.branch_id');
        })
        ->leftJoin('customers', function($join)
        {
            $join->on('customers.id', '=', 'delivery.customer_id');
        })
        ->leftJoin('payment_terms', function($join)
        {
            $join->on('payment_terms.id', '=', 'delivery.payment_terms_id');
        })
        ->leftJoin('users', function($join)
        {
            $join->on('users.id', '=', 'delivery.agent_id');
        })
        ->where([
            'delivery.is_active' => 1,
            'delivery.delivery_doc_no' => $request->get('dr_no')
        ])
        ->first();
            
        $deliverLines2 = DeliveryLine::select([
            'items.code', 
            'items.name', 
            'delivery_lines.uom', 
            'delivery_lines.total_amount', 
            'delivery_lines.posted_quantity as quantity',
            'delivery_lines.quantity as prep_quantity',  
            'delivery_lines.plus', 
            'delivery_lines.discount1',
            'delivery_lines.discount2', 
            'delivery_lines.srp',
            ])
        ->leftJoin('delivery', function($join)
        {
            $join->on('delivery.id', '=', 'delivery_lines.delivery_id');
        })
        ->leftJoin('items', function($join)
        {
            $join->on('items.id', '=', 'delivery_lines.item_id');
        })
        ->where('delivery_lines.posted_quantity', '>', 0)
        ->where([
            'delivery_lines.is_active' => 1,
            'delivery.delivery_doc_no' => $request->get('dr_no')
        ])
        ->get();

        $deliverLines = DeliveryLine::select([
            'items.code', 
            'items.name', 
            'delivery_lines.uom', 
            'delivery_lines.total_amount', 
            'delivery_lines.quantity', 
            'delivery_lines.plus', 
            'delivery_lines.discount1',
            'delivery_lines.discount2', 
            'delivery_lines.srp',
            ])
        ->leftJoin('delivery', function($join)
        {
            $join->on('delivery.id', '=', 'delivery_lines.delivery_id');
        })
        ->leftJoin('items', function($join)
        {
            $join->on('items.id', '=', 'delivery_lines.item_id');
        })
        ->where([
            'delivery_lines.is_active' => 1,
            'delivery.delivery_doc_no' => $request->get('dr_no')
        ])
        ->get();

        PDF::SetMargins(10, 0, 10, false);
        PDF::SetAutoPageBreak(true, 0);
        PDF::SetTitle('Delivery Receipt ('.$request->get('dr_no').')');
        PDF::AddPage('P', 'LETTER');
        $tbl = '<div style="font-size:10pt">&nbsp;</div>';
        $tbl .= '<table id="heaer-table" width="100%" cellspacing="0" cellpadding="0" border="0" style="font-size: 9px;">
            <thead>
                <tr>
                    <td align="center"><p style="font-size: 22px">'.$delivery->drHeader.'</p></td>
                </tr>
                <tr>
                    <td align="center"><p style="font-size: 9px">'.$delivery->drAddress.'</p></td>
                </tr>
                <tr>
                    <td align="center" style="font-size: 11px">DELIVERY RECEIPT</td>
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
                <td align="right" width="17%"><strong>SOLD TO:&nbsp;&nbsp;</strong></td>
                <td align="left" width="82%" style="border-bottom-width:0.1px;">'.ucwords($delivery->customer).'</td>
            </tr>
            <tr>
                <td align="right" width="17%"><div style="font-size:5pt">&nbsp;</div><strong>ADDRESS:&nbsp;&nbsp;</strong></td>
                <td align="left" width="82%" style="height:39px;border-bottom-width:0.1px;"><div style="font-size:5pt">&nbsp;</div>'.$delivery->address.'</td>
            </tr>
            <tr>
                <td align="right" width="17%"><div style="font-size:5pt">&nbsp;</div><strong>CONTACT#:&nbsp;&nbsp;</strong></td>
                <td align="left" width="82%" style="border-bottom-width:0.1px;"><div style="font-size:5pt">&nbsp;</div>'.$delivery->contact.'</td>
            </tr>
        </thead>
        </table>';
        $tbl .= '</td>';
        $tbl .= '<td width="35%">';
        $tbl .= '<table width="100%" cellspacing="0" cellpadding="1" border="0" style="font-size: 9px;">
        <thead>
            <tr>
                <td align="right" width="25%"><strong>DR#:&nbsp;&nbsp;</strong></td>
                <td align="left" width="75%" style="border-bottom-width:0.1px;">'.$request->get('dr_no').'</td>
            </tr>
            <tr>
                <td align="right" width="25%"><div style="font-size:5pt">&nbsp;</div><strong>DATE:&nbsp;&nbsp;</strong></td>
                <td align="left" width="75%" style="border-bottom-width:0.1px;"><div style="font-size:5pt">&nbsp;</div>'.date('M d, Y', strtotime($delivery->transaction_date)).'</td>
            </tr>
            <tr>
                <td align="right" width="25%"><div style="font-size:5pt">&nbsp;</div><strong>AGENT:&nbsp;&nbsp;</strong></td>
                <td align="left" width="75%" style="border-bottom-width:0.1px;"><div style="font-size:5pt">&nbsp;</div>'.$delivery->agent.'</td>
            </tr>
            <tr>
                <td align="right" width="25%"><div style="font-size:5pt">&nbsp;</div><strong>TERMS:&nbsp;&nbsp;</strong></td>
                <td align="left" width="75%" style="border-bottom-width:0.1px;"><div style="font-size:5pt">&nbsp;</div>'.$delivery->terms.'</td>
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
                <td rowspan="2" align="center" width="9%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;"><div style="font-size:6pt">&nbsp;</div><strong>QTY</strong></td>
                <td rowspan="2" align="center" width="7%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;"><div style="font-size:6pt">&nbsp;</div><strong>UOM</strong></td>
                <td rowspan="2" align="center" width="40%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;"><div style="font-size:6pt">&nbsp;</div><strong>ITEM DESCRIPTION</strong></td>
                <td align="center" width="6%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;"><strong>PLUS</strong></td>
                <td align="center" width="6%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;"><strong>DISC</strong></td>
                <td align="center" width="6%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;"><strong>DISC</strong></td>
                <td rowspan="2" align="center" width="11%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;"><div style="font-size:6pt">&nbsp;</div><strong>PRICE</strong></td>
                <td rowspan="2" align="center" width="15%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;"><div style="font-size:6pt">&nbsp;</div><strong>AMOUNT</strong></td>
            </tr>
            <tr>
                <td width="6%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;">+&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;%</td>
                <td width="6%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;">-&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; %</td>
                <td width="6%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;">-&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; %</td>
            </tr>
            <tr>
                <td align="center" width="9%" style="height: 457px;border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;"></td>
                <td align="center" width="7%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;"></td>
                <td align="center" width="40%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;"></td>
                <td align="center" width="6%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;"></td>
                <td align="center" width="6%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;"></td>
                <td align="center" width="6%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;"></td>
                <td align="center" width="11%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;"></td>
                <td align="center" width="15%" style="border-top-width:0.1px;border-left-width:0.1px;border-bottom-width:0.1px;border-right-width:0.1px;"></td>
            </tr>
        </thead>
        <tbody>';
        
        $tbl .= '</tbody>';
        $tbl .= '<tfoot>';
        $tbl .= '<tr>';
            $tbl .= '<td align="right" colspan="7" style="border-top-width:0.1px;border-left-width:0.1px;border-right-width:0.1px; font-size:10px" width="85%"><strong>TOTAL AMOUNT</strong></td>';
            $tbl .= '<td align="right" style="border-top-width:0.1px;border-left-width:0.1px;border-right-width:0.1px;font-size:10px" width="15%"></td>';
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
                <td align="center" width="25%" style="border-bottom-width:0.1px;"></td>
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

        $tbl = '<div style="font-size:5pt">&nbsp;</div>';
        $tbl .= '<table cellspacing="0" cellpadding="0" border="0" style="padding: 0">
        <tr>
        <td width="60%" style="padding:0"><table width="100%" cellspacing="0" cellpadding="0" border="0" style="font-size: 8px;">
        <tr>
        <td align="left" style="font-size: 8px;"><strong>NOTE</strong>: Make all check payable to King Power Hardware Materials Wholesaling Materials.
        </td>
        </tr>
        <tr>
        <td align="left" style="font-size: 8px;"><strong>IMPORTANT AGREEMENT</strong>: price adjustment, defect, shortage, return must be done.<br/>within 10 working days after receipt of goods, all condition considered final after elapse of grace period
        </td>
        </tr>
        </table>
        </td>
        <td width="40%">
        <table width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
        <td align="center" style="font-size: 8px;">RECEIVED THE ABOVE ARTICLES IN GOOD CONDITION
        </td>
        </tr>
        <tr>
        <td align="center" style="font-size: 8px;">&nbsp;
        </td>
        </tr>
        <tr>
        <td align="center" style="font-size: 8px; border-bottom-width:0.1px;">&nbsp;
        </td>
        </tr>
        <tr>
        <td align="center" style="font-size: 8px;"><strong>CUSTOMER AUTHORIZED SIGNATURE</strong>
        </td>
        </tr>
        </table>
        </td>
        </tr>
        </table>';
        PDF::writeHTML($tbl, false, false, false, false, '');
            
        $totalAmt = 0;
        PDF::SetXY(10, 73);
        $tbl = '<table width="100%" cellspacing="0" cellpadding="2" border="0" style="font-size: 10px;">
        <tbody>';
        if ($request->get('document') == 'preparation') {
            foreach ($deliverLines as $line) {
                $totalAmt += floatval($line->total_amount);
                $total = number_format(floor(($line->total_amount*100))/100,2);
                $srp = number_format(floor(($line->srp*100))/100,2);
                $tbl .= '<tr>';
                    $tbl .= '<td align="center" style="border-left-width:0.1px;border-right-width:0.1px;" width="9%">'.$line->quantity.'</td>';
                    $tbl .= '<td align="center" style="border-left-width:0.1px;border-right-width:0.1px;" width="7%">'.$line->uom.'</td>';
                    $tbl .= '<td align="left" style="border-left-width:0.1px;border-right-width:0.1px;" width="40%">'.$line->code.' - '.$line->name.'</td>';
                    $tbl .= '<td align="center" style="border-left-width:0.1px;border-right-width:0.1px;" width="6%">'.$line->plus.'</td>';
                    $tbl .= '<td align="center" style="border-left-width:0.1px;border-right-width:0.1px;" width="6%">'.$line->discount1.'</td>';
                    $tbl .= '<td align="center" style="border-left-width:0.1px;border-right-width:0.1px;" width="6%">'.$line->discount2.'</td>';
                    $tbl .= '<td align="right" style="border-left-width:0.1px;border-right-width:0.1px;" width="11%">'.$srp.'</td>';
                    $tbl .= '<td align="right" style="border-left-width:0.1px;border-right-width:0.1px;" width="15%">'.$total.'</td>';
                $tbl .= '</tr>';
            }
        } else {
            foreach ($deliverLines2 as $line) {
                $srpVal = floatval($line->total_amount) / floatval($line->prep_quantity);
                $amount = floatval($line->quantity) * floatval($srpVal);
                $total = number_format(floor(($amount*100))/100,2);
                $totalAmt += floatval($amount);
                $srp = number_format(floor(($line->srp*100))/100,2);
                $tbl .= '<tr>';
                    $tbl .= '<td align="center" style="border-left-width:0.1px;border-right-width:0.1px;" width="9%">'.$line->quantity.'</td>';
                    $tbl .= '<td align="center" style="border-left-width:0.1px;border-right-width:0.1px;" width="7%">'.$line->uom.'</td>';
                    $tbl .= '<td align="left" style="border-left-width:0.1px;border-right-width:0.1px;" width="40%">'.$line->code.' - '.$line->name.'</td>';
                    $tbl .= '<td align="center" style="border-left-width:0.1px;border-right-width:0.1px;" width="6%">'.$line->plus.'</td>';
                    $tbl .= '<td align="center" style="border-left-width:0.1px;border-right-width:0.1px;" width="6%">'.$line->discount1.'</td>';
                    $tbl .= '<td align="center" style="border-left-width:0.1px;border-right-width:0.1px;" width="6%">'.$line->discount2.'</td>';
                    $tbl .= '<td align="right" style="border-left-width:0.1px;border-right-width:0.1px;" width="11%">'.$srp.'</td>';
                    $tbl .= '<td align="right" style="border-left-width:0.1px;border-right-width:0.1px;" width="15%">'.$total.'</td>';
                $tbl .= '</tr>';
            }
        }
        $tbl .=' </tbody>
        </table>';
        PDF::writeHTML($tbl, false, false, false, false, '');

        PDF::SetXY(10, 233.2);
        $tbl = '<table width="100%" cellspacing="0" cellpadding="2" border="0" style="font-size: 9px;">
        <tbody>
        <tr>
        <td width="85%">&nbsp;</td>
        <td width="15%" align="right" style="font-size: 10px"><strong>'.number_format(floor(($totalAmt*100))/100,2).'</strong></td>
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