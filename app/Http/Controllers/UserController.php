<?php

namespace App\Http\Controllers;

use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Role;
use App\Models\Branch;
use App\Models\AuditLog;
use App\Models\SecretQuestion;
use Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\File;

class UserController extends Controller
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
        $roles = (new Role)->all_roles_selectpicker();
        $branches = (new Branch)->all_branches_multiple_selectpicker();
        $secrets = (new SecretQuestion)->all_secret_question_selectpicker();
        return view('modules/components/users/manage')->with(compact('menus', 'roles', 'branches', 'secrets'));
    }

    public function inactive(Request $request)
    {   
        // $this->is_permitted(1);    
        $menus = $this->load_menus();
        $agents = (new User)->all_agents_selectpicker();
        return view('modules/components/users/manage-inactive')->with(compact('menus'));
    }

    public function store(Request $request)
    {   
        // $this->is_permitted(0);
        $timestamp = date('Y-m-d H:i:s');

        $rows = User::with([
            'role' =>  function($q) { 
                $q->select(['user_id', 'role_id']); 
            }
        ])->where([
            'email' => $request->email
        ])->count();

        if ($rows > 0) {
            $data = array(
                'field' => 'email',
                'title' => 'Oh snap!',
                'text' => 'You cannot create a user with an existing email.',
                'type' => 'error',
                'class' => 'btn-danger'
            );
    
            echo json_encode( $data ); exit();
        }
        
        $rows = User::with([
            'role' =>  function($q) { 
                $q->select(['user_id', 'role_id']); 
            }
        ])->where([
            'username' => $request->username
        ])->count();

        if ($rows > 0) {
            $data = array(
                'field' => 'username',
                'title' => 'Oh snap!',
                'text' => 'You cannot create a user with an username.',
                'type' => 'error',
                'class' => 'btn-danger'
            );
    
            echo json_encode( $data ); exit();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'password' => $request->password,
            'type' => (new Role)->where('id', $request->type)->first()->code,
            'assignment' => implode(',',$request->assignment),
            'secret_question_id' => $request->secret_question_id,
            'secret_password' => Hash::make($request->secret_password),
            'created_at' => $timestamp,
            'created_by' => Auth::user()->id
        ]);

        $userRole = UserRole::create([
            'user_id' => $user->id,
            'role_id' => $request->type,
            'created_at' => $timestamp,
            'created_by' => Auth::user()->id
        ]);

        if (!$user) {
            throw new NotFoundHttpException();
        }

        $this->audit_logs('users', $user->id, 'has inserted a new user.', User::find($user->id), $timestamp, Auth::user()->id);
        $this->audit_logs('users_roles', $userRole->id, 'has inserted a new user role.', UserRole::find($userRole->id), $timestamp, Auth::user()->id);
        
        $data = array(
            'title' => 'Well done!',
            'text' => 'The user has been successfully saved.',
            'type' => 'success',
            'class' => 'btn-brand'
        );

        echo json_encode( $data ); exit();
    }

    public function find(Request $request, $id)
    {    
        $user = User::find($id);

        if(!$user) {
            throw new NotFoundHttpException();
        }

        $data = (object) array(
            'id' => $user->id,
            'name' => $user->name,
            'type' => (new Role)->where('code', $user->type)->first()->id,
            'assignment' => explode(',',$user->assignment),
            'username' => $user->username,
            'email' => $user->email,
            'secret_question_id' => $user->secret_question_id
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
        $rows = User::with([
            'role' =>  function($q) { 
                $q->select(['user_id', 'role_id']); 
            }
        ])
        ->where('id', '!=', $id)
        ->where([
            'email' => $request->email
        ])->count();

        if ($rows > 0) {
            $data = array(
                'field' => 'email',
                'title' => 'Oh snap!',
                'text' => 'You cannot create a user with an existing email.',
                'type' => 'error',
                'class' => 'btn-danger'
            );
    
            echo json_encode( $data ); exit();
        }
        
        $rows = User::with([
            'role' =>  function($q) { 
                $q->select(['user_id', 'role_id']); 
            }
        ])
        ->where('id', '!=', $id)
        ->where([
            'username' => $request->username
        ])->count();
        
        $user = User::find($id);
        
        if(!$user) {
            throw new NotFoundHttpException();
        }

        $rows = User::where('id', '!=', $id)->where('email', $request->email)->count();    

        if ($rows > 0) {
            $data = array(
                'title' => 'Oh snap!',
                'rows' => $rows,
                'text' => 'The email is already in use.',
                'type' => 'error',
                'class' => 'btn-danger'
            );
    
            echo json_encode( $data ); exit();
        }

        $password = User::where('id', '=', $id)->pluck('password');
        if ($password != $request->password) {
            $secret_password = User::where('id', '=', $id)->pluck('secret_password');
            if ($secret_password != $request->secret_password) {
                User::where('id', '=', $id)
                ->update([
                    'username' => $request->username,
                    'name' => $request->name,
                    'email' => $request->email,
                    'assignment' => implode(',',$request->assignment),
                    'password' => Hash::make($request->password),
                    'type' => (new Role)->where('id', $request->type)->first()->code,
                    'secret_question_id' => $request->secret_question_id,
                    'secret_password' => Hash::make($request->secret_password),
                    'updated_at' => $timestamp,
                    'updated_by' => Auth::user()->id
                ]);
            } else {
                User::where('id', '=', $id)
                ->update([
                    'username' => $request->username,
                    'name' => $request->name,
                    'email' => $request->email,
                    'assignment' => implode(',',$request->assignment),
                    'password' => Hash::make($request->password),
                    'type' => (new Role)->where('id', $request->type)->first()->code,
                    'secret_question_id' => $request->secret_question_id,
                    'updated_at' => $timestamp,
                    'updated_by' => Auth::user()->id
                ]);
            }
        } else {
            $secret_password = User::where('id', '=', $id)->pluck('secret_password');
            if ($secret_password != $request->secret_password) {
                User::where('id', '=', $id)
                ->update([
                    'username' => $request->username,
                    'name' => $request->name,
                    'email' => $request->email,
                    'assignment' => implode(',',$request->assignment),
                    'type' => (new Role)->where('id', $user->role->type)->pluck('name'),
                    'secret_question_id' => $request->secret_question_id,
                    'secret_password' => Hash::make($request->secret_password),
                    'updated_at' => $timestamp,
                    'updated_by' => Auth::user()->id
                ]);
            } else {
                User::where('id', '=', $id)
                ->update([
                    'username' => $request->username,
                    'name' => $request->name,
                    'email' => $request->email,
                    'assignment' => implode(',',$request->assignment),
                    'type' => (new Role)->where('id', $user->role->type)->pluck('name'),
                    'secret_question_id' => $request->secret_question_id,
                    'updated_at' => $timestamp,
                    'updated_by' => Auth::user()->id
                ]);
            }
        }

        $this->audit_logs('users', $id, 'has modified a user.', User::find($id), $timestamp, Auth::user()->id);
        
        $user_role = UserRole::where('user_id', '=', $id)
        ->update([
            'user_id' => $user->id,
            'role_id' => $request->type,
            'updated_at' => $timestamp,
            'updated_by' => Auth::user()->id
        ]);
        $user_role = UserRole::where('user_id', '=', $id)->get();
        if ($user_role->count() > 0) {
            $this->audit_logs('users_roles', $user_role->first()->id, 'has modified a user role.', UserRole::find($user_role->first()->id), $timestamp, Auth::user()->id);
        }

        $data = array(
            'title' => 'Well done!',
            'text' => 'The user has been successfully updated.',
            'type' => 'success',
            'class' => 'btn-brand'
        );

        echo json_encode( $data ); exit();
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
        $msg .= '<table class="table align-middle table-row-dashed fs-6 gy-5" id="userTable">';
        $msg .= '<thead>';
            $msg .= '<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">';
            $msg .= '<th class="w-10px pe-2">';
            $msg .= '<div class="form-check form-check-sm form-check-custom form-check-solid me-3">';
            $msg .= '<input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_purchase_order_type_table .form-check-input" value="1" />';
            $msg .= '</div>';
            $msg .= '</th>';
            $msg .= '<th class="min-w-50px">Name</th>';
            $msg .= '<th class="min-w-125px">Email</th>';
            $msg .= '<th class="min-w-125px">Username</th>';
            $msg .= '<th class="min-w-125px">Type</th>';
            $msg .= '<th class="min-w-125px">Assignment</th>';
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
                $msg .= '<tr data-row-id="'.$row->id.'" data-row-user="'.$row->username.'">';
                $msg .= '<td>';
                $msg .= '<div class="form-check form-check-sm form-check-custom form-check-solid">';
                $msg .= '<input class="form-check-input" type="checkbox" value="'.$row->id.'" />';
                $msg .= '</div>';
                $msg .= '</td>';
                $msg .= '<td>';
                $msg .= '<a href="#" class="text-gray-800 text-hover-primary mb-1">'.$row->name.'</a>';
                $msg .= '</td>';
                $msg .= '<td>'.$row->email.'</td>';
                $msg .= '<td>'.$row->username.'</td>';
                $msg .= '<td>'.$row->type.'</td>';
                $msg .= '<td>'.$row->assignment.'</td>';
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
                $msg .= '<a href="javascript:;" title="remove this" class="remove-btn btn btn-sm btn-light btn-active-light-danger">';
                $msg .= '<!--begin::Svg Icon | path: assets/media/icons/duotone/General/Trash.svg-->
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

    public function get_line_items($limit, $start_from, $keywords = '', $status)
    {
        if (!empty($keywords)) {
            $res = User::select([
                'users.id',
                'users.name',
                'users.email',
                'users.username',
                'users.type',
                'users.assignment',
                'users.created_at',
                'users.updated_at'
            ])
            ->where('users.id', '!=', 1)
            ->where('users.is_active', $status)
            ->where(function($q) use ($keywords) {
                $q->where('users.username', 'like', '%' . $keywords . '%')
                  ->orWhere('users.name', 'like', '%' . $keywords . '%')
                  ->orWhere('users.email', 'like', '%' . $keywords . '%')
                  ->orWhere('users.type', 'like', '%' . $keywords . '%');
            })
            ->skip($start_from)->take($limit)
            ->orderBy('users.id', 'desc')
            ->get();
        } else {
            $res = User::select([
                'users.id',
                'users.name',
                'users.email',
                'users.username',
                'users.type',
                'users.assignment',
                'users.created_at',
                'users.updated_at'
            ])
            ->where('users.id', '!=', 1)
            ->where('users.is_active', $status)
            ->skip($start_from)->take($limit)
            ->orderBy('users.id', 'desc')
            ->get();
        }

        return $res->map(function($user) {
            return (object) [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'type' => ucwords(str_replace('-',' ', $user->type)),
                'assignment' => (new Branch)->get_assignment($user->assignment),
                'modified_at' => ($user->updated_at !== NULL) ? date('d-M-Y', strtotime($user->updated_at)).'<br/>'. date('h:i A', strtotime($user->updated_at)) : date('d-M-Y', strtotime($user->created_at)).'<br/>'. date('h:i A', strtotime($user->created_at))
            ];
        });
    }

    public function get_page_count($keywords = '', $status)
    {
        if (!empty($keywords)) {
            $res = User::select([
                'users.id',
                'users.name',
                'users.email',
                'users.username',
                'users.type',
                'users.assignment',
                'users.created_at',
                'users.updated_at'
            ])
            ->where('users.id', '!=', 1)
            ->where('users.is_active', $status)
            ->where(function($q) use ($keywords) {
                $q->where('users.username', 'like', '%' . $keywords . '%')
                  ->orWhere('users.name', 'like', '%' . $keywords . '%')
                  ->orWhere('users.email', 'like', '%' . $keywords . '%')
                  ->orWhere('users.type', 'like', '%' . $keywords . '%');
            })
            ->count();
        } else {
            $res = User::select([
                'users.id',
                'users.name',
                'users.email',
                'users.username',
                'users.type',
                'users.assignment',
                'users.created_at',
                'users.updated_at'
            ])
            ->where('users.id', '!=', 1)
            ->where('users.is_active', $status)
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
        $msg .= '<table class="table align-middle table-row-dashed fs-6 gy-5" id="userTable">';
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
                $msg .= '<tr data-row-id="'.$row->id.'" data-row-user="'.$row->username.'">';
                $msg .= '<td>';
                $msg .= '<div class="form-check form-check-sm form-check-custom form-check-solid">';
                $msg .= '<input class="form-check-input" type="checkbox" value="'.$row->id.'" />';
                $msg .= '</div>';
                $msg .= '</td>';
                $msg .= '<td>';
                $msg .= '<a href="#" class="text-gray-800 text-hover-primary mb-1">'.$row->name.'</a>';
                $msg .= '</td>';
                $msg .= '<td>'.$row->email.'</td>';
                $msg .= '<td>'.$row->username.'</td>';
                $msg .= '<td>'.$row->type.'</td>';
                $msg .= '<td>'.$row->assignment.'</td>';
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

    public function remove(Request $request, $id)
    {   
        // $this->is_permitted(3);
        $timestamp = date('Y-m-d H:i:s');
        $user = User::where([
            'id' => $id,
        ])
        ->update([
            'updated_at' => $timestamp,
            'updated_by' => Auth::user()->id,
            'is_active' => 0
        ]);
        $this->audit_logs('purchase_orders_types', $id, 'has removed a purchase order type.', User::find($id), $timestamp, Auth::user()->id);
        
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
        $user = User::where([
            'id' => $id,
        ])
        ->update([
            'updated_at' => $timestamp,
            'updated_by' => Auth::user()->id,
            'is_active' => 1
        ]);
        $this->audit_logs('purchase_orders_types', $id, 'has restored a purchase order type.', User::find($id), $timestamp, Auth::user()->id);
        
        $data = array(
            'title' => 'Well done!',
            'text' => 'The purchase order type has been successfully restored.',
            'type' => 'success',
            'class' => 'btn-brand'
        );

        echo json_encode( $data ); exit();
    }

    public function export(Request $request)
    {   
        $fileName = 'users_'.time().'.csv';

        $users = User::select([
            'users.name', 
            'users.email', 
            'users.type',
            'users.username',
            'users.assignment',
        ])
        ->where('users.is_active', 1)
        ->orderBy('users.id', 'asc')
        ->get();

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('Name', 'Email', 'Type', 'Username', 'Assignment');
        $callback = function() use($users, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($users as $user) {
                // $assignment = array();
                // $assignments = explode(',', $user->assignment);
                // foreach ($assignments as $assignmentx) {
                //     $assignment[] = (new Branch)->where(['id', $assignmentx])->first()->name;
                // }
                $row['name']      = $user->name;
                $row['email']     = $user->email;
                $row['type']      = $user->type;
                $row['username']  = $user->username;
                $row['assignment'] = $user->assignment;
                fputcsv($file, array($row['name'], $row['email'], $row['type'], $row['username'], $row['assignment']));
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
                        $exist = User::where('username', $data[3])->get();
                        if ($exist->count() > 0) {
                            $user = User::find($exist->first()->id);
                            $user->name = $data[0];
                            $user->username = $data[1];
                            $user->type = $data[2];
                            $user->type = $data[2];
                            $user->type = $data[2];
                            $user->updated_at = $timestamp;
                            $user->updated_by = Auth::user()->id;

                            if ($user->update()) {
                                $this->audit_logs('purchase_orders_types', $exist->first()->id, 'has modified a purchase order type.', User::find($exist->first()->id), $timestamp, Auth::user()->id);
                            }
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