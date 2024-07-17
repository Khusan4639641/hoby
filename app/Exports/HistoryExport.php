<?php

namespace App\Exports;

use App\Models\Payment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;


class HistoryExport implements
    FromCollection,
    ShouldAutoSize,
    WithMapping,
    WithHeadings,
    WithEvents
{
    use Exportable;
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
            // $order = Order::with('contract')->has('contract')->first();
            // dd($order);

        $query = Payment::with('contract')->with('buyer')/*->has('contract')*/->has('buyer')->where('type', '=', 'user');

        report_filter($query);


        return $query->get();

    }

    public function map($payment): array{
            return [
                $payment->buyer->user->fio,
                $payment->buyer->user->id,
                $payment->created_at,
                $payment->amount,
                $payment->payment_system,
                $payment->status,
                $payment->state,
                $payment->transaction_id,
//                isset($payment->contract) ? $payment->contract->id : "",  // dev_nurlan 06.04.2022
//                isset($payment->contract) ? $payment->contract->generalCompany->name_ru : "",  // Торговая компания (на русском)  // dev_nurlan 06.04.2022
            ];
    }


    public function headings(): array
    {
        return [
            'Фио',
            'Лицевой счет',
            'Дата создания',
            'Сумма пополнения',
            'Платежная система',
            'Статус',
            'Проверка',
            'Транзакция',
//            'Номер Договора',  // dev_nurlan 06.04.2022
//            "Торговая компания (на рус.)",  // dev_nurlan 06.04.2022
        ];

    }


    public function registerEvents(): array
    {
        return [


            AfterSheet::class => function(AfterSheet $event) {
                $event->sheet->getStyle('A1:H1')->applyFromArray([
                    'font' => [
                        'bold' => true
                    ],
                ]);


                $rows = $event->sheet->getDelegate()->getHighestRow();

                $event->sheet->getStyle('AC1:H'.$rows)->applyFromArray([
                    'borders' => [
                        'right' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                        ],
                    ]
                ]);


                $event->sheet->getStyle('A1:H'.$rows)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOTTED,
                        ],
                    ]
                ]);

                $event->sheet->getStyle('AC1:H'.$rows)->applyFromArray([
                    'borders' => [
                        'right' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                        ],
                    ]
                ]);

                $event->sheet->getStyle('A1:H1')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ]
                ])->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('e6e6eb');;
                $event->sheet->setAutoFilter('A1:H1');

            }
        ];
    }

}

?>
