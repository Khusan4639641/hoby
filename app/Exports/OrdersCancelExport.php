<?php

namespace App\Exports;

use App\Helpers\EncryptHelper;
use App\Helpers\NdsStopgagHelper;
use App\Models\OrderProduct;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

// Бухгалтерия с отмененными
class OrdersCancelExport {


    // $data [2021-02-21, YYYY-mm-dd]
    public static function report($data = [])
    {
        $query = OrderProduct::with(['order.contract','order.company','order.buyer','order.buyer.personals','order.buyer.user','order.contract.cancel'])
                ->has('order')
                ->has('order.company')
                //->has('order.company.currentUniqNum')
                ->has('order.buyer')
                ->has('order.buyer.user')
                ->has('order.contract')
            ->orderBy('created_at', 'desc');

        report_filter($query);

        // $query->take(10);

        $products = $query->get();

        $data = [];
        $cancel = [];

        foreach ($products as $product){
            if($product->order && $product->order->contract && $product->order->contract->cancel) {
                $_contract = $product->order->contract;
                $cancel[$_contract->id] = 1;
            }
        }

        foreach ($products as $i => $product) {

            if(!$product->order) continue;

            // покупка
            // $nds = (int)$product->company->nds;
            $nds = @$product->order->partner->settings->nds >0 ? NdsStopgagHelper::getActualNds($product->order->created_at) : 0;
            $price = $product->price_discount * $product->amount;
            $priceNds = $nds>0 ? $price / ($nds+1)*$nds : 0;

            $priceNoNdsSum = /*$product->withoutNdsDiscount ??*/ $price - $priceNds;
            $priceNoNds = $priceNoNdsSum / $product->amount;

            // продажа test
            $prod_nds = NdsStopgagHelper::getActualNds($product->order->created_at);
            $prod_priceNds = ($product->price * $product->amount) / ($prod_nds+1) * $prod_nds ; // 0.1725
            $sum_price = $product->price * $product->amount;

            $payment_day = 0;
            foreach ($product->order->contract->schedule as $schedule){
                if(isset($schedule->payment_date)) {
                    $payment_day = date('d', strtotime($schedule->payment_date));
                    break;
                }
            }

            // dev_nurlan 06.04.2022
            $inn_is_set = false;
            $pinfl_is_set = false;
            if (  isset($product->order->buyer->personals)  ) {
                if (  isset($product->order->buyer->personals->inn)  ) {
                    $inn = $product->order->buyer->personals->inn;
                    if (  $inn !== ""  ) {
                        $inn_is_set = true;
                    }
                }
                if (  isset($product->order->buyer->personals->pinfl)  ) {
                    $pinfl = $product->order->buyer->personals->pinfl;
                    if (  $pinfl !== ""  ) {
                        $pinfl_is_set = true;
                    }
                }
            }

            $is_cancel = isset($cancel[$product->order->contract->id]);

            $_data = [
                $product->order->company->currentUniqNum->uniq_num,
                $product->order->company->manager->fio ?? "",    // dev_nurlan 14.04.2022
                $product->order->contract->prefix_act,
                // $product->order->contract->confirmed,
                $product->order->contract->period,
                '', //$product->order->contract->canceledDate,
                date('Y-m-d',strtotime($product->order->contract->created_at)),
                $product->order->company->id,
                $product->order->company->name,
                $product->order->contract->generalCompany->name_ru,  // Торговая компания (на русском)  // dev_nurlan 05.04.2022
                $product->order->company->brand,
                $product->order->company->inn,
                str_replace(';',',',$product->name),
                str_replace('.',',',$product->amount), // J колво
                str_replace('.',',',$priceNoNds), //  $product->withoutNdsDiscount, // K = l5/j5 цена единицы
                str_replace('.',',',$priceNoNdsSum), // $product->withoutNdsDiscount*$product->amount, // L стоимость поставки O5-N5
                $nds>0 ? NdsStopgagHelper::getActualNdsValue($product->order->created_at) . '%' : '0%', // M ндс %
                str_replace('.',',',$priceNds), // N=O5/(1+M5)*M5 $product->price_discount*$product->amount - $product->withoutNdsDiscount*$product->amount, // N
                str_replace('.',',',$price), // $product->price_discount*$product->amount, // O
                $product->order->contract->id, // id кредита
                $product->order->buyer->user->fio,
                $product->order->buyer->user->id,
                $inn_is_set     ? EncryptHelper::decryptData($inn)      : "",
                $pinfl_is_set   ? EncryptHelper::decryptData($pinfl)    : "",
//                str_replace(';',',',$product->name),
                str_replace('.',',',$product->amount), // U колво
                str_replace('.',',',($sum_price-$prod_priceNds) / $product->amount), // $product->ndsPrice, // V =W5/U5  цена
                str_replace('.',',',$sum_price-$prod_priceNds), //  $product->ndsPrice*$product->amount, // W =Z5-Y5 стоимость поставки
                NdsStopgagHelper::getActualNdsValue($product->order->created_at) . '%', // X ставка ндс 15%
                str_replace('.',',',$prod_priceNds), // $product->price - $product->ndsPrice, // Y =Z5/(1+X5)*X5  сумма ндс
                str_replace('.',',',$sum_price), // Z стоимость ндс
                str_replace('.',',',$product->order->contract->deposit), // AA
                $is_cancel ? 'В рассрочке (cancel)' : $product->order->contract->status_caption,
                (int)$payment_day
            ];

            $data[] = $_data;

            if($is_cancel){

                $_data = [
                    $product->order->company->currentUniqNum->uniq_num,
                    $product->order->company->manager->fio ?? "",    // dev_nurlan 14.04.2022
                    $product->order->contract->prefix_act,
                    // $product->order->contract->confirmed,
                    $product->order->contract->period,
                    $product->order->contract->canceledDate,
                    date('Y-m-d',strtotime($product->order->contract->canceled_at)), // created_at,
                    $product->order->company->id,
                    $product->order->company->name,
                    $product->order->contract->generalCompany->name_ru,  // Торговая компания (на русском)  // dev_nurlan 05.04.2022
                    $product->order->company->brand,
                    $product->order->company->inn,
                    str_replace(';',',',$product->name),
                    str_replace('.',',',$product->amount * -1), // J колво
                    str_replace('.',',',$priceNoNds *-1), //  $product->withoutNdsDiscount, // K = l5/j5 цена единицы
                    str_replace('.',',',$priceNoNdsSum*-1), // $product->withoutNdsDiscount*$product->amount, // L стоимость поставки O5-N5
                    $nds>0 ? NdsStopgagHelper::getActualNdsValue($product->order->created_at) . '%' : '0%', // M ндс %
                    str_replace('.',',',$priceNds*-1), // N=O5/(1+M5)*M5 $product->price_discount*$product->amount - $product->withoutNdsDiscount*$product->amount, // N
                    str_replace('.',',',$price*-1), // $product->price_discount*$product->amount, // O
                    $product->order->contract->id, // id кредита
                    $product->order->buyer->user->fio,
                    $product->order->buyer->user->id,
                    $inn_is_set     ? EncryptHelper::decryptData($inn)      : "ИНН отсутствует",
                    $pinfl_is_set   ? EncryptHelper::decryptData($pinfl)    : "ПИНФЛ отсутствует",
//                    $product->name,
                    str_replace('.',',',$product->amount*-1), // U колво
                    str_replace('.',',',(($sum_price-$prod_priceNds) / $product->amount) * -1), // $product->ndsPrice, // V =W5/U5  цена
                    str_replace('.',',',($sum_price-$prod_priceNds) * -1), //  $product->ndsPrice*$product->amount, // W =Z5-Y5 стоимость поставки
                    '15%', // X ставка ндс 15%
                    str_replace('.',',',$prod_priceNds * -1), // $product->price - $product->ndsPrice, // Y =Z5/(1+X5)*X5  сумма ндс
                    str_replace('.',',',$sum_price * -1), // Z стоимость ндс
                    str_replace('.',',',$product->order->contract->deposit  * -1), // AA
                    'Отменен', //$product->order->contract->status_caption,
                    (int)$payment_day
                ];

                $data[] = $_data;
            }

        }

        $sort = array_column($data, 4);
        array_multisort($sort, SORT_DESC, $data);

        $sdata = '';
        foreach ($data as $dat) {
            $sdata .= implode(';', $dat) ."\n";
        }

        $data = self::headings() . "\n" . $sdata;

        return $data;

    }


    public static function headings(){
        $header = [
            'Договор',
            'Ответственный менеджер',  // dev_nurlan 14.04.2022
            'Спецификация',
            // 'Оформлена',
            'Срок кредита',
            'Дата Отмены',
            'Создан',
            'ID Компании',
            'Компания',
            "Торговая компания (на рус.)",  // dev_nurlan 06.04.2022
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
//            'Наименование Товара',
            'Количество',
            'Цена',
            'Стоимость поставки',
            'Ставка НДС %',
            'Сумма НДС',
            'Стоимость с НДС',
            'Депозит',
            'Статус',
            'Число оплаты',
        ];

        $header = implode(';',$header);

        return $header;
    }
}
