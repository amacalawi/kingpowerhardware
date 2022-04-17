<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Item;
use App\Models\ItemInventory;
use App\Models\ItemTransaction;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseOrderLinePosting;
use App\Models\PurchaseOrderType;
use App\Models\PurchaseOrderPrintStart;
use App\Models\PaymentTerm;
use App\Models\UnitOfMeasurement;
use App\Models\User;
use Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\File;
use PDF;
// use App\Components\FlashMessages;
// use App\Helper\Helper;

class PurchaseOrderController extends Controller
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
        $suppliers = (new Supplier)->all_suppliers_selectpicker();
        $uoms = (new UnitOfMeasurement)->all_uom_selectpicker();
        $payment_terms = (new PaymentTerm)->all_payment_term_selectpicker();
        $items = (new Item)->all_item_selectpicker();
        $types = (new PurchaseOrderType)->all_po_type_selectpicker();
        return view('modules/purchase-order/manage')->with(compact('menus', 'uoms', 'types', 'items', 'branches', 'suppliers', 'payment_terms'));
    }

    public function store(Request $request)
    {   
        // $this->is_permitted(0);
        $timestamp = date('Y-m-d H:i:s');
        $poNo = (new PurchaseOrderPrintStart)->get_po_no($request->branch_id);

        $purchaseOrder = PurchaseOrder::create([
            'branch_id' => $request->branch_id,
            'supplier_id' => $request->supplier_id,
            'payment_terms_id' => $request->payment_terms_id,
            'purchase_order_type_id' => $request->purchase_order_type_id,
            'po_no' => $poNo,
            'contact_person' => $request->contact_person,
            'contact_no' => $request->contact_no,
            'due_date' => date('Y-m-d', strtotime($request->get('due_date'))),
            'delivery_place' => $request->address,
            'remarks' => $request->remarks,
            'created_at' => $timestamp,
            'created_by' => Auth::user()->id
        ]);

        if (!$purchaseOrder) {
            throw new NotFoundHttpException();
        }

        $this->audit_logs('purchase_orders', $purchaseOrder->id, 'has inserted a new purchase order.', PurchaseOrder::find($purchaseOrder->id), $timestamp, Auth::user()->id);
        $res = PurchaseOrderPrintStart::where('branch_id', $request->branch_id)->update(['print_start' => intval(str_replace('PO-','',$poNo))]);

        $data = array(
            'purchase_order_id' => $purchaseOrder->id,
            'po_no' => $poNo,
            'title' => 'Well done!',
            'text' => 'The purchase order has been successfully stored.',
            'type' => 'success',
            'class' => 'btn-brand'
        );

        echo json_encode( $data ); exit();
    }

    public function find(Request $request, $id)
    {    
        $purchaseOrder = PurchaseOrder::find($id);

        if(!$purchaseOrder) {
            throw new NotFoundHttpException();
        }

        $purchaseOrder = (object) array(
            'po_no' => $purchaseOrder->po_no,
            'purchase_order_id' => $purchaseOrder->id,
            'branch_id' => $purchaseOrder->branch_id,
            'supplier_id' => $purchaseOrder->supplier_id,
            'payment_terms_id' => $purchaseOrder->payment_terms_id,
            'purchase_order_type_id' => $purchaseOrder->purchase_order_type_id,
            'contact_person' => $purchaseOrder->contact_person,
            'address' => $purchaseOrder->delivery_place,
            'remarks' => $purchaseOrder->remarks,
            'total_amount' => $purchaseOrder->total_amount,
            'contact_no' => strval($purchaseOrder->contact_no)
        );

        return response()
        ->json([
            'status' => 'ok',
            'data' => $purchaseOrder
        ]);
    }

    public function update(Request $request, $id)
    {    
        // $this->is_permitted(2);
        $timestamp = date('Y-m-d H:i:s');
        $purchaseOrder = PurchaseOrder::find($id);

        if(!$purchaseOrder) {
            throw new NotFoundHttpException();
        }

        // $purchaseOrder->branch_id = $request->branch_id;
        $purchaseOrder->supplier_id = $request->supplier_id;
        $purchaseOrder->payment_terms_id = $request->payment_terms_id;
        $purchaseOrder->purchase_order_type_id = $request->purchase_order_type_id;
        $purchaseOrder->contact_person = $request->contact_person;
        $purchaseOrder->contact_no = $request->contact_no;
        $purchaseOrder->delivery_place = $request->address;
        $purchaseOrder->remarks = $request->remarks;
        $purchaseOrder->updated_at = $timestamp;
        $purchaseOrder->updated_by = Auth::user()->id;

        if ($purchaseOrder->update()) {
            $data = array(
                'title' => 'Well done!',
                'text' => 'The PurchaseOrder has been successfully modified.',
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
        $msg .= '<table class="table align-middle table-row-dashed fs-6 gy-5" id="purchaseOrderTable">';
        $msg .= '<thead>';
            $msg .= '<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">';
            $msg .= '<th class="w-10px pe-2">';
            $msg .= '<div class="form-check form-check-sm form-check-custom form-check-solid me-3">';
            $msg .= '<input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_customers_table .form-check-input" value="1" />';
            $msg .= '</div>';
            $msg .= '</th>';
            $msg .= '<th class="min-w-100px text-center">Branch&nbsp;&amp;&nbsp;Trans&nbsp;Date</th>';
            $msg .= '<th class="min-w-150px text-center">PO&nbsp;Type&nbsp;&amp;&nbsp;No</th>';
            $msg .= '<th class="min-w-150px text-center">Supplier</th>';
            $msg .= '<th class="min-w-100px text-center">Terms&nbsp;&amp;&nbsp;DueDate</th>';
            $msg .= '<th class="min-w-100px text-center">Contact&nbsp;Person</th>';
            $msg .= '<th class="min-w-100px text-right">Total&nbsp;Amount</th>';
            $msg .= '<th class="min-w-50px text-center">Status</th>';
            $msg .= '<th class="text-center">Last&nbsp;Modified</th>';
            $msg .= '<th class="text-center min-w-70px">Actions</th>';
            $msg .= '</tr>';
        $msg .= '</thead>';
        $msg .= '<tbody class="fw-bold text-gray-600">';
        
        $query = $this->get_line_items($per_page, $start_from, $keywords);
        $count = $this->get_page_count($keywords);
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
                if ($row->status == 'open') {
                    $status = '<span class="badge badge-status badge-light-dark">'.$row->status.'</span>';
                } else if ($row->status == 'prepared') {
                    $status = '<span class="badge badge-status badge-light-warning">'.$row->status.'</span>';
                } else if ($row->status == 'partial'){
                    $status = '<span class="badge badge-status badge-light-primary">'.$row->status.'</span>';
                } else {
                    $status = '<span class="badge badge-status badge-light-success">'.$row->status.'</span>';
                } 
                $msg .= '<tr data-row-amount="'.$row->total_amount.'" data-row-po="'.$row->po_no.'" data-row-id="'.$row->id.'">';
                $msg .= '<td>';
                $msg .= '<div class="form-check form-check-sm form-check-custom form-check-solid">';
                $msg .= '<input class="form-check-input" type="checkbox" value="'.$row->id.'" />';
                $msg .= '</div>';
                $msg .= '</td>';
                $msg .= '<td class="text-center">('.$row->branch.')<br/>'.$row->transaction_date.'</td>';
                $msg .= '<td class="text-center">('.$row->type.')<br/><strong>'.$row->po_no.'</strong></td>';
                $msg .= '<td class="text-center">'.$row->supplier.'</td>';
                $msg .= '<td class="text-center">('.$row->terms.')<br/>'.$row->due_date.'</td>';
                $msg .= '<td class="text-center">'.$row->contact_person.'</td>';
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
            $res = PurchaseOrder::select([
                'purchase_orders.created_at',
                'purchase_orders.updated_at',
                'purchase_orders.id',
                'purchase_orders.po_no as po_no',
                'purchase_orders.contact_person as contactperson',
                'purchase_orders.due_date as due_date',
                'suppliers.name as supplier',
                'payment_terms.name as payment_terms',
                'branches.name as branch',
                'purchase_orders.status as status',
                'purchase_orders.total_amount as total_amount',
                'purchase_orders_types.name as type'
            ])
            ->leftJoin('purchase_orders_types', function($join)
            {
                $join->on('purchase_orders_types.id', '=', 'purchase_orders.purchase_order_type_id');
            })
            ->leftJoin('suppliers', function($join)
            {
                $join->on('suppliers.id', '=', 'purchase_orders.supplier_id');
            })
            ->leftJoin('branches', function($join)
            {
                $join->on('branches.id', '=', 'purchase_orders.branch_id');
            })
            ->leftJoin('payment_terms', function($join)
            {
                $join->on('payment_terms.id', '=', 'purchase_orders.payment_terms_id');
            })
            ->whereIn('branches.id', 
                explode(',', trim((new User)->select(['assignment'])->where('id', $user)->first()->assignment))
            )
            ->where('purchase_orders.is_active', 1)
            ->where(function($q) use ($keywords) {
                $q->where('purchase_orders.po_no', 'like', '%' . $keywords . '%')
                  ->orWhere('purchase_orders.contact_person', 'like', '%' . $keywords . '%')
                  ->orWhere('purchase_orders.total_amount', 'like', '%' . $keywords . '%')
                  ->orWhere('suppliers.name', 'like', '%' . $keywords . '%')
                  ->orWhere('payment_terms.name', 'like', '%' . $keywords . '%')
                  ->orWhere('branches.name', 'like', '%' . $keywords . '%')
                  ->orWhere('purchase_orders_types.name', 'like', '%' . $keywords . '%');
            })
            ->skip($start_from)->take($limit)
            ->orderBy('purchase_orders.id', 'desc')
            ->get();
        } else {
            $res = PurchaseOrder::select([
                'purchase_orders.created_at',
                'purchase_orders.updated_at',
                'purchase_orders.id',
                'purchase_orders.po_no as po_no',
                'purchase_orders.contact_person as contactperson',
                'purchase_orders.due_date as due_date',
                'suppliers.name as supplier',
                'payment_terms.name as payment_terms',
                'branches.name as branch',
                'purchase_orders.status as status',
                'purchase_orders.total_amount as total_amount',
                'purchase_orders_types.name as type'
            ])
            ->leftJoin('purchase_orders_types', function($join)
            {
                $join->on('purchase_orders_types.id', '=', 'purchase_orders.purchase_order_type_id');
            })
            ->leftJoin('suppliers', function($join)
            {
                $join->on('suppliers.id', '=', 'purchase_orders.supplier_id');
            })
            ->leftJoin('branches', function($join)
            {
                $join->on('branches.id', '=', 'purchase_orders.branch_id');
            })
            ->leftJoin('payment_terms', function($join)
            {
                $join->on('payment_terms.id', '=', 'purchase_orders.payment_terms_id');
            })
            ->whereIn('branches.id', 
                explode(',', trim((new User)->select(['assignment'])->where('id', $user)->first()->assignment))
            )
            ->where('purchase_orders.is_active', 1)
            ->skip($start_from)->take($limit)
            ->orderBy('purchase_orders.id', 'desc')
            ->get();
        }

        return $res->map(function($pur) {
            return (object) [
                'id' => $pur->id,
                'po_no' => $pur->po_no,
                'contact_person' => $pur->contactperson,
                'due_date' => date('d-M-Y', strtotime($pur->due_date)),
                'supplier' => $pur->supplier,
                'terms' => $pur->payment_terms,
                'branch' => $pur->branch,
                'total_amount' => number_format(floor(($pur->total_amount*100))/100,2),
                'status' => $pur->status,
                'type' => $pur->type,
                'transaction_date' => date('d-M-Y', strtotime($pur->created_at)),
                'modified_at' => ($pur->updated_at !== NULL) ? date('d-M-Y', strtotime($pur->updated_at)).'<br/>'. date('h:i A', strtotime($pur->updated_at)) : date('d-M-Y', strtotime($pur->created_at)).'<br/>'. date('h:i A', strtotime($pur->created_at))
            ];
        });
    }

    public function get_page_count($keywords = '')
    {   
        $user = Auth::user()->id;
        if (!empty($keywords)) {
            $res = PurchaseOrder::select([
                'purchase_orders.created_at',
                'purchase_orders.updated_at',
                'purchase_orders.id',
                'purchase_orders.po_no as po_no',
                'purchase_orders.contact_person as contactperson',
                'purchase_orders.due_date as due_date',
                'suppliers.name as supplier',
                'payment_terms.name as payment_terms',
                'branches.name as branch',
                'purchase_orders.status as status',
                'purchase_orders.total_amount as total_amount',
                'purchase_orders_types.name as type'
            ])
            ->leftJoin('purchase_orders_types', function($join)
            {
                $join->on('purchase_orders_types.id', '=', 'purchase_orders.purchase_order_type_id');
            })
            ->leftJoin('suppliers', function($join)
            {
                $join->on('suppliers.id', '=', 'purchase_orders.supplier_id');
            })
            ->leftJoin('branches', function($join)
            {
                $join->on('branches.id', '=', 'purchase_orders.branch_id');
            })
            ->leftJoin('payment_terms', function($join)
            {
                $join->on('payment_terms.id', '=', 'purchase_orders.payment_terms_id');
            })
            ->whereIn('branches.id', 
                explode(',', trim((new User)->select(['assignment'])->where('id', $user)->first()->assignment))
            )
            ->where('purchase_orders.is_active', 1)
            ->where(function($q) use ($keywords) {
                $q->where('purchase_orders.po_no', 'like', '%' . $keywords . '%')
                  ->orWhere('purchase_orders.contact_person', 'like', '%' . $keywords . '%')
                  ->orWhere('purchase_orders.total_amount', 'like', '%' . $keywords . '%')
                  ->orWhere('suppliers.name', 'like', '%' . $keywords . '%')
                  ->orWhere('payment_terms.name', 'like', '%' . $keywords . '%')
                  ->orWhere('branches.name', 'like', '%' . $keywords . '%')
                  ->orWhere('purchase_orders_types.name', 'like', '%' . $keywords . '%');
            })
            ->orderBy('purchase_orders.id', 'desc')
            ->count();
        } else {
            $res = PurchaseOrder::select([
                'purchase_orders.created_at',
                'purchase_orders.updated_at',
                'purchase_orders.id',
                'purchase_orders.po_no as po_no',
                'purchase_orders.contact_person as contactperson',
                'purchase_orders.due_date as due_date',
                'suppliers.name as supplier',
                'payment_terms.name as payment_terms',
                'branches.name as branch',
                'purchase_orders.status as status',
                'purchase_orders.total_amount as total_amount',
                'purchase_orders_types.name as type'
            ])
            ->leftJoin('purchase_orders_types', function($join)
            {
                $join->on('purchase_orders_types.id', '=', 'purchase_orders.purchase_order_type_id');
            })
            ->leftJoin('suppliers', function($join)
            {
                $join->on('suppliers.id', '=', 'purchase_orders.supplier_id');
            })
            ->leftJoin('branches', function($join)
            {
                $join->on('branches.id', '=', 'purchase_orders.branch_id');
            })
            ->leftJoin('payment_terms', function($join)
            {
                $join->on('payment_terms.id', '=', 'purchase_orders.payment_terms_id');
            })
            ->whereIn('branches.id', 
                explode(',', trim((new User)->select(['assignment'])->where('id', $user)->first()->assignment))
            )
            ->where('purchase_orders.is_active', 1)
            ->orderBy('purchase_orders.id', 'desc')
            ->count();
        }

        return $res;
    }

    public function all_active_lines(Request $request)
    {   
        $keywords     = $request->get('keywords'); 
        $purchase_orderID   = $request->get('id');   
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
        $msg .= '<table class="table align-middle table-row-dashed fs-8 mt-5" id="PurchaseOrderLineTable">';
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
        
        $query = $this->get_purchase_order_line_items($per_page, $start_from, $keywords, $purchase_orderID);
        $count = $this->get_purchase_order_page_count($keywords, $purchase_orderID);
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

    public function get_purchase_order_line_items($limit, $start_from, $keywords = '', $purchase_orderID)
    {
        if (!empty($keywords)) {
            $res = PurchaseOrderLine::select([
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
            ->where([
                'purchase_orders_lines.purchase_order_id' => $purchase_orderID, 
                'purchase_orders_lines.is_active' => 1
            ])
            ->where(function($q) use ($keywords) {
                $q->where('purchase_orders_lines.srp', 'like', '%' . $keywords . '%')
                  ->orWhere('purchase_orders_lines.uom', 'like', '%' . $keywords . '%')
                  ->orWhere('purchase_orders_lines.quantity', 'like', '%' . $keywords . '%')
                  ->orWhere('purchase_orders_lines.total_amount', 'like', '%' . $keywords . '%')
                  ->orWhere('purchase_orders_lines.posted_quantity', 'like', '%' . $keywords . '%')
                  ->orWhere('unit_of_measurements.code', 'like', '%' . $keywords . '%')
                  ->orWhere('items.code', 'like', '%' . $keywords . '%')
                  ->orWhere('items.name', 'like', '%' . $keywords . '%')
                  ->orWhere('items.description', 'like', '%' . $keywords . '%');
            })
            ->skip($start_from)->take($limit)
            ->orderBy('purchase_orders_lines.id', 'asc')
            ->get();
        } else {
            $res = PurchaseOrderLine::select([
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
            ->where([
                'purchase_orders_lines.purchase_order_id' => $purchase_orderID, 
                'purchase_orders_lines.is_active' => 1
            ])
            ->skip($start_from)->take($limit)
            ->orderBy('purchase_orders_lines.id', 'asc')
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
                'posted_quantity' => $del->posted_quantity,
                'modified_at' => ($del->updated_at !== NULL) ? date('d-M-Y', strtotime($del->updated_at)).'<br/>'. date('h:i A', strtotime($del->updated_at)) : date('d-M-Y', strtotime($del->created_at)).'<br/>'. date('h:i A', strtotime($del->created_at))
            ];
        });
    }

    public function get_purchase_order_page_count($keywords = '', $purchase_orderID)
    {
        if (!empty($keywords)) {
            $res = PurchaseOrderLine::select([
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
            ->where([
                'purchase_orders_lines.purchase_order_id' => $purchase_orderID, 
                'purchase_orders_lines.is_active' => 1
            ])
            ->where(function($q) use ($keywords) {
                $q->where('purchase_orders_lines.srp', 'like', '%' . $keywords . '%')
                  ->orWhere('purchase_orders_lines.uom', 'like', '%' . $keywords . '%')
                  ->orWhere('purchase_orders_lines.quantity', 'like', '%' . $keywords . '%')
                  ->orWhere('purchase_orders_lines.total_amount', 'like', '%' . $keywords . '%')
                  ->orWhere('purchase_orders_lines.posted_quantity', 'like', '%' . $keywords . '%')
                  ->orWhere('unit_of_measurements.code', 'like', '%' . $keywords . '%')
                  ->orWhere('items.name', 'like', '%' . $keywords . '%')
                  ->orWhere('items.code', 'like', '%' . $keywords . '%')
                  ->orWhere('items.description', 'like', '%' . $keywords . '%');
            })
            ->orderBy('purchase_orders_lines.id', 'asc')
            ->count();
        } else {
            $res = PurchaseOrderLine::select([
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
            ->where([
                'purchase_orders_lines.purchase_order_id' => $purchase_orderID, 
                'purchase_orders_lines.is_active' => 1
            ])
            ->orderBy('purchase_orders_lines.id', 'asc')
            ->count();
        }

        return $res;
    }

    public function get_supplier_info(Request $request, $supplier)
    {
        $res = (new Supplier)->select([
            'suppliers.contact_no',
            'suppliers.address',
            'suppliers.contact_person',
            'suppliers.payment_terms_id',
            'payment_terms.code'
        ])->leftJoin('payment_terms', function($join){
            $join->on('payment_terms.id', '=', 'suppliers.payment_terms_id');
        })->where('suppliers.id', $supplier)->first();

        $date = date('Y-m-d');
        $data = array(
            'contact_no' => $res->contact_no,
            'address' => $res->address,
            'contact_person' => $res->contact_person,
            'payment_terms_id' => $res->payment_terms_id,
            'due_date' => date('Y-m-d', strtotime($date. ' + '.$res->code.' days'))
        );

        echo json_encode( $data ); exit();
    }

    public function get_po_no(Request $request, $branch)
    {
        $poNo = (new PurchaseOrderPrintStart)->get_po_no($branch);
        return $poNo;
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
        
        $srp = 0;
        $res = (new PurchaseOrderLine)->where('item_id', $itemID)->get();
        if ($res->count() > 0) {
            $srp = $res->last()->srp;
        }
        $data = array(
            'srp' => $srp,
            'uom_id' => $item->uom->id
        );

        echo json_encode( $data ); exit();
    }

    public function store_line_item(Request $request, $purchaseOrderID)
    {   
        // $this->is_permitted(0);
        $timestamp = date('Y-m-d H:i:s');

        $purchaseOrderLine = PurchaseOrderLine::create([
            'purchase_order_id' => $purchaseOrderID,
            'item_id' => $request->item_id,
            'uom_id' => $request->uom_id,
            'srp' => $request->srp,
            'quantity' => $request->qty,
            'total_amount' => $request->get('total_amount'),
            'created_at' => $timestamp,
            'created_by' => Auth::user()->id
        ]);

        if (!$purchaseOrderLine) {
            throw new NotFoundHttpException();
        }

        $this->audit_logs('purchase_orders_lines', $purchaseOrderLine->id, 'has inserted a new purchase order line.', PurchaseOrderLine::find($purchaseOrderLine->id), $timestamp, Auth::user()->id);

        $result = PurchaseOrderLine::where(['purchase_order_id' => $purchaseOrderID, 'is_active' => 1])->get();
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
        
        $purchaseOrder = PurchaseOrder::where([
            'id' => $purchaseOrderID,
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

    public function find_line_item(Request $request, $id)
    {    
        $res = PurchaseOrderLine::with([
            'item' =>  function($q) { 
                $q->select(['id', 'code', 'name']);
            },
            'purchase_order' =>  function($q) { 
                $q->select(['id', 'branch_id', 'supplier_id']);
            }
        ])
        ->where(['id' => $id])
        ->first();

        $purchaseOrder = (object) array(
            'purchase_order_line_id' => $id,
            'purchase_order_idx' => $res->purchase_order_id,
            'item_name' => $res->item->code.' - '.$res->item->name,
            'available_qty' => (new ItemInventory)->find_item_inventory($res->purchase_order->branch_id, $res->item->id),
            'for_posting' => (floatval($res->quantity) - floatval($res->posted_quantity))
        );

        return response()
        ->json([
            'status' => 'ok',
            'data' => $purchaseOrder
        ]);
    }

    public function find_line(Request $request, $id)
    {    
        $res = PurchaseOrderLine::where(['id' => $id])->first();

        $poLines = (object) array(
            'purchase_order_line_id' => $id,
            'item_id' => $res->item_id,
            'uom_id' => $res->uom_id,
            'qty' => $res->quantity,
            'srp' => $res->srp,
            'total_amount' => $res->total_amount
        );

        return response()
        ->json([
            'status' => 'ok',
            'data' => $poLines
        ]);
    }

    public function remove_line_item(Request $request, $id)
    {   
        // $this->is_permitted(3);
        $timestamp = date('Y-m-d H:i:s');
        $PurchaseOrderLine = PurchaseOrderLine::where([
            'id' => $id,
        ])
        ->update([
            'updated_at' => $timestamp,
            'updated_by' => Auth::user()->id,
            'is_active' => 0
        ]);
        $this->audit_logs('purchase_orders_lines', $id, 'has removed a po line.', PurchaseOrderLine::find($id), $timestamp, Auth::user()->id);
        
        $poID = PurchaseOrderLine::where('id', $id)->first()->purchase_order_id;
        $result = PurchaseOrderLine::where(['purchase_order_id' => $poID, 'is_active' => 1])->get();
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
        
        $purchaseOrder = PurchaseOrder::where([
            'id' => $poID,
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
        $PurchaseOrderLine = PurchaseOrderLine::find($id);

        if(!$PurchaseOrderLine) {
            throw new NotFoundHttpException();
        }
        
        $purchaseOrderID = $PurchaseOrderLine->purchase_order_id;
        $PurchaseOrderLine->item_id = $request->item_id;
        $PurchaseOrderLine->uom_id = $request->uom_id;
        $PurchaseOrderLine->srp = $request->srp;
        $PurchaseOrderLine->quantity = $request->qty;
        $PurchaseOrderLine->total_amount = $request->get('total_amount');
        $PurchaseOrderLine->updated_at = $timestamp;
        $PurchaseOrderLine->updated_by = Auth::user()->id;

        if ($PurchaseOrderLine->update()) {
            $this->audit_logs('purchase_orders_lines', $id, 'has modified a po line.', PurchaseOrderLine::find($id), $timestamp, Auth::user()->id);
            $result = PurchaseOrderLine::where(['purchase_order_id' => $purchaseOrderID, 'is_active' => 1])->get();
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
            
            $purchaseOrder = PurchaseOrder::where([
                'id' => $purchaseOrderID,
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

    public function post_line_item(Request $request, $id)
    {   
        $timestamp = date('Y-m-d H:i:s');
        $PurchaseOrderLine = PurchaseOrderLine::find($id);

        if(!$PurchaseOrderLine) {
            throw new NotFoundHttpException();
        }

        $posting = PurchaseOrderLinePosting::create([
            'purchase_order_line_id' => $id,
            'quantity' => $request->qty_to_post,
            'date_received' => date('Y-m-d', strtotime($request->date_received)),
            'created_at' => $timestamp,
            'created_by' => Auth::user()->id
        ]);

        if (!$posting) {
            throw new NotFoundHttpException();
        }
        
        $PurchaseOrder = PurchaseOrder::where('id', $PurchaseOrderLine->purchase_order_id)->first();
        $inventory = ItemInventory::where(['item_id' => $PurchaseOrderLine->item_id, 'branch_id' => $PurchaseOrder->branch_id, 'is_active' => 1])->get();
        if ($inventory->count() > 0) {
            $inventory = $inventory->first();
            $quantity = floatval($request->qty_to_post); 
            $qtyLeft   = floatval($inventory->quantity) + floatval($quantity);
            ItemInventory::where('id', $inventory->id)->update(['quantity' => $qtyLeft]);

            $transaction = ItemTransaction::create([
                'item_id' => $PurchaseOrderLine->item_id,
                'branch_id' => $PurchaseOrder->branch_id,
                'transaction' => 'Receiving',
                'based_quantity' => $inventory->quantity,
                'issued_quantity' => $quantity,
                'left_quantity' => $qtyLeft,
                'srp' => $PurchaseOrderLine->srp,
                'total_amount' => $PurchaseOrderLine->total_amount,
                'issued_by' => Auth::user()->id,
                'received_by' => Auth::user()->id,
                'remarks' => 'Item receiving from '.$PurchaseOrder->po_no,
                'created_at' => $timestamp,
                'created_by' => Auth::user()->id
            ]);
    
            if (!$transaction) {
                throw new NotFoundHttpException();
            }
    
            $this->audit_logs('items_transactions', $transaction->id, 'has inserted a new item transaction.', ItemTransaction::find($transaction->id), $timestamp, Auth::user()->id);

            $postedQuantity = floatval($PurchaseOrderLine->posted_quantity) + floatval($quantity);
            $PurchaseOrderLine->posted_quantity = $postedQuantity;
            if (floatval($postedQuantity) == floatval($PurchaseOrderLine->quantity)) {
                $PurchaseOrderLine->status = 'posted';
            }
            $PurchaseOrderLine->updated_at = $timestamp;
            $PurchaseOrderLine->updated_by = Auth::user()->id;

            if ($PurchaseOrderLine->update()) {
                $this->audit_logs('purchase_orders_lines', $id, 'has posted a quantity('.$quantity.') on purchase order line.', PurchaseOrderLine::find($id), $timestamp, Auth::user()->id);
            }

            $result = PurchaseOrderLine::where(['purchase_order_id' => $PurchaseOrder->id, 'is_active' => 1])->get();
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
            
            $PurchaseOrder = PurchaseOrder::where([
                'id' => $PurchaseOrder->id,
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

    public function export(Request $request)
    {   
        $fileName = 'purchase_orders_'.time().'.csv';

        $purchase_orders = PurchaseOrder::select([
            'purchase_orders.created_at as transaction_date',
            'branches.name as branch',
            'suppliers.name as supplier',
            'purchase_orders.po_no as po_no',
            'purchase_orders_types.name as po_type',
            'payment_terms.name as terms',
            'purchase_orders.due_date as due',
            'purchase_orders.total_amount as total',
            'purchase_orders.contact_person as contact',
            'purchase_orders.status as status'
        ])
        ->join('suppliers', function($join)
        {
            $join->on('suppliers.id', '=', 'purchase_orders.supplier_id');
        })
        ->join('branches', function($join)
        {
            $join->on('branches.id', '=', 'purchase_orders.branch_id');
        })
        ->join('payment_terms', function($join)
        {
            $join->on('payment_terms.id', '=', 'purchase_orders.payment_terms_id');
        })
        ->join('purchase_orders_types', function($join)
        {
            $join->on('purchase_orders_types.id', '=', 'purchase_orders.purchase_order_type_id');
        })
        ->where('purchase_orders.is_active', 1)
        ->orderBy('purchase_orders.id', 'asc')
        ->get();

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('Branch', 'Transaction Date', 'Type', 'PO No', 'Supplier', 'Payment Terms', 'Due Date', 'Contact Person', 'Total Amount', 'Status');

        $callback = function() use($purchase_orders, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($purchase_orders as $purchase_order) {
                $row['transaction'] = $purchase_order->transaction_date;
                $row['type']        = $purchase_order->po_type;
                $row['po_no']       = $purchase_order->po_no;
                $row['branch']      = $purchase_order->branch;
                $row['supplier']    = $purchase_order->supplier;
                $row['terms']       = $purchase_order->terms;
                $row['due']         = $purchase_order->due;
                $row['contact']     = $purchase_order->contact;
                $row['total']       = $purchase_order->total;
                $row['status']      = $purchase_order->status;
                fputcsv($file, array($row['branch'], $row['transaction'], $row['type'], $row['po_no'], $row['supplier'], $row['terms'], $row['due'], $row['contact'], $row['total'], $row['status']));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function preview(Request $request)
    {   
        $purchase = PurchaseOrder::select([
            'purchase_orders.created_at as purchaseDate',
            'purchase_orders.updated_at',
            'purchase_orders.id as poID',
            'purchase_orders.po_no as poNo',
            'purchase_orders.delivery_place as deliveryPlace',
            'purchase_orders.contact_person as contactPerson',
            'purchase_orders.contact_no as contactNo',
            'purchase_orders.due_date as due_date',
            'suppliers.name as supplier',
            'payment_terms.name as payment_terms',
            'branches.name as branch',
            'purchase_orders.status as status',
            'purchase_orders.total_amount as total_amount',
            'purchase_orders_types.name as type',
            'purchase_orders.created_by as purchaseCreated',
            'branches.dr_header as drHeader',
            'branches.dr_address as drAddress'
        ])
        ->leftJoin('purchase_orders_types', function($join)
        {
            $join->on('purchase_orders_types.id', '=', 'purchase_orders.purchase_order_type_id');
        })
        ->leftJoin('suppliers', function($join)
        {
            $join->on('suppliers.id', '=', 'purchase_orders.supplier_id');
        })
        ->leftJoin('branches', function($join)
        {
            $join->on('branches.id', '=', 'purchase_orders.branch_id');
        })
        ->leftJoin('payment_terms', function($join)
        {
            $join->on('payment_terms.id', '=', 'purchase_orders.payment_terms_id');
        })
        ->where([
            'purchase_orders.is_active' => 1, 
            'purchase_orders.po_no' => $request->get('po_no'),
            'branches.id' => $request->get('branch')
        ])
        ->first();

        $lineItems = PurchaseOrderLine::select([
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
        ->where([
            'purchase_orders_lines.purchase_order_id' => $purchase->poID, 
            'purchase_orders_lines.is_active' => 1
        ])
        ->orderBy('purchase_orders_lines.id', 'asc')
        ->get();

        PDF::SetMargins(10, 0, 10, false);
        PDF::SetAutoPageBreak(true, 0);
        PDF::SetTitle('Purchase Order ('.$request->get('po_no').')');
        PDF::AddPage('P', 'LETTER');
        $tbl = '<div style="font-size:10pt">&nbsp;</div>';
        $tbl .= '<table id="heaer-table" width="100%" cellspacing="0" cellpadding="0" border="0" style="font-size: 9px;">
            <thead>
                <tr>
                    <td align="center"><p style="font-size: 22px">'.$purchase->drHeader.'</p></td>
                </tr>
                <tr>
                    <td align="center"><p style="font-size: 9px">'.$purchase->drAddress.'</p></td>
                </tr>
                <tr>
                    <td align="center" style="font-size: 11px">PURCHASE ORDER</td>
                </tr>
            </thead>
            </table>';
        PDF::writeHTML($tbl, false, false, false, false, '');

        $tbl = '<div style="font-size:15pt">&nbsp;</div>';
        $tbl .= '<table>';
        $tbl .= '<tbody>';
        $tbl .= '<tr>';
        $tbl .= '<td width="64%">';
        $tbl .= '<table width="100%" cellspacing="0" cellpadding="1" border="0" style="font-size: 9px;">
        <thead>
            <tr>
                <td align="right" width="20%"><strong>SUPPLIER:&nbsp;&nbsp;</strong></td>
                <td align="left" width="78%" style="border-bottom-width:0.1px;">'.$purchase->supplier.'</td>
            </tr>
            <tr>
                <td align="right" width="20%"><div style="font-size:5pt">&nbsp;</div><strong>CONTACT NO:&nbsp;&nbsp;</strong></td>
                <td align="left" width="78%" style="border-bottom-width:0.1px;"><div style="font-size:5pt">&nbsp;</div>'.$purchase->contactPerson.' ('.$purchase->contactNo.')</td>
            </tr>
            <tr>
                <td align="right" width="20%"><div style="font-size:5pt">&nbsp;</div><strong>ADDRESS:&nbsp;&nbsp;</strong></td>
                <td align="left" width="78%" style="height:39px;border-bottom-width:0.1px;"><div style="font-size:5pt">&nbsp;</div>'.$purchase->deliveryPlace.'</td>
            </tr>
        </thead>
        </table>';
        $tbl .= '</td>';
        $tbl .= '<td width="36%">';
        $tbl .= '<table width="100%" cellspacing="0" cellpadding="1" border="0" style="font-size: 9px;">
        <thead>
            <tr>
                <td align="right" width="25%"><strong>PO#:&nbsp;&nbsp;</strong></td>
                <td align="left" width="75%" style="border-bottom-width:0.1px;">'.$purchase->poNo.' ('.$purchase->type.')</td>
            </tr>
            <tr>
                <td align="right" width="25%"><div style="font-size:5pt">&nbsp;</div><strong>DATE:&nbsp;&nbsp;</strong></td>
                <td align="left" width="75%" style="border-bottom-width:0.1px;"><div style="font-size:5pt">&nbsp;</div>'.date('d-M-Y', strtotime($purchase->purchaseDate)).'</td>
            </tr>
            <tr>
                <td align="right" width="25%"><div style="font-size:5pt">&nbsp;</div><strong>TERMS:&nbsp;&nbsp;</strong></td>
                <td align="left" width="75%" style="border-bottom-width:0.1px;"><div style="font-size:5pt">&nbsp;</div>'.$purchase->payment_terms.' ('.date('d-M-Y', strtotime($purchase->due_date)).')</td>
            </tr>
            <tr>
                <td align="right" width="25%"><div style="font-size:5pt">&nbsp;</div><strong>BRANCH:&nbsp;&nbsp;</strong></td>
                <td align="left" width="75%" style="border-bottom-width:0.1px;"><div style="font-size:5pt">&nbsp;</div>'.$purchase->branch.'</td>
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
                <td align="center" width="25%" style="border-bottom-width:0.1px;">'.ucwords((new User)->where('id', $purchase->purchaseCreated)->first()->name).'</td>
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

            if ($request->get('document') == 'preparation') {
                $tbl .= '<tr>';
                $tbl .= '<td align="center" width="12%">'.$line->quantity.'</td>';
                $tbl .= '<td align="center" width="7%">'.$line->uom.'</td>';
                $tbl .= '<td align="left" width="45%">'.$line->itemCode.' - '.$line->itemName.'</td>';
                $tbl .= '<td align="right" width="15%">'.$srp.'</td>';
                $tbl .= '<td align="right" width="21%">'.$total.'</td>';
                $tbl .= '</tr>';
            } else {
                if (floatval($line->posted_quantity) > 0) {
                    $tbl .= '<tr>';
                    $tbl .= '<td align="center" width="12%">'.$line->posted_quantity.'</td>';
                    $tbl .= '<td align="center" width="7%">'.$line->uom.'</td>';
                    $tbl .= '<td align="left" width="45%">'.$line->itemCode.' - '.$line->itemName.'</td>';
                    $tbl .= '<td align="right" width="15%">'.$srp.'</td>';
                    $tbl .= '<td align="right" width="21%">'.$total.'</td>';
                    $tbl .= '</tr>';
                }
            }
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