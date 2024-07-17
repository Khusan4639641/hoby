<?php

namespace App\Exports;

use App\Models\Contract;
use App\Models\ContractPaymentsSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomQuerySize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;


class PaymentDateExport implements
    FromQuery,
    ShouldAutoSize,
    WithMapping,
    WithHeadings,
    WithEvents,
    WithCustomQuerySize
{
    use Exportable;

    public static $headers = [];

    public function query() {

        $query = $this->getQuery();

        return $query;
    }

    public function map($contract): array{

        $time = strtotime(date('Y-m-d 23:00:00'));

        $sum = 0;
        $debts = 0;
        if($contract->schedule) {
            foreach ($contract->schedule as $item) {
                if ( $item->status == 1 || strtotime($item->payment_date) > $time ) continue;
                $sum +=$item->balance;
                $debts +=$item->total;
                Log::channel('report')->info($contract->id . ' ' . $item->id . ' ' . strtotime($item->payment_date) .' < '. $time . ' ' . date('Y-m-d 23:00:00') . ' ' . $item->payment_date);
            }
        }

        $data = [
            $contract->buyer->user->id,
            $contract->buyer->user->fio,
            $contract->buyer->addressRegistration->address,
            $contract->buyer->user->phone,
            $contract->company->id,
            $contract->company->name,
            $contract->generalCompany->name_ru,  // Торговая компания (на русском)  // dev_nurlan 07.04.2022
            $contract->company->brand,
            $contract->order->productsName, //$product_name,
            $contract->id,
            $contract->created_at,
            $contract->confirmed,
            $contract->total,
            $contract->period,
            $contract->schedule[0]->payment_date,
            $contract->schedule[0]->total, // еж.мес. взнос
            $debts-$sum,
            $sum
        ];

        return $data;
    }

    public function headings(): array
    {

        $header = [
            'Id Клиента',
            'ФИО клиента',
            'Адрес',
            'Телефон',
            'ID Магазина',
            'Компания',
            "Торговая компания (на рус.)",  // dev_nurlan 07.04.2022
            'Бренд',
            'Наименование товара',
            'ID Договора',
            'Дата договора',
            'Оформлен',
            'Сумма договора',
            'Срок кредита',
            'Дата погашения',
            'Ежемесячный взнос',
            'Погашенная сумма',
            'Остаток долга'
        ];


        return $header;

    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {

                $rows = $event->sheet->getDelegate()->getHighestRow();

                $event->sheet->getStyle('A2:R'.$rows)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOTTED,
                        ],
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ]
                    ]
                ]);

                $event->sheet->getStyle('A1:R1')->applyFromArray([
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
                  ->getStartColor()->setARGB('e6e6eb');
                $event->sheet->setAutoFilter('A1:R1');
            }
        ];
    }

    private function getQuery() {

        $query = Contract::with(['debts','schedule','buyer.user', 'company', 'buyer.addressRegistration', 'order'])
            ->has('debts')
            ->has('schedule')
            ->has('buyer.user')
            ->has('company')
            ->has('buyer.addressRegistration')
            ->has('order')
            ->whereIn('contracts.status', [1,3,4]);


        report_filter($query,false);

        return $query;
    }

    public function querySize(): int
    {
        $query = $this->getQuery();

        return $query->count();
    }

}


