<?php

namespace App\Exports;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\Billing;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\PaymentTerm;
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

class CollectionReportExport implements WithEvents, WithStyles, WithColumnWidths, WithTitle
{
    public function __construct($request)
    {   
        $this->orderby      = $request->get('order_by');  
        $this->keywords     = $request->get('keywords');
        $this->dateFrom     = $request->get('dateFrom');  
        $this->dateTo       = $request->get('dateTo');  
        $this->type         = $request->get('payment_type_id');  
        $this->branch       = $request->get('branch_id');
        $this->customer     = $request->get('customer_id');  
        $this->agent        = $request->get('agent_id');  
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
        $customer     = $this->customer;  
        $agent        = $this->agent;  
        $status       = $this->status; 

        $lines = Payment::select([
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
                
                $maxColumn = $this->getColumns(13);
                $firstStartColumn = $this->getColumns(0);
                $firstEndColumn = $this->getColumns(6);
                $secondStartColumn = $this->getColumns(7);
                $headers = ['INVOICE DATE', 'INVOICE NO', 'BRANCH', 'CUSTOMER', 'AGENT', 'PAYMENT TYPE', 'BANK NAME', 'ACCOUNT NO', 'ACCOUNT NAME', 'CHEQUE NO', 'CHEQUE DATE', 'STATUS', 'AMOUNT'];
                
                $event->sheet->getDelegate()->mergeCells('A1:'.$maxColumn.'1');
                $event->sheet->getStyle('A1:'.$maxColumn.'1')->applyFromArray($styleArray1)->getAlignment()->setHorizontal('center');
                $event->sheet->setCellValue('A1', 'COLLECTION REPORT');

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
                    if ($header == 'CUSTOMER') {
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
                    $event->sheet->setCellValue('A'.$rows, date('d-M-Y', strtotime($line->invoiceDate)));
                    $event->sheet->getStyle('A'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('center');

                    $event->sheet->setCellValue('B'.$rows, $line->invoiceNo);
                    $event->sheet->getStyle('B'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('center');

                    $event->sheet->setCellValue('C'.$rows, $line->branch);
                    $event->sheet->getStyle('C'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('center');

                    $event->sheet->getDelegate()->mergeCells('D'.$rows.':E'.$rows);
                    $event->sheet->setCellValue('D'.$rows, $line->customer);
                    $event->sheet->getStyle('D'.$rows.':E'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('center');

                    $event->sheet->setCellValue('F'.$rows, $line->agent);
                    $event->sheet->getStyle('F'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('center');

                    $event->sheet->setCellValue('G'.$rows, $line->type);
                    $event->sheet->getStyle('G'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('center');

                    $event->sheet->setCellValue('H'.$rows, $line->bankName);
                    $event->sheet->getStyle('H'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('center');

                    $event->sheet->setCellValue('I'.$rows, $line->acctNo);
                    $event->sheet->getStyle('I'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('center');

                    $event->sheet->setCellValue('J'.$rows, $line->acctName);
                    $event->sheet->getStyle('J'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('center');

                    $event->sheet->setCellValue('K'.$rows, $line->chequeNo);
                    $event->sheet->getStyle('K'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('center');

                    $chequeDate = $line->chequeDate ? date('d-M-Y', strtotime($line->chequeDate)) : '';
                    $event->sheet->setCellValue('L'.$rows, $chequeDate);
                    $event->sheet->getStyle('L'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('center');

                    $event->sheet->setCellValue('M'.$rows, $line->status);
                    $event->sheet->getStyle('M'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('center');

                    $event->sheet->setCellValue('N'.$rows, number_format(floor(($line->amount*100))/100,2));
                    $event->sheet->getStyle('N'.$rows)->applyFromArray($styleArray5)->getAlignment()->setHorizontal('right');
                    $totalAmt += floatval($line->amount);
                    $rows++;
                }

                $event->sheet->getDelegate()->mergeCells('A'.$rows.':M'.$rows);
                $event->sheet->setCellValue('A'.$rows, 'TOTAL AMOUNT:');
                $event->sheet->getStyle('A'.$rows.':M'.$rows)->applyFromArray($styleArray6)->getAlignment()->setHorizontal('right');

                $event->sheet->setCellValue('N'.$rows, number_format(floor(($totalAmt*100))/100,2));
                $event->sheet->getStyle('N'.$rows)->applyFromArray($styleArray3)->getAlignment()->setHorizontal('right');
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
        return 'COLLECTION REPORT';
    }
}
