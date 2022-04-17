<?php

namespace App\Exports;

use App\Models\Branch;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\Delivery;
use App\Models\DeliveryLine;
use App\Models\DeliveryLinePosting;
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

class SalesItemReportExport implements WithEvents, WithStyles, WithColumnWidths, WithTitle
{
    public function __construct($request)
    {   
        $this->orderby      = $request->get('order_by');  
        $this->keywords     = $request->get('keywords');
        $this->dateFrom     = $request->get('dateFrom');  
        $this->dateTo       = $request->get('dateTo');  
        $this->branch       = $request->get('branch_id');
        $this->category     = $request->get('item_category_id');  
        $this->item         = $request->get('item_id');   
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
        $branch       = $this->branch;
        $category     = $this->category;  
        $item         = $this->item;  

        $lines = DeliveryLinePosting::select([
            'delivery_lines_posting.id as id',
            'delivery_lines_posting.delivery_line_id as lineId',
            'delivery_lines_posting.date_delivered as transDate',
            'delivery_lines.uom as uom',
            'items.code as itemCode',
            'items.name as itemName',
            'items_category.name as itemCategory',
            'delivery_lines.is_special as is_special',
            'delivery_lines.srp as srp',
            'delivery_lines.quantity as prepQuantity',
            'delivery_lines_posting.quantity as quantity',
            'delivery_lines.total_amount as totalAmt',
            'delivery_lines.discount1 as disc1',
            'delivery_lines.discount2 as disc2',
            'delivery_lines.plus as plus',
            'branches.name as branch'
        ])
        ->leftJoin('delivery_lines', function($join)
        {
            $join->on('delivery_lines.id', '=', 'delivery_lines_posting.delivery_line_id');
        })
        ->leftJoin('items', function($join)
        {
            $join->on('items.id', '=', 'delivery_lines.item_id');
        })
        ->leftJoin('items_category', function($join)
        {
            $join->on('items_category.id', '=', 'items.item_category_id');
        })
        ->leftJoin('delivery', function($join)
        {
            $join->on('delivery.id', '=', 'delivery_lines.delivery_id');
        })
        ->leftJoin('branches', function($join)
        {
            $join->on('branches.id', '=', 'delivery.branch_id');
        })
        ->where(function($q) use ($keywords) {
            if (!empty($keywords)) {
                $q->where('delivery_lines.discount1', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.discount2', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.plus', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.uom', 'like', '%' . $keywords . '%')
                ->orWhere('delivery_lines.srp', 'like', '%' . $keywords . '%')
                ->orWhere('items.name', 'like', '%' . $keywords . '%')
                ->orWhere('items.code', 'like', '%' . $keywords . '%')
                ->orWhere('branches.name', 'like', '%' . $keywords . '%')
                ->orWhere('items_category.name', 'like', '%' . $keywords . '%');
            }
        })
        ->where(function($q) use ($dateFrom, $dateTo, $dateFrom2, $dateTo2) {
            if (!empty($dateFrom) && !empty($dateTo)) {
                $q->where('delivery_lines_posting.date_delivered', '>=', $dateFrom2)
                    ->where('delivery_lines_posting.date_delivered', '<=', $dateTo2);
            } else if (!empty($dateFrom) && empty($dateTo)) {
                $q->where('delivery_lines_posting.date_delivered', '=', $dateFrom);
            } else if (empty($dateFrom) && !empty($dateTo)) {
                $q->where('delivery_lines_posting.date_delivered', '=', $dateTo);
            }
        })
        ->where(function($q) use ($item){
            if ($item != '') {
                $q->where('items.id', '=',  $item);
            }
        })
        ->where(function($q) use ($category){
            if ($category != '') {
                $q->where('items_category.id', '=',  $category);
            }
        })
        ->where(function($q) use ($branch){
            if ($branch != '') {
                $q->where('branches.id', '=',  $branch);
            }
        })
        ->where('delivery_lines_posting.is_active', 1)
        ->orderBy('delivery_lines_posting.id', $orderby)
        ->get();

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
                $styleArray3 = [
                    'font' => [
                        // 'name'      =>  'Calibri',
                        'size'      =>  12,
                        'bold'      =>  true,
                        'color' => ['rgb' => 'f1416c']
                    ]
                ];

                $dateFrom = $this->dateFrom ? date('d-M-Y', strtotime($this->dateFrom)) : '';
                $dateTo   = $this->dateTo ? date('d-M-Y', strtotime($this->dateTo)) : '';
                
                $maxColumn = $this->getColumns(11);
                $firstStartColumn = $this->getColumns(0);
                $firstEndColumn = $this->getColumns(5);
                $secondStartColumn = $this->getColumns(6);
                $headers = ['TRANSACTION DATE', 'BRANCH', 'CATEGORY', 'ITEMS', 'QTY', 'UOM', 'SRP', 'PLUS', 'DISC1', 'DISC2', 'IS SPECIAL', 'TOTAL'];
                
                $event->sheet->getDelegate()->mergeCells('A1:'.$maxColumn.'1');
                $event->sheet->getDelegate()->mergeCells('A2:'.$maxColumn.'2');
                $event->sheet->getStyle('A1:'.$maxColumn.'2')->applyFromArray($styleArray1)->getAlignment()->setHorizontal('center');
                $event->sheet->setCellValue('A1', 'SALES ITEM REPORT');

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
                foreach ($headers as $header)
                {   
                    $column = $this->getColumns($count);
                    $event->sheet->getStyle($column.''.$rows)->applyFromArray($styleArray2)->getAlignment()->setHorizontal('center');
                    $event->sheet->setCellValue($column.''.$rows, $header);
                    $count++;
                }

                $rows = 7; $totalAmt = 0;
                foreach ($lines as $line)
                {   
                    $event->sheet->setCellValue('A'.$rows, date('d-M-Y', strtotime($line->transDate)));
                    $event->sheet->getStyle('A'.$rows)->getAlignment()->setHorizontal('center');

                    $event->sheet->setCellValue('B'.$rows, $line->branch);
                    $event->sheet->getStyle('B'.$rows)->getAlignment()->setHorizontal('center');

                    $event->sheet->setCellValue('C'.$rows, $line->itemCategory);
                    $event->sheet->getStyle('C'.$rows)->getAlignment()->setHorizontal('center');

                    $event->sheet->setCellValue('D'.$rows, $line->itemCode.' - '.$line->itemName);
                    $event->sheet->getStyle('D'.$rows)->getAlignment()->setHorizontal('center');

                    $event->sheet->setCellValue('E'.$rows, $line->quantity);
                    $event->sheet->getStyle('E'.$rows)->getAlignment()->setHorizontal('center');

                    $event->sheet->setCellValue('F'.$rows, $line->uom);
                    $event->sheet->getStyle('F'.$rows)->getAlignment()->setHorizontal('center');

                    $event->sheet->setCellValue('G'.$rows, number_format(floor(($line->srp*100))/100,2));
                    $event->sheet->getStyle('G'.$rows)->getAlignment()->setHorizontal('right');

                    $plus = $line->plus ? $line->plus.'%' : '';
                    $event->sheet->setCellValue('H'.$rows, $plus);
                    $event->sheet->getStyle('H'.$rows)->getAlignment()->setHorizontal('center');

                    $disc1 = $line->disc1 ? $line->disc1.'%' : '';
                    $event->sheet->setCellValue('I'.$rows, $disc1);
                    $event->sheet->getStyle('I'.$rows)->getAlignment()->setHorizontal('center');

                    $disc2 = $line->disc2 ? $line->disc2.'%' : '';
                    $event->sheet->setCellValue('J'.$rows, $disc2);
                    $event->sheet->getStyle('J'.$rows)->getAlignment()->setHorizontal('center');

                    $is_special = ($line->is_special == 0) ? 'No' : 'Yes';
                    $event->sheet->setCellValue('K'.$rows, $is_special);
                    $event->sheet->getStyle('K'.$rows)->getAlignment()->setHorizontal('center');

                    $event->sheet->setCellValue('L'.$rows, number_format(floor(($line->totalAmt*100))/100,2));
                    $event->sheet->getStyle('L'.$rows)->getAlignment()->setHorizontal('right');
                    $totalAmt += floatval($line->totalAmt);
                    $rows++;
                }

                $event->sheet->setCellValue('K'.$rows, 'TOTAL AMOUNT:');
                $event->sheet->getStyle('K'.$rows)->applyFromArray($styleArray2)->getAlignment()->setHorizontal('right');

                $event->sheet->setCellValue('L'.$rows, number_format(floor(($totalAmt*100))/100,2));
                $event->sheet->getStyle('L'.$rows)->applyFromArray($styleArray3)->getAlignment()->setHorizontal('right');
            },
        ];
    }

    public function columnWidths(): array
    {   
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

    public function styles(Worksheet $sheet)
    {
        return [];
    }

    public function title(): string
    {
        return 'SALES ITEM REPORT';
    }
}
