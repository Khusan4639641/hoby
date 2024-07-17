<?php

namespace App\Exports;

use App\Models\Contract;
use App\Enums\ExcelReportsNumberFormatsEnum;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithProperties;

use \PhpOffice\PhpSpreadsheet\Style\Border;
use \PhpOffice\PhpSpreadsheet\Style\Fill;

class DelaysForEachPartnerExport implements
    FromQuery,
    ShouldAutoSize,
    WithMapping,
    WithHeadings,
    WithEvents,
    WithColumnFormatting,
    WithColumnWidths,
    WithProperties
{
    use Exportable;

    private int $company_id, $company_parent_id, $user_id;

    function __construct($company_id, $company_parent_id, $user_id)
    {
        $this->company_id = $company_id;
        $company_parent_id
            ? $this->company_parent_id = $company_parent_id
            : $this->company_parent_id = 0;
        $this->user_id = $user_id;
    }

    public function query(): Builder
    {
        $contracts = Contract::with(['company', 'buyer', 'buyer.guarants', 'buyer.addressRegistration', 'debts', 'order'])
            ->selectRaw('contracts.id')    // поле id из таблицы contracts
            ->selectRaw('contracts.period')    // поле period из таблицы contracts
            //->selectRaw('contracts.confirmed_at')    // поле confirmed_at из таблицы contracts
            ->selectRaw('contracts.created_at')    // поле created_at из таблицы contracts
            ->selectRaw('contracts.status')    // total из таблицы contracts
            ->selectRaw('contracts.total')    // total из таблицы contracts
            ->selectRaw('contracts.company_id')    // company_id из талицы contracts
            ->selectRaw('contracts.order_id')    // company_id из талицы contracts
            ->selectRaw('contracts.user_id')    // user_id из таблицы contracts
            ->selectRaw('contracts.expired_days')    // expired_days из таблицы contracts
            ->selectRaw('katm_regions.region_name')
            ->selectRaw('katm_regions.local_region_name')
            ->leftJoin("users", "users.id", "=", "contracts.user_id")
            ->leftJoin('katm_regions', function ($join) {
                $join->on('katm_regions.region', '=', 'users.region')
                    ->whereColumn('katm_regions.local_region', 'users.local_region');
            })
            ->whereIn('contracts.status', [Contract::STATUS_OVERDUE_60_DAYS, Contract::STATUS_OVERDUE_30_DAYS]) // просроченные контракты
            ->where('contracts.company_id', $this->company_id)  // контракты именно этого вендора
            ->where('contracts.partner_id', $this->user_id)     // контракты именно этого вендора
            ->orderBy('contracts.id');

        $filterType = request()->type ?? '';
        switch ($filterType) {
            case 'last_day':
                $delayedDays = 1;
                break;
            case 'last_week':
                $delayedDays = date('w');
                break;
        }

        if ($filterType == 'last_day' || $filterType == 'last_week') {
            $contracts->whereHas('debtsLast', function ($subQuery) use ($delayedDays) {
                $subQuery->whereRaw(DB::raw("DATEDIFF(CURDATE(), payment_date) BETWEEN 1 and $delayedDays"));
            });
        } else {
            $contract = new Contract(); // у меня не было выбора :)
            $contracts->whereBetween('contracts.created_at', report_filter($contract, false));
            unset($contract);
        }

        return $contracts;
    }

    public function map($contract): array
    {
        //$contract_confirmed_at = Carbon::parse($contract->getAttributes()['confirmed_at'])->format("d.m.Y H:i:s");

        if ($contract->expired_days === 0) {
            $expired_days = 0;
        } elseif ($contract->expired_days && $contract->expired_days > 0) {
            $expired_days = $contract->expired_days;
        } else {
            $expired_days = null;
        }


        $guarants = "";    // несколько доверителей    // ($contract->buyer && !empty($contract->buyer->guarants)) ? $contract->buyer->guarants : null,
        if ($contract->buyer && $contract->buyer->guarants) {
            foreach ($buyer_guarants = $contract->buyer->guarants->take(2) as $guarant) {
                if ($buyer_guarants->last() === $guarant) {
                    $guarants .= $guarant->name . ' ' . $guarant->phone;
                } else {
                    $guarants .= $guarant->name . ' ' . $guarant->phone . ', ';
                }
            }
        }

        $product_names = "";    // несколько product name'ов    //  data_get($contract, 'order.productsName', null)  // $contract->order ? $contract->order->productsName : null,
        if ($contract->order && $contract->order->products) {
            foreach ($contract->order->products as $product) {
                if ($contract->order->products->last() === $product) {
                    $product_names .= $product->original_name ?: $product->name;
                } else {
                    $product_names .= $product->original_name ? $product->original_name . ", " : $product->name . ", ";
                }
            }
        }

        return [
            //$contract_confirmed_at,
            $contract->id ?? null,
            $contract->period ?? null,
            $contract->created_at,
            data_get($contract, 'debts.0.total', null),  // $contract->debts[0]->total ?? null,
            $expired_days,
            data_get($contract, 'debts.0.payment_date', null),  // $contract->debts[0]->payment_date ?? null,
            $contract->delaySum ?? null,
            $contract->total ?? null,
            $contract->user_id ?? null,
            data_get($contract, 'buyer.fio', null),
            optional($contract->buyer)->getGender(),
            data_get($contract, 'buyer.age', null),
            $contract->region_name ?? null,
            $contract->local_region_name ?? null,
            data_get($contract, 'buyer.addressRegistration.address', null), // $contract->buyer ? $contract->buyer->addressRegistration->address : null,
            data_get($contract, 'buyer.phone', null),  // $contract->buyer ? $contract->buyer->phone : null,
            $guarants ?? null,
            $contract->company_id ?? null,
            $contract->company->brand ?? null,
            $product_names ?? null,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'L' => 12,
            'M' => 10,
            'N' => 50,
            'O' => 20,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'Q' => ExcelReportsNumberFormatsEnum::EXCEL_FORMAT_NUMBER_UZBEKISTAN_PHONE_NUMBER
        ];
    }

    public function headings(): array
    {
        return [
            //"Оформлен",               // 'Оформлен',
            "ID кредита",             // 'ID Кредита',
            "Срок кредита",           // 'Срок кредита',
            "Дата создания",          // 'Дата создания',
            "Ежемесячный взнос",      // 'Ежемесячный взнос',
            "Просрочено дней",        // 'Просрочено дней',
            "Просрочено с даты",      // (new)
            "Просрочка",              // 'Просрочка',
            "Сумма договора",         // 'Сумма договора',
            "ID клиента",             // 'Id Клиента',
            "Покупатель",             // 'Покупатель',
            "Пол",                    // 'Пол',
            "Возраст",                // 'Возраст',
            "Область",                // (new)
            "Район",                  // (new)
            "Адрес",                  // 'Адрес',
            "Телефон",                // 'Телефон',
            "Доверители",             // 'Доверители',
            "ID компании",            // 'ID Магазина',
            "Бренд",                  // 'Бренд'
            "Наименование товара",    // 'Наименование товара'
        ];
    }

    public function registerEvents(): array
    {
        $EXCEL_FORMAT_NUMBER_UZBEKISTAN_PHONE_NUMBER = "[<=998999999999](+###)##-###-##-##";

        return [
            AfterSheet::class => function (AfterSheet $event) use ($EXCEL_FORMAT_NUMBER_UZBEKISTAN_PHONE_NUMBER) {

                $rows = $event->sheet->getDelegate()->getHighestRow();

                $event->sheet->getStyle('A2:U' . $rows)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_DOTTED,
                        ],
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ]
                    ]
                ]);

                $event->sheet->getStyle('A1:U1')->applyFromArray([
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
                $event->sheet->setAutoFilter('A1:U1');
            }
        ];
    }

    public function properties(): array
    {
        return [
            'creator' => 'Нурлан Сарсенбаев',
            'lastModifiedBy' => 'Нурлан Сарсенбаев',
            'title' => 'Отчёт по просрочникам',
            'description' => 'Отчёт по просрочникам',
            'subject' => 'Просрочники',
            'keywords' => 'просрочники,эскпорт,таблица,отчёт',
            'category' => 'Просрочники',
            'manager' => 'Эмма Каспарьянц',
            'company' => 'test',
        ];
    }
}


