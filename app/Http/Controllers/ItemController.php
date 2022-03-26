<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemInventory;
use App\Models\ItemTransaction;
use App\Models\ItemTransfer;
use App\Models\UnitOfMeasurement;
use App\Models\User;
use App\Models\AuditLog;
use Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\File;
// use App\Components\FlashMessages;
// use App\Helper\Helper;

class ItemController extends Controller
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
        // // $this->is_permitted(1);    
        $menus = $this->load_menus();
        $branches = (new Branch)->all_branches_selectpicker(Auth::user()->id);
        $unit_of_measurements = (new UnitOfMeasurement)->all_uom_selectpicker();
        $categories = (new ItemCategory)->all_item_category_selectpicker();
        $users = (new User)->all_users_selectpicker();
        $receivingTrans = ['' => 'Select a transaction', 'Returned Item' => 'Returned Item'];
        $withdrawalTrans = ['' => 'Select a transaction', 'Withdrawal' => 'Withdrawal', 'Transfer Item' => 'Transfer Item', 'Damaged Item' => 'Damaged Item'];
        return view('modules/components/items/manage')->with(compact('menus', 'users', 'branches', 'unit_of_measurements', 'categories', 'withdrawalTrans', 'receivingTrans'));
    }

    public function inactive(Request $request)
    {       
        // // $this->is_permitted(1);    
        $menus = $this->load_menus();
        $branches = (new Branch)->all_branches_selectpicker();
        $unit_of_measurements = (new UnitOfMeasurement)->all_uom_selectpicker();
        $categories = (new ItemCategory)->all_item_category_selectpicker();
        return view('modules/components/items/manage-inactive')->with(compact('menus', 'branches', 'unit_of_measurements', 'categories'));
    }

    public function generate_item_code()
    {
        $count = Item::count();
        if ($count < 9) {
            return 'H-0000'.($count + 1);
        } else if ($count < 99) {
            return 'H-000'.($count + 1);
        } else if ($count < 999) {
            return 'H-00'.($count + 1);
        } else if ($count < 9999) {
            return 'H-0'.($count + 1);
        } else if ($count < 99999) {
            return 'H-'.($count + 1);
        }
    }

    public function store(Request $request)
    {   
        // $this->is_permitted(0);
        $timestamp = date('Y-m-d H:i:s');

        $rows = Item::where([
            'code' => $request->code
        ])->count();

        if ($rows > 0) {
            $data = array(
                'title' => 'Oh snap!',
                'text' => 'You cannot create a item with an existing code.',
                'type' => 'error',
                'class' => 'btn-danger'
            );
    
            echo json_encode( $data ); exit();
        }

        $item = Item::create([
            'item_category_id' => $request->item_category_id,
            'uom_id' => $request->uom_id,
            'code' => $this->generate_item_code(),
            'name' => $request->name,
            'description' => $request->description,
            'srp' => $request->srp,
            'reorder_level' => $request->reorder_level,
            'created_at' => $timestamp,
            'created_by' => Auth::user()->id
        ]);

        if (!$item) {
            throw new NotFoundHttpException();
        }

        $this->audit_logs('items', $item->id, 'has inserted a new item.', Item::find($item->id), $timestamp, Auth::user()->id);
        
        $branches = (new Branch)->all_branches();
        foreach ($branches as $branch) {
            $itemInventory = ItemInventory::create([
                'item_id' => $item->id,
                'branch_id' => $branch->id,
                'quantity' => 0,
                'created_at' => $timestamp,
                'created_by' => Auth::user()->id
            ]);
            if (!$itemInventory) {
                throw new NotFoundHttpException();
            }
            $this->audit_logs('items_inventory', $itemInventory->id, 'has inserted a new item inventory.', ItemInventory::find($itemInventory->id), $timestamp, Auth::user()->id);
        }

        $data = array(
            'title' => 'Well done!',
            'text' => 'The item has been successfully stored.',
            'type' => 'success',
            'class' => 'btn-brand'
        );

        echo json_encode( $data ); exit();
    }

    public function find(Request $request, $id)
    {    
        $item = Item::find($id);

        if(!$item) {
            throw new NotFoundHttpException();
        }

        return response()
        ->json([
            'status' => 'ok',
            'data' => $item
        ]);
    }

    public function update(Request $request, $id)
    {    
        // $this->is_permitted(2);
        $timestamp = date('Y-m-d H:i:s');
        $item = Item::find($id);

        if(!$item) {
            throw new NotFoundHttpException();
        }

        $item->item_category_id = $request->item_category_id;
        $item->uom_id = $request->uom_id;
        $item->name = $request->name;
        $item->description = $request->description;
        $item->srp = $request->srp;
        $item->reorder_level = $request->reorder_level;
        $item->updated_at = $timestamp;
        $item->updated_by = Auth::user()->id;

        if ($item->update()) {
            $this->audit_logs('items', $id, 'has modified an item.', Item::find($id), $timestamp, Auth::user()->id);
            
            $branches = (new Branch)->all_branches();
            foreach ($branches as $branch) {
                $exist = ItemInventory::where(['item_id' => $id, 'branch_id' => $branch->id])->get();
                if (!($exist->count() > 0)) {
                    $itemInventory = ItemInventory::create([
                        'item_id' => $item->id,
                        'branch_id' => $branch->id,
                        'quantity' => 0,
                        'created_at' => $timestamp,
                        'created_by' => Auth::user()->id
                    ]);
                    if (!$itemInventory) {
                        throw new NotFoundHttpException();
                    }
                    $this->audit_logs('items_inventory', $itemInventory->id, 'has inserted a new item inventory.', ItemInventory::find($itemInventory->id), $timestamp, Auth::user()->id);
                }
            }
            
            $data = array(
                'title' => 'Well done!',
                'text' => 'The item has been successfully modified.',
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
        $msg .= '<table class="table align-middle table-row-dashed fs-6 gy-5" id="itemTable">';
        $msg .= '<thead>';
            $msg .= '<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">';
            $msg .= '<th class="w-10px pe-2">';
            $msg .= '<div class="form-check form-check-sm form-check-custom form-check-solid me-3">';
            $msg .= '<input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_items_table .form-check-input" value="1" />';
            $msg .= '</div>';
            $msg .= '</th>';
            $msg .= '<th class="min-w-50px">Code</th>';
            $msg .= '<th class="min-w-125px">Product Category</th>';
            $msg .= '<th class="min-w-125px">Item Description</th>';
            $msg .= '<th class="min-w-125px text-center">Total Quantity</th>';
            $msg .= '<th class="min-w-125px text-center">UOM</th>';
            $msg .= '<th class="text-center min-w-125px">SRP</th>';
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
                $itemDescription = (strlen($row->description) > 0) ? $row->name.'<br/>('.$row->description.')' : $row->name;
                $itemSrp = number_format(floor(($row->srp*100))/100, 2);
                if ($itemSrp <= 0) {
                    $itemSrp = 0;
                }
                $itemInventory = Item::with([
                    'inventory' =>  function($q) { 
                        $q->select(['id', 'item_id', 'quantity']);
                    }
                ])
                ->where(['id' => $row->id])->first()->inventory()->sum('quantity');
                $msg .= '<tr data-row-id="'.$row->id.'" data-row-code="'.$row->code.'" data-row-name="'.$row->name.'">';
                $msg .= '<td>';
                $msg .= '<div class="form-check form-check-sm form-check-custom form-check-solid">';
                $msg .= '<input class="form-check-input" type="checkbox" value="'.$row->id.'" />';
                $msg .= '</div>';
                $msg .= '</td>';
                $msg .= '<td>';
                $msg .= '<a href="#" class="text-gray-800 text-hover-primary mb-1">'.$row->code.'</a>';
                $msg .= '</td>';
                $msg .= '<td>'.$row->category.'</td>';
                $msg .= '<td>'.$itemDescription.'</td>';
                $msg .= '<td class="text-center">'.$itemInventory.'</td>';
                $msg .= '<td class="text-center">'.$row->uom.'</td>';
                $msg .= '<td class="text-center">'.$itemSrp.'</td>';
                $msg .= '<td class="text-center">'.$row->modified_at.'</td>';
                $msg .= '<td class="text-center">';
                $msg .= '<a href="javascript:;" title="modify this" class="edit-btn btn btn-sm btn-light btn-active-light-primary">';
                $msg .= '<!--begin::Svg Icon | path: assets/media/icons/duotone/Design/Edit.svg-->
                <span class="svg-icon svg-icon-muted svg-icon-2hx"><svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                        <path d="M8,17.9148182 L8,5.96685884 C8,5.56391781 8.16211443,5.17792052 8.44982609,4.89581508 L10.965708,2.42895648 C11.5426798,1.86322723 12.4640974,1.85620921 13.0496196,2.41308426 L15.5337377,4.77566479 C15.8314604,5.0588212 16,5.45170806 16,5.86258077 L16,17.9148182 C16,18.7432453 15.3284271,19.4148182 14.5,19.4148182 L9.5,19.4148182 C8.67157288,19.4148182 8,18.7432453 8,17.9148182 Z" fill="#000000" fill-rule="nonzero" transform="translate(12.000000, 10.707409) rotate(-135.000000) translate(-12.000000, -10.707409) "/>
                        <rect fill="#000000" opacity="0.3" x="5" y="20" width="15" height="2" rx="1"/>
                </svg></span>
                <!--end::Svg Icon--></a>';
                $msg .= '<a href="javascript:;" title="remove this" class="remove-btn btn btn-sm btn-light btn-active-light-danger">';
                $msg .= '<!--begin::Svg Icon | path: assets/media/icons/duotone/Design/Eraser.svg-->
                <span class="svg-icon svg-icon-muted svg-icon-2hx"><svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                        <path d="M6,9 L6,15 L10,15 L10,9 L6,9 Z M6.25,7 L19.75,7 C20.9926407,7 22,7.81402773 22,8.81818182 L22,15.1818182 C22,16.1859723 20.9926407,17 19.75,17 L6.25,17 C5.00735931,17 4,16.1859723 4,15.1818182 L4,8.81818182 C4,7.81402773 5.00735931,7 6.25,7 Z" fill="#000000" fill-rule="nonzero" transform="translate(13.000000, 12.000000) rotate(-45.000000) translate(-13.000000, -12.000000) "/>
                </svg></span>
                <!--end::Svg Icon--></a>';

                $msg .= '<a href="javascript:;" title="view this" class="view-btn btn btn-sm btn-light btn-active-light-info">';
                $msg .= '<!--begin::Svg Icon | path: assets/media/icons/duotone/Communication/Chat6.svg-->
                <span class="svg-icon svg-icon-muted svg-icon-2hx"><svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                    <path opacity="0.3" fill-rule="evenodd" clip-rule="evenodd" d="M14.4862 18L12.7975 21.0566C12.5304 21.54 11.922 21.7153 11.4386 21.4483C11.2977 21.3704 11.1777 21.2597 11.0887 21.1255L9.01653 18H5C3.34315 18 2 16.6569 2 15V6C2 4.34315 3.34315 3 5 3H19C20.6569 3 22 4.34315 22 6V15C22 16.6569 20.6569 18 19 18H14.4862Z" fill="black"/>
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M6 7H15C15.5523 7 16 7.44772 16 8C16 8.55228 15.5523 9 15 9H6C5.44772 9 5 8.55228 5 8C5 7.44772 5.44772 7 6 7ZM6 11H11C11.5523 11 12 11.4477 12 12C12 12.5523 11.5523 13 11 13H6C5.44772 13 5 12.5523 5 12C5 11.4477 5.44772 11 6 11Z" fill="black"/>
                </svg></span>
                <!--end::Svg Icon--></a>';

                $msg .= '<br/><div style="margin-top:10px"></div>';

                $msg .= '<a href="javascript:;" title="post item" class="post-btn btn btn-sm btn-light btn-active-light-success">';
                $msg .= '<!--begin::Svg Icon | path: assets/media/icons/duotone/Navigation/Plus.svg-->
                <span class="svg-icon svg-icon-muted svg-icon-2hx"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                        <rect fill="#000000" x="4" y="11" width="16" height="2" rx="1"/>
                        <rect fill="#000000" opacity="0.5" transform="translate(12.000000, 12.000000) rotate(-270.000000) translate(-12.000000, -12.000000) " x="4" y="11" width="16" height="2" rx="1"/>
                </svg></span>
                <!--end::Svg Icon--></a>';

                $msg .= '<a href="javascript:;" title="withdraw item" class="withdraw-btn btn btn-sm btn-light btn-active-light-warning">';
                $msg .= '<!--begin::Svg Icon | path: assets/media/icons/duotone/Navigation/Minus.svg-->
                <span class="svg-icon svg-icon-muted svg-icon-2hx"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                        <rect fill="#000000" x="4" y="11" width="16" height="2" rx="1"/>
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

        $msg .= '<div class="row"><div class="col-sm-6 pl-5"><div class="dataTables_paginate paging_simple_numbers" id="kt_items_table_paginate"><ul class="pagination" style="margin-bottom: 0;">';

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
            $res = Item::select([
                'items.code',
                'items.id',
                'items.name',
                'items.description',
                'items.srp',
                'items.reorder_level',
                'items_category.name as category',
                'unit_of_measurements.code as uom',
                'items.created_at',
                'items.updated_at'
            ])
            ->leftJoin('items_category', function($join)
            {
                $join->on('items_category.id', '=', 'items.item_category_id');
            })
            ->leftJoin('unit_of_measurements', function($join)
            {
                $join->on('unit_of_measurements.id', '=', 'items.uom_id');
            })
            ->whereIn('items.id',
                (new ItemInventory)->select('item_id')
                ->whereIn('branch_id', 
                    explode(',', trim((new User)->select(['assignment'])->where('id', Auth::user()->id)->first()->assignment))
                )
                ->where('is_active', 1)
            )
            ->where('items.is_active', 1)
            ->where(function($q) use ($keywords) {
                $q->where('items.code', 'like', '%' . $keywords . '%')
                  ->orWhere('items.name', 'like', '%' . $keywords . '%')
                  ->orWhere('items.description', 'like', '%' . $keywords . '%')
                  ->orWhere('items.mobile_no', 'like', '%' . $keywords . '%')
                  ->orWhere('items.email', 'like', '%' . $keywords . '%')
                  ->orWhere('items_category.name', 'like', '%' . $keywords . '%')
                  ->orWhere('unit_of_measurements.code', 'like', '%' . $keywords . '%');
            })
            ->skip($start_from)->take($limit)
            ->orderBy('items.id', 'desc')
            ->get();
        } else {
            $res = Item::select([
                'items.code',
                'items.id',
                'items.name',
                'items.description',
                'items.srp',
                'items.reorder_level',
                'items_category.name as category',
                'unit_of_measurements.code as uom',
                'items.created_at',
                'items.updated_at'
            ])
            ->leftJoin('items_category', function($join)
            {
                $join->on('items_category.id', '=', 'items.item_category_id');
            })
            ->leftJoin('unit_of_measurements', function($join)
            {
                $join->on('unit_of_measurements.id', '=', 'items.uom_id');
            })
            ->whereIn('items.id',
                (new ItemInventory)->select('item_id')
                ->whereIn('branch_id', 
                    explode(',', trim((new User)->select(['assignment'])->where('id', Auth::user()->id)->first()->assignment))
                )
                ->where('is_active', 1)
            )
            ->where('items.is_active', 1)
            ->skip($start_from)->take($limit)
            ->orderBy('items.id', 'desc')
            ->get();
        }

        return $res->map(function($item) {
            return (object) [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
                'description' => $item->description,
                'uom' => $item->uom,
                'category' => $item->category,
                'srp' => $item->srp,
                'reorder_level' => $item->reorder_level,
                'modified_at' => ($item->updated_at !== NULL) ? date('d-M-Y', strtotime($item->updated_at)).'<br/>'. date('h:i A', strtotime($item->updated_at)) : date('d-M-Y', strtotime($item->created_at)).'<br/>'. date('h:i A', strtotime($item->created_at))
            ];
        });
    }

    public function get_page_count($limit, $start_from, $keywords = '')
    {
        if (!empty($keywords)) {
            $res = Item::select([
                'items.code',
                'items.id',
                'items.name',
                'items.description',
                'items.srp',
                'items.reorder_level',
                'items_category.name as category',
                'unit_of_measurements.name as uom'
            ])
            ->leftJoin('items_category', function($join)
            {
                $join->on('items_category.id', '=', 'items.item_category_id');
            })
            ->leftJoin('unit_of_measurements', function($join)
            {
                $join->on('unit_of_measurements.id', '=', 'items.uom_id');
            })
            ->whereIn('items.id',
                (new ItemInventory)->select('item_id')
                ->whereIn('branch_id', 
                    explode(',', trim((new User)->select(['assignment'])->where('id', Auth::user()->id)->first()->assignment))
                )
                ->where('is_active', 1)
            )
            ->where('items.is_active', 1)
            ->where(function($q) use ($keywords) {
                $q->where('items.code', 'like', '%' . $keywords . '%')
                  ->orWhere('items.name', 'like', '%' . $keywords . '%')
                  ->orWhere('items.description', 'like', '%' . $keywords . '%')
                  ->orWhere('items.mobile_no', 'like', '%' . $keywords . '%')
                  ->orWhere('items.email', 'like', '%' . $keywords . '%')
                  ->orWhere('items_category.name', 'like', '%' . $keywords . '%')
                  ->orWhere('unit_of_measurements.code', 'like', '%' . $keywords . '%');
            })
            ->count();
        } else {
            $res = Item::select([
                'items.code',
                'items.id',
                'items.name',
                'items.description',
                'items.srp',
                'items.reorder_level',
                'items_category.name as category',
                'unit_of_measurements.name as uom'
            ])
            ->leftJoin('items_category', function($join)
            {
                $join->on('items_category.id', '=', 'items.item_category_id');
            })
            ->leftJoin('unit_of_measurements', function($join)
            {
                $join->on('unit_of_measurements.id', '=', 'items.uom_id');
            })
            ->whereIn('items.id',
                (new ItemInventory)->select('item_id')
                ->whereIn('branch_id', 
                    explode(',', trim((new User)->select(['assignment'])->where('id', Auth::user()->id)->first()->assignment))
                )
                ->where('is_active', 1)
            )
            ->where('items.is_active', 1)
            ->count();
        }

        return $res;
    }

    public function remove(Request $request, $id)
    {   
        // $this->is_permitted(3);
        $timestamp = date('Y-m-d H:i:s');
        $item = Item::where([
            'id' => $id,
        ])
        ->update([
            'updated_at' => $timestamp,
            'updated_by' => Auth::user()->id,
            'is_active' => 0
        ]);
        $this->audit_logs('items', $id, 'has removed a item.', Item::find($id), $timestamp, Auth::user()->id);
        
        $data = array(
            'title' => 'Well done!',
            'text' => 'The item has been successfully removed.',
            'type' => 'success',
            'class' => 'btn-brand'
        );

        echo json_encode( $data ); exit();
    }

    public function restore(Request $request, $id)
    {   
        // $this->is_permitted(3);
        $timestamp = date('Y-m-d H:i:s');
        $item = Item::where([
            'id' => $id,
        ])
        ->update([
            'updated_at' => $timestamp,
            'updated_by' => Auth::user()->id,
            'is_active' => 1
        ]);
        $this->audit_logs('items', $id, 'has removed a item.', Item::find($id), $timestamp, Auth::user()->id);
        
        $data = array(
            'title' => 'Well done!',
            'text' => 'The item has been successfully removed.',
            'type' => 'success',
            'class' => 'btn-brand'
        );

        echo json_encode( $data ); exit();
    }

    public function export(Request $request)
    {   
        $fileName = 'items.csv';

        $items = Item::select(['items.id', 'items.code', 'items.name', 'items.description', 'items.email', 'items.mobile_no', 'items.mobile_no', 'items.agent_id'])
        ->join('users', function($join)
        {
            $join->on('users.id', '=', 'items.agent_id');
        })
        ->where('items.is_active', 1)
        ->orderBy('items.id', 'asc')
        ->get();

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('ID No.', 'Code', 'Name', 'Company', 'Email', 'Mobile No.', 'Address', 'Agent ID');

        $callback = function() use($items, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($items as $item) {
                $row['id']        = $item->id;
                $row['code']      = $item->code;
                $row['name']      = $item->name;
                $row['company']   = $item->description;
                $row['email']     = $item->email;
                $row['mobile_no'] = $item->mobile_no;
                $row['address']   = $item->address;
                $row['agent_id']  = ($item->agent_id > 0) ? $item->agent_id : '-';
                fputcsv($file, array($row['id'], $row['code'], $row['name'], $row['company'], $row['email'], $row['mobile_no'], $row['address'], $row['agent_id']));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function get_all_inventory(Request $request, $id)
    {   
        $res = ItemInventory::with([
            'item' =>  function($q) { 
                $q->select(['items.id', 'items.code', 'items.name', 'items.description', 'items.srp', 'items_category.name as category', 'unit_of_measurements.code as uom'])
                ->leftJoin('unit_of_measurements', function($join)
                {
                    $join->on('unit_of_measurements.id', '=', 'items.uom_id');
                })
                ->leftJoin('items_category', function($join)
                {
                    $join->on('items_category.id', '=', 'items.item_category_id');
                });
            },
            'branch' =>  function($q) { 
                $q->select(['id', 'code', 'name']);
            },
        ])
        ->whereIn('branch_id', 
            explode(',', trim((new User)->select(['assignment'])->where('id', Auth::user()->id)->first()->assignment))
        )
        ->where(['item_id' => $id, 'is_active' => 1])->get();

        return $res->map(function($item) {
            return (object) [
                'id' => $item->id,
                'branch_id' => $item->branch->id,
                'branch' => $item->branch->name,
                'code' => $item->item->code,
                'name' => $item->item->name,
                'description' => $item->item->description,
                'uom' => $item->item->uom,
                'category' => $item->item->category,
                'srp' => $item->item->srp,
                'quantity' => $item->quantity,
                'reorder_level' => $item->item->reorder_level,
                'modified_at' => ($item->updated_at !== NULL) ? date('d-M-Y', strtotime($item->updated_at)).'<br/>'. date('h:i A', strtotime($item->updated_at)) : date('d-M-Y', strtotime($item->created_at)).'<br/>'. date('h:i A', strtotime($item->created_at))
            ];
        });
    }

    public function all_active_inventory(Request $request)
    {   
        $keywords     = $request->get('keywords');  
        $itemID       = $request->get('itemID');
        $branchID     = $request->get('branchID');
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
        $msg .= '<table class="table align-middle table-row-dashed fs-6 gy-5" id="itemTable">';
        $msg .= '<thead>';
            $msg .= '<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">';
            $msg .= '<th class="min-w-50px">Transaction</th>';
            $msg .= '<th class="min-w-125px text-center">Transaction Date</th>';
            $msg .= '<th class="min-w-150px">Issued By</th>';
            $msg .= '<th class="min-w-150px">Received By</th>';
            $msg .= '<th class="min-w-100px text-center">Based Quantity</th>';
            $msg .= '<th class="min-w-100px text-center">Issued Quantity</th>';
            $msg .= '<th class="min-w-100px text-center">Left Quantity</th>';
            $msg .= '<th class="min-w-175px">Remarks</th>';
            $msg .= '</tr>';
        $msg .= '</thead>';
        $msg .= '<tbody class="fw-bold text-gray-600">';
        
        $query = $this->get_line_items_inventory($per_page, $start_from, $keywords, $itemID, $branchID);
        $count = $this->get_page_count_inventory($per_page, $start_from, $keywords, $itemID, $branchID);
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
                $msg .= '<tr">';
                $msg .= '<td>'.$row->transaction.'</td>';
                $msg .= '<td class="text-center">'.$row->transaction_date.'</td>';
                $msg .= '<td>'.$row->issued_by.'</td>';
                $msg .= '<td>'.$row->received_by.'</td>';
                $msg .= '<td class="text-center">'.$row->based_quantity.'</td>';
                $msg .= '<td class="text-center">'.$row->issued_quantity.'</td>';
                $msg .= '<td class="text-center">'.$row->left_quantity.'</td>';
                $msg .= '<td>'.$row->remarks.'</td>';
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

        $msg .= '<div class="row"><div class="col-sm-6 pl-5"><div class="dataTables_paginate paging_simple_numbers" id="kt_items_table_paginate"><ul class="pagination paginationx" style="margin-bottom: 0;">';

        // FOR ENABLING THE PREVIOUS BUTTON
        if ($previous_btn && $cur_page > 1) {
            $pre = $cur_page - 1;
            $msg .= '<li data-row-item="'.$itemID.'" data-row-branch="'.$branchID.'" class="paginate_button page-item" p="'.$pre.'">';
            $msg .= '<a href="javascript:;" aria-label="Previous" class="page-link">';
            $msg .= '<i class="la la-angle-left"></i>';
            $msg .= '</a>';
            $msg .= '</li>';
        } else if ($previous_btn) {
            $msg .= '<li data-row-item="'.$itemID.'" data-row-branch="'.$branchID.'" class="paginate_button page-item disabled">';
            $msg .= '<a href="javascript:;" aria-label="Previous" class="page-link">';
            $msg .= '<i class="la la-angle-left"></i>';
            $msg .= '</a>';
            $msg .= '</li>';
        }
        for ($i = $start_loop; $i <= $end_loop; $i++) {

            if ($cur_page == $i)
                $msg .= '<li data-row-item="'.$itemID.'" data-row-branch="'.$branchID.'" class="paginate_button page-item active" p="'.$i.'"><a href="javascript:;" class="page-link">'.$i.'</a></li>';
            else
                $msg .= '<li data-row-item="'.$itemID.'" data-row-branch="'.$branchID.'" class="paginate_button page-item ping" p="'.$i.'"><a href="javascript:;" class="page-link">'.$i.'</a></li>';
        }

        // TO ENABLE THE NEXT BUTTON
        if ($next_btn && $cur_page < $no_of_paginations) {
            $nex = $cur_page + 1;
            $msg .= '<li data-row-item="'.$itemID.'" data-row-branch="'.$branchID.'" class="paginate_button page-item" p="'.$nex.'">';
            $msg .= '<a href="javascript:;" aria-label="Next" class="page-link">';
            $msg .= '<i class="la la-angle-right"></i>';
            $msg .= '</a>';
            $msg .= '</li>';
        } else if ($next_btn) {
            $msg .= '<li data-row-item="'.$itemID.'" data-row-branch="'.$branchID.'" class="paginate_button page-item disabled">';
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

    public function get_line_items_inventory($limit, $start_from, $keywords = '', $itemID, $branchID)
    {
        if (!empty($keywords)) {
            $res = ItemTransaction::with([
                'item' =>  function($q) { 
                    $q->select(['id', 'code', 'name', 'description']);
                },
                'branch' =>  function($q) { 
                    $q->select(['id', 'code', 'name', 'description']);
                },
                'issued' => function($q) { 
                    $q->select(['id', 'name']);
                },
            ])
            ->leftJoin('items', function($join)
            {
                $join->on('items.id', '=', 'items_transactions.item_id');
            })
            ->leftJoin('branches', function($join)
            {
                $join->on('branches.id', '=', 'items_transactions.branch_id');
            })
            ->where([
                'items_transactions.is_active' => 1, 
                'items_transactions.item_id' => $itemID, 
                'items_transactions.branch_id' => $branchID
            ])
            // ->where(function($q) use ($keywords) {
            //     $q->where('items.code', 'like', '%' . $keywords . '%')
            //       ->orWhere('items.name', 'like', '%' . $keywords . '%')
            //       ->orWhere('items.description', 'like', '%' . $keywords . '%')
            //       ->orWhere('items.mobile_no', 'like', '%' . $keywords . '%')
            //       ->orWhere('items.email', 'like', '%' . $keywords . '%')
            //       ->orWhere('items_category.name', 'like', '%' . $keywords . '%')
            //       ->orWhere('unit_of_measurements.code', 'like', '%' . $keywords . '%');
            // })
            ->skip($start_from)->take($limit)
            ->orderBy('items_transactions.id', 'desc')
            ->get();
        } else {
            $res = ItemTransaction::with([
                'item' =>  function($q) { 
                    $q->select(['id', 'code', 'name', 'description']);
                },
                'branch' =>  function($q) { 
                    $q->select(['id', 'code', 'name', 'description']);
                },
                'issued' => function($q) { 
                    $q->select(['id', 'name']);
                },
            ])
            ->leftJoin('items', function($join)
            {
                $join->on('items.id', '=', 'items_transactions.item_id');
            })
            ->leftJoin('branches', function($join)
            {
                $join->on('branches.id', '=', 'items_transactions.branch_id');
            })
            ->where([
                'items_transactions.is_active' => 1, 
                'items_transactions.item_id' => $itemID, 
                'items_transactions.branch_id' => $branchID
            ])
            ->skip($start_from)->take($limit)
            ->orderBy('items_transactions.id', 'desc')
            ->get();
        }

        return $res->map(function($item) {
            return (object) [
                'id' => $item->id,
                'item_id' => $item->item_id,
                'branch_id' => $item->branch_id,
                'transaction' => $item->transaction,
                'issued_by' => $item->issued->name,
                'received_by' => $item->received->name,
                'transaction_date' => date('d-M-Y', strtotime($item->created_at)).'<br/>'. date('h:i A', strtotime($item->created_at)),
                'based_quantity' => $item->based_quantity,
                'issued_quantity' => $item->issued_quantity,
                'left_quantity' => $item->left_quantity,
                'remarks' => $item->remarks
            ];
        });
    }

    public function get_page_count_inventory($limit, $start_from, $keywords = '', $itemID, $branchID)
    {
        if (!empty($keywords)) {
            $res = ItemTransaction::with([
                'item' =>  function($q) { 
                    $q->select(['id', 'code', 'name', 'description']);
                },
                'branch' =>  function($q) { 
                    $q->select(['id', 'code', 'name', 'description']);
                },
            ])
            ->leftJoin('items', function($join)
            {
                $join->on('items.id', '=', 'items_transactions.item_id');
            })
            ->leftJoin('branches', function($join)
            {
                $join->on('branches.id', '=', 'items_transactions.branch_id');
            })
            ->where([
                'items_transactions.is_active' => 1, 
                'items_transactions.item_id' => $itemID, 
                'items_transactions.branch_id' => $branchID
            ])
            ->count();
        } else {
            $res = ItemTransaction::with([
                'item' =>  function($q) { 
                    $q->select(['id', 'code', 'name', 'description']);
                },
                'branch' =>  function($q) { 
                    $q->select(['id', 'code', 'name', 'description']);
                },
            ])
            ->leftJoin('items', function($join)
            {
                $join->on('items.id', '=', 'items_transactions.item_id');
            })
            ->leftJoin('branches', function($join)
            {
                $join->on('branches.id', '=', 'items_transactions.branch_id');
            })
            ->where([
                'items_transactions.is_active' => 1, 
                'items_transactions.item_id' => $itemID, 
                'items_transactions.branch_id' => $branchID
            ])
            ->count();
        }

        return $res;
    }

    public function find_item_quantity(Request $reqest, $itemId, $branchId)
    {
        $res = ItemInventory::select('items_inventory.quantity', 'items.srp', 'items.srp2', 'branches.is_srp')
        ->leftJoin('branches', function($join)
        {
            $join->on('branches.id', '=', 'items_inventory.branch_id');
        })
        ->leftJoin('items', function($join)
        {
            $join->on('items.id', '=', 'items_inventory.item_id');
        })
        ->where([
            'items_inventory.is_active' => 1, 
            'items_inventory.item_id' => $itemId, 
            'items_inventory.branch_id' => $branchId
        ])->first();

        $data = array(
            'srp' => ($res->is_srp > 0) ? $res->srp2 : $res->srp,
            'quantity' => $res->quantity
        );

        echo json_encode( $data ); exit();
    }

    public function store_withdrawal(Request $request)
    {   
        // $this->is_permitted(0);
        $timestamp = date('Y-m-d H:i:s');
        $leftQty = floatval($request->get('based_quantity')) - floatval($request->issued_quantity);
        $transaction = ItemTransaction::create([
            'item_id' => $request->get('itemId'),
            'branch_id' => $request->branch_id,
            'transaction' => strval($request->transaction),
            'based_quantity' => $request->get('based_quantity'),
            'issued_quantity' => $request->issued_quantity,
            'left_quantity' => $leftQty,
            'srp' => $request->get('srp'),
            'total_amount' => $request->get('total_amount'),
            'issued_by' => $request->issued_by,
            'received_by' => $request->received_by,
            'remarks' => $request->remarks,
            'created_at' => $timestamp,
            'created_by' => Auth::user()->id
        ]);

        if (!$transaction) {
            throw new NotFoundHttpException();
        }

        $this->audit_logs('items_transactions', $transaction->id, 'has inserted a new item transaction.', ItemTransaction::find($transaction->id), $timestamp, Auth::user()->id);

        if (strval($request->transaction) == 'Withdrawal' || strval($request->transaction) == 'Damaged Item') {
            $inventory = ItemInventory::where(['item_id' => $request->get('itemId'), 'branch_id' => $request->branch_id, 'is_active' => 1])->get();
            if ($inventory->count() > 0) {
                $inventory = $inventory->first();
                $qtyLeft   = floatval($inventory->quantity) - floatval($request->issued_quantity);
                ItemInventory::where('id', $inventory->id)->update(['quantity' => $qtyLeft]);
            }
        } else {
            $inventory = ItemInventory::where(['item_id' => $request->get('itemId'), 'branch_id' => $request->branch_id, 'is_active' => 1])->get();
            if ($inventory->count() > 0) {
                $inventory = $inventory->first();
                $qtyLeft   = floatval($inventory->quantity) - floatval($request->issued_quantity);
                ItemInventory::where('id', $inventory->id)->update(['quantity' => $qtyLeft]);
            }

            $transfer = ItemInventory::where(['item_id' => $request->get('itemId'), 'branch_id' => $request->get('transfer_to'), 'is_active' => 1])->get();
            if ($transfer->count() > 0) {
                $transfer = $transfer->first();
                $basedQuantity = $transfer->quantity;
                $qtyLeft   = floatval($transfer->quantity) + floatval($request->issued_quantity);
                ItemInventory::where('id', $transfer->id)->update(['quantity' => $qtyLeft]);

                $itemTransfer = ItemTransfer::create([
                    'transaction_id' => $transaction->id,
                    'branch_id' => $request->get('transfer_to'),
                    'created_at' => $timestamp,
                    'created_by' => Auth::user()->id
                ]);
        
                if (!$itemTransfer) {
                    throw new NotFoundHttpException();
                }
        
                $this->audit_logs('items_transfer', $itemTransfer->id, 'has inserted a new item transfer.', ItemTransfer::find($itemTransfer->id), $timestamp, Auth::user()->id);
                
                $leftQty = floatval($basedQuantity) + floatval($request->issued_quantity);
                $transactionx = ItemTransaction::create([
                    'item_id' => $request->get('itemId'),
                    'branch_id' => $request->get('transfer_to'),
                    'transaction' => 'Received Item',
                    'based_quantity' => $basedQuantity,
                    'issued_quantity' => $request->issued_quantity,
                    'left_quantity' => $leftQty,
                    'srp' => $request->get('srp'),
                    'total_amount' => $request->get('total_amount'),
                    'issued_by' => $request->issued_by,
                    'received_by' => $request->received_by,
                    'remarks' => 'Transferred item from branch('.(new Branch)->where('id', $request->branch_id)->first()->name.')',
                    'created_at' => $timestamp,
                    'created_by' => Auth::user()->id
                ]);
        
                if (!$transactionx) {
                    throw new NotFoundHttpException();
                }
        
                $this->audit_logs('items_transactions', $transactionx->id, 'has inserted a new item transaction.', ItemTransaction::find($transactionx->id), $timestamp, Auth::user()->id);
            }
        }
        
        $data = array(
            'title' => 'Well done!',
            'text' => 'The item has been successfully withdrawn.',
            'type' => 'success',
            'class' => 'btn-brand'
        );

        echo json_encode( $data ); exit();
    }

    public function store_receiving(Request $request)
    {
        // $this->is_permitted(0);
        $timestamp = date('Y-m-d H:i:s');
        $leftQty = floatval($request->get('based_quantity')) + floatval($request->issued_quantity);
        $transaction = ItemTransaction::create([
            'item_id' => $request->get('itemId'),
            'branch_id' => $request->branch_id,
            'transaction' => strval($request->transaction),
            'based_quantity' => $request->get('based_quantity'),
            'issued_quantity' => $request->issued_quantity,
            'left_quantity' => $leftQty,
            'srp' => $request->get('srp'),
            'total_amount' => $request->get('total_amount'),
            'issued_by' => $request->issued_by,
            'received_by' => $request->received_by,
            'remarks' => $request->remarks,
            'created_at' => $timestamp,
            'created_by' => Auth::user()->id
        ]);

        if (!$transaction) {
            throw new NotFoundHttpException();
        }

        $this->audit_logs('items_transactions', $transaction->id, 'has inserted a new item transaction.', ItemTransaction::find($transaction->id), $timestamp, Auth::user()->id);

        $inventory = ItemInventory::where(['item_id' => $request->get('itemId'), 'branch_id' => $request->branch_id, 'is_active' => 1])->get();
        if ($inventory->count() > 0) {
            $inventory = $inventory->first();
            $qtyLeft   = floatval($inventory->quantity) + floatval($request->issued_quantity);
            ItemInventory::where('id', $inventory->id)->update(['quantity' => $qtyLeft]);
        }

        $data = array(
            'title' => 'Well done!',
            'text' => 'The item has been successfully received.',
            'type' => 'success',
            'class' => 'btn-brand'
        );

        echo json_encode( $data ); exit();
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