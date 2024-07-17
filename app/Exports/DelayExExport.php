<?php

namespace App\Exports;

use App\Helpers\EncryptHelper;
use App\Models\Contract;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;


class DelayExExport implements
    FromQuery,
    ShouldAutoSize,
    WithMapping,
    WithHeadings,
    WithEvents,
    WithColumnFormatting
{
    use Exportable;

    public static $headers = [];
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
        $cury = date('Y');
        $curm = date('n');
        $y = date('Y', strtotime($this->earliestContractCreatedAt));
        $m = date('n', strtotime($this->earliestContractCreatedAt));
        $_header = [];
        $_header['sum'] = 0;

        while ($y <= $cury || $m <= $curm) {
            $_header[$y . '.' . $m] = 0;

            $m++;

            if ($m == 13) {
                $m = 1;
                $y++;
                if ($y > $cury) break;
            }

            if ($m > $curm && $y == $cury) break;
        }

        $schedule_payment_date = "";
        $schedule_total = "";
        if ($contract->schedule && !empty($contract->schedule) && ($contract->schedule->count() > 0)) {
            $schedule_payment_date = $contract->schedule[0]->payment_date;
            $schedule_total = $contract->schedule[0]->total; // еж.мес. взнос

            foreach ($contract->debts as $item) {

                $date = date('Y.n', strtotime($item->payment_date));
                if (!isset($_header[$date])) $_header[$date] = 0;
                $_header['sum'] += $item->balance;
                $_header[$date] = $item->balance;
            }
        }

        $product_name = "";
        if (
            $contract->order
            && $contract->order->productsName
        ) {
            $order_productName = $contract->order->productsName;
            $product_name = str_replace(';', ',', $order_productName);
        }


        $buyer_id = "";
        $buyer_fio = "";
        $buyer_passport_number = "";
        $buyer_pinfl = "";
        $buyer_gender = "";
        $buyer_age = "";
        $addressRegistration = "";
        $buyer_phone = "";
        if ($contract->buyer) {
            $buyer_id = $contract->buyer->id;
            $buyer_fio = $contract->buyer->fio;
            if ($contract->buyer->addressRegistration && $contract->buyer->addressRegistration->address) {
                $addressRegistration = $contract->buyer->addressRegistration->address;
            }
            if ($contract->buyer->personals) {
                if ($contract->buyer->personals->passport_number) {
                    $buyer_passport_number = EncryptHelper::decryptData($contract->buyer->personals->passport_number);
                }
                if ($contract->buyer->personals->pinfl) {
                    $buyer_pinfl = EncryptHelper::decryptData($contract->buyer->personals->pinfl);
                }
            }
            $buyer_gender = $contract->buyer->gender === 1 ? 'М' : 'Ж';
            if ($contract->buyer->birth_date) {
                $diff = date_diff(date_create($contract->buyer->birth_date), date_create(now()));
                $buyer_age = $diff->format('%y');
            }
            $buyer_phone = $contract->buyer->phone;
        }

        $data = [
            $buyer_id,
            $buyer_fio,
            $buyer_passport_number,
            $buyer_pinfl,
            $buyer_gender,
            $buyer_age,
            $addressRegistration,
            $buyer_phone,
            $contract->company->id,
            $contract->company->name,
            $contract->generalCompany->name_ru,  // Торговая компания (на русском)
            $contract->company->brand,
            $product_name,
            $contract->id,
            $contract->company->manager->fio ?? "",
            $contract->created_at,
            $contract->confirmed,
            $contract->total,
            $contract->period,
            $schedule_payment_date,
            $schedule_total, // еж.мес. взнос
            $contract->delayDays,
            $contract->balance,
            $contract->paymentSum,
        ];

        $data = array_merge($data, $_header);
        return $data;
    }

    public function headings(): array
    {
        $header = [
            'Id Клиента',
            'Покупатель',
            'Серия и номер паспорта',
            'ПИНФЛ',
            'Пол',
            'Возраст',
            'Адрес',
            'Телефон',
            'ID Магазина',
            'Компания',
            "Торговая компания (на рус.)",
            'Бренд',
            'Наименование товара',
            'ID Договора',
            'Ответственный менеджер',
            'Дата создания',
            'Оформлен',
            'Сумма договора',
            'Срок кредита',
            'Дата погашения',
            'Ежемесячный взнос',
            'Просрочено дней',
            'Баланс',
            'Оплачено'
        ];

        $cury = date('Y');
        $curm = date('n');
        $y = date('Y', strtotime($this->earliestContractCreatedAt));
        $m = date('n', strtotime($this->earliestContractCreatedAt));
        $_header = [];
        $_header['sum'] = 'Всего долг';

        $months = ['', 'январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'];

        while ($y <= $cury || $m <= $curm) {
            $_header[$y . '.' . $m] = 'Долг за ' . $months[$m] . ' ' . $y;
            $m++;
            if ($m == 13) {
                $m = 1;
                $y++;
                if ($y > $cury) break;
            }
            if ($m > $curm && $y == $cury) break;
        }

        $header = array_merge($header, $_header);

        return $header;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $rows = $event->sheet->getDelegate()->getHighestRow();

                $event->sheet->getStyle('A2:Y' . $rows)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_DOTTED,
                        ],
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ]
                ]);

                $event->sheet->getStyle('A1:Y1')->applyFromArray([
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
                $event->sheet->setAutoFilter('A1:Y1');
                $event->sheet->setSplitRow = 1;
                $event->sheet->setSplitCol = 0;
            }
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_NUMBER,
            'D' => NumberFormat::FORMAT_NUMBER,
        ];
    }
}


