<?php

namespace App\Exports;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\Delivery;
use App\Models\DeliveryLine;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Events\BeforeWriting;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DeliveryReportExport implements WithEvents, WithStyles, WithColumnWidths, WithTitle
{
    public function __construct($request)
    {
        $this->orderby      = $request->get('order_by');  
        $this->keywords     = $request->get('keywords');
        $this->dateFrom     = $request->get('dateFrom');  
        $this->dateTo       = $request->get('dateTo');  
        $this->type         = $request->get('type');  
        $this->branch       = $request->get('branch');
        $this->customer     = $request->get('customer');  
        $this->agent        = $request->get('agent');  
        $this->status       = $request->get('status');  
    }

    public function getColumns($count)
    {
        $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        return $columns[$count];
    }

    public function registerEvents(): array
    {   
        $dateFrom2 = date('Y-m-d', strtotime($this->dateFrom)).' 00:00:01';
        $dateTo2   = date('Y-m-d', strtotime($this->dateTo)).' 23:59:59';
        $orderby      = ($this->orderby == 'asc') ? 'ASC' : 'DESC';  
        $keywords     = $this->keywords;
        $dateFrom     = $this->dateFrom;  
        $dateTo       = $this->dateTo;  
        $type         = $this->type;  
        $branch       = $this->branch;
        $customer     = $this->customer;  
        $agent        = $this->agent;  
        $status       = $this->status;  

        if ($type == 'summary') {
            $lines = Delivery::select([
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
            ->get();
        } else {
            $lines = DeliveryLine::select([
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
            ->orderBy('delivery_lines.id', $orderby)
            ->get();
        }

        return [
            AfterSheet::class => function(AfterSheet $event) use ($lines) {
                $styleArray1 = [
                    'font' => [
                        // 'name'      =>  'Calibri',
                        'size'      =>  14,
                        'bold'      =>  true
                    ]
                ];
                $styleArray2 = [
                    'font' => [
                        // 'name'      =>  'Calibri',
                        'size'      =>  12,
                        'bold'      =>  true
                    ]
                ];

                $dateFrom = $this->dateFrom ? date('d-M-Y', strtotime($this->dateFrom)) : '';
                $dateTo   = $this->dateTo ? date('d-M-Y', strtotime($this->dateTo)) : '';

                if ($this->type == 'summary') {
                    $maxColumn = $this->getColumns(7);
                    $firstStartColumn = $this->getColumns(0);
                    $firstEndColumn = $this->getColumns(3);
                    $secondStartColumn = $this->getColumns(4);
                    $headers = ['TRANSACTION DATE', 'DR NO', 'BRANCH', 'CUSTOMER', 'AGENT', 'STATUS', 'TOTAL'];
                } else {
                    $maxColumn = $this->getColumns(13);
                    $firstStartColumn = $this->getColumns(0);
                    $firstEndColumn = $this->getColumns(6);
                    $secondStartColumn = $this->getColumns(7);
                    $headers = ['TRANSACTION DATE', 'DR NO', 'BRANCH', 'CUSTOMER', 'AGENT', 'ITEMS', 'QTY', 'UOM', 'SRP', 'PLUS', 'DISC1', 'DISC2', 'STATUS', 'TOTAL'];
                }

                $event->sheet->getDelegate()->mergeCells('A1:'.$maxColumn.'1');
                $event->sheet->getDelegate()->mergeCells('A2:'.$maxColumn.'2');
                $event->sheet->getStyle('A1:'.$maxColumn.'2')->applyFromArray($styleArray1)->getAlignment()->setHorizontal('center');
                $event->sheet->setCellValue('A1', 'DELIVERY REPORTS');

                $event->sheet->getDelegate()->mergeCells($firstStartColumn.'3:'.$firstEndColumn.'3');
                $event->sheet->getDelegate()->mergeCells($secondStartColumn.'3:'.$maxColumn.'3');
                $event->sheet->getStyle($firstStartColumn.'3:'.$firstEndColumn.'3')->applyFromArray($styleArray2)->getAlignment()->setHorizontal('right');
                $event->sheet->getStyle($secondStartColumn.'3:'.$maxColumn.'3')->applyFromArray($styleArray2)->getAlignment()->setHorizontal('left');
                $event->sheet->setCellValue($firstStartColumn.'3', 'START DATE');
                $event->sheet->setCellValue($secondStartColumn.'3', 'END DATE');

                $event->sheet->getDelegate()->mergeCells($firstStartColumn.'4:'.$firstEndColumn.'4');
                $event->sheet->getDelegate()->mergeCells($secondStartColumn.'4:'.$maxColumn.'4');
                $event->sheet->getStyle($firstStartColumn.'4:'.$firstEndColumn.'4')->getAlignment()->setHorizontal('right');
                $event->sheet->getStyle($secondStartColumn.'4:'.$maxColumn.'4')->getAlignment()->setHorizontal('left');
                $event->sheet->setCellValue($firstStartColumn.'4', $dateFrom);
                $event->sheet->setCellValue($secondStartColumn.'4', $dateTo);

                $rows = 6; $count = 0;
                if ($this->type == 'summary') {
                    foreach ($headers as $header)
                    {   
                        if ($header == 'CUSTOMER') {
                            $column = $this->getColumns($count);
                            $column2 = $this->getColumns(($count + 1));
                            $event->sheet->getDelegate()->mergeCells($column.''.$rows.':'.$column2.''.$rows);
                            $event->sheet->getStyle($column.''.$rows.':'.$column2.''.$rows)->applyFromArray($styleArray2)->getAlignment()->setHorizontal('center');
                            $event->sheet->setCellValue($column.''.$rows, $header);
                            $count++;
                        } else {
                            $column = $this->getColumns($count);
                            $event->sheet->getStyle($column.''.$rows)->applyFromArray($styleArray2)->getAlignment()->setHorizontal('center');
                            $event->sheet->setCellValue($column.''.$rows, $header);
                        }
                        $count++;
                    }
                } else {
                    foreach ($headers as $header)
                    {   
                        $column = $this->getColumns($count);
                        $event->sheet->getStyle($column.''.$rows)->applyFromArray($styleArray2)->getAlignment()->setHorizontal('center');
                        $event->sheet->setCellValue($column.''.$rows, $header);
                        $count++;
                    }
                }

                $rows = 7;
                if ($this->type == 'summary') {
                    foreach ($lines as $line)
                    {   
                        $event->sheet->setCellValue('A'.$rows, date('d-M-Y', strtotime($line->transDate)));
                        $event->sheet->getStyle('A'.$rows)->getAlignment()->setHorizontal('center');

                        $event->sheet->setCellValue('B'.$rows, $line->docNo);
                        $event->sheet->getStyle('B'.$rows)->getAlignment()->setHorizontal('center');

                        $event->sheet->setCellValue('C'.$rows, $line->branch);
                        $event->sheet->getStyle('C'.$rows)->getAlignment()->setHorizontal('center');

                        $event->sheet->getDelegate()->mergeCells('D'.$rows.':E'.$rows);
                        $event->sheet->setCellValue('D'.$rows, $line->customer);
                        $event->sheet->getStyle('D'.$rows)->getAlignment()->setHorizontal('center');

                        $event->sheet->setCellValue('F'.$rows, $line->agent);
                        $event->sheet->getStyle('F'.$rows)->getAlignment()->setHorizontal('center');

                        $event->sheet->setCellValue('G'.$rows, $line->status);
                        $event->sheet->getStyle('G'.$rows)->getAlignment()->setHorizontal('center');

                        $event->sheet->setCellValue('H'.$rows, number_format(floor(($line->totalAmt*100))/100,2));
                        $event->sheet->getStyle('H'.$rows)->getAlignment()->setHorizontal('right');
                        
                        $rows++;
                    }
                } else {
                    foreach ($lines as $line)
                    {  
                        $event->sheet->setCellValue('A'.$rows, date('d-M-Y', strtotime($line->transDate)));
                        $event->sheet->getStyle('A'.$rows)->getAlignment()->setHorizontal('center');

                        $event->sheet->setCellValue('B'.$rows, $line->docNo);
                        $event->sheet->getStyle('B'.$rows)->getAlignment()->setHorizontal('center');

                        $event->sheet->setCellValue('C'.$rows, $line->branch);
                        $event->sheet->getStyle('C'.$rows)->getAlignment()->setHorizontal('center');

                        $event->sheet->setCellValue('D'.$rows, $line->customer);
                        $event->sheet->getStyle('D'.$rows)->getAlignment()->setHorizontal('center');

                        $event->sheet->setCellValue('E'.$rows, $line->agent);
                        $event->sheet->getStyle('E'.$rows)->getAlignment()->setHorizontal('center');

                        $event->sheet->setCellValue('F'.$rows, $line->itemCode.' - '.$line->itemName);
                        $event->sheet->getStyle('F'.$rows)->getAlignment()->setHorizontal('center');

                        $quantity = ($this->status == 'posted' || $this->status == 'partial') ? $line->posted_quantity : $line->quantity;
                        $event->sheet->setCellValue('G'.$rows, $quantity);
                        $event->sheet->getStyle('G'.$rows)->getAlignment()->setHorizontal('center');

                        $event->sheet->setCellValue('H'.$rows, $line->uom);
                        $event->sheet->getStyle('H'.$rows)->getAlignment()->setHorizontal('center');

                        $event->sheet->setCellValue('I'.$rows, number_format(floor(($line->srp*100))/100,2));
                        $event->sheet->getStyle('I'.$rows)->getAlignment()->setHorizontal('right');

                        $plus = $line->plus ? $line->plus.'%' : '';
                        $event->sheet->setCellValue('J'.$rows, $plus);
                        $event->sheet->getStyle('J'.$rows)->getAlignment()->setHorizontal('center');

                        $disc1 = $line->disc1 ? $line->disc1.'%' : '';
                        $event->sheet->setCellValue('K'.$rows, $disc1);
                        $event->sheet->getStyle('K'.$rows)->getAlignment()->setHorizontal('center');

                        $disc2 = $line->disc2 ? $line->disc2.'%' : '';
                        $event->sheet->setCellValue('L'.$rows, $disc2);
                        $event->sheet->getStyle('L'.$rows)->getAlignment()->setHorizontal('center');

                        $event->sheet->setCellValue('M'.$rows, $line->status);
                        $event->sheet->getStyle('M'.$rows)->getAlignment()->setHorizontal('center');

                        $totalAmt = 0;
                        if ($line->status == 'partial') {
                            $srpVal = floatval($line->total_amount) / floatval($line->quantity);
                            $totalAmt = floatval($line->posted_quantity) * floatval($srpVal);
                        } else {
                            $totalAmt = $line->total_amount;
                        }
                        $event->sheet->setCellValue('N'.$rows, number_format(floor(($totalAmt*100))/100,2));
                        $event->sheet->getStyle('N'.$rows)->getAlignment()->setHorizontal('right');

                        $rows++;
                    }
                }
            },
        ];
    }

    public function columnWidths(): array
    {   
        if ($this->type == 'summary') {
            return [
                'A' => 20,
                'B' => 20,
                'C' => 20,
                'D' => 20,
                'E' => 20,
                'F' => 20,
                'G' => 20,
                'H' => 20   
            ];
        } else {
            return [
                'A' => 20,
                'B' => 20,
                'C' => 20,
                'D' => 20,
                'E' => 20,
                'F' => 20,
                'G' => 20,
                'H' => 20,
                'I' => 20,
                'J' => 20,
                'K' => 20,
                'L' => 20,
                'M' => 20,
                'N' => 20,   
            ];
        }
    }

    public function styles(Worksheet $sheet)
    {
        return [];
    }

    public function title(): string
    {
        return 'DELIVERY REPORT';
    }
}
