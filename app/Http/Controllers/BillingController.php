<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Bank;
use App\Models\Branch;
use App\Models\Billing;
use App\Models\BillingLine;
use App\Models\Customer;
use App\Models\DeliveryLinePosting;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\PaymentTerm;
use App\Models\User;
use Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\File;
use PDF;
// use App\Components\FlashMessages;
// use App\Helper\Helper;

class BillingController extends Controller
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
        $invoices = (new Invoice)->all_invoice_selectpicker();
        $customers = (new Customer)->all_customer_selectpicker();
        $agents = (new User)->all_agents_selectpicker();
        $terms = (new PaymentTerm)->all_payment_term_selectpicker();
        $types = (new PaymentType)->all_payment_type_selectpicker();
        $banks = (new Bank)->all_bank_selectpicker();
        return view('modules/billing/manage')->with(compact('menus', 'banks', 'types', 'branches', 'invoices', 'customers', 'agents', 'terms'));
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
        $msg .= '<table class="table align-middle table-row-dashed fs-6 gy-5" id="billingTable">';
        $msg .= '<thead>';
            $msg .= '<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">';
            $msg .= '<th class="w-10px pe-2">';
            $msg .= '<div class="form-check form-check-sm form-check-custom form-check-solid me-3">';
            $msg .= '<input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_customers_table .form-check-input" value="1" />';
            $msg .= '</div>';
            $msg .= '</th>';
            $msg .= '<th class="text-center">Invoice&nbsp;Date</th>';
            $msg .= '<th class="min-w-100px text-center">Invoice&nbsp;No</th>';
            $msg .= '<th class="min-w-100px text-center">Branch</th>';
            $msg .= '<th class="min-w-150px">Customer</th>';
            $msg .= '<th class="min-w-150px">Agent</th>';
            $msg .= '<th class="min-w-100px text-right">Total&nbsp;Amount</th>';
            $msg .= '<th class="min-w-80px text-center">Status</th>';
            $msg .= '<th class="text-center">Last Modified</th>';
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
                } else if ($row->status == 'partial') {
                    $status = '<span class="badge badge-status badge-light-warning">'.$row->status.'</span>';
                } else if ($row->status == 'completed') {
                    $status = '<span class="badge badge-status badge-light-success">'.$row->status.'</span>';
                } else {
                    $status = '<span class="badge badge-status badge-light-primary">'.$row->status.'</span>';
                }
                $msg .= '<tr data-row-amount="'.$row->totalAmt.'" data-row-invoice="'.$row->invoiceNo.'" data-row-id="'.$row->id.'">';
                $msg .= '<td>';
                $msg .= '<div class="form-check form-check-sm form-check-custom form-check-solid">';
                $msg .= '<input class="form-check-input" type="checkbox" value="'.$row->id.'" />';
                $msg .= '</div>';
                $msg .= '</td>';
                $msg .= '<td class="text-center">'.$row->invoiceDate.'</td>';
                $msg .= '<td class="text-center"><a href="javascript:;" class="text-gray-800 text-hover-primary mb-1">'.$row->invoiceNo.'</a></td>';
                $msg .= '<td class="text-center">'.$row->branch.'</td>';
                $msg .= '<td>'.$row->customer.'</td>';
                $msg .= '<td>'.$row->agent.'</td>';
                $msg .= '<td class="text-right">₱'.$row->totalAmt.'</td>';
                $msg .= '<td class="text-center v-middle">'.$status.'</td>';
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
        $res = Billing::select([
            'billing.id',
            'billing.invoice_no as invoiceNo',
            'billing.invoice_date as invoiceDate',
            'billing.created_at',
            'billing.updated_at',
            'billing.billing_amount as totalAmt',
            'billing.status',
            'users.name as agent',
            'customers.name as customer',
            'branches.name as branch',
            'payment_terms.name as paymentTerms'
        ])
        ->leftJoin('invoices', function($join)
        {
            $join->on('invoices.id', '=', 'billing.invoice_id');
        })
        ->leftJoin('users', function($join)
        {
            $join->on('users.id', '=', 'billing.agent_id');
        })
        ->leftJoin('customers', function($join)
        {
            $join->on('customers.id', '=', 'billing.customer_id');
        })
        ->leftJoin('branches', function($join)
        {
            $join->on('branches.id', '=', 'billing.branch_id');
        })
        ->leftJoin('payment_terms', function($join)
        {
            $join->on('payment_terms.id', '=', 'billing.payment_terms_id');
        })
        ->whereIn('branches.id', 
            explode(',', trim((new User)->select(['assignment'])->where('id', $user)->first()->assignment))
        )
        ->where('billing.is_active', 1)
        ->where(function($q) use ($keywords) {
            if (!empty($keywords)) {
            $q->where('billing.invoice_no', 'like', '%' . $keywords . '%')
                ->orWhere('billing.invoice_date', 'like', '%' . $keywords . '%')
                ->orWhere('billing.billing_amount', 'like', '%' . $keywords . '%')
                ->orWhere('billing.status', 'like', '%' . $keywords . '%')
                ->orWhere('customers.name', 'like', '%' . $keywords . '%')
                ->orWhere('payment_terms.name', 'like', '%' . $keywords . '%')
                ->orWhere('branches.name', 'like', '%' . $keywords . '%')
                ->orWhere('users.name', 'like', '%' . $keywords . '%');
            }
        })
        ->skip($start_from)->take($limit)
        ->orderBy('billing.id', 'desc')
        ->get();

        return $res->map(function($bill) {
            return (object) [
                'id' => $bill->id,
                'invoiceNo' => $bill->invoiceNo,
                'agent' => $bill->agent,
                'customer' => $bill->customer,
                'paymentTerms' => $bill->paymentTerms,
                'branch' => $bill->branch,
                'invoiceDate' => date('d-M-Y', strtotime($bill->invoiceDate)),
                'totalAmt' => number_format(floor(($bill->totalAmt*100))/100,2),
                'status' => $bill->status,
                'modified_at' => ($bill->updated_at !== NULL) ? date('d-M-Y', strtotime($bill->updated_at)).'<br/>'. date('h:i A', strtotime($bill->updated_at)) : date('d-M-Y', strtotime($bill->created_at)).'<br/>'. date('h:i A', strtotime($bill->created_at))
            ];
        });
    }

    public function get_page_count($keywords = '')
    {   
        $user = Auth::user()->id;
        $res = Billing::select([
            'billing.id',
            'billing.invoice_no as invoiceNo',
            'billing.invoice_date as invoiceDate',
            'billing.created_at',
            'billing.updated_at',
            'billing.billing_amount as totalAmt',
            'billing.status',
            'users.name as agent',
            'customers.name as customer',
            'branches.name as branch',
            'payment_terms.name as paymentTerms'
        ])
        ->leftJoin('invoices', function($join)
        {
            $join->on('invoices.id', '=', 'billing.invoice_id');
        })
        ->leftJoin('users', function($join)
        {
            $join->on('users.id', '=', 'billing.agent_id');
        })
        ->leftJoin('customers', function($join)
        {
            $join->on('customers.id', '=', 'billing.customer_id');
        })
        ->leftJoin('branches', function($join)
        {
            $join->on('branches.id', '=', 'billing.branch_id');
        })
        ->leftJoin('payment_terms', function($join)
        {
            $join->on('payment_terms.id', '=', 'billing.payment_terms_id');
        })
        ->whereIn('branches.id', 
            explode(',', trim((new User)->select(['assignment'])->where('id', $user)->first()->assignment))
        )
        ->where('billing.is_active', 1)
        ->where(function($q) use ($keywords) {
            if (!empty($keywords)) {
            $q->where('billing.invoice_no', 'like', '%' . $keywords . '%')
                ->orWhere('billing.invoice_date', 'like', '%' . $keywords . '%')
                ->orWhere('billing.billing_amount', 'like', '%' . $keywords . '%')
                ->orWhere('billing.status', 'like', '%' . $keywords . '%')
                ->orWhere('customers.name', 'like', '%' . $keywords . '%')
                ->orWhere('payment_terms.name', 'like', '%' . $keywords . '%')
                ->orWhere('branches.name', 'like', '%' . $keywords . '%')
                ->orWhere('users.name', 'like', '%' . $keywords . '%');
            }
        })
        ->orderBy('billing.id', 'desc')
        ->count();

        return $res;
    }

    public function all_active_payment_lines(Request $request)
    {   
        $keywords     = $request->get('keywords');  
        $billingID    = $request->get('billing');
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
        $msg .= '<table class="table align-middle table-row-dashed fs-8 gy-2" id="paymentLineTable">';
        $msg .= '<thead>';
            $msg .= '<tr class="text-start text-gray-400 fw-bolder fs-8 text-uppercase gs-0">';
            $msg .= '<th class="w-10px pe-2">';
            $msg .= '<div class="form-check form-check-sm form-check-custom form-check-solid me-3">';
            $msg .= '<input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_customers_table .form-check-input" value="1" />';
            $msg .= '</div>';
            $msg .= '</th>';
            $msg .= '<th class="text-center">Payment<br/>Type</th>';
            $msg .= '<th class="text-center">Amount<br/>Paid</th>';
            $msg .= '<th class="text-center">Bank<br/>Name</th>';
            $msg .= '<th class="text-center">Account<br/>No</th>';
            $msg .= '<th class="text-center">Account<br/>Name</th>';
            $msg .= '<th class="text-center">Cheque<br/>No</th>';
            $msg .= '<th class="text-center">Cheque<br/>Date</th>';
            $msg .= '<th class="text-center">External<br/>Doc</th>';
            $msg .= '<th class="min-w-80px text-center v-middle">Status</th>';
            $msg .= '<th class="text-center min-w-70px">Actions</th>';
            $msg .= '</tr>';
        $msg .= '</thead>';
        $msg .= '<tbody class="fw-bold text-gray-600">';
        
        $query = $this->get_payment_line_items($per_page, $start_from, $billingID, $keywords);
        $count = $this->get_payment_page_count($billingID, $keywords);
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
                if ($row->status == 'draft') {
                    $status = '<span class="badge badge-status badge-light-dark">'.$row->status.'</span>';
                } else {
                    $status = '<span class="badge badge-status badge-light-success">'.$row->status.'</span>';
                } 
                $msg .= '<tr data-row-status="'.$row->status.'" data-row-billing-id="'.$billingID.'" data-row-amount="'.$row->amount.'" title="'.$row->modified_at.'" data-row-id="'.$row->id.'">';
                $msg .= '<td>';
                $msg .= '<div class="form-check form-check-sm form-check-custom form-check-solid">';
                $msg .= '<input class="form-check-input" type="checkbox" value="'.$row->id.'" />';
                $msg .= '</div>';
                $msg .= '</td>';
                $msg .= '<td class="text-center">'.$row->paymentType.'</td>';
                $msg .= '<td class="text-center">'.$row->amount.'</td>';
                $msg .= '<td class="text-center">'.$row->bankName.'</td>';
                $msg .= '<td class="text-center">'.$row->bankNo.'</td>';
                $msg .= '<td class="text-center">'.$row->bankAcct.'</td>';
                $msg .= '<td class="text-center">'.$row->chequeNo.'</td>';
                $msg .= '<td class="text-center">'.$row->chequeDate.'</td>';
                $msg .= '<td class="text-center">'.$row->extDoc.'</td>';
                $msg .= '<td class="text-center">'.$status.'</td>';
                $msg .= '<td class="text-center">';
                $msg .= '<a href="javascript:;" title="modify this" class="edit-btn btn btn-sm btn-light btn-active-light-warning">';
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
                $msg .= '<a href="javascript:;" title="post this" class="post-btn btn btn-sm btn-light btn-active-light-info">';
                $msg .= '<!--begin::Svg Icon | path: assets/media/icons/duotone/General/Lock.svg-->
                <span class="svg-icon svg-icon-muted svg-icon-2hx"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                        <mask fill="white">
                            <use xlink:href="#path-1"/>
                        </mask>
                        <g/>
                        <path d="M7,10 L7,8 C7,5.23857625 9.23857625,3 12,3 C14.7614237,3 17,5.23857625 17,8 L17,10 L18,10 C19.1045695,10 20,10.8954305 20,12 L20,18 C20,19.1045695 19.1045695,20 18,20 L6,20 C4.8954305,20 4,19.1045695 4,18 L4,12 C4,10.8954305 4.8954305,10 6,10 L7,10 Z M12,5 C10.3431458,5 9,6.34314575 9,8 L9,10 L15,10 L15,8 C15,6.34314575 13.6568542,5 12,5 Z" fill="#000000"/>
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

    public function get_payment_line_items($limit, $start_from, $billingID, $keywords = '')
    {   
        $res = Payment::select([
            'payments.id as paymentId',
            'payment_types.name as paymentType',
            'payments.bank_name as bankName',
            'payments.bank_no as bankNo',
            'payments.bank_account as bankAcct',
            'payments.cheque_no as chequeNo',
            'payments.cheque_date as chequeDate',
            'payments.external_doc as extDoc',
            'payments.amount as amount',
            'payments.status as status',
            'payments.created_at',
            'payments.updated_at'
        ])
        ->leftJoin('payment_types', function($join)
        {
            $join->on('payment_types.id', '=', 'payments.payment_type_id');
        })
        ->leftJoin('billing', function($join)
        {
            $join->on('billing.id', '=', 'payments.billing_id');
        })
        ->where('payments.billing_id', $billingID)
        ->where('payments.is_active', 1)
        ->where(function($q) use ($keywords) {
            if (!empty($keywords)) {
            $q->where('payment_types.name', 'like', '%' . $keywords . '%')
                ->orWhere('payments.bank_name', 'like', '%' . $keywords . '%')
                ->orWhere('payments.bank_no', 'like', '%' . $keywords . '%')
                ->orWhere('payments.bank_account', 'like', '%' . $keywords . '%')
                ->orWhere('payments.cheque_no', 'like', '%' . $keywords . '%')
                ->orWhere('payments.cheque_date', 'like', '%' . $keywords . '%')
                ->orWhere('payments.external_doc', 'like', '%' . $keywords . '%')
                ->orWhere('payments.status', 'like', '%' . $keywords . '%')
                ->orWhere('payments.amount', 'like', '%' . $keywords . '%');
            }
        })
        ->skip($start_from)->take($limit)
        ->orderBy('billing.id', 'desc')
        ->get();

        return $res->map(function($payment) {
            return (object) [
                'id' => $payment->paymentId,
                'paymentType' => $payment->paymentType,
                'bankName' => $payment->bankName,
                'bankNo' => $payment->bankNo,
                'bankAcct' => $payment->bankAcct,
                'chequeNo' => $payment->chequeNo,
                'chequeDate' => $payment->chequeDate ? date('d-M-Y', strtotime($payment->chequeDate)) : '',
                'extDoc' => $payment->extDoc,
                'amount' => number_format(floor(($payment->amount*100))/100,2),
                'status' => $payment->status,
                'modified_at' => ($payment->updated_at !== NULL) ? date('d-M-Y', strtotime($payment->updated_at)).' '. date('h:i A', strtotime($payment->updated_at)) : date('d-M-Y', strtotime($payment->created_at)).' '. date('h:i A', strtotime($payment->created_at))
            ];
        });
    }

    public function get_payment_page_count($billingID, $keywords = '')
    {   
        $res = Payment::select([
            'payments.id as paymentId',
            'payment_types.name as paymentType',
            'payments.bank_name as bankName',
            'payments.bank_no as bankNo',
            'payments.bank_account as bankAcct',
            'payments.cheque_no as chequeNo',
            'payments.cheque_date as chequeDate',
            'payments.external_doc as extDoc',
            'payments.amount as amount',
            'payments.status as status',
            'payments.created_at',
            'payments.updated_at'
        ])
        ->leftJoin('payment_types', function($join)
        {
            $join->on('payment_types.id', '=', 'payments.payment_type_id');
        })
        ->leftJoin('billing', function($join)
        {
            $join->on('billing.id', '=', 'payments.billing_id');
        })
        ->where('payments.billing_id', $billingID)
        ->where('payments.is_active', 1)
        ->where(function($q) use ($keywords) {
            if (!empty($keywords)) {
            $q->where('payment_types.name', 'like', '%' . $keywords . '%')
                ->orWhere('payments.bank_name', 'like', '%' . $keywords . '%')
                ->orWhere('payments.bank_no', 'like', '%' . $keywords . '%')
                ->orWhere('payments.bank_account', 'like', '%' . $keywords . '%')
                ->orWhere('payments.cheque_no', 'like', '%' . $keywords . '%')
                ->orWhere('payments.cheque_date', 'like', '%' . $keywords . '%')
                ->orWhere('payments.external_doc', 'like', '%' . $keywords . '%')
                ->orWhere('payments.status', 'like', '%' . $keywords . '%')
                ->orWhere('payments.amount', 'like', '%' . $keywords . '%');
            }
        })
        ->orderBy('billing.id', 'desc')
        ->count();

        return $res;
    }
    
    public function all_active_billing_lines(Request $request)
    {   
        $keywords     = $request->get('keywords');  
        $billingID    = $request->get('billing');
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
        $msg .= '<table class="table align-middle table-row-dashed fs-8 gy-2" id="billingLineTable">';
        $msg .= '<thead>';
            $msg .= '<tr class="text-start text-gray-400 fw-bolder fs-8 text-uppercase gs-0">';
            $msg .= '<th class="w-10px pe-2">';
            $msg .= '<div class="form-check form-check-sm form-check-custom form-check-solid me-3">';
            $msg .= '<input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_customers_table .form-check-input" value="1" />';
            $msg .= '</div>';
            $msg .= '</th>';
            $msg .= '<th class="text-center">DR No</th>';
            $msg .= '<th class="text-center">Branch</th>';
            $msg .= '<th class="text-center">Customer</th>';
            $msg .= '<th class="text-center">Item&nbsp;Description</th>';
            $msg .= '<th class="text-center">Quantity</th>';
            $msg .= '<th class="text-center">UOM</th>';
            $msg .= '<th class="text-center">SRP</th>';
            $msg .= '<th class="text-center">PLUS</th>';
            $msg .= '<th class="text-center">DISC1</th>';
            $msg .= '<th class="text-center">DISC2</th>';
            $msg .= '<th class="text-right">TOTAL</th>';
            $msg .= '<th class="text-center min-w-70px">Actions</th>';
            $msg .= '</tr>';
        $msg .= '</thead>';
        $msg .= '<tbody class="fw-bold text-gray-600">';
        
        $query = $this->get_billing_line_items($per_page, $start_from, $billingID, $keywords);
        $count = $this->get_billing_page_count($billingID, $keywords);
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
                $msg .= '<tr title="'.$row->modified_at.'" data-row-id="'.$row->id.'" data-row-item="'.$row->item.'">';
                $msg .= '<td>';
                $msg .= '<div class="form-check form-check-sm form-check-custom form-check-solid">';
                $msg .= '<input class="form-check-input" type="checkbox" value="'.$row->id.'" />';
                $msg .= '</div>';
                $msg .= '</td>';
                $msg .= '<td class="text-center">'.$row->docNo.'</td>';
                $msg .= '<td class="text-center">'.$row->branch.'</td>';
                $msg .= '<td class="text-center">'.$row->customer.'</td>';
                $msg .= '<td class="text-center">'.$row->item.'</td>';
                $msg .= '<td class="text-center">'.$row->quantity.'</td>';
                $msg .= '<td class="text-center">'.$row->uom.'</td>';
                $msg .= '<td class="text-center">'.$row->srp.'</td>';
                $msg .= '<td class="text-center">'.$row->plus.'</td>';
                $msg .= '<td class="text-center">'.$row->disc1.'</td>';
                $msg .= '<td class="text-center">'.$row->disc2.'</td>';
                $msg .= '<td class="text-right">'.$row->totalAmt.'</td>';
                $msg .= '<td class="text-center">';
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

    public function get_billing_line_items($limit, $start_from, $billingID, $keywords = '')
    {   
        $res = BillingLine::select([
            'billing_lines.id as id',
            'items.code as itemCode',
            'items.name as itemName',
            'delivery_lines.srp as srp',
            'delivery_lines.uom as uom',
            'delivery_lines.quantity as prepQuantity',
            'delivery_lines_posting.quantity as quantity',
            'delivery_lines.plus as plus',
            'delivery_lines.discount1 as disc1',
            'delivery_lines.discount2 as disc2',
            'delivery_lines.total_amount as totalAmt',
            'delivery.delivery_doc_no as docNo',
            'customers.name as customer',
            'branches.name as branch',
            'billing_lines.created_at',
            'billing_lines.updated_at'
        ])
        ->leftJoin('billing', function($join)
        {
            $join->on('billing.id', '=', 'billing_lines.billing_id');
        })
        ->leftJoin('delivery_lines_posting', function($join)
        {
            $join->on('delivery_lines_posting.id', '=', 'billing_lines.delivery_line_posting_id');
        })
        ->leftJoin('delivery_lines', function($join)
        {
            $join->on('delivery_lines.id', '=', 'delivery_lines_posting.delivery_line_id');
        })
        ->leftJoin('items', function($join)
        {
            $join->on('items.id', '=', 'delivery_lines.item_id');
        })
        ->leftJoin('delivery', function($join)
        {
            $join->on('delivery.id', '=', 'delivery_lines.delivery_id');
        })
        ->leftJoin('branches', function($join)
        {
            $join->on('branches.id', '=', 'delivery.branch_id');
        })
        ->leftJoin('customers', function($join)
        {
            $join->on('customers.id', '=', 'delivery.customer_id');
        })
        ->where('billing_lines.billing_id', $billingID)
        ->where('billing_lines.is_active', 1)
        ->where(function($q) use ($keywords) {
            if (!empty($keywords)) {
            $q->where('delivery.delivery_doc_no', 'like', '%' . $keywords . '%')
                ->orWhere('customers.name', 'like', '%' . $keywords . '%')
                ->orWhere('branches.name', 'like', '%' . $keywords . '%')
                ->orWhere('items.code', 'like', '%' . $keywords . '%')
                ->orWhere('items.name', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines_posting.quantity', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.uom', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.srp', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.discount1', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.discount2', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.plus', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.total_amount', 'like', '%' . $keywords . '%');
            }
        })
        ->skip($start_from)->take($limit)
        ->orderBy('billing_lines.id', 'desc')
        ->get();

        return $res->map(function($bill) {
            $srpVal = floatval($bill->totalAmt) / floatval($bill->prepQuantity);
            $totalAmt = floatval($srpVal) * floatval($bill->quantity);
            return (object) [
                'id' => $bill->id,
                'docNo' => $bill->docNo,
                'branch' => $bill->branch,
                'customer' => $bill->customer,
                'item' => $bill->itemCode.' - '.$bill->itemName,
                'quantity' => $bill->quantity,
                'uom' => $bill->uom,
                'srp' => $bill->srp,
                'plus' => $bill->plus,
                'disc1' => $bill->disc1,
                'disc2' => $bill->disc2,
                'totalAmt' => number_format(floor(($totalAmt*100))/100,2),
                'status' => $bill->status,
                'modified_at' => ($bill->updated_at !== NULL) ? date('d-M-Y', strtotime($bill->updated_at)).' '. date('h:i A', strtotime($bill->updated_at)) : date('d-M-Y', strtotime($bill->created_at)).' '. date('h:i A', strtotime($bill->created_at))
            ];
        });
    }

    public function get_billing_page_count($billingID, $keywords = '')
    {   
        $res = BillingLine::select([
            'billing_lines.id as id',
            'items.code as itemCode',
            'items.name as itemName',
            'delivery_lines.srp as srp',
            'delivery_lines.uom as uom',
            'delivery_lines.quantity as prepQuantity',
            'delivery_lines_posting.quantity as quantity',
            'delivery_lines.plus as plus',
            'delivery_lines.discount1 as disc1',
            'delivery_lines.discount2 as disc2',
            'delivery_lines.total_amount as totalAmt',
            'delivery.delivery_doc_no as docNo',
            'customers.name as customer',
            'branches.name as branch',
            'billing_lines.created_at',
            'billing_lines.updated_at'
        ])
        ->leftJoin('billing', function($join)
        {
            $join->on('billing.id', '=', 'billing_lines.billing_id');
        })
        ->leftJoin('delivery_lines_posting', function($join)
        {
            $join->on('delivery_lines_posting.id', '=', 'billing_lines.delivery_line_posting_id');
        })
        ->leftJoin('delivery_lines', function($join)
        {
            $join->on('delivery_lines.id', '=', 'delivery_lines_posting.delivery_line_id');
        })
        ->leftJoin('items', function($join)
        {
            $join->on('items.id', '=', 'delivery_lines.item_id');
        })
        ->leftJoin('delivery', function($join)
        {
            $join->on('delivery.id', '=', 'delivery_lines.delivery_id');
        })
        ->leftJoin('branches', function($join)
        {
            $join->on('branches.id', '=', 'delivery.branch_id');
        })
        ->leftJoin('customers', function($join)
        {
            $join->on('customers.id', '=', 'delivery.customer_id');
        })
        ->where('billing_lines.billing_id', $billingID)
        ->where('billing_lines.is_active', 1)
        ->where(function($q) use ($keywords) {
            if (!empty($keywords)) {
            $q->where('delivery.delivery_doc_no', 'like', '%' . $keywords . '%')
                ->orWhere('customers.name', 'like', '%' . $keywords . '%')
                ->orWhere('branches.name', 'like', '%' . $keywords . '%')
                ->orWhere('items.code', 'like', '%' . $keywords . '%')
                ->orWhere('items.name', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines_posting.quantity', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.uom', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.srp', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.discount1', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.discount2', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.plus', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.total_amount', 'like', '%' . $keywords . '%');
            }
        })
        ->orderBy('billing_lines.id', 'desc')
        ->count();

        return $res;
    }

    public function get_invoice_no(Request $request, $branch, $type)
    {   
        return $this->get_invoice_no_via_branch_type($branch, $type);
    }

    public function get_invoice_no_via_branch_type($branch = '', $type = '')
    {   
        $invoiceNo = '';
        if ($branch !== '' && $type !== '') {
            $year = date('Y');
            $code = Invoice::where('id', $type)->first()->code;
            $count = Billing::where([
                'branch_id' => $branch,
                'invoice_id' => $type
            ])
            ->where('created_at', 'like', '%' . $year . '%')
            ->count();
            
            $yearPrefix = substr($year, -2);
            $invoiceNo .= $code.''.$yearPrefix;   
            if ($count < 9) {
                $invoiceNo .= '000'.($count + 1);
            } else if ($count < 99) {
                $invoiceNo .= '00'.($count + 1);
            } else if ($count < 999) {
                $invoiceNo .= '0'.($count + 1);
            } else {
                $invoiceNo .= ($count + 1);
            }
        }
        return $invoiceNo;
    }

    public function get_customer_info(Request $request, $customer)
    {
        $res = (new Customer)->where('id', $customer)->first();
        $mobileNo = $res->mobile_no ? (strlen($res->mobile_no) == 10) ? '(0'.$res->mobile_no.')' : '('.$res->mobile_no.')' : '';
        $data = array(
            'contact_no' => $res->mobile_no,
            'address' => $res->address,
            'agent_id' => $res->agent_id,
            'instructions' => $mobileNo.'
'.$res->address,
        );

        echo json_encode( $data ); exit();
    }

    public function get_due_date(Request $request, $paymentTerms = '', $invoiceDate = '')
    {   
        $duedate = '';
        if ($paymentTerms !== '' && $invoiceDate !== '') {
            $date = date('Y-m-d', strtotime($invoiceDate));
            $code = PaymentTerm::where('id', $paymentTerms)->first()->code;
            $duedate = date('d-M-Y', strtotime($date. ' + '.$code.' days'));
        }
        return $duedate;
    }

    public function store(Request $request)
    {   
        // $this->is_permitted(0);
        $timestamp = date('Y-m-d H:i:s');
        $invoiceNo = $this->get_invoice_no_via_branch_type($request->branch_id, $request->invoice_id);

        $billing = Billing::create([
            'branch_id' => $request->branch_id,
            'customer_id' => $request->customer_id,
            'agent_id' => $request->agent_id,
            'payment_terms_id' => $request->payment_terms_id,
            'invoice_id' => $request->invoice_id,
            'invoice_no' => $invoiceNo,
            'invoice_date' => date('Y-m-d', strtotime($request->invoice_date)),
            'due_date' => date('Y-m-d', strtotime($request->get('due_date'))),
            'countered_date' => $request->countered_date ? date('Y-m-d', strtotime($request->countered_date)) : NULL,
            'countered_by' => $request->countered_by ? $request->countered_by : NULL,
            'instructions' => $request->instructions,
            'created_at' => $timestamp,
            'created_by' => Auth::user()->id
        ]);

        if (!$billing) {
            throw new NotFoundHttpException();
        }

        $this->audit_logs('billing', $billing->id, 'has inserted a new billing.', Billing::find($billing->id), $timestamp, Auth::user()->id);

        $data = array(
            'billing_id' => $billing->id,
            'title' => 'Well done!',
            'text' => 'The billing has been successfully stored.',
            'type' => 'success',
            'class' => 'btn-brand'
        );

        echo json_encode( $data ); exit();
    }

    public function update(Request $request, $id)
    {    
        // $this->is_permitted(2);
        $timestamp = date('Y-m-d H:i:s');
        $billing = Billing::find($id);

        if(!$billing) {
            throw new NotFoundHttpException();
        }

        // $billing->branch_id = $request->branch_id;
        $billing->customer_id = $request->get('customer');
        $billing->agent_id = $request->agent_id;
        $billing->payment_terms_id = $request->payment_terms_id;
        $billing->invoice_date = date('Y-m-d', strtotime($request->invoice_date));
        $billing->due_date = date('Y-m-d', strtotime($request->get('due_date')));
        $billing->countered_date = $request->countered_date ? date('Y-m-d', strtotime($request->countered_date)) : NULL;
        $billing->countered_by = $request->countered_by ? $request->countered_by : NULL;
        $billing->instructions = $request->instructions;
        $billing->updated_at = $timestamp;
        $billing->updated_by = Auth::user()->id;

        if ($billing->update()) {
            $data = array(
                'billing_count' => (new BillingLine)->where(['billing_id' => $id, 'is_active' => 1])->count(),
                'title' => 'Well done!',
                'text' => 'The billing has been successfully modified.',
                'type' => 'success',
                'class' => 'btn-brand'
            );
            echo json_encode( $data ); exit();
        }
    }

    public function find(Request $request, $id)
    {    
        $billing = Billing::find($id);

        if(!$billing) {
            throw new NotFoundHttpException();
        }

        $billing = (object) array(
            'billing_id' => $billing->id,
            'branch_id' => $billing->branch_id,
            'customer_id' => $billing->customer_id,
            'agent_id' => $billing->agent_id,
            'payment_terms_id' => $billing->payment_terms_id,
            'invoice_id' => $billing->invoice_id,
            'invoice_no' => $billing->invoice_no,
            'invoice_date' => date('d-M-Y', strtotime($billing->invoice_date)),
            'due_date' => date('d-M-Y', strtotime($billing->due_date)),
            'countered_date' => $billing->countered_date ? date('d-M-Y', strtotime($billing->countered_date)) : '',
            'countered_by' => $billing->countered_by,
            'instructions' => $billing->instructions,
            'billing_amount' => number_format(floor(($billing->billing_amount*100))/100,2),
            'billing_paid' => number_format(floor(($billing->billing_paid*100))/100,2)	
        );

        return response()
        ->json([
            'status' => 'ok',
            'data' => $billing
        ]);
    }

    public function all_active_unbilled_lines(Request $request)
    {   
        $keywords     = $request->get('keywords');  
        $billingID    = $request->get('billing');
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
        $msg .= '<table class="table align-middle table-row-dashed fs-8 gy-5" id="unbilledLineTable">';
        $msg .= '<thead>';
            $msg .= '<tr class="text-start text-gray-400 fw-bolder fs-8 text-uppercase gs-0">';
            $msg .= '<th class="w-10px pe-2">';
            $msg .= '<div class="form-check form-check-sm form-check-custom form-check-solid me-3">';
            $msg .= '<input class="form-check-input" type="checkbox" value="all" />';
            $msg .= '</div>';
            $msg .= '</th>';
            $msg .= '<th class="text-center">DR No</th>';
            $msg .= '<th class="text-center">Branch</th>';
            $msg .= '<th class="text-center">Customer</th>';
            $msg .= '<th class="text-center">Item&nbsp;Description</th>';
            $msg .= '<th class="text-center">Quantity</th>';
            $msg .= '<th class="text-center">UOM</th>';
            $msg .= '<th class="text-center">SRP</th>';
            $msg .= '<th class="text-center">PLUS</th>';
            $msg .= '<th class="text-center">DISC1</th>';
            $msg .= '<th class="text-center">DISC2</th>';
            $msg .= '<th class="text-right">TOTAL</th>';
            $msg .= '</tr>';
        $msg .= '</thead>';
        $msg .= '<tbody class="fw-bold text-gray-600">';
        
        $query = $this->get_unbilled_line_items($per_page, $start_from, $billingID, $keywords);
        $count = $this->get_unbilled_page_count($billingID, $keywords);
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
                $msg .= '<tr data-row-id="'.$row->id.'">';
                $msg .= '<td>';
                $msg .= '<div class="form-check form-check-sm form-check-custom form-check-solid">';
                $msg .= '<input class="form-check-input" type="checkbox" value="'.$row->id.'" />';
                $msg .= '</div>';
                $msg .= '</td>';
                $msg .= '<td class="text-center">'.$row->docNo.'</td>';
                $msg .= '<td class="text-center">'.$row->branch.'</td>';
                $msg .= '<td class="text-center">'.$row->customer.'</td>';
                $msg .= '<td class="text-center">'.$row->item.'</td>';
                $msg .= '<td class="text-center">'.$row->quantity.'</td>';
                $msg .= '<td class="text-center">'.$row->uom.'</td>';
                $msg .= '<td class="text-center">'.$row->srp.'</td>';
                $msg .= '<td class="text-center">'.$row->plus.'</td>';
                $msg .= '<td class="text-center">'.$row->disc1.'</td>';
                $msg .= '<td class="text-center">'.$row->disc2.'</td>';
                $msg .= '<td class="text-right">'.$row->totalAmt.'</td>';
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

    public function get_unbilled_line_items($limit, $start_from, $billingID, $keywords = '')
    {   
        $customerID = (new Billing)->where(['id' => $billingID])->first()->customer_id;
        $res = DeliveryLinePosting::select([
            'delivery_lines_posting.id as id',
            'items.code as itemCode',
            'items.name as itemName',
            'delivery_lines.srp as srp',
            'delivery_lines.uom as uom',
            'delivery_lines.quantity as prepQuantity',
            'delivery_lines_posting.quantity as quantity',
            'delivery_lines.plus as plus',
            'delivery_lines.discount1 as disc1',
            'delivery_lines.discount2 as disc2',
            'delivery_lines.total_amount as totalAmt',
            'delivery.delivery_doc_no as docNo',
            'customers.name as customer',
            'branches.name as branch',
        ])
        ->leftJoin('delivery_lines', function($join)
        {
            $join->on('delivery_lines.id', '=', 'delivery_lines_posting.delivery_line_id');
        })
        ->leftJoin('items', function($join)
        {
            $join->on('items.id', '=', 'delivery_lines.item_id');
        })
        ->leftJoin('delivery', function($join)
        {
            $join->on('delivery.id', '=', 'delivery_lines.delivery_id');
        })
        ->leftJoin('branches', function($join)
        {
            $join->on('branches.id', '=', 'delivery.branch_id');
        })
        ->leftJoin('customers', function($join)
        {
            $join->on('customers.id', '=', 'delivery.customer_id');
        })
        ->where([
            'delivery.customer_id' => $customerID,
            'delivery_lines_posting.is_attached' => 0,
            'delivery_lines_posting.is_active' => 1
        ])
        ->where(function($q) use ($keywords) {
            if (!empty($keywords)) {
            $q->where('delivery.delivery_doc_no', 'like', '%' . $keywords . '%')
                ->orWhere('customers.name', 'like', '%' . $keywords . '%')
                ->orWhere('branches.name', 'like', '%' . $keywords . '%')
                ->orWhere('items.code', 'like', '%' . $keywords . '%')
                ->orWhere('items.name', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines_posting.quantity', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.uom', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.srp', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.discount1', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.discount2', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.plus', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.total_amount', 'like', '%' . $keywords . '%');
            }
        })
        ->skip($start_from)->take($limit)
        ->orderBy('delivery_lines_posting.id', 'desc')
        ->get();

        return $res->map(function($bill) {
            $srpVal = floatval($bill->totalAmt) / floatval($bill->prepQuantity);
            $totalAmt = floatval($srpVal) * floatval($bill->quantity);
            return (object) [
                'id' => $bill->id,
                'docNo' => $bill->docNo,
                'branch' => $bill->branch,
                'customer' => $bill->customer,
                'item' => $bill->itemCode.' - '.$bill->itemName,
                'quantity' => $bill->quantity,
                'uom' => $bill->uom,
                'srp' => $bill->srp,
                'plus' => $bill->plus,
                'disc1' => $bill->disc1,
                'disc2' => $bill->disc2,
                'totalAmt' => number_format(floor(($totalAmt*100))/100,2),
            ];
        });
    }

    public function get_unbilled_page_count($billingID, $keywords = '')
    {   
        $customerID = (new Billing)->where(['id' => $billingID])->first()->customer_id;
        $res = DeliveryLinePosting::select([
            'delivery_lines_posting.id as id',
            'items.code as itemCode',
            'items.name as itemName',
            'delivery_lines.srp as srp',
            'delivery_lines.uom as uom',
            'delivery_lines.quantity as prepQuantity',
            'delivery_lines_posting.quantity as quantity',
            'delivery_lines.plus as plus',
            'delivery_lines.discount1 as disc1',
            'delivery_lines.discount2 as disc2',
            'delivery_lines.total_amount as totalAmt',
            'delivery.delivery_doc_no as docNo',
            'customers.name as customer',
            'branches.name as branch',
        ])
        ->leftJoin('delivery_lines', function($join)
        {
            $join->on('delivery_lines.id', '=', 'delivery_lines_posting.delivery_line_id');
        })
        ->leftJoin('items', function($join)
        {
            $join->on('items.id', '=', 'delivery_lines.item_id');
        })
        ->leftJoin('delivery', function($join)
        {
            $join->on('delivery.id', '=', 'delivery_lines.delivery_id');
        })
        ->leftJoin('branches', function($join)
        {
            $join->on('branches.id', '=', 'delivery.branch_id');
        })
        ->leftJoin('customers', function($join)
        {
            $join->on('customers.id', '=', 'delivery.customer_id');
        })
        ->where([
            'delivery.customer_id' => $customerID,
            'delivery_lines_posting.is_attached' => 0,
            'delivery_lines_posting.is_active' => 1
        ])
        ->where(function($q) use ($keywords) {
            if (!empty($keywords)) {
            $q->where('delivery.delivery_doc_no', 'like', '%' . $keywords . '%')
                ->orWhere('customers.name', 'like', '%' . $keywords . '%')
                ->orWhere('branches.name', 'like', '%' . $keywords . '%')
                ->orWhere('items.code', 'like', '%' . $keywords . '%')
                ->orWhere('items.name', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines_posting.quantity', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.uom', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.srp', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.discount1', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.discount2', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.plus', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.total_amount', 'like', '%' . $keywords . '%');
            }
        })
        ->orderBy('delivery_lines_posting.id', 'desc')
        ->count();

        return $res;
    }

    public function attach(Request $request, $id)
    {
        $timestamp = date('Y-m-d H:i:s');
        $billing = Billing::find($id);
        foreach ($request->postingID as $postingID) {
            $res = BillingLine::where([
                'billing_id' => $id,
                'delivery_line_posting_id' => $postingID
            ])
            ->get();

            if ($res->count() > 0) {
                $billingLine = BillingLine::find($res->first()->id);
                if(!$billingLine) {
                    throw new NotFoundHttpException();
                }

                $billingLine->is_active = 1;
                $billingLine->updated_at = $timestamp;
                $billingLine->updated_by = Auth::user()->id;
        
                if ($billingLine->update()) {
                    $this->audit_logs('billing_lines', $billingLine->id, 'has attached a new billing line to invoice('.$billing->invoice_no.').', BillingLine::find($billingLine->id), $timestamp, Auth::user()->id);
                }
            } else {
                $billingLine = BillingLine::create([
                    'billing_id' => $id,
                    'delivery_line_posting_id' => $postingID,
                    'created_at' => $timestamp,
                    'created_by' => Auth::user()->id
                ]);

                if (!$billing) {
                    throw new NotFoundHttpException();
                }

                $this->audit_logs('billing_lines', $billingLine->id, 'has attached a new billing line to invoice('.$billing->invoice_no.').', BillingLine::find($billingLine->id), $timestamp, Auth::user()->id);
            }

            DeliveryLinePosting::where('id', $postingID)->update(['is_attached' => 1]);
        }
        
        $lines = BillingLine::select([
            'delivery_lines.quantity as prepQuantity',
            'delivery_lines_posting.quantity as quantity',
            'delivery_lines.total_amount as totalAmt'
        ])
        ->leftJoin('delivery_lines_posting', function($join)
        {
            $join->on('delivery_lines_posting.id', '=', 'billing_lines.delivery_line_posting_id');
        })
        ->leftJoin('delivery_lines', function($join)
        {
            $join->on('delivery_lines.id', '=', 'delivery_lines_posting.delivery_line_id');
        })
        ->where([
            'billing_lines.billing_id' => $id,
            'billing_lines.is_active' => 1
        ])
        ->get();

        $totalAmt = 0;
        if ($lines->count() > 0) {
            foreach ($lines as $line) {
                $srpVal = floatval($line->totalAmt) / floatval($line->prepQuantity);
                $total  = floatval($srpVal) * floatval($line->quantity);
                $totalAmt += floatval($total);
            }
        }

        $billing->billing_amount = $totalAmt;
        $billing->updated_at = $timestamp;
        $billing->updated_by = Auth::user()->id;
        $billing->update();

        $data = array(
            'billing_count' => (new BillingLine)->where(['billing_id' => $id, 'is_active' => 1])->count(),
            'totalAmt' => number_format(floor(($totalAmt*100))/100,2),
            'title' => 'Well done!',
            'text' => 'The billing has been successfully stored.',
            'type' => 'success',
            'class' => 'btn-brand'
        );

        echo json_encode( $data ); exit();
    }

    public function remove_billing_line(Request $request, $id)
    {    
        // $this->is_permitted(2);
        $timestamp = date('Y-m-d H:i:s');
        $billingLine = BillingLine::find($id);
        $billingID = $billingLine->billing_id;
        $postingID = $billingLine->delivery_line_posting_id;
        $billingLine->updated_at = $timestamp;
        $billingLine->updated_by = Auth::user()->id;
        $billingLine->is_active = 0;
        if ($billingLine->update()) {
            $posting = DeliveryLinePosting::where(['id' => $postingID])
            ->update([
                'updated_at' => $timestamp,
                'updated_by' => Auth::user()->id,
                'is_attached' => 0
            ]);
            $this->audit_logs('delivery_lines_posting', $postingID, 'has dettached a delivery line posting from billing.', DeliveryLinePosting::find($postingID), $timestamp, Auth::user()->id);
        }            
        $billing = Billing::find($billingID);
        $lines = BillingLine::select([
            'delivery_lines.quantity as prepQuantity',
            'delivery_lines_posting.quantity as quantity',
            'delivery_lines.total_amount as totalAmt'
        ])
        ->leftJoin('delivery_lines_posting', function($join)
        {
            $join->on('delivery_lines_posting.id', '=', 'billing_lines.delivery_line_posting_id');
        })
        ->leftJoin('delivery_lines', function($join)
        {
            $join->on('delivery_lines.id', '=', 'delivery_lines_posting.delivery_line_id');
        })
        ->where([
            'billing_lines.billing_id' => $billingID,
            'billing_lines.is_active' => 1
        ])
        ->get();
            
        $totalAmt = 0;
        if ($lines->count() > 0) {
            foreach ($lines as $line) {
                $srpVal = floatval($line->totalAmt) / floatval($line->prepQuantity);
                $total  = floatval($srpVal) * floatval($line->quantity);
                $totalAmt += floatval($total);
            }
        }

        $billing->billing_amount = $totalAmt;
        $billing->updated_at = $timestamp;
        $billing->updated_by = Auth::user()->id;
        if ($billing->update()) {
            $this->audit_logs('billing', $billingID, 'has modified a billing.', Billing::find($billingID), $timestamp, Auth::user()->id);
            $data = array(
                'totalAmt' => number_format(floor(($totalAmt*100))/100,2),
                'title' => 'Well done!',
                'text' => 'The billing line has been successfully removed.',
                'type' => 'success',
                'class' => 'btn-brand'
            );
            echo json_encode( $data ); exit();
        }
    }

    public function get_bank_info(Request $request, $id)
    {
        $res = Bank::find($id);
        $data = array(
            'bank_name' => $res->bank_name,
            'bank_no' => $res->bank_no,
            'bank_account' => $res->bank_account
        );

        echo json_encode( $data ); exit();
    }

    public function store_payment_line(Request $request, $billingID)
    {   
        // $this->is_permitted(0);
        $timestamp = date('Y-m-d H:i:s');

        $payment = Payment::create([
            'billing_id' => $billingID,
            'payment_type_id' => $request->payment_type_id,
            'bank_id' => $request->bank_id,
            'amount' => $request->amount,
            'bank_name' => $request->get('bank_name'),
            'bank_no' => $request->get('bank_no'),
            'bank_account' => $request->get('bank_account'),
            'cheque_no' => $request->get('cheque_no'),
            'cheque_date' => $request->get('cheque_date') ? date('Y-m-d', strtotime($request->get('cheque_date'))) : NULL,
            'external_doc' => $request->external_doc,
            'created_at' => $timestamp,
            'created_by' => Auth::user()->id
        ]);

        if (!$payment) {
            throw new NotFoundHttpException();
        }

        $this->audit_logs('payments', $payment->id, 'has inserted a new payment.', Payment::find($payment->id), $timestamp, Auth::user()->id);
        $lines = Payment::where(['billing_id' => $billingID, 'is_active' => 1, 'status' => 'posted'])->get();
        $totalAmt = 0;
        if ($lines->count() > 0) {
            foreach ($lines as $line) {
                $totalAmt += floatval($line->amount);
            }
        }
        $bills = Billing::find($billingID);
        $bills->billing_paid = $totalAmt;
        $bills->status = (floatval($totalAmt) > 0) ? (floatval($totalAmt) >= floatval($bills->billing_amount)) ? 'completed' : 'partial' : 'open';
        $bills->updated_at = $timestamp;
        $bills->updated_by = Auth::user()->id;
        $bills->update();

        $data = array(
            'totalAmt' => number_format(floor(($totalAmt*100))/100,2),
            'title' => 'Well done!',
            'text' => 'The payment has been successfully stored.',
            'type' => 'success',
            'class' => 'btn-brand'
        );

        echo json_encode( $data ); exit();
    }

    public function update_payment_line(Request $request, $paymentID)
    {    
        // $this->is_permitted(2);
        $timestamp = date('Y-m-d H:i:s');
        $payment = Payment::find($paymentID);

        if(!$payment) {
            throw new NotFoundHttpException();
        }

        $billingID = $payment->billing_id;
        $payment->payment_type_id = $request->payment_type_id;
        $payment->bank_id = $request->bank_id;
        $payment->amount = $request->amount;
        $payment->bank_name = $request->get('bank_name');
        $payment->bank_no = $request->get('bank_no');
        $payment->bank_account = $request->get('bank_account');
        $payment->cheque_no = $request->get('cheque_no');
        $payment->cheque_date = $request->get('cheque_date') ? date('Y-m-d', strtotime($request->get('cheque_date'))) : NULL;
        $payment->external_doc = $request->external_doc;
        $payment->updated_at = $timestamp;
        $payment->updated_by = Auth::user()->id;

        if ($payment->update()) {
            $this->audit_logs('payments', $paymentID, 'has modified a payment.', Payment::find($paymentID), $timestamp, Auth::user()->id);
            $lines = Payment::where(['billing_id' => $billingID, 'is_active' => 1, 'status' => 'posted'])->get();
            $totalAmt = 0;
            if ($lines->count() > 0) {
                foreach ($lines as $line) {
                    $totalAmt += floatval($line->amount);
                }
            }
            $bills = Billing::find($billingID);
            $bills->billing_paid = $totalAmt;
            $bills->status = (floatval($totalAmt) > 0) ? (floatval($totalAmt) >= floatval($bills->billing_amount)) ? 'completed' : 'partial' : 'open';
            $bills->updated_at = $timestamp;
            $bills->updated_by = Auth::user()->id;
            $bills->update();
            $data = array(
                'totalAmt' => number_format(floor(($totalAmt*100))/100,2),
                'title' => 'Well done!',
                'text' => 'The payment has been successfully modified.',
                'type' => 'success',
                'class' => 'btn-brand'
            );
            echo json_encode( $data ); exit();
        }
    }

    public function find_payment_line(Request $request, $id)
    {
        $payment = Payment::find($id);

        if(!$payment) {
            throw new NotFoundHttpException();
        }

        $data = (object) array(
            'payment_id' => $payment->id,
            'payment_type_id' => $payment->payment_type_id,
            'bank_id' => $payment->bank_id,
            'bank_name' => $payment->bank_name,
            'bank_no' => $payment->bank_no,
            'bank_account' => $payment->bank_account,
            'cheque_no' => $payment->cheque_no,
            'cheque_date' => ($payment->cheque_date == NULL) ? '' : date('d-M-Y', strtotime($payment->cheque_date)),
            'amount' => $payment->amount,
            'external_doc' => $payment->external_doc
        );

        return response()
        ->json([
            'status' => 'ok',
            'data' => $data
        ]);
    }

    public function post_payment_line(Request $request, $paymentID)
    {
        $timestamp = date('Y-m-d H:i:s');
        $payment = Payment::find($paymentID);

        if(!$payment) {
            throw new NotFoundHttpException();
        }

        $billingID = $payment->billing_id;
        $payment->status = 'posted';
        $payment->updated_at = $timestamp;
        $payment->updated_by = Auth::user()->id;
        if ($payment->update()) {
            $this->audit_logs('payments', $paymentID, 'has posted a payment.', Payment::find($paymentID), $timestamp, Auth::user()->id);
            $lines = Payment::where(['billing_id' => $billingID, 'is_active' => 1, 'status' => 'posted'])->get();
            $totalAmt = 0;
            if ($lines->count() > 0) {
                foreach ($lines as $line) {
                    $totalAmt += floatval($line->amount);
                }
            }
            $bills = Billing::find($billingID);
            $bills->billing_paid = $totalAmt;
            $bills->status = (floatval($totalAmt) > 0) ? (floatval($totalAmt) >= floatval($bills->billing_amount)) ? 'completed' : 'partial' : 'open';
            $bills->updated_at = $timestamp;
            $bills->updated_by = Auth::user()->id;
            $bills->update();
            $data = array(
                'totalAmt' => number_format(floor(($totalAmt*100))/100,2),
                'title' => 'Well done!',
                'text' => 'The payment has been successfully posted.',
                'type' => 'success',
                'class' => 'btn-brand'
            );
            echo json_encode( $data ); exit();
        }
    }

    public function remove_payment_line(Request $request, $paymentID)
    {
        $timestamp = date('Y-m-d H:i:s');
        $payment = Payment::find($paymentID);

        if(!$payment) {
            throw new NotFoundHttpException();
        }

        $billingID = $payment->billing_id;
        $payment->is_active = 0;
        $payment->updated_at = $timestamp;
        $payment->updated_by = Auth::user()->id;
        if ($payment->update()) {
            $this->audit_logs('payments', $paymentID, 'has removed a payment.', Payment::find($paymentID), $timestamp, Auth::user()->id);
            $lines = Payment::where(['billing_id' => $billingID, 'is_active' => 1, 'status' => 'posted'])->get();
            $totalAmt = 0;
            if ($lines->count() > 0) {
                foreach ($lines as $line) {
                    $totalAmt += floatval($line->amount);
                }
            }
            $bills = Billing::find($billingID);
            $bills->billing_paid = $totalAmt;
            $bills->status = (floatval($totalAmt) > 0) ? (floatval($totalAmt) >= floatval($bills->billing_amount)) ? 'completed' : 'partial' : 'open';
            $bills->updated_at = $timestamp;
            $bills->updated_by = Auth::user()->id;
            $bills->update();
            $data = array(
                'totalAmt' => number_format(floor(($totalAmt*100))/100,2),
                'title' => 'Well done!',
                'text' => 'The payment has been successfully removed.',
                'type' => 'success',
                'class' => 'btn-brand'
            );
            echo json_encode( $data ); exit();
        }
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