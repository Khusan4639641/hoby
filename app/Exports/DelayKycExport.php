<?php

namespace App\Exports;

use App\Models\Contract;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomQuerySize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DelayKycExport implements
    FromQuery,
    ShouldAutoSize,
    WithMapping,
    WithHeadings,
    WithEvents,
    WithCustomQuerySize
{
    use Exportable;

    public string $earliestContractCreatedAt;
    public int $delayedDays;
    public object $query;

    public function __construct()
    {
        $filterType = request()->type ?? '';
        switch ($filterType) {
            case 'last_day':
                $this->delayedDays = 1;
                break;
            case 'last_week':
                $this->delayedDays = date('w');
                break;
        }

        $this->query = Contract::with(['debts', 'schedule', 'buyer.user', 'company', 'buyer.addressRegistration', 'order', 'payments'])
            ->whereIn('status', [Contract::STATUS_OVERDUE_30_DAYS, Contract::STATUS_OVERDUE_60_DAYS]);

        if ($filterType == 'last_day' || $filterType == 'last_week') {
            $this->query->whereHas('debtsLast', function ($subQuery) {
                $subQuery->whereRaw(DB::raw("DATEDIFF(CURDATE(), payment_date) BETWEEN 1 and $this->delayedDays"));
            });
        } else {
            report_filter($this->query, false);
        }
        $this->earliestContractCreatedAt = $this->query->pluck('created_at')->first() ?: now();
    }

    public function query()
    {
        return $this->query;
    }

    public function map($contract): array
    {
        return [
            $contract->confirmed,
            $contract->id,
            $contract->period,
            $contract->created_at,
            $contract->schedule[0]->total,
            (int)$contract->delayDays,
            $contract->delaySum,
            $contract->balance,
            $contract->buyer->user->id,
            $contract->buyer->user->fio,
            $contract->buyer->user->gender == 1 ? 'М' : 'Ж',
            number_format((time() - strtotime($contract->buyer->user->birth_date)) / 365 / 86400, 1, ',', ''),
            $contract->buyer->addressRegistration->address ?? '',
            $contract->buyer->user->phone,
            $contract->company->id,
            $contract->generalCompany->name_ru,  // Торговая компания (на русском)
            $contract->company->brand,
            $contract->order->productsName,
        ];
    }

    public function headings(): array
    {
        return [
            'Оформлен',
            'ID Кредита',
            'Срок кредита',
            'Дата создания',
            'Ежемесячный взнос',
            'Просрочено дней',
            'Просрочка',
            'Сумма договора',
            'Id Клиента',
            'Покупатель',
            'Пол',
            'Возраст',
            'Адрес',
            'Телефон',
            'ID Магазина',
            "Торговая компания (на рус.)",
            'Бренд',
            'Наименование товара',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $rows = $event->sheet->getDelegate()->getHighestRow();

                $event->sheet->getStyle('A2:R' . $rows)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_DOTTED,
                        ],
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ]
                    ]
                ]);

                $event->sheet->getStyle('A1:R1')->applyFromArray([
                    'font' => [
                        'bold' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ]
                    ]
                ])->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('e6e6eb');
                $event->sheet->setAutoFilter('A1:R1');
            }
        ];
    }

    public function querySize(): int
    {
        return $this->query->count();
    }
}


