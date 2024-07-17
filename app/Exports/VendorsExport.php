<?php

namespace App\Exports;


use App\Helpers\NdsStopgagHelper;
use App\Models\OrderProduct;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\AfterSheet;


class VendorsExport implements
    FromQuery,
    ShouldAutoSize,
    WithMapping,
    WithHeadings,
    WithEvents,
    WithStrictNullComparison
{
    use Exportable;

    public function query($data = [])
    {

        $query = OrderProduct::with(['order.contract','order.company','order.buyer','order.buyer.user','order'])
                ->has('order.company')
                ->has('order.buyer')
                ->has('order.buyer.user')
                ->has('order.contract')
                ->leftJoin('orders',function($query){
                    $query->on('orders.id', 'order_products.order_id');
                })
                ->where('orders.partner_id', Auth::user()->id)
                ->select('order_products.*', 'orders.partner_id')
                //->where('orders.company_id',Auth::user()->id)
               ->orderBy('order_products.created_at', 'desc');



        report_filter($query);

      //  $query->take(10);

        return $query;

    }

    public function map($product): array{

        $contract_statuses = [
            0=>'На модерации',
            2=>'На подтверждении',
            1=>'В рассрочке',
            3=>'В рассрочке',
            4=>'В рассрочке',
            5=>'Отменен',
            9=>'Закрыт'
        ];

        // покупка
        // $nds = (int)$product->company->nds;
        $nds = @$product->order->partner->settings->nds >0 ? NdsStopgagHelper::getActualNds($product->order->created_at) : 0;
        $price = $product->price_discount * $product->amount;
        $priceNds = $nds>0 ? $price / ($nds+1)*$nds : 0;

        $priceNoNdsSum = /*$product->withoutNdsDiscount ?? */ $price - $priceNds;

        // 01.01.2023 hotfix
        if ( isset($product->amount) && ($product->amount > 0) ) {
            $priceNoNds = $priceNoNdsSum / $product->amount;
        } else {
            $priceNoNds = 0;
        }

        // продажа test
        $prod_nds = NdsStopgagHelper::getActualNds($product->order->created_at);
        $prod_priceNds = ($product->price * $product->amount) / ($prod_nds+1)*$prod_nds ; // 0.1725
        $sum_price = $product->price * $product->amount;


        $product_name = str_replace(';',',',$product->name);

        return [
            $product->order->company->uniq_num,
            $product->order->contract->prefix_act,
            // $product->order->contract->confirmed,
            $product->order->contract->period,
            $product->order->contract->canceledDate,
            $product->order->contract->created_at,
            $product->order->company->name,
            $product->order->contract->generalCompany->name_ru,  // Торговая компания (на русском)  // dev_nurlan 07.04.2022
            $product->order->company->brand,
            $product->order->company->inn,
            $product_name,
            $product->amount, // J колво
            $priceNoNds, //  $product->withoutNdsDiscount, // K = l5/j5 цена единицы
            $priceNoNdsSum, // $product->withoutNdsDiscount*$product->amount, // L стоимость поставки O5-N5
            $nds>0 ? NdsStopgagHelper::getActualNdsValue($product->order->created_at) . '%' : '0%', // M ндс %
            $priceNds, // N=O5/(1+M5)*M5 $product->price_discount*$product->amount - $product->withoutNdsDiscount*$product->amount, // N
            $price, // $product->price_discount*$product->amount, // O
            $product->order->contract->id, // P id кредита
            $product->order->buyer->user->fio, // Q
            $contract_statuses[$product->order->contract->status],
            /*$product->order->buyer->user->id,
            $product->order->buyer->personals->innNoCrypt,
            $product->name,
            $product->amount, // U колво
            ($sum_price-$prod_priceNds) / $product->amount, // $product->ndsPrice, // V =W5/U5  цена
            $sum_price-$prod_priceNds, //  $product->ndsPrice*$product->amount, // W =Z5-Y5 стоимость поставки
            '15%', // X ставка ндс 15%
            $prod_priceNds, // $product->price - $product->ndsPrice, // Y =Z5/(1+X5)*X5  сумма ндс
            $sum_price, // Z стоимость ндс
            $product->order->contract->deposit, // AA
            $product->order->contract->status_caption */
        ];

    }

    public function headings(): array
    {
        return [
            'Договор',
            'Спецификация',
           // 'Оформлена',
            'Срок кредита',
            'Дата Отмены',
            'Создан',
            'Компания',
            "Торговая компания (на рус.)",  // dev_nurlan 07.04.2022
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
            'Статус'
            /*'ID Покупателя',
            'ИНН Покупателя',
            'Наименование Товара',
            'Количество',
            'Цена',
            'Стоимость поставки',
            'Ставка НДС %',
            'Сумма НДС',
            'Стоимость с НДС',
            'Депозит',
            'Статус', */
        ];

    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {

                $rows = $event->sheet->getDelegate()->getHighestRow();

                $event->sheet->getStyle('A2:S'.$rows)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOTTED,
                        ],
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ]
                    ]
                ]);

                $event->sheet->getStyle('A1:S1')->applyFromArray([
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
                $event->sheet->setAutoFilter('A1:S1');

            }
        ];
    }

}
