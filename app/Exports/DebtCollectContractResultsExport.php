<?php

namespace App\Exports;

use App\Models\DebtCollect\DebtCollectContractResult;
use App\Models\DebtCollect\DebtCollector;
use App\Scopes\DebtCollectScope;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class DebtCollectContractResultsExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    use Exportable;

    private Carbon $month;

    public function __construct(string $month)
    {
        ini_set('max_execution_time', 0);
        $date = Carbon::createFromFormat('Y-m', $month);
        if(!$date->isValid()) {
            $date = Carbon::now();
        }

        $this->month = $date;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $period_start_at = $this->month->clone()->startOfMonth();
        $period_end_at = $this->month->clone()->endOfMonth();

        $collect_results = DebtCollectContractResult::with(['collector', 'contract'])
            ->whereBetween('period_start_at', [$period_start_at, $period_end_at])
            ->orderBy('collector_id')
            ->get();

        $rows = collect();
        foreach($collect_results as $collect_result) {
            $collector = DebtCollector::withoutGlobalScope(DebtCollectScope::class)->find($collect_result->collector_id);
            $contract = $collect_result->contract;

            $processedAt = $collector->contractProcessedAt($contract->id);

            $row = [
                'collector_id' => $collector->id,
                'collector_full_name' => $collector->full_name,
                'debtor_id' => $contract->user_id,
                'contract_id' => $contract->id,
                'processed_at' => $processedAt
            ];

            if($collect_result->total_amount) {
                $row['total_amount'] = $collect_result->total_amount;
                $row['amount'] = $collect_result->amount;
            } else {
                $row['total_amount'] = round($collect_result->payments()->sum('amount'), 2);
                $row['amount'] = round($row['total_amount'] * ($collect_result->rate / 100), 2);
            }

            $rows->push($row);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Ф.И.О.',
            'Должник',
            'Контракт',
            'Обработан',
            'Привлечено, сум',
            'Вознаграждение, сум'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $event->sheet->getStyle('A1:G1')->applyFromArray([
                    'font' => [
                        'bold' => true
                    ],
                ]);


                $rows = $event->sheet->getDelegate()->getHighestRow();

                $event->sheet->getStyle('AC1:G'.$rows)->applyFromArray([
                    'borders' => [
                        'right' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                        ],
                    ]
                ]);


                $event->sheet->getStyle('A1:G'.$rows)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOTTED,
                        ],
                    ]
                ]);

                $event->sheet->getStyle('AC1:G'.$rows)->applyFromArray([
                    'borders' => [
                        'right' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                        ],
                    ]
                ]);

                $event->sheet->getStyle('A1:G1')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ]
                ])->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('e6e6eb');
                $event->sheet->setAutoFilter('A1:G1');

            }
        ];
    }
}
