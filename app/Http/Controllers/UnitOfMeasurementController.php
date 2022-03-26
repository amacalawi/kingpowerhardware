<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\UnitOfMeasurement;
use App\Models\User;
use App\Models\AuditLog;
use Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\File;
// use App\Components\FlashMessages;
// use App\Helper\Helper;

class UnitOfMeasurementController extends Controller
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
        // $menus = $this->load_menus();
        return view('modules/components/unit-of-measurements/manage');
    }

    public function inactive(Request $request)
    {       
        // // $this->is_permitted(1);    
        // // $menus = $this->load_menus();
        return view('modules/components/unit-of-measurements/manage-inactive');
    }

    public function store(Request $request)
    {   
        // $this->is_permitted(0);
        $timestamp = date('Y-m-d H:i:s');

        $rows = UnitOfMeasurement::where([
            'code' => $request->code
        ])->count();

        if ($rows > 0) {
            $data = array(
                'title' => 'Oh snap!',
                'text' => 'You cannot create a UnitOfMeasurement with an existing code.',
                'type' => 'error',
                'class' => 'btn-danger'
            );
    
            echo json_encode( $data ); exit();
        }

        $UnitOfMeasurement = UnitOfMeasurement::create([
            'code' => $request->code,
            'name' => $request->name,
            'description' => $request->description,
            'created_at' => $timestamp,
            'created_by' => Auth::user()->id
        ]);

        if (!$UnitOfMeasurement) {
            throw new NotFoundHttpException();
        }

        $this->audit_logs('unit_of_measurements', $UnitOfMeasurement->id, 'has inserted a new unit of measurment.', UnitOfMeasurement::find($UnitOfMeasurement->id), $timestamp, Auth::user()->id);
        
        $data = array(
            'title' => 'Well done!',
            'text' => 'The unit of measurement has been successfully stored.',
            'type' => 'success',
            'class' => 'btn-brand'
        );

        echo json_encode( $data ); exit();
    }

    public function find(Request $request, $id)
    {    
        $UnitOfMeasurement = UnitOfMeasurement::find($id);

        if(!$UnitOfMeasurement) {
            throw new NotFoundHttpException();
        }

        return response()
        ->json([
            'status' => 'ok',
            'data' => $UnitOfMeasurement
        ]);
    }

    public function update(Request $request, $id)
    {    
        // $this->is_permitted(2);
        $timestamp = date('Y-m-d H:i:s');
        $UnitOfMeasurement = UnitOfMeasurement::find($id);

        if(!$UnitOfMeasurement) {
            throw new NotFoundHttpException();
        }

        $UnitOfMeasurement->code = $request->code;
        $UnitOfMeasurement->name = $request->name;
        $UnitOfMeasurement->description = $request->description;
        $UnitOfMeasurement->updated_at = $timestamp;
        $UnitOfMeasurement->updated_by = Auth::user()->id;

        if ($UnitOfMeasurement->update()) {
            $this->audit_logs('unit_of_measurements', $id, 'has modified a unit of measurement.', UnitOfMeasurement::find($id), $timestamp, Auth::user()->id);
            $data = array(
                'title' => 'Well done!',
                'text' => 'The unit of measurement has been successfully modified.',
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
        $msg .= '<table class="table align-middle table-row-dashed fs-6 gy-5" id="uomTable">';
        $msg .= '<thead>';
            $msg .= '<tr class="text-start text-gray-400 fw-bolder fs-7 text-uppercase gs-0">';
            $msg .= '<th class="w-10px pe-2">';
            $msg .= '<div class="form-check form-check-sm form-check-custom form-check-solid me-3">';
            $msg .= '<input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_unit_of_measurements_table .form-check-input" value="1" />';
            $msg .= '</div>';
            $msg .= '</th>';
            $msg .= '<th class="min-w-100px">Code</th>';
            $msg .= '<th class="min-w-125px">Name</th>';
            $msg .= '<th class="min-w-125px">Description</th>';
            $msg .= '<th class="min-w-125px text-center">Last Modified</th>';
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
                $msg .= '<tr data-row-id="'.$row->id.'" data-row-code="'.$row->code.'">';
                $msg .= '<td>';
                $msg .= '<div class="form-check form-check-sm form-check-custom form-check-solid">';
                $msg .= '<input class="form-check-input" type="checkbox" value="'.$row->id.'" />';
                $msg .= '</div>';
                $msg .= '</td>';
                $msg .= '<td>';
                $msg .= '<a href="#" class="text-gray-800 text-hover-primary mb-1">'.$row->code.'</a>';
                $msg .= '</td>';
                $msg .= '<td>';
                $msg .= $row->name;
                $msg .= '</td>';
                $msg .= '<td>'.$row->description.'</td>';
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
                <!--end::Svg Icon-->';
                $msg .= '</td>';
                $msg .= '</tr>';
            }
        }
        $msg .= '</tbody>';
        $msg .= '</table>';
        $msg .= '</div>';

        $count = $this->get_page_count($per_page, $start_from, $keywords);
  
        $no_of_paginations = ceil($count / $per_page);

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

        $msg .= '<div class="row"><div class="col-sm-6 pl-5"><div class="dataTables_paginate paging_simple_numbers" id="kt_unit_of_measurements_table_paginate"><ul class="pagination" style="margin-bottom: 0;">';

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
            $res = UnitOfMeasurement::select([
                'unit_of_measurements.code',
                'unit_of_measurements.id',
                'unit_of_measurements.name',
                'unit_of_measurements.description',
                'unit_of_measurements.created_at',
                'unit_of_measurements.updated_at'
            ])
            ->where('unit_of_measurements.is_active', 1)
            ->where(function($q) use ($keywords) {
                $q->where('unit_of_measurements.code', 'like', '%' . $keywords . '%')
                  ->orWhere('unit_of_measurements.name', 'like', '%' . $keywords . '%')
                  ->orWhere('unit_of_measurements.description', 'like', '%' . $keywords . '%');
            })
            ->skip($start_from)->take($limit)
            ->orderBy('unit_of_measurements.id', 'desc')
            ->get();
        } else {
            $res = UnitOfMeasurement::select([
                'unit_of_measurements.code',
                'unit_of_measurements.id',
                'unit_of_measurements.name',
                'unit_of_measurements.description',
                'unit_of_measurements.created_at',
                'unit_of_measurements.updated_at'
            ])
            ->where('unit_of_measurements.is_active', 1)
            ->skip($start_from)->take($limit)
            ->orderBy('unit_of_measurements.id', 'desc')
            ->get();
        }

        return $res->map(function($uom) {
            return (object) [
                'id' => $uom->id,
                'code' => $uom->code,
                'name' => $uom->name,
                'description' => $uom->description,
                'modified_at' => ($uom->updated_at !== NULL) ? 
                    date('d-M-Y', strtotime($uom->updated_at)).'<br/>'. date('h:i A', strtotime($uom->updated_at)) : 
                    date('d-M-Y', strtotime($uom->created_at)).'<br/>'. date('h:i A', strtotime($uom->created_at))
            ];
        });
    }

    public function get_page_count($limit, $start_from, $keywords = '')
    {
        if (!empty($keywords)) {
            $res = UnitOfMeasurement::select([
                'unit_of_measurements.code',
                'unit_of_measurements.id',
                'unit_of_measurements.name',
                'unit_of_measurements.description'
            ])
            ->where('unit_of_measurements.is_active', 1)
            ->where(function($q) use ($keywords) {
                $q->where('unit_of_measurements.code', 'like', '%' . $keywords . '%')
                  ->orWhere('unit_of_measurements.name', 'like', '%' . $keywords . '%')
                  ->orWhere('unit_of_measurements.description', 'like', '%' . $keywords . '%');
            })
            ->count();
        } else {
            $res = UnitOfMeasurement::select([
                'unit_of_measurements.code',
                'unit_of_measurements.id',
                'unit_of_measurements.name',
                'unit_of_measurements.description'
            ])
            ->where('unit_of_measurements.is_active', 1)
            ->count();
        }

        return $res;
    }

    public function remove(Request $request, $id)
    {   
        // $this->is_permitted(3);
        $timestamp = date('Y-m-d H:i:s');
        $UnitOfMeasurement = UnitOfMeasurement::where([
            'id' => $id,
        ])
        ->update([
            'updated_at' => $timestamp,
            'updated_by' => Auth::user()->id,
            'is_active' => 0
        ]);
        $this->audit_logs('unit_of_measurements', $id, 'has removed a unit of measurement.', UnitOfMeasurement::find($id), $timestamp, Auth::user()->id);
        
        $data = array(
            'title' => 'Well done!',
            'text' => 'The unit of measurement has been successfully removed.',
            'type' => 'success',
            'class' => 'btn-brand'
        );

        echo json_encode( $data ); exit();
    }

    public function restore(Request $request, $id)
    {   
        // $this->is_permitted(3);
        $timestamp = date('Y-m-d H:i:s');
        $UnitOfMeasurement = UnitOfMeasurement::where([
            'id' => $id,
        ])
        ->update([
            'updated_at' => $timestamp,
            'updated_by' => Auth::user()->id,
            'is_active' => 1
        ]);
        $this->audit_logs('unit_of_measurements', $id, 'has removed a unit of measurement.', UnitOfMeasurement::find($id), $timestamp, Auth::user()->id);
        
        $data = array(
            'title' => 'Well done!',
            'text' => 'The unit of measurement has been successfully restored.',
            'type' => 'success',
            'class' => 'btn-brand'
        );

        echo json_encode( $data ); exit();
    }

    public function export(Request $request)
    {   
        $fileName = 'unit_of_measurements.csv';

        $unit_of_measurements = UnitOfMeasurement::select([
            'unit_of_measurements.id', 
            'unit_of_measurements.code', 
            'unit_of_measurements.name', 
            'unit_of_measurements.description', 
        ])
        ->where('unit_of_measurements.is_active', 1)
        ->orderBy('unit_of_measurements.id', 'asc')
        ->get();

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array('ID No.', 'Code', 'Name', 'Description');

        $callback = function() use($unit_of_measurements, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($unit_of_measurements as $UnitOfMeasurement) {
                $row['id']        = $UnitOfMeasurement->id;
                $row['code']      = $UnitOfMeasurement->code;
                $row['name']      = $UnitOfMeasurement->name;
                $row['description']   = $UnitOfMeasurement->description;
                fputcsv($file, array($row['id'], $row['code'], $row['name'], $row['description']));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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