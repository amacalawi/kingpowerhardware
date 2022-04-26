<?php

namespace App\Exports;

use App\Models\Branch;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\TransferItem;
use App\Models\TransferItemLine;
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

class TransferItemReportExport implements WithEvents, WithStyles, WithColumnWidths, WithTitle
{
    public function __construct($request)
    {   
        $this->orderby      = $request->get('order_by');  
        $this->keywords     = $request->get('keywords');
        $this->dateFrom     = $request->get('dateFrom');  
        $this->dateTo       = $request->get('dateTo');    
        $this->branchFrom   = $request->get('transfer_from');
        $this->branchTo     = $request->get('transfer_to');
        $this->category     = $request->get('item_category_id');  
        $this->item         = $request->get('item_id');  
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
        $branchFrom   = $this->branchFrom;
        $branchTo     = $this->branchTo;
        $category     = $this->category;  
        $item         = $this->item;  
        $status       = $this->status;

        $lines = TransferItemLine::select([
            'transfer_items_lines.id as id',
            'transfer_items_lines.created_at as transDate',
            'items.name as itemName',
            'items.code as itemCode',
            'items_category.name as itemCategory',
            'transfer_items_lines.quantity as quantity',
            'unit_of_measurements.code as uom',
            'transfer_items_lines.srp as srp',
            'transfer_items_lines.total_amount as total_amount',
            'transfer_items_lines.posted_quantity as posted_quantity',
            'transfer_items_lines.discount1 as disc1',
            'transfer_items_lines.discount2 as disc2',
            'transfer_items_lines.plus as plus',
            'bra1.name as branchFrom',
            'bra2.name as branchTo',
            'transfer_items.transfer_no as transNo',
            'transfer_items_lines.status as status',
        ])
        ->leftJoin('items', function($join)
        {
            $join->on('items.id', '=', 'transfer_items_lines.item_id');
        })
        ->leftJoin('unit_of_measurements', function($join)
        {
            $join->on('unit_of_measurements.id', '=', 'transfer_items_lines.uom_id');
        })
        ->leftJoin('items_category', function($join)
        {
            $join->on('items_category.id', '=', 'items.item_category_id');
        })
        ->leftJoin('transfer_items', function($join)
        {
            $join->on('transfer_items.id', '=', 'transfer_items_lines.transfer_item_id');
        })
        ->leftJoin('branches as bra1', function($join)
        {
            $join->on('bra1.id', '=', 'transfer_items.transfer_from');
        })
        ->leftJoin('branches as bra2', function($join)
        {
            $join->on('bra2.id', '=', 'transfer_items.transfer_to');
        })
        ->where(function($q) use ($keywords) {
            if (!empty($keywords)) {
                $q->where('transfer_items_lines.srp', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.quantity', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.total_amount', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.posted_quantity', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.discount1', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.discount2', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items_lines.plus', 'like', '%' . $keywords . '%')
                  ->orWhere('unit_of_measurements.code', 'like', '%' . $keywords . '%')
                  ->orWhere('items.code', 'like', '%' . $keywords . '%')
                  ->orWhere('items.name', 'like', '%' . $keywords . '%')
                  ->orWhere('bra1.name', 'like', '%' . $keywords . '%')
                  ->orWhere('bra2.name', 'like', '%' . $keywords . '%')
                  ->orWhere('transfer_items.transfer_no', 'like', '%' . $keywords . '%')
                  ->orWhere('items_category.name', 'like', '%' . $keywords . '%');
            }
        })
        ->where(function($q) use ($dateFrom, $dateTo, $dateFrom2, $dateTo2) {
            if (!empty($dateFrom) && !empty($dateTo)) {
                $q->where('transfer_items_lines.created_at', '>=', $dateFrom2)
                    ->where('transfer_items_lines.created_at', '<=', $dateTo2);
            } else if (!empty($dateFrom) && empty($dateTo)) {
                $q->where('transfer_items_lines.created_at', '=', $dateFrom);
            } else if (empty($dateFrom) && !empty($dateTo)) {
                $q->where('transfer_items_lines.created_at', '=', $dateTo);
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
        ->where(function($q) use ($branchFrom){
            if ($branchFrom != '') {
                $q->where('bra1.id', '=',  $branchFrom);
            }
        })
        ->where(function($q) use ($branchTo){
            if ($branchTo != '') {
                $q->where('bra2.id', '=',  $branchTo);
            }
        })
        ->where(function($q) use ($status){
            if ($status != '') {
                $q->where("transfer_items_lines.status", $status);
            }
        })
        ->where('transfer_items_lines.is_active', 1)
        ->orderBy('transfer_items_lines.id', $orderby)
        ->get();

        return [
            AfterSheet::class => function(AfterSheet $event) use ($lines) {
                $styleArray1 = [
                    'font' => [
                        // 'name'      =>  'Calibri',
                        'size'      =>  14,
                        'bold'      =>  true,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '3c3939'],
                        ],
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'color' => ['rgb' => '3c3939']
                    ]
                ];
                $styleArray2 = [
                    'font' => [
                        // 'name'      =>  'Calibri',
                        'size'      =>  12,
                        'bold'      =>  true,
                        'color' => ['rgb' => '000000']
                    ],
                ];
                $styleArray3 = [
                    'font' => [
                        // 'name'      =>  'Calibri',
                        'size'      =>  12,
                        'bold'      =>  true,
                        'color' => ['rgb' => 'f1416c']
                    ],
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ]
                ];

                $styleArray4 = [
                    'font' => [
                        // 'name'      =>  'Calibri',
                        'size'      =>  12,
                        'bold'      =>  true,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '3c3939'],
                        ],
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'color' => ['rgb' => '3c3939']
                    ]
                ];

                $styleArray5 = [
                    'font' => [
                        // 'name'      =>  'Calibri',
                        'size'      =>  11,
                    ],
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ]
                ];

                $styleArray6 = [
                    'font' => [
                        // 'name'      =>  'Calibri',
                        'size'      =>  12,
                        'bold'      =>  true,
                        'color' => ['rgb' => '000000']
                    ],
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ]
                ];

                $dateFrom = $this->dateFrom ? date('d-M-Y', strtotime($this->dateFrom)) : '';
                $dateTo   = $this->dateTo ? date('d-M-Y', strtotime($this->dateTo)) : '';
                
                $maxColumn = $this->getColumns(11);
                $firstStartColumn = $this->getColumns(0);
                $firstEndColumn = $this->getColumns(5);
                $secondStartColumn = $this->getColumns(6);
                $headers = ['TRANSACTION DATE', 'TRANSFER NO', 'BRANCH FROM', 'BRANCH TO', 'CATEGORY', 'ITEMS', 'UOM', 'QUANTITY', 'SRP', 'PLUS', 'DISCOUNT 1', 'DISCOUNT 2', 'STATUS', 'TOTAL'];
                
                $event->sheet->getDelegate()->mergeCells('A1:'.$maxColumn.'1');
                $event->sheet->getStyle('A1:'.$maxColumn.'1')->applyFromArray($styleArray1)->getAlignment()->setHorizontal('center');
                $event->sheet->setCellValue('A1', 'TRANSFER ITEM REPORT');

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
                    if ($header == 'ITEMS') {
                        $column = $this->getColumns($count);
                        $column2 = $this->getColumns(($count + 1));
                        $event->sheet->getDelegate()->mergeCells($column.''.$rows.':'.$column2.''.$rows);
                        $event->sheet->getStyle($column.''.$rows.':'.$column2.''.$rows)->applyFromArray($styleArray4)->getAlignment()->setHorizontal('center');
                        $event->sheet->setCellValue($column.''.$rows, $header);
                        $count++;
                    } else {
                        $column = $this->getColumns($count);
                        $event->sheet->getStyle($column.''.$rows)->applyFromArray($styleArray4)->getAlignment()->setHorizontal('center');
                        $event->sheet->setCellValue($column.''.$rows, $header);
                    }
                    $count++;
                }

                $rows = 7; $totalAmt = 0;
                foreach ($lines as $line)
                {   
                    $event->sheet->setCellValue('A'.$rows, date('d-M-Y', strtotime($line->transDate)));
                    $event->sheet->getStyle('A'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('center');

                    $event->sheet->setCellValue('B'.$rows, $line->transNo);
                    $event->sheet->getStyle('B'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('center');

                    $event->sheet->setCellValue('C'.$rows, $line->branchFrom);
                    $event->sheet->getStyle('C'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('center');
                    
                    $event->sheet->setCellValue('D'.$rows, $line->branchTo);
                    $event->sheet->getStyle('D'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('center');

                    $event->sheet->setCellValue('E'.$rows, $line->itemCategory);
                    $event->sheet->getStyle('E'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('center');

                    $event->sheet->getDelegate()->mergeCells('F'.$rows.':G'.$rows);
                    $event->sheet->setCellValue('F'.$rows, $line->itemCode.' - '.$line->itemName);
                    $event->sheet->getStyle('F'.$rows.':G'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('left');

                    $event->sheet->setCellValue('H'.$rows, $line->uom);
                    $event->sheet->getStyle('H'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('center');

                    $quantity = ($this->status == 'partial') ? $line->posted_quantity : $line->quantity;
                    $event->sheet->setCellValue('I'.$rows, $quantity);
                    $event->sheet->getStyle('I'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('center');

                    $event->sheet->setCellValue('J'.$rows, number_format(floor(($line->srp*100))/100,2));
                    $event->sheet->getStyle('J'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('right');
                    
                    $plus = $line->plus ? $line->plus.'%' : '';
                    $event->sheet->setCellValue('K'.$rows, $plus);
                    $event->sheet->getStyle('K'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('center');

                    $disc1 = $line->disc1 ? $line->disc1.'%' : '';
                    $event->sheet->setCellValue('L'.$rows, $disc1);
                    $event->sheet->getStyle('L'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('center');

                    $disc2 = $line->disc2 ? $line->disc2.'%' : '';
                    $event->sheet->setCellValue('M'.$rows, $disc2);
                    $event->sheet->getStyle('M'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('center');

                    $status = ($this->status == 'partial' || $this->status == 'posted') ? $line->status : 'prepared';
                    $event->sheet->setCellValue('N'.$rows, $status);
                    $event->sheet->getStyle('N'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('center');

                    
                    $event->sheet->setCellValue('O'.$rows, number_format(floor(($line->total_amount*100))/100,2));
                    $event->sheet->getStyle('O'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('right');
                    $totalAmt += floatval($line->total_amount);
                    $rows++;
                }

                $event->sheet->getDelegate()->mergeCells('A'.$rows.':N'.$rows);
                $event->sheet->setCellValue('A'.$rows, 'TOTAL AMOUNT:');
                $event->sheet->getStyle('A'.$rows.':N'.$rows)->applyFromArray($styleArray6)->getAlignment()->setHorizontal('right');

                $event->sheet->setCellValue('O'.$rows, number_format(floor(($totalAmt*100))/100,2));
                $event->sheet->getStyle('O'.$rows)->applyFromArray($styleArray3)->getAlignment()->setHorizontal('right');
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
            'O' => 20,     
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [];
    }

    public function title(): string
    {
        return 'TRANSFER ITEM REPORT';
    }
}
