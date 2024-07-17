<?php

namespace App\Exports;


use App\Helpers\EncryptHelper;
use App\Helpers\NdsStopgagHelper;
use App\Models\OrderProduct;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

// бухгалтерия
class Contracts2Export implements
    FromQuery,
    ShouldAutoSize,
    WithMapping,
    WithHeadings,
    WithEvents
{

    use Exportable;

//    $data [2021-02-21, YYYY-mm-dd]
    public function query($data = [])
    {

        $query = OrderProduct::with(['order.contract','order.contract.schedule','order.company','order.buyer','order.buyer.personals','order.buyer.user'])
                ->has('order.company')
                ->has('order.buyer')
                ->has('order.buyer.user')
                ->has('order.contract')
            ->orderBy('created_at', 'desc');

        report_filter($query);

        //$query->take(10);

        return $query;

    }


    public function map($product): array{

        // покупка
        // $nds = (int)$product->company->nds;
        $nds = @$product->order->partner->settings->nds >0 ? NdsStopgagHelper::getActualNds($product->order->created_at) : 0;
        $price = $product->price_discount * $product->amount;
        $priceNds = $nds>0 ? $price / ($nds+1)*$nds : 0;

        $priceNoNdsSum = /*$product->withoutNdsDiscount ??*/ $price - $priceNds;
        // $priceNoNds = $priceNoNdsSum / $product->amount;

        // продажа test
        $prod_nds = NdsStopgagHelper::getActualNds($product->order->created_at);
        $prod_priceNds = ($product->price * $product->amount) / ($prod_nds+1) * $prod_nds ; // 0.1725
        $sum_price = $product->price * $product->amount;

        /* $payment_day = 0;
        foreach ($product->order->contract->schedule as $schedule){
            if(isset($schedule->payment_date)) {
                $payment_day = date('d', strtotime($schedule->payment_date));
                break;
            }
        }; */

        $_schedule = [
            0 => 0,
            1 => 0
        ];
        if($product->order->contract->schedule){
            foreach ($product->order->contract->schedule as $item){
                $_schedule[$item->status] += $item->balance;
            }
        }

        //dd($product->order->contract->schedule);

        //Log::channel('report')->info($product->id . ';' . $product->order_id . ';' . $price . ';' . $product->price . ';' . $product->amount . ';' . $nds . ';' . $prod_priceNds . ';' . $sum_price . ';' . $product->order->contract->status);

        return [

            $product->order->contract->id,
            $product->order->contract->period,
            $product->order->contract->created_at,
            $product->order->contract->company->name,
            $product->order->contract->company->brand,
            $product->order->contract->company->inn,
            $product->order->productsName,


               /*  $product->order->company->uniq_num,
                $product->order->contract->prefix_act,
                // $product->order->contract->confirmed,
                 $product->order->contract->period,
                 $product->order->contract->canceledDate,
                 $product->order->contract->created_at,
                 $product->order->company->id,
                 $product->order->company->name,
                 $product->order->company->brand,
                 $product->order->company->inn,
                 $product->name,
                 $product->amount, // J колво */

                 //$priceNoNds, //  $product->withoutNdsDiscount, // K = l5/j5 цена единицы
                 $priceNoNdsSum, // $product->withoutNdsDiscount*$product->amount, // L стоимость поставки O5-N5
                 $nds>0 ? NdsStopgagHelper::getActualNdsValue($product->order->created_at) . '%' : '0%', // M ндс %
                 $priceNds, // N=O5/(1+M5)*M5 $product->price_discount*$product->amount - $product->withoutNdsDiscount*$product->amount, // N
                 //$price, // $product->price_discount*$product->amount, // O
                 //$product->order->contract->id, // id кредита

            $product->order->partner_total,
            $product->order->contract->buyer->user->fio,
            $product->order->buyer->user->id,
            $product->order->buyer->personals->innNoCrypt,
            //$product->order->productsName,

                 /*$product->order->buyer->user->fio,
                 $product->order->buyer->user->id,
                 $product->order->buyer->personals->innNoCrypt,
                 EncryptHelper::decryptData($product->order->buyer->personals->pinfl),
                 $product->name,
                 $product->amount, // U колво */

                 //($sum_price-$prod_priceNds) / $product->amount, // $product->ndsPrice, // V =W5/U5  цена
                 $sum_price-$prod_priceNds, //  $product->ndsPrice*$product->amount, // W =Z5-Y5 стоимость поставки
                 NdsStopgagHelper::getActualNdsValue($product->order->created_at) . '%', // X ставка ндс 15%
                 $prod_priceNds, // $product->price - $product->ndsPrice, // Y =Z5/(1+X5)*X5  сумма ндс
                // $sum_price, // Z стоимость ндс

                 /* $product->order->contract->deposit, // AA
                 $product->order->contract->status_caption,
                (int)$payment_day */

            $product->order->total,
            $product->order->contract->deposit,
            $product->order->contract->status_caption,
            $product->order->contract->canceled_at,
            $_schedule[0],
            $_schedule[1]

            ];

    }


    public function headings(): array
    {
        return [

            'Оферта',
            'Срок кредита',
            'Создан',
            'Компания',
            'Бренд',
            'ИНН Поставщика',
            'Наименование Товара',

            'Стоимость поставки',
            'НДС',
            'Сумма НДС',

            'Цена Покупная',
            //'ID кредита',
            'Покупатель',
            'ID Покупателя',
            'ИНН Покупателя',
            //'Наименование Товара',

            'Стоимость поставки',
            'Ставка НДС %',
            'Сумма НДС',

            'Цена Окончательная',
            'Депозит',
            'Статус',
            'Дата отмены',
            'Просрочка',
            'Погашено'

           /* 'Договор',
            'Спецификация',
           // 'Оформлена',
            'Срок кредита',
            'Дата Отмены',
            'Создан',
            'ID Компании',
            'Компания',
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
            'ИНН Покупателя',
            'ПИНФЛ Покупателя',
            'Наименование Товара',
            'Количество',
            'Цена',
            'Стоимость поставки',
            'Ставка НДС %',
            'Сумма НДС',
            'Стоимость с НДС',
            'Депозит',
            'Статус',
            'Число оплаты' */
        ];

    }


    public function registerEvents(): array
    {
        return [

            AfterSheet::class => function(AfterSheet $event) {
                $event->sheet->getStyle('A1:AE1')->applyFromArray([
                    'font' => [
                        'bold' => true
                    ],
                ]);

                $rows = $event->sheet->getDelegate()->getHighestRow();

                $event->sheet->getStyle('AC1:AE'.$rows)->applyFromArray([
                    'borders' => [
                        'right' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                        ],
                    ]
                ]);


                $event->sheet->getStyle('A1:AE'.$rows)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOTTED,
                        ],
                    ]
                ]);

                $event->sheet->getStyle('AC1:AE'.$rows)->applyFromArray([
                    'borders' => [
                        'right' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                        ],
                    ]
                ]);

                $event->sheet->getStyle('A1:AE1')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ]
                ])->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('e6e6eb');
                $event->sheet->setAutoFilter('A1:AE1');

            }
        ];
    }

}
