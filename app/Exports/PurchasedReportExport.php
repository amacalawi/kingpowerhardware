<?php

namespace App\Exports;

use App\Models\Branch;
use App\Models\Supplier;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseOrderType;
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

class PurchasedReportExport implements WithEvents, WithStyles, WithColumnWidths, WithTitle
{
    public function __construct($request)
    {
        $this->orderby      = $request->get('order_by');  
        $this->keywords     = $request->get('keywords');
        $this->dateFrom     = $request->get('dateFrom');  
        $this->dateTo       = $request->get('dateTo');  
        $this->type         = $request->get('type');  
        $this->branch       = $request->get('branch');
        $this->supplier     = $request->get('supplier_id');  
        $this->po_type      = $request->get('purchase_order_type_id');  
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
        $orderby      = $this->orderby;  
        $keywords     = $this->keywords;
        $dateFrom     = $this->dateFrom;  
        $dateTo       = $this->dateTo;  
        $type         = $this->type;  
        $branch       = $this->branch;
        $supplier     = $this->supplier;  
        $po_type      = $this->po_type;  
        $status       = $this->status;  

        if ($type == 'summary') {
            $lines = PurchaseOrder::select([
                'purchase_orders.id as id',
                'branches.name as branch',
                'suppliers.name as supplier',
                'purchase_orders_types.name as po_type',
                'purchase_orders.po_no as poNo',
                'purchase_orders.created_at as transDate',
                'purchase_orders.total_amount as totalAmt',
                'purchase_orders.status as status'
            ])
            ->leftJoin('purchase_orders_types', function($join)
            {
                $join->on('purchase_orders_types.id', '=', 'purchase_orders.purchase_order_type_id');
            })
            ->leftJoin('branches', function($join)
            {
                $join->on('branches.id', '=', 'purchase_orders.branch_id');
            })
            ->leftJoin('suppliers', function($join)
            {
                $join->on('suppliers.id', '=', 'purchase_orders.supplier_id');
            })
            ->where(function($q) use ($keywords) {
                if (!empty($keywords)) {
                    $q->where('purchase_orders.po_no', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders.total_amount', 'like', '%' . $keywords . '%')
                    ->orWhere('suppliers.name', 'like', '%' . $keywords . '%')
                    ->orWhere('branches.name', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_types.name', 'like', '%' . $keywords . '%');
                }
            })
            ->where(function($q) use ($dateFrom, $dateTo, $dateFrom2, $dateTo2) {
                if (!empty($dateFrom) && !empty($dateTo)) {
                    $q->where('purchase_orders.created_at', '>=', $dateFrom2)
                        ->where('purchase_orders.created_at', '<=', $dateTo2);
                } else if (!empty($dateFrom) && empty($dateTo)) {
                    $q->where('purchase_orders.created_at', '=', $dateFrom);
                } else if (empty($dateFrom) && !empty($dateTo)) {
                    $q->where('purchase_orders.created_at', '=', $dateTo);
                }
            })
            ->where(function($q) use ($supplier){
                if ($supplier != '') {
                    $q->where('suppliers.id', '=',  $supplier);
                }
            })
            ->where(function($q) use ($po_type){
                if ($po_type != '') {
                    $q->where('purchase_orders_types.id', '=',  $po_type);
                }
            })
            ->where(function($q) use ($branch){
                if ($branch != '') {
                    $q->where('branches.id', '=',  $branch);
                }
            })
            ->where(function($q) use ($status){
                if ($status != '') {
                    $q->where("purchase_orders.status", $status);
                }
            })
            ->where('purchase_orders.status', '!=', 'draft')
            ->where('purchase_orders.is_active', 1)
            ->orderBy('purchase_orders.id', $orderby)
            ->get();
        } else {
            $lines = PurchaseOrderLine::select([
                'purchase_orders.id as id',
                'branches.name as branch',
                'suppliers.name as supplier',
                'purchase_orders_types.name as po_type',
                'purchase_orders.po_no as poNo',
                'purchase_orders.created_at as transDate',
                'purchase_orders.total_amount as totalAmt',
                'purchase_orders_lines.status as status',
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
            ->leftJoin('purchase_orders', function($join)
            {
                $join->on('purchase_orders.id', '=', 'purchase_orders_lines.purchase_order_id');
            })
            ->leftJoin('purchase_orders_types', function($join)
            {
                $join->on('purchase_orders_types.id', '=', 'purchase_orders.purchase_order_type_id');
            })
            ->leftJoin('branches', function($join)
            {
                $join->on('branches.id', '=', 'purchase_orders.branch_id');
            })
            ->leftJoin('suppliers', function($join)
            {
                $join->on('suppliers.id', '=', 'purchase_orders.supplier_id');
            })
            ->where(function($q) use ($keywords) {
                if (!empty($keywords)) {
                    $q->where('purchase_orders.po_no', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders.total_amount', 'like', '%' . $keywords . '%')
                    ->orWhere('suppliers.name', 'like', '%' . $keywords . '%')
                    ->orWhere('branches.name', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_types.name', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_lines.srp', 'like', '%' . $keywords . '%')
                    ->orWhere('unit_of_measurements.code', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_lines.quantity', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_lines.total_amount', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_lines.discount1', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_lines.discount2', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_lines.plus', 'like', '%' . $keywords . '%')
                    ->orWhere('purchase_orders_lines.posted_quantity', 'like', '%' . $keywords . '%')
                    ->orWhere('items.code', 'like', '%' . $keywords . '%')
                    ->orWhere('items.name', 'like', '%' . $keywords . '%')
                    ->orWhere('items.description', 'like', '%' . $keywords . '%');
                }
            })
            ->where(function($q) use ($dateFrom, $dateTo, $dateFrom2, $dateTo2) {
                if (!empty($dateFrom) && !empty($dateTo)) {
                    $q->where('purchase_orders.created_at', '>=', $dateFrom2)
                        ->where('purchase_orders.created_at', '<=', $dateTo2);
                } else if (!empty($dateFrom) && empty($dateTo)) {
                    $q->where('purchase_orders.created_at', '=', $dateFrom);
                } else if (empty($dateFrom) && !empty($dateTo)) {
                    $q->where('purchase_orders.created_at', '=', $dateTo);
                }
            })
            ->where(function($q) use ($supplier){
                if ($supplier != '') {
                    $q->where('suppliers.id', '=',  $supplier);
                }
            })
            ->where(function($q) use ($po_type){
                if ($po_type != '') {
                    $q->where('purchase_orders_types.id', '=',  $po_type);
                }
            })
            ->where(function($q) use ($branch){
                if ($branch != '') {
                    $q->where('branches.id', '=',  $branch);
                }
            })
            ->where(function($q) use ($status){
                if ($status != '') {
                    $q->where("purchase_orders_lines.status", $status);
                }
            })
            ->where('purchase_orders.status', '!=', 'draft')
            ->where('purchase_orders_lines.is_active', 1)
            ->orderBy('purchase_orders_lines.id', $orderby)
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
                    $headers = ['TRANSACTION DATE', 'PO NO', 'BRANCH', 'SUPPLIER', 'TYPE', 'TOTAL', 'STATUS'];
                } else {
                    $maxColumn = $this->getColumns(11);
                    $firstStartColumn = $this->getColumns(0);
                    $firstEndColumn = $this->getColumns(5);
                    $secondStartColumn = $this->getColumns(6);
                    $headers = ['TRANSACTION DATE', 'PO NO', 'BRANCH', 'SUPPLIER', 'TYPE', 'ITEMS', 'QTY', 'UOM', 'SRP', 'TOTAL', 'STATUS'];
                }

                $event->sheet->getDelegate()->mergeCells('A1:'.$maxColumn.'1');
                $event->sheet->getDelegate()->mergeCells('A2:'.$maxColumn.'2');
                $event->sheet->getStyle('A1:'.$maxColumn.'2')->applyFromArray($styleArray1)->getAlignment()->setHorizontal('center');
                $event->sheet->setCellValue('A1', 'PURCHASED REPORTS');

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
                        if ($header == 'SUPPLIER') {
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

                        $event->sheet->setCellValue('B'.$rows, $line->poNo);
                        $event->sheet->getStyle('B'.$rows)->getAlignment()->setHorizontal('center');

                        $event->sheet->setCellValue('C'.$rows, $line->branch);
                        $event->sheet->getStyle('C'.$rows)->getAlignment()->setHorizontal('center');

                        $event->sheet->getDelegate()->mergeCells('D'.$rows.':E'.$rows);
                        $event->sheet->setCellValue('D'.$rows, $line->supplier);
                        $event->sheet->getStyle('D'.$rows)->getAlignment()->setHorizontal('center');

                        $event->sheet->setCellValue('F'.$rows, $line->po_type);
                        $event->sheet->getStyle('F'.$rows)->getAlignment()->setHorizontal('center');

                        $event->sheet->setCellValue('G'.$rows, number_format(floor(($line->totalAmt*100))/100,2));
                        $event->sheet->getStyle('G'.$rows)->getAlignment()->setHorizontal('right');

                        $event->sheet->setCellValue('H'.$rows, $line->status);
                        $event->sheet->getStyle('H'.$rows)->getAlignment()->setHorizontal('center');
                        
                        $rows++;
                    }
                } else {
                    foreach ($lines as $line)
                    {   
                        $event->sheet->setCellValue('A'.$rows, date('d-M-Y', strtotime($line->transDate)));
                        $event->sheet->getStyle('A'.$rows)->getAlignment()->setHorizontal('center');

                        $event->sheet->setCellValue('B'.$rows, $line->poNo);
                        $event->sheet->getStyle('B'.$rows)->getAlignment()->setHorizontal('center');

                        $event->sheet->setCellValue('C'.$rows, $line->branch);
                        $event->sheet->getStyle('C'.$rows)->getAlignment()->setHorizontal('center');

                        $event->sheet->setCellValue('D'.$rows, $line->supplier);
                        $event->sheet->getStyle('D'.$rows)->getAlignment()->setHorizontal('center');

                        $event->sheet->setCellValue('E'.$rows, $line->po_type);
                        $event->sheet->getStyle('E'.$rows)->getAlignment()->setHorizontal('center');

                        $event->sheet->setCellValue('F'.$rows, $line->itemCode.' - '.$line->itemName);
                        $event->sheet->getStyle('F'.$rows)->getAlignment()->setHorizontal('center');

                        $quantity = ($this->status == 'posted') ? $line->posted_quantity : $line->quantity;
                        $event->sheet->setCellValue('G'.$rows, $quantity);
                        $event->sheet->getStyle('G'.$rows)->getAlignment()->setHorizontal('center');

                        $event->sheet->setCellValue('H'.$rows, $line->uom);
                        $event->sheet->getStyle('H'.$rows)->getAlignment()->setHorizontal('center');

                        $event->sheet->setCellValue('I'.$rows, number_format(floor(($line->srp*100))/100,2));
                        $event->sheet->getStyle('I'.$rows)->getAlignment()->setHorizontal('right');

                        $event->sheet->setCellValue('J'.$rows, number_format(floor(($line->total_amount*100))/100,2));
                        $event->sheet->getStyle('J'.$rows)->getAlignment()->setHorizontal('right');

                        $event->sheet->setCellValue('K'.$rows, $line->status);
                        $event->sheet->getStyle('K'.$rows)->getAlignment()->setHorizontal('center');

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
        return 'PURCHASED REPORT';
    }
}
