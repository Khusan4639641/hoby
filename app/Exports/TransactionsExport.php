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


class TransactionsExport implements
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

        $query = Payment::with('buyer');
        //->whereIn('type',  ['auto', 'refund', 'user','user_auto']);

        report_filter($query,'created_at');

        /// $query->take(50);

        return $query->get();

    }

    public function map($payment): array{

        $check = (int)(@$payment->schedule->total - $payment->amount == @$payment->schedule->balance);

        /*$payment_system = '';
        $payment_system_all = 0; // [];
        $card_sum = $deposit_sum = $other_sum = 0;
        foreach ($payment->paymentSystem as $item){
            if($payment->payment_system =='DEPOSIT'){
                if( $item->amount==$payment->amount) {
                    //$payment_system = $item->payment_system . ' (' . $item->amount . ')';
                    $deposit_sum +=$item->amount;
                    break;
                }else{
                    $deposit_sum +=$item->amount;
                  //  $payment_system_all[] = $item->payment_system . ' (' . $item->amount . ')';
                }
            }elseif(in_array($item->payment_system,['UZCARD','HUMO'])){
                $card_sum +=$item->amount;
            }else{
                $other_sum +=$item->amount;
            }
            $payment_system_all+=$item->amount;

        } */
        /*
        if($payment->payment_system =='DEPOSIT' && $payment_system == ''){
            $payment_system = implode(', ',$payment_system_all);
        } */

        $_types = [
          'user' => 'Клиент пополнил',
          'user_auto' => 'Клиент оплатил',
          'refund' => 'Возврат',
          'auto' => 'Автосписание',
          'upay' => 'upay',
        ];

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
            isset($_types[$payment->type]) ? $_types[$payment->type] : '' ,
            $payment->status,
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
            'Тип',
            'Статус',
        ];
    }

    public function registerEvents(): array
    {
        return [

            AfterSheet::class => function(AfterSheet $event) {

                $rows = $event->sheet->getDelegate()->getHighestRow();

                $event->sheet->getStyle('A2:P'.$rows)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOTTED,
                        ],
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ]
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
                  ->getStartColor()->setARGB('e6e6eb');;
                $event->sheet->setAutoFilter('A1:P1');

            }
        ];
    }

}

?>
