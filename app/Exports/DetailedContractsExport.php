<?php

namespace App\Exports;


use App\Helpers\NdsStopgagHelper;
use App\Models\Contract;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

// договора
class DetailedContractsExport implements
    FromCollection,
    ShouldAutoSize,
    WithHeadings,
    WithEvents,
    ShouldQueue
{
    use Exportable;

    private $dates = [];
    private $datesFromTo = [];

    public static $cancel = [];

    private function getDebit()
    {
        return '((payments.type = \'auto\' AND payments.payment_system IN(' . $this->getFromOutDebitTypes() . ')) OR (payments.type = \'user_auto\' AND payments.payment_system IN(' . $this->getFromAccountDebitTypes() . ')) OR (payments.type = \'user_auto\' AND payments.payment_system IN(' . $this->getOldFromAccountDebitTypes() . ')))';
    }

    private function getFromOutDebitTypes()
    {
//        type: auto
        return implode(',', [
            '\'UZCARD\'',
            '\'HUMO\'',
            '\'ACCOUNT\'',
            '\'PNFL\'',
        ]);
    }

    private function getFromAccountDebitTypes()
    {
//        type: user_auto
        return implode(',', [
            '\'ACCOUNT\'',
        ]);
    }

    private function getOldFromAccountDebitTypes()
    {
//        type: user_auto
        return implode(',', [
            '\'UZCARD\'',
            '\'HUMO\'',
        ]);
    }

    private function divideByMonth($min, $max)
    {
        $result = [];
        $current = Carbon::parse($min);
        $maxDate = Carbon::parse($max);
        while ($current->format('Y-m') <= $maxDate->format('Y-m')) {
            $result[] = $current->format('Y-m');
            $current->addMonth();
        }
        return $result;
    }

    public function __construct()
    {
        $row = Contract::first();
        $this->datesFromTo = report_filter($row);
        $this->dates = $this->divideByMonth($this->datesFromTo['date_from'], $this->datesFromTo['date_to']);
    }

    public function collection()
    {

        $dates = [];
        foreach ($this->datesFromTo as $key => $date) {
            $dates[$key] = Carbon::parse($date)->format('Y-m');
        }

        $expDate = NdsStopgagHelper::getExpiryDate();
        $curNds = config('test.nds');
        $subQuery = DB::table('contracts')
            ->whereRaw('contracts.company_id IS NOT NULL')
            ->whereRaw('contracts.partner_id IS NOT NULL')
            ->whereRaw('contracts.order_id IS NOT NULL')
            ->whereRaw('contracts.user_id IS NOT NULL')
            ->whereRaw('((contracts.`status` != 0 AND contracts.`status` != 2 AND contracts.`status` != 5))')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('buyer_personals')
                    ->whereColumn('buyer_personals.user_id', 'contracts.user_id');
            })
            ->whereRaw('DATE_FORMAT(`contracts`.`created_at`, \'%Y-%m\') BETWEEN \'' . $dates['date_from'] . '\' AND  \'' . $dates['date_to'] . '\'')
            ->groupBy('contracts.id')
            ->groupBy('contracts.period')
            ->groupBy('contracts.created_at')
            ->groupBy('companies.name')
            ->groupBy('general_companies.name_ru')  // Торговая компания (на русском)  // dev_nurlan 07.04.2022
            ->groupBy('companies.brand')
            ->groupBy('companies.inn')
            ->groupBy('partner_settings.nds')
            ->groupBy('orders.partner_total')
            ->groupBy('users.surname')
            ->groupBy('users.name')
            ->groupBy('users.patronymic')
            ->groupBy('users.gender')
            ->groupBy('users.birth_date')
            ->groupBy('contracts.user_id')
            ->groupBy('orders.total')
            ->groupBy('contracts.deposit')
            ->groupBy('contracts.status')
            ->groupBy('contracts.canceled_at')
            ->orderBy('contracts.created_at', 'desc')
            ->selectRaw('contracts.id AS contract_id')
            ->selectRaw('contracts.period')
            ->selectRaw('contracts.created_at')
            ->selectRaw('companies.name AS company_name')
            ->selectRaw('general_companies.name_ru AS general_company_name_ru')  // Торговая компания (на русском) // dev_nurlan 07.04.2022
            ->selectRaw('companies.brand AS company_brand')
            ->selectRaw('companies.inn AS company_inn')
            ->selectRaw('IF(partner_settings.nds, IF(DATE(contracts.created_at) > "' . $expDate . '", \''. $curNds*100 .'%\', \'15%\') , \'0%\' ) AS nds')
            ->selectRaw('orders.partner_total AS partner_total')
            ->selectRaw('contracts.id')
            ->selectRaw('CONCAT( users.surname, \' \', users.name, \' \', users.patronymic ) AS fio')
            ->selectRaw('IF( users.gender = 1, \'М\', \'Ж\' ) AS gender')
            ->selectRaw('TIMESTAMPDIFF( YEAR, users.birth_date, CURDATE()) AS old')
            ->selectRaw('contracts.user_id')
            ->selectRaw('orders.total AS total')
            ->selectRaw('contracts.deposit')
            ->selectRaw('CASE contracts.status WHEN 0 THEN \'' . __('contract.status_' . 0) . '\' WHEN 1 THEN \'' . __('contract.status_' . 1) . '\' WHEN 2 THEN \'' . __('contract.status_' . 2) . '\' WHEN 3 THEN \'' . __('contract.status_3' . 3) . '\' WHEN 4 THEN \'' . __('contract.status_' . 4) . '\' WHEN 5 THEN \'' . __('contract.status_' . 5) . '\' WHEN 9 THEN \'' . __('contract.status_' . 9) . '\' WHEN 10 THEN \'' . __('contract.status_' . 10) . '\' ELSE \'\' END AS status_caption')
            ->selectRaw('contracts.canceled_at')
            ->selectRaw('orders.partner_total AS price')
            ->selectRaw('orders.total AS price_prod')
            ->selectRaw( 'IF(DATE(contracts.created_at) > "' . $expDate . '", ' . $curNds . ', 0.15) as prod_nds')
            ->leftJoin('companies', 'companies.id', '=', 'contracts.company_id')
            ->leftJoin('general_companies', 'general_companies.id', '=', 'contracts.general_company_id')
            ->leftJoin('users', 'users.id', '=', 'contracts.user_id')
            ->leftJoin('orders', 'orders.id', '=', 'contracts.order_id')
            ->leftJoin('users AS partners', 'orders.partner_id', '=', 'partners.id')
            ->leftJoin('partner_settings', 'partners.company_id', '=', 'partner_settings.company_id')->get();

        $query = DB::table(DB::raw("({$subQuery->toSql()}) AS t"))
            ->selectRaw('t.contract_id')
            ->selectRaw('t.period')
            ->selectRaw('t.created_at')
            ->selectRaw('t.company_name')
            ->selectRaw('t.general_company_name_ru')  // Торговая компания (на русском) // dev_nurlan 07.04.2022
            ->selectRaw('t.company_brand')
            ->selectRaw('t.company_inn')
            ->selectRaw('( t.price - IF( t.nds > 0, ( t.price / ( t.nds + 1 ) * t.nds ), 0 )) AS price_no_nds_sum')
            ->selectRaw('t.nds')
            ->selectRaw('IF( t.nds > 0, ( t.price / ( t.nds + 1 ) * t.nds ), 0 ) AS price_nds')
            ->selectRaw('t.partner_total')
            ->selectRaw('t.contract_id AS id')
            ->selectRaw('t.fio')
            ->selectRaw('t.gender')
            ->selectRaw('t.old')
            ->selectRaw('t.user_id')
            ->selectRaw('t.price_prod - (( t.price_prod ) / ( t.prod_nds + 1 ) * t.prod_nds ) AS without_prod_price_nds')
            ->selectRaw('t.prod_nds')
            ->selectRaw('(( t.price_prod ) / ( t.prod_nds + 1 ) * t.prod_nds ) AS prod_price_nds')
            ->selectRaw('t.total')
            ->selectRaw('t.deposit')
            ->selectRaw('t.status_caption')
            ->selectRaw('t.canceled_at')
            ->leftJoin('payments', 'payments.contract_id', '=', 't.id')
            ->leftJoin('contract_payments_schedule', 'contract_payments_schedule.contract_id', '=', 't.id')
            ->whereRaw($this->getDebit())
            ->groupBy('t.contract_id')
            ->groupBy('t.period')
            ->groupBy('t.created_at')
            ->groupBy('t.company_name')
            ->groupBy('t.general_company_name_ru')  // Торговая компания (на русском) // dev_nurlan 07.04.2022
            ->groupBy('t.company_brand')
            ->groupBy('t.company_inn')
            ->groupBy('t.nds')
            ->groupBy('t.partner_total')
            ->groupBy('t.contract_id')
            ->groupBy('t.fio')
            ->groupBy('t.gender')
            ->groupBy('t.old')
            ->groupBy('t.user_id')
            ->groupBy('t.prod_nds')
            ->groupBy('t.total')
            ->groupBy('t.deposit')
            ->groupBy('t.status_caption')
            ->groupBy('t.canceled_at')
            ->groupBy('t.price')
            ->groupBy('t.price_prod');

        foreach ($this->dates as $date) {
            $query->selectRaw('SUM(IF(DATE_FORMAT( payments.created_at, \'%Y-%m\' ) = \'' . $date . '\' AND DATE_FORMAT( contract_payments_schedule.payment_date, \'%Y-%m\' ) = \'' . $date . '\',payments.amount, 0)) AS \'' . $date . '_paid\'');
            $query->selectRaw('MAX(IF(DATE_FORMAT( payments.created_at, \'%Y-%m\' ) = \'' . $date . '\' AND DATE_FORMAT( contract_payments_schedule.payment_date, \'%Y-%m\' ) = \'' . $date . '\',contract_payments_schedule.total, 0)) AS \'' . $date . '_debt\'');
        }

        return $query->get();
    }


    public function headings(): array
    {
        $header[] = 'Оферта';
        $header[] = 'Срок кредита';
        $header[] = 'Создан';
        $header[] = 'Компания';
        $header[] = "Торговая компания (на рус.)";  // dev_nurlan 07.04.2022
        $header[] = 'Бренд';
        $header[] = 'ИНН Поставщика';
        $header[] = 'Стоимость поставки';
        $header[] = 'НДС';
        $header[] = 'Сумма НДС';
        $header[] = 'Цена Покупная';
        $header[] = 'ID кредита';
        $header[] = 'Покупатель';
        $header[] = 'Пол';
        $header[] = 'Возраст';
        $header[] = 'ID Покупателя';
        $header[] = 'Стоимость поставки';
        $header[] = 'Ставка НДС %';
        $header[] = 'Сумма НДС';
        $header[] = 'Цена Окончательная';
        $header[] = 'Депозит';
        $header[] = 'Статус';
        $header[] = 'Дата отмены';
        foreach ($this->dates as $date) {
            $header[] = 'Оплачено за ' . $date;
            $header[] = 'План за ' . $date;
        }
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
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOTTED,
                        ],
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ]
                    ]
                ]);

                $event->sheet->getStyle('A1:Y1')->applyFromArray([
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
                $event->sheet->setAutoFilter('A1:Y1');

            }
        ];
    }

}
