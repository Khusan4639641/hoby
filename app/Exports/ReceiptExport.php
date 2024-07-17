<?php

namespace App\Exports;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReceiptExport implements
    WithHeadings,
    WithEvents,
    WithStyles,
    FromQuery,
    WithMapping
{
    use Exportable;

    private array $date;
    private int $company_id;
    private float $currentNds;

    public function __construct($company_id, $date)
    {
        $this->company_id = $company_id;
        $this->date = $date;
        $this->currentNds = config('test.nds'); // example (0.12)
    }

    public function headings(): array
    {
        return [
            "ID кредита",
            "Срок кредита",
            "Создан",
            "Отправлен чек (создание)(дата)",
            "№ чека",
            "Сумма чека",
            "Ставка НДС %",
            "НДС",
            "Фискальный номер",
            "Наименование Товара",
            "Количество",
            "ИКПУ",
            "Дата Отмены",
            "Отправлен чек (отмена)(дата)",
            "№ чека",
            "ID Компании",
            "Компания",
            "Депозит",
            "Статус",
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style for headers
            1 => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->autoSize();
                $rows = $event->sheet->getDelegate()->getHighestRow();

                $event->sheet->getStyle('A2:S' . $rows)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_DOTTED,
                        ],
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ]
                    ]
                ]);
                $event->sheet->getStyle('A1:S1')->applyFromArray([
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
                $event->sheet->setAutoFilter('A1:S1');
            }
        ];
    }

    public function query($data = [])
    {
        $sub_query = DB::table('order_products')
            ->selectRaw('    CONCAT(
              TRIM( user_manager.NAME ),
              " ",
              TRIM( user_manager.surname ),
              " ",
            TRIM( user_manager.patronymic )) AS FIO,
            contracts.prefix_act,
            contracts.period,
            DATE_FORMAT( contracts.created_at, "%d.%m.%Y" ) AS created_at,
            DATE_FORMAT( uta.updated_at, "%d.%m.%Y" ) AS tax_updated_at,
            uta.id AS tax_id,
            uta.fiscal_sign,
            uta.json_data,

            DATE_FORMAT( contracts.canceled_at, "%d.%m.%Y" ) AS canceled_at,
            DATE_FORMAT( utc.updated_at, "%d.%m.%Y" ) AS cancelled_tax_updated_at,
            utc.id AS cancelled_tax_id,
            companies.id AS company_id,
            companies.NAME AS company_name,
            company_uniq_nums.uniq_num AS uniq_num,
            general_companies.name_ru AS general_company_name_ru,
            companies.inn AS company_inn,
            order_products.NAME AS product_name,
            order_products.amount AS product_amount,
           order_products.psic_code AS psic_code,
          IF
            ( IF ( partner_settings.nds > 0, ' . $this->currentNds . ', 0 ) > 0, "' . ($this->currentNds * 100) . '%", "0%" ) AS nds,
            orders.partner_total AS price,
            contracts.id AS contract_id,
            "' . ($this->currentNds * 100) . '%" AS nds_percent,
            ( orders.total / ( ' . $this->currentNds . ' + 1 ) * ' . $this->currentNds . ' ) AS prod_price_nds,
            orders.total AS sum_price,
            contracts.deposit,
            contracts.STATUS AS contract_status,
            order_products.price_discount AS price_discount,
            order_products.price AS product_price '
            )
            ->leftJoin('orders', 'order_products.order_id', '=', 'orders.id')
            ->leftJoin('companies', 'orders.company_id', '=', 'companies.id')
            ->leftJoin('contracts', 'orders.id', '=', 'contracts.order_id')
            ->leftJoin('general_companies', 'contracts.general_company_id', '=', 'general_companies.id')
            ->leftJoin('company_uniq_nums', function (JoinClause $joinClause) {
                $joinClause->on('company_uniq_nums.company_id', '=', 'companies.id')
                    ->on('company_uniq_nums.general_company_id', '=', 'contracts.general_company_id');
            })
            ->leftJoin('users as user_manager', 'user_manager.id', '=', 'companies.manager_id')
            ->leftJoin('uz_taxes as utc', function (JoinClause $joinClause) {
                $joinClause->on('utc.contract_id', '=', 'contracts.id')
                    ->where('utc.type', '=', 0)
                    ->where('utc.STATUS', 0);
            })
            ->leftJoin('uz_taxes as uta', function (JoinClause $joinClause) {
                $joinClause->on('uta.contract_id', '=', 'contracts.id')
                    ->where('uta.type', '=', 0)
                    ->where('uta.STATUS', 1);
            })
            ->leftJoin('users', 'users.id', '=', 'orders.partner_id')
            ->leftJoin('partner_settings', 'partner_settings.company_id', '=', 'users.company_id')
            ->whereIn('contracts.STATUS', [1, 3, 4, 5, 9])
            ->where(function (Builder $builder) {
                $builder->whereBetween('contracts.created_at', $this->date)
                    ->orWhereBetween('contracts.canceled_at', $this->date);
            })
            ->where('contracts.company_id', $this->company_id);


        $query = DB::table('t')
            ->select([
                "t.contract_id",
                "t.period",
                "t.created_at",
                "t.tax_updated_at",
                "t.tax_id",
                DB::raw("((JSON_EXTRACT(t.json_data, '$.ReceivedCard')/100) - ((JSON_EXTRACT(t.json_data, '$.ReceivedCard')/100) - t.price)) as tax_price"),
                "t.nds",
                DB::raw("(JSON_EXTRACT(t.json_data, '$.TotalVAT')/100) as total_vat"),
                "t.fiscal_sign",
                "t.product_name",
                "t.product_amount",
                "t.psic_code",
                "t.canceled_at",
                "t.cancelled_tax_updated_at",
                "t.cancelled_tax_id",
                "t.company_id",
                "t.company_name",
                "t.deposit",
            ])
            ->selectRaw("CASE
            t.contract_status
            WHEN 0 THEN
            'На модерации'
            WHEN 1 THEN
            'В рассрочке'
            WHEN 2 THEN
            'Не подтвержден'
            WHEN 3 THEN
            'Просрочен'
            WHEN 4 THEN
            'Просрочен'
            WHEN 5 THEN
            'Отменен'
            WHEN 9 THEN
            'Закрыт'
            WHEN 10 THEN
            'На проверке' ELSE ''
          END AS status_caption")
            ->from($sub_query, 't')
            ->orderByDesc('t.created_at');

        return $query;
    }

    public function map($row): array
    {
        return [
            $row->contract_id,
            $row->period,
            $row->created_at,
            $row->tax_updated_at,
            $row->tax_id,
            $row->tax_price,
            $row->nds,
            $row->total_vat,
            $row->fiscal_sign,
            $row->product_name,
            $row->product_amount,
            $row->psic_code,
            $row->canceled_at,
            $row->cancelled_tax_updated_at,
            $row->cancelled_tax_id,
            $row->company_id,
            $row->company_name,
            $row->deposit,
            $row->status_caption
        ];
    }
}
