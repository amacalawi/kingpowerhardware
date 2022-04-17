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
use App\Models\Billing;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\PaymentTerm;
use Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\File;
use App\Exports\CollectionReportExport;
use Maatwebsite\Excel\Facades\Excel;
// use App\Components\FlashMessages;
// use App\Helper\Helper;

class CollectionReportsController extends Controller
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
        $types = (new PaymentType)->all_payment_type_selectpicker();
        $customers = (new Customer)->all_customer_selectpicker();
        $statuses = ['' => 'Select a status', 'draft' => 'Draft', 'posted' => 'Posted'];
        $orderby = ['asc' => 'Ascending', 'desc' => 'Descending'];
        return view('modules/reports/collection-reports/manage')->with(compact('customers', 'orderby', 'statuses', 'menus', 'agents', 'branches', 'types'));
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
        $per_page     = 10 == -1 ? 0 : 10;
        $page         = $cur_page !== null ? $cur_page : 1;
        $start_from   = ($page-1) * $per_page;

        $previous_btn = true;
        $next_btn = true;
        $value = 0;
        $first_btn = true;
        $pagess = 0;
        $last_btn = true;

        $query = $this->get_line_items($per_page, $start_from, $dateFrom, $dateTo, $type, $branch, $customer, $agent, $status, $orderby, $keywords);
        $count = $this->get_page_count($dateFrom, $dateTo, $type, $branch, $customer, $agent, $status, $orderby, $keywords);
        $sumAmt = $this->get_page_amount($dateFrom, $dateTo, $type, $branch, $customer, $agent, $status, $orderby, $keywords);
        $no_of_paginations = ceil($count / $per_page);
        $assets = url('assets/media/illustrations/work.png');

        $msg = "";
        
        $msg .= '<div class="table-responsive">';
        $msg .= '<table data-row-count="'.$count.'" class="table align-middle table-row-dashed fs-8 gy-3" id="collectionReportTable">';
        $msg .= '<thead>';
        $msg .= '<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">';
        $msg .= '<th class="text-center">Invoice&nbsp;Date</th>';
        $msg .= '<th class="text-center">Invoice&nbsp;No</th>';
        $msg .= '<th class="text-center">Branch</th>';
        $msg .= '<th class="">Customer</th>';
        $msg .= '<th class="">Agent</th>';
        $msg .= '<th class="text-center">Payment&nbsp;Type</th>';
        $msg .= '<th class="text-center">Bank&nbsp;Name</th>';
        $msg .= '<th class="text-center">Account&nbsp;No</th>';
        $msg .= '<th class="text-center">Account&nbsp;Name</th>';
        $msg .= '<th class="text-center">Cheque&nbsp;No</th>';
        $msg .= '<th class="text-center">Cheque&nbsp;Date</th>';
        $msg .= '<th class="text-center">Status</th>';
        $msg .= '<th class="text-right">Amount</th>';
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
                $msg .= '<tr data-row-id="'.$row->id.'" data-row-inv="'.$row->invoiceNo.'">';
                $msg .= '<td class="text-center">'.$row->invoiceDate.'</td>';
                $msg .= '<td class="text-center">'.$row->invoiceNo.'</td>';
                $msg .= '<td class="text-center">'.$row->branch.'</td>';
                $msg .= '<td>'.$row->customer.'</td>';
                $msg .= '<td>'.$row->agent.'</td>';
                $msg .= '<td class="text-center">'.$row->type.'</td>';
                $msg .= '<td class="text-center">'.$row->bankName.'</td>';
                $msg .= '<td class="text-center">'.$row->acctNo.'</td>';
                $msg .= '<td class="text-center">'.$row->acctName.'</td>';
                $msg .= '<td class="text-center">'.$row->chequeNo.'</td>';
                $msg .= '<td class="text-center">'.$row->chequeDate.'</td>';
                $msg .= '<td class="text-center">'.$row->status.'</td>';
                $msg .= '<td class="text-right">'.$row->amount.'</td>';
                $msg .= '</tr>';
            }
        }
        $msg .= '</tbody>';
        $msg .= '<tfoot>';
        $msg .= '<tr class="fs-5">';
        $msg .= '<td class="text-right" colspan="12"><strong>TOTAL AMOUNT:</strong></td>';
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

    public function get_line_items($limit, $start_from, $dateFrom = '', $dateTo = '', $type, $branch = '', $customer = '', $agent = '', $status = '', $orderby, $keywords = '')
    {   
        $dateFrom2 = date('Y-m-d', strtotime($dateFrom)).' 00:00:01';
        $dateTo2   = date('Y-m-d', strtotime($dateTo)).' 23:59:59';

        $res = Payment::select([
            'payments.id as id',
            'branches.name as branch',
            'customers.name as customer',
            'users.name as agent',
            'billing.invoice_no as invoiceNo',
            'billing.invoice_date as invoiceDate',
            'payments.status as status',
            'payments.bank_name as bankName',
            'payments.bank_no as acctNo',
            'payments.bank_account as acctName',
            'payments.amount as amount',
            'payment_types.name as type',
            'payments.cheque_no as chequeNo',
            'payments.cheque_date as chequeDate'
        ])
        ->leftJoin('payment_types', function($join)
        {
            $join->on('payment_types.id', '=', 'payments.payment_type_id');
        })
        ->leftJoin('billing', function($join)
        {
            $join->on('billing.id', '=', 'payments.billing_id');
        })
        ->leftJoin('users', function($join)
        {
            $join->on('users.id', '=', 'billing.agent_id');
        })
        ->leftJoin('branches', function($join)
        {
            $join->on('branches.id', '=', 'billing.branch_id');
        })
        ->leftJoin('customers', function($join)
        {
            $join->on('customers.id', '=', 'billing.customer_id');
        })
        ->where(function($q) use ($keywords) {
            if (!empty($keywords)) {
                $q->where('billing.invoice_no', 'like', '%' . $keywords . '%')
                ->orWhere('billing.invoice_date', 'like', '%' . $keywords . '%')
                ->orWhere('customers.name', 'like', '%' . $keywords . '%')
                ->orWhere('branches.name', 'like', '%' . $keywords . '%')
                ->orWhere('users.name', 'like', '%' . $keywords . '%')
                ->orWhere('payments.status', 'like', '%' . $keywords . '%')
                ->orWhere('payments.bank_name', 'like', '%' . $keywords . '%')
                ->orWhere('payments.bank_no', 'like', '%' . $keywords . '%')
                ->orWhere('payments.bank_account', 'like', '%' . $keywords . '%')
                ->orWhere('payments.cheque_no', 'like', '%' . $keywords . '%')
                ->orWhere('payments.cheque_date', 'like', '%' . $keywords . '%')
                ->orWhere('payments.status', 'like', '%' . $keywords . '%')
                ->orWhere('payments.amount', 'like', '%' . $keywords . '%')
                ->orWhere('payment_types.name', 'like', '%' . $keywords . '%');
            }
        })
        ->where(function($q) use ($dateFrom, $dateTo, $dateFrom2, $dateTo2) {
            if (!empty($dateFrom) && !empty($dateTo)) {
                $q->where('billing.invoice_date', '>=', $dateFrom2)
                    ->where('billing.invoice_date', '<=', $dateTo2);
            } else if (!empty($dateFrom) && empty($dateTo)) {
                $q->where('billing.invoice_date', '=', $dateFrom);
            } else if (empty($dateFrom) && !empty($dateTo)) {
                $q->where('billing.invoice_date', '=', $dateTo);
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
                $q->where("payments.status", $status);
            }
        })
        ->where(function($q) use ($type){
            if ($type != '') {
                $q->where('payment_types.id', '=',  $type);
            }
        })
        ->where('payments.is_active', 1)
        ->skip($start_from)->take($limit)
        ->orderBy('payments.id', $orderby)
        ->get();

        return $res->map(function($payment) {
            return (object) [
                'id' => $payment->id,
                'invoiceDate' => date('d-M-Y', strtotime($payment->invoiceDate)),
                'invoiceNo' => $payment->invoiceNo,
                'status' => $payment->status,
                'agent' => $payment->agent,
                'branch' => $payment->branch,
                'customer' => $payment->customer,
                'bankName' => $payment->bankName,
                'acctNo' => $payment->acctNo,
                'acctName' => $payment->acctName,
                'chequeNo' => $payment->chequeNo,
                'chequeDate' => $payment->chequeDate ? date('d-M-Y', strtotime($payment->chequeDate)) : '',
                'amount' =>  number_format(floor(($payment->amount*100))/100,2),
                'type' => $payment->type,
            ];
        });
    }

    public function get_page_count($dateFrom, $dateTo, $type, $branch, $customer, $agent, $status, $orderby, $keywords= '')
    {
        $dateFrom2 = date('Y-m-d', strtotime($dateFrom)).' 00:00:01';
        $dateTo2   = date('Y-m-d', strtotime($dateTo)).'23:59:59';
        $res = Payment::select([
            'payments.id as id',
            'branches.name as branch',
            'customers.name as customer',
            'users.name as agent',
            'billing.invoice_no as invoiceNo',
            'billing.invoice_date as invoiceDate',
            'payments.status as status',
            'payments.bank_name as bankName',
            'payments.bank_no as acctNo',
            'payments.bank_account as acctName',
            'payments.amount as amount',
            'payment_types.name as type',
            'payments.cheque_no as chequeNo',
            'payments.cheque_date as chequeDate'
        ])
        ->leftJoin('payment_types', function($join)
        {
            $join->on('payment_types.id', '=', 'payments.payment_type_id');
        })
        ->leftJoin('billing', function($join)
        {
            $join->on('billing.id', '=', 'payments.billing_id');
        })
        ->leftJoin('users', function($join)
        {
            $join->on('users.id', '=', 'billing.agent_id');
        })
        ->leftJoin('branches', function($join)
        {
            $join->on('branches.id', '=', 'billing.branch_id');
        })
        ->leftJoin('customers', function($join)
        {
            $join->on('customers.id', '=', 'billing.customer_id');
        })
        ->where(function($q) use ($keywords) {
            if (!empty($keywords)) {
                $q->where('billing.invoice_no', 'like', '%' . $keywords . '%')
                ->orWhere('billing.invoice_date', 'like', '%' . $keywords . '%')
                ->orWhere('customers.name', 'like', '%' . $keywords . '%')
                ->orWhere('branches.name', 'like', '%' . $keywords . '%')
                ->orWhere('users.name', 'like', '%' . $keywords . '%')
                ->orWhere('payments.status', 'like', '%' . $keywords . '%')
                ->orWhere('payments.bank_name', 'like', '%' . $keywords . '%')
                ->orWhere('payments.bank_no', 'like', '%' . $keywords . '%')
                ->orWhere('payments.bank_account', 'like', '%' . $keywords . '%')
                ->orWhere('payments.cheque_no', 'like', '%' . $keywords . '%')
                ->orWhere('payments.cheque_date', 'like', '%' . $keywords . '%')
                ->orWhere('payments.status', 'like', '%' . $keywords . '%')
                ->orWhere('payments.amount', 'like', '%' . $keywords . '%')
                ->orWhere('payment_types.name', 'like', '%' . $keywords . '%');
            }
        })
        ->where(function($q) use ($dateFrom, $dateTo, $dateFrom2, $dateTo2) {
            if (!empty($dateFrom) && !empty($dateTo)) {
                $q->where('billing.invoice_date', '>=', $dateFrom2)
                    ->where('billing.invoice_date', '<=', $dateTo2);
            } else if (!empty($dateFrom) && empty($dateTo)) {
                $q->where('billing.invoice_date', '=', $dateFrom);
            } else if (empty($dateFrom) && !empty($dateTo)) {
                $q->where('billing.invoice_date', '=', $dateTo);
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
                $q->where("payments.status", $status);
            }
        })
        ->where(function($q) use ($type){
            if ($type != '') {
                $q->where('payment_types.id', '=',  $type);
            }
        })
        ->where('payments.is_active', 1)
        ->orderBy('payments.id', $orderby)
        ->count();

        return $res;
    }

    public function get_page_amount($dateFrom, $dateTo, $type, $branch, $customer, $agent, $status, $orderby, $keywords= '')
    {
        $dateFrom2 = date('Y-m-d', strtotime($dateFrom)).' 00:00:01';
        $dateTo2   = date('Y-m-d', strtotime($dateTo)).'23:59:59';
        $res = Payment::select([
            'payments.id as id',
            'branches.name as branch',
            'customers.name as customer',
            'users.name as agent',
            'billing.invoice_no as invoiceNo',
            'billing.invoice_date as invoiceDate',
            'payments.status as status',
            'payments.bank_name as bankName',
            'payments.bank_no as acctNo',
            'payments.bank_account as acctName',
            'payments.amount as amount',
            'payment_types.name as type',
            'payments.cheque_no as chequeNo',
            'payments.cheque_date as chequeDate'
        ])
        ->leftJoin('payment_types', function($join)
        {
            $join->on('payment_types.id', '=', 'payments.payment_type_id');
        })
        ->leftJoin('billing', function($join)
        {
            $join->on('billing.id', '=', 'payments.billing_id');
        })
        ->leftJoin('users', function($join)
        {
            $join->on('users.id', '=', 'billing.agent_id');
        })
        ->leftJoin('branches', function($join)
        {
            $join->on('branches.id', '=', 'billing.branch_id');
        })
        ->leftJoin('customers', function($join)
        {
            $join->on('customers.id', '=', 'billing.customer_id');
        })
        ->where(function($q) use ($keywords) {
            if (!empty($keywords)) {
                $q->where('billing.invoice_no', 'like', '%' . $keywords . '%')
                ->orWhere('billing.invoice_date', 'like', '%' . $keywords . '%')
                ->orWhere('customers.name', 'like', '%' . $keywords . '%')
                ->orWhere('branches.name', 'like', '%' . $keywords . '%')
                ->orWhere('users.name', 'like', '%' . $keywords . '%')
                ->orWhere('payments.status', 'like', '%' . $keywords . '%')
                ->orWhere('payments.bank_name', 'like', '%' . $keywords . '%')
                ->orWhere('payments.bank_no', 'like', '%' . $keywords . '%')
                ->orWhere('payments.bank_account', 'like', '%' . $keywords . '%')
                ->orWhere('payments.cheque_no', 'like', '%' . $keywords . '%')
                ->orWhere('payments.cheque_date', 'like', '%' . $keywords . '%')
                ->orWhere('payments.status', 'like', '%' . $keywords . '%')
                ->orWhere('payments.amount', 'like', '%' . $keywords . '%')
                ->orWhere('payment_types.name', 'like', '%' . $keywords . '%');
            }
        })
        ->where(function($q) use ($dateFrom, $dateTo, $dateFrom2, $dateTo2) {
            if (!empty($dateFrom) && !empty($dateTo)) {
                $q->where('billing.invoice_date', '>=', $dateFrom2)
                    ->where('billing.invoice_date', '<=', $dateTo2);
            } else if (!empty($dateFrom) && empty($dateTo)) {
                $q->where('billing.invoice_date', '=', $dateFrom);
            } else if (empty($dateFrom) && !empty($dateTo)) {
                $q->where('billing.invoice_date', '=', $dateTo);
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
                $q->where("payments.status", $status);
            }
        })
        ->where(function($q) use ($type){
            if ($type != '') {
                $q->where('payment_types.id', '=',  $type);
            }
        })
        ->where('payments.is_active', 1)
        ->orderBy('payments.id', $orderby)
        ->sum('payments.amount');

        return $res;
    }

    public function export(Request $request)
    {
        return Excel::download(new CollectionReportExport($request), 'collection_report_'.time().'.xlsx');
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