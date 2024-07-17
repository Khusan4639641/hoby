<?php

namespace App\Exports;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;


class PaymentsExport implements
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

        $query = Payment::with('contract','paymentSystem')->with('buyer')->with('schedule')->has('contract')->has('buyer')->whereIn('type',  ['auto', 'refund']);

        report_filter($query);


        return $query->get();

    }

    public function map($payment): array{

            $check = (int)(@$payment->schedule->total - $payment->amount == @$payment->schedule->balance);
            $payment_system = '';
            $payment_system_all = [];
            foreach ($payment->paymentSystem as $item){
                if($payment->payment_system =='DEPOSIT'){
                    if( $item->amount==$payment->amount) {
                        $payment_system = $item->payment_system . ' (' . $item->amount . ')';
                        break;
                    }else{
                        $payment_system_all[] = $item->payment_system . ' (' . $item->amount . ')';
                    }
                }
            }

            if($payment->payment_system =='DEPOSIT' && $payment_system == ''){
                $payment_system = implode(', ',$payment_system_all);
            }

            return [
                $payment->id,
                @$payment->contract->confirmed,
                @$payment->contract->id,
                isset($payment->contract) ? $payment->contract->generalCompany->name_ru : "",  // Торговая компания (на русском)  // dev_nurlan 06.04.2022
                $payment->created_at,
                @$payment->schedule->total,
                @$payment->amount,
                @$payment->schedule->balance,
                (int) $check,
                @$payment->buyer->user->id,
                @$payment->buyer->user->fio,
                @$payment->contract->balance,
                @$payment->buyer->settings->zcoin,
                $payment->payment_system,
                $payment_system,
                $payment->statusText,
            ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Оформлен',
            'Номер Договора',
            "Торговая компания (на рус.)",  // dev_nurlan 06.04.2022
            'Дата создания',
            'Сумма к списанию',
            'Списано',
            'Остаток',
            'Проверка',
            'ID Пользователя',
            'ФИО',
            'Долг',
            'zcoin',
            'Платежная система',
            'Пополнение',
            'Статус',
        ];

    }

    public function registerEvents(): array
    {
        return [

            AfterSheet::class => function(AfterSheet $event) {
                $event->sheet->getStyle('A1:O1')->applyFromArray([
                ]);

                $rows = $event->sheet->getDelegate()->getHighestRow();

                $event->sheet->getStyle('A2:P'.$rows)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOTTED,
                        ],
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ]
                ]);

                $event->sheet->getStyle('A1:P1')->applyFromArray([
                    'font' => [
                        'bold' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ]
                    ]
                ])->getFill()
                  ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('e6e6eb');     // какой-то цвет устанавливает
                $event->sheet->setAutoFilter('A1:P1');     // Добавляет фильтры

            }
        ];
    }

}

?>
