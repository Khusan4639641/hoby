<?php

namespace App\Exports;


use App\Helpers\NdsStopgagHelper;
use App\Models\Contract;
use Illuminate\Support\Facades\Config;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// Бухгалтерия
class OrdersExport implements
    FromQuery,
    ShouldAutoSize,
    WithHeadings,
    WithEvents,
    WithMapping,
    WithColumnFormatting
{
    use Exportable;

    public function query($data = [])
    {
        $rawQuery = Contract::first();
        $datesFromTo = report_filter($rawQuery);
        $from = $datesFromTo['date_from'];
        $to = $datesFromTo['date_to'];

        $expDate = NdsStopgagHelper::getExpiryDate();
        $curNds = config('test.nds');
        $ifNds = "IF(DATE(contracts.created_at) > \"$expDate\", $curNds, 0.15)";
        $ifNdsPlus = "IF(DATE(contracts.created_at) > \"$expDate\", 1 + $curNds, 1.15)";
        $query = Contract::leftJoin('companies as com', 'com.id' , 'contracts.company_id')
            ->leftJoin('company_uniq_nums as cun', function($leftJoin) {
                $leftJoin->on('cun.company_id', '=', 'com.id')
                    ->on('cun.general_company_id', '=', 'contracts.general_company_id');
            })
            ->leftJoin('users as u', 'u.id',  'contracts.user_id')
//            ->leftJoin('users as user_manager', 'user_manager.id',  'com.manager_id')
//            ->leftJoin('uz_taxes as uta', function ($join) {
//                $join->on('uta.contract_id', '=', 'contracts.id')
//                    ->where('uta.type', '=', 0)
//                    ->where('uta.status', '=', 1);
//            })
//            ->leftJoin('uz_taxes as utc', function ($join) {
//                $join->on('utc.contract_id', '=', 'contracts.id')
//                    ->where('utc.type', '=', 0)
//                    ->where('utc.status', '=', 0);
//            })
            ->leftJoin('general_companies as gc', 'gc.id' , 'com.general_company_id')
            ->leftJoin('partner_settings as ps', 'ps.company_id' , 'com.id')
            ->leftJoin('order_products as op', 'op.order_id' , 'contracts.order_id')
            ->select(
                'cun.uniq_num', // Договор
                'contracts.prefix_act', // Спецификация
                'contracts.period', // Срок кредита
            )
//            ->selectRaw('CONCAT(TRIM(user_manager.name), " ", TRIM(user_manager.surname), " ", TRIM(user_manager.patronymic)) AS manager_FIO') // Менеджер
            ->selectRaw('DATE_FORMAT(contracts.canceled_at, "%d.%m.%Y") as contract_canceled_at') // Дата Отмены
            ->selectRaw('DATE_FORMAT(contracts.created_at, "%d.%m.%Y") as contract_created_at') // Создан
//            ->selectRaw('DATE_FORMAT(uta.updated_at, "%d.%m.%Y") as tax_updated_at') // tax_updated_at
//            ->selectRaw('DATE_FORMAT(utc.updated_at, "%d.%m.%Y") as cancelled_tax_updated_at') // cancelled_tax_updated_at
//            ->selectRaw('uta.id as tax_id') // tax_id
//            ->selectRaw('uta.fiscal_sign') // fiscal_sign
//            ->selectRaw('utc.id as cancelled_tax_id') // cancelled_tax_id
            ->addSelect(
                'com.id as company_id', // ID Компании
                'com.name as company_name', // Компания
                'gc.name_ru as general_company_name_ru', // Торговая компания (на рус.)
                'com.brand as company_brand', // Бренд
                'com.inn', // ИНН Поставщика
                'op.name as product_name', // Наименование Товара
                'op.amount as product_amount' // Количество
            )
            ->selectRaw("(op.price_discount * op.amount - IF(ps.nds > 0, op.price_discount * op.amount / $ifNdsPlus * $ifNds, 0)) / op.amount as price_no_nds") // Цена
            ->selectRaw("op.price_discount * op.amount - IF(ps.nds > 0, op.price_discount * op.amount / $ifNdsPlus  * $ifNds, 0) AS price_no_nds_sum") // Стоимость поставки
            ->selectRaw("IF(ps.nds > 0, $ifNds * 100, 0) AS nds") // НДС
            ->selectRaw("IF(ps.nds > 0, op.price_discount * op.amount / $ifNdsPlus * $ifNds, 0)  AS price_nds") //СуммаНДС
            ->selectRaw('op.price_discount * op.amount AS price') // Стоимость с НДС
            ->selectRaw('contracts.id') // ID кредита
            ->selectRaw('CONCAT(TRIM(u.name), " ", TRIM(u.surname), " ", TRIM(u.patronymic)) AS FIO') // Покупатель
            ->selectRaw('u.id as user_id') // ID Покупателя
            ->selectRaw("(op.price * op.amount - (op.price * op.amount) / $ifNdsPlus * $ifNds) / op.amount as sum_price_minus_prod_price_nds_divided_to_amount") // Цена
            ->selectRaw("op.price * op.amount - (op.price * op.amount) / $ifNdsPlus * $ifNds as sum_price_minus_prod_price_nds")// Стоимость поставки
            ->selectRaw("op.price * op.amount / $ifNdsPlus * $ifNds AS prod_price_nds") // Сумма НДС
            ->selectRaw('op.price * op.amount AS sum_price') // Стоимость с НДС
            ->selectRaw('contracts.deposit') // Депозит
            ->selectRaw('contracts.status') // Статус
            ->orderBy('contracts.created_at', 'desc')
            ->whereBetween('contracts.created_at', [$from, $to])
            ->whereIn('contracts.status', [Contract::STATUS_ACTIVE, Contract::STATUS_OVERDUE_60_DAYS, Contract::STATUS_OVERDUE_30_DAYS, Contract::STATUS_CANCELED, Contract::STATUS_COMPLETED])
            ->groupBy(
                'contracts.id',
                'contracts.status',
                'cun.uniq_num',
                'contracts.prefix_act',
                'contracts.period',
                'contracts.created_at',
                'contracts.canceled_at',
//                'user_manager.name',
//                'user_manager.surname',
//                'user_manager.patronymic',
//                'uta.id',
//                'uta.updated_at',
//                'uta.fiscal_sign',
//                'utc.id',
//                'utc.updated_at',
                'com.id',
                'com.name',
                'gc.name_ru',
                'com.brand',
                'com.inn',
                'op.name',
                'op.amount',
                'op.price',
                'u.id',
                'contracts.deposit',
                'op.id',
                'u.name',
                'u.surname',
                'u.patronymic',
                'op.price_discount',
                'op.amount',
                'ps.nds',
            );

        return $query;
    }

    public function map($product): array {
        return [
            $product->uniq_num,
//            $product->manager_FIO,
            $product->prefix_act,
            $product->period,
            $product->contract_created_at,
            $product->contract_canceled_at,
//            $product->tax_updated_at,
//            $product->tax_id,
//            $product->fiscal_sign,
//            $product->cancelled_tax_updated_at,
//            $product->cancelled_tax_id,
            $product->company_id,
            $product->company_name,
            $product->general_company_name_ru,
            $product->company_brand,
            $product->inn,
            $product->product_name,
            $product->product_amount,
            $product->price_no_nds,
            $product->price_no_nds_sum, //
            $product->nds,
            $product->price_nds,
            $product->price,
            $product->id,
            $product->FIO,
            $product->user_id,
            $product->sum_price_minus_prod_price_nds_divided_to_amount,
            $product->sum_price_minus_prod_price_nds, //
            $product->prod_price_nds,
            $product->sum_price,
            $product->deposit,
            $product->statusCaption,
        ];
    }

    public function headings(): array
    {
        return [
            'Договор',
//            'Менеджер',
            'Спецификация',
            'Срок кредита',
            'Создан',
            'Дата Отмены',
//            'Дата отправки чека',
//            'Номер Чека',
//            'Фискальный номер',
//            'Дата отправки чека на отмену',
//            'Номер чека отмены',
            'ID Компании',
            'Компания',
            "Торговая компания (на рус.)",
            'Бренд',
            'ИНН Поставщика',
            'Наименование Товара',
            'Количество',
            'Цена',
            'Стоимость поставки',
            'НДС',
            'Сумма НДС',
            'Стоимость с НДС',
            'ID кредита',
            'Покупатель',
            'ID Покупателя',
            'Цена',
            'Стоимость поставки',
            'Сумма НДС',
            'Стоимость с НДС',
            'Депозит',
            'Статус',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'K' => NumberFormat::FORMAT_TEXT,
            'H' => NumberFormat::FORMAT_NUMBER,
            'M' => NumberFormat::FORMAT_NUMBER_00,
            'N' => NumberFormat::FORMAT_NUMBER_00,
            'P' => NumberFormat::FORMAT_NUMBER_00,
            'U' => NumberFormat::FORMAT_NUMBER_00,
            'V' => NumberFormat::FORMAT_NUMBER_00,
            'W' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {

                $rows = $event->sheet->getDelegate()->getHighestRow();

                $event->sheet->getStyle('A2:AA' . $rows)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_DOTTED,
                        ],
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ]
                    ]
                ]);


                $event->sheet->getStyle('A1:AA1')->applyFromArray([
                    'font' => [
                        'bold' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ]
                ])->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('e6e6eb');     // какой-то цвет устанавливает
                $event->sheet->setAutoFilter('A1:AA1');     // Добавляет фильтры
            }
        ];
    }

}
