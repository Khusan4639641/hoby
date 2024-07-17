<?php

namespace App\Exports;

use App\Helpers\NdsStopgagHelper;
use App\Models\OrderProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class OrdersCancelNewExport implements
    FromCollection,
    ShouldAutoSize,
    WithHeadings,
    WithEvents,
    WithColumnFormatting
{
    use Exportable;

    public function collection()
    {
        $row = OrderProduct::first();
        $datesFromTo = report_filter($row);
        $expDate = NdsStopgagHelper::getExpiryDate();
        $curNds = config('test.nds');
        $subQuery = DB::table('order_products')
            ->selectRaw('CONCAT(TRIM(user_manager.name), " ", TRIM(user_manager.surname), " ", TRIM(user_manager.patronymic)) AS FIO')
            ->selectRaw('contracts.prefix_act')
//            ->selectRaw('CONCAT(TRIM(client.name), " ", TRIM(client.surname), " ", TRIM(client.patronymic)) AS FIO')
//            ->selectRaw('client.id as user_id')
            ->selectRaw('contracts.period')
            ->selectRaw('contracts.created_at AS created_at')
            ->selectRaw("DATE_FORMAT(uta.updated_at, '%d.%m.%Y') as tax_updated_at")
            ->selectRaw("uta.id as tax_id")
            ->selectRaw("uta.fiscal_sign")
            ->selectRaw('DATE_FORMAT(contracts.canceled_at, "%d.%m.%Y") AS canceled_at')
            ->selectRaw('DATE_FORMAT(utc.updated_at, "%d.%m.%Y") as cancelled_tax_updated_at')
            ->selectRaw("utc.id as cancelled_tax_id")
            ->selectRaw('companies.id AS company_id')
            ->selectRaw('companies.name AS company_name')
//            ->selectRaw('companies.brand AS company_brand')
            ->selectRaw('company_uniq_nums.uniq_num AS uniq_num')
            ->selectRaw('general_companies.name_ru AS general_company_name_ru') // Торговая компания (на русском)
            ->selectRaw('companies.inn AS company_inn')
            ->selectRaw('order_products.name AS product_name')
            ->selectRaw('order_products.amount AS product_amount')
            ->selectRaw('IF(partner_settings.nds, IF(DATE(contracts.created_at) > "' . $expDate . '", \''. $curNds*100 .'%\', \'15%\') , \'0%\' ) AS nds')
            ->selectRaw('orders.partner_total AS price')
            ->selectRaw('contracts.id AS contract_id')
            ->selectRaw( 'IF(DATE(contracts.created_at) > "' . $expDate . '", \''. $curNds*100 .'%\', \'15%\') AS nds_percent')
            ->selectRaw('(orders.total / (IF(DATE(contracts.created_at) > "' . $expDate . '", ' . $curNds . ' , 0.15) + 1) * (IF(DATE(contracts.created_at) > "' . $expDate . '", ' . $curNds . ' , 0.15))) as prod_price_nds')
            ->selectRaw('orders.total AS sum_price')
            ->selectRaw('contracts.deposit')
            ->selectRaw('contracts.status AS contract_status')
            ->selectRaw('order_products.price_discount AS price_discount')
            ->selectRaw('order_products.price AS product_price')
            ->selectRaw('partner_settings.nds AS partner_settings_nds')
            ->leftJoin('orders', 'order_products.order_id', '=', 'orders.id')
            ->leftJoin('companies', 'orders.company_id', '=', 'companies.id')
            ->leftJoin('contracts', 'orders.id', '=', 'contracts.order_id')
            ->leftJoin('general_companies', 'contracts.general_company_id', '=', 'general_companies.id')
            ->leftJoin('company_uniq_nums', function($leftJoin) {
                $leftJoin
                    ->on('company_uniq_nums.company_id', '=', 'companies.id')
                    ->on('company_uniq_nums.general_company_id', '=', 'contracts.general_company_id');
            })
            ->leftJoin('users as user_manager', 'user_manager.id', '=', 'companies.manager_id')
//            ->leftJoin('users as client', 'client.id', '=', 'contracts.user_id')
            ->leftJoin('uz_taxes as utc', function($leftJoin) {
                $leftJoin
                    ->on('utc.contract_id', '=', 'contracts.id')
                    ->whereRaw("utc.type = 0") // sell
                    ->whereRaw('utc.status = 0'); // cancel
            })
            ->leftJoin('uz_taxes as uta', function($leftJoin) {
                $leftJoin
                    ->on('uta.contract_id', '=', 'contracts.id')
                    ->whereRaw("uta.type = 0") // sell
                    ->whereRaw('uta.status = 1'); // accept
            })
            ->leftJoin('users', 'users.id', '=', 'orders.partner_id')
            ->leftJoin('partner_settings', 'partner_settings.company_id', '=', 'users.company_id')
            ->whereRaw('contracts.status IN (1, 3, 4, 5, 9)')
            ->whereRaw('contracts.created_at BETWEEN \'' . $datesFromTo['date_from'] . '\' AND \'' . $datesFromTo['date_to'] . '\' OR contracts.canceled_at BETWEEN \'' . $datesFromTo['date_from'] . '\' AND \'' . $datesFromTo['date_to'] . '\'')
            ->orderByRaw('contracts.created_at desc');

        $query = DB::table(DB::raw("({$subQuery->toSql()}) AS t"))
            ->selectRaw('t.uniq_num') // Договор
            ->selectRaw('t.FIO') // Ответственный менеджер
            ->selectRaw('t.prefix_act') // Спецификация
            ->selectRaw('t.period') // Срок кредита
            ->selectRaw("DATE_FORMAT(t.created_at, '%d.%m.%Y')") // Создан
            ->selectRaw('t.tax_updated_at') // Отправлен чек (создание)(дата)
            ->selectRaw('t.tax_id') // № чека
            ->selectRaw('t.fiscal_sign') // Фискальный номер
            ->selectRaw('t.canceled_at') // Дата Отмены
            ->selectRaw('t.cancelled_tax_updated_at') // Отправлен чек (отмена)(дата)
            ->selectRaw('t.cancelled_tax_id') // № чека
            ->selectRaw('t.company_id') // ID Компании
            ->selectRaw('t.company_name') // Компания
            ->selectRaw('t.general_company_name_ru')  // Торговая компания (на русском)
//            ->selectRaw('t.company_brand')  // Бренд
            ->selectRaw('t.company_inn') // ИНН Поставщика
            ->selectRaw('t.product_name') // Наименование Товара
            ->selectRaw('t.product_amount') // Количество
            ->selectRaw("t.price_discount / IF(t.partner_settings_nds > 0, ( IF(t.created_at > '$expDate', $curNds, 0.15) + 1 ), 1) AS price_no_nds") // Цена
            ->selectRaw("t.price_discount / ( IF(t.partner_settings_nds > 0, IF(t.created_at > '$expDate', $curNds, 0.15), 0) + 1) * t.product_amount AS price_no_nds_sum") // Стоимость поставки
            ->selectRaw('t.nds') // НДС
            ->selectRaw("(t.price_discount - t.price_discount / ( IF(t.partner_settings_nds > 0, IF(t.created_at > '$expDate', $curNds, 0.15), 0) + 1)) * t.product_amount AS price_nds") // Сумма НДС (Закупка)
            ->selectRaw('(t.price_discount) * t.product_amount AS price_discount') // Стоимость с НДС (Закупка)
            ->selectRaw('t.contract_id') // ID кредита
//            ->selectRaw('t.FIO') // Клиент
//            ->selectRaw('t.user_id') // ID Клиента
            ->selectRaw("t.product_price / ( IF(t.created_at > '$expDate', $curNds, 0.15) + 1 ) AS sum_price_without_prod_price_nds_by_product") // Цена
            ->selectRaw("t.product_price / ( IF(t.created_at > '$expDate', $curNds, 0.15) + 1 ) * t.product_amount AS sum_price_without_prod_price_nds") // Стоимость поставки
            ->selectRaw('t.nds_percent') // Ставка НДС %
            ->selectRaw("(t.product_price - (t.product_price / (IF(t.created_at > '$expDate', $curNds, 0.15) + 1) )) * t.product_amount") // Сумма НДС (Продажа)
            ->selectRaw('(t.product_price) * t.product_amount') // Стоимость с НДС (Продажа)
            ->selectRaw('t.deposit') // Депозит
            ->selectRaw('CASE t.contract_status WHEN 0 THEN \'' . __('contract.status_' . 0) . '\' WHEN 1 THEN \'' . __('contract.status_' . 1) . '\' WHEN 2 THEN \'' . __('contract.status_' . 2) . '\' WHEN 3 THEN \'' . __('contract.status_' . 3) . '\' WHEN 4 THEN \'' . __('contract.status_' . 4) . '\' WHEN 5 THEN \'' . __('contract.status_' . 5) . '\' WHEN 9 THEN \'' . __('contract.status_' . 9) . '\' WHEN 10 THEN \'' . __('contract.status_' . 10) . '\' ELSE \'\' END AS status_caption');

        if (env('ORDERS_CANCEL_NEW_REPORT_LOGGING')){
            Log::channel('orders_cancel_new_report_results')->info(str_repeat('*',20).'ORDERS_CANCEL_NEW_REPORT_START'.str_repeat('*',20));
            foreach ($query->get() as $query_result){
                Log::channel('orders_cancel_new_report_results')->info("result: ", [$query_result]);
            }
            Log::channel('orders_cancel_new_report_results')->info(str_repeat('*',20).'ORDERS_CANCEL_NEW_REPORT_END'.str_repeat('*',20));
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Договор',
            'Ответственный менеджер',
            'Спецификация',
            'Срок кредита',
            'Создан',
            'Отправлен чек (создание)(дата)',
            '№ чека',
            'Фискальный номер',
            'Дата Отмены',
            'Отправлен чек (отмена)(дата)',
            '№ чека',
            'ID Компании',
            'Компания',
            "Торговая компания (на рус.)",
//            "Бренд",
            'ИНН Поставщика',
            'Наименование Товара',
            'Количество',
            'Цена',
            'Стоимость поставки',
            'НДС',
            'Сумма НДС (Закупка)',
            'Стоимость с НДС (Закупка)',
            'ID кредита',
//            'Клиент',
//            'ID Клиента',
            'Цена',
            'Стоимость поставки',
            'Ставка НДС %',
            'Сумма НДС (Продажа)',
            'Стоимость с НДС (Продажа)',
            'Депозит',
            'Статус',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'H' => NumberFormat::FORMAT_NUMBER,
            'P' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function registerEvents(): array
    {
        return [

            AfterSheet::class => function (AfterSheet $event) {

                $rows = $event->sheet->getDelegate()->getHighestRow();

                $event->sheet->getStyle('A2:AD' . $rows)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOTTED,
                        ],
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ]
                    ]
                ]);

                $event->sheet->getStyle('A1:AD1')->applyFromArray([
                    'font' => [
                        'bold' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ]
                ])->getFill()
                  ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('e6e6eb');
                $event->sheet->setAutoFilter('A1:Y1');

            }
        ];
    }
}
