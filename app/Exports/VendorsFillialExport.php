<?php

namespace App\Exports;


use App\Helpers\MathHelper;
use App\Helpers\NdsStopgagHelper;
use App\Models\Company;
use App\Models\Order;
use App\Models\OrderProduct;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;


class VendorsFillialExport implements
    FromQuery,
    ShouldAutoSize,
    WithMapping,
    WithHeadings,
    WithEvents
{
    use Exportable;

    public static function report(){

       // $vendors = Company::whereNull('parent_id')->pluck('id')->toArray();

        $query = Order::with(['company','company.parent','contractsDelay','company.settings','partner'])
            ->from('orders as orders')
            ->has('company')
            ->has('buyer')
            ->has('buyer.user')
            ->has('contract')
            ->leftJoin('companies as c', function($query) /*use ($vendors)*/{
                $query->on('c.id', 'orders.company_id');
            })
            ->leftJoin('contracts as cc', function($query) /*use ($vendors)*/{
                $query->on('cc.order_id', 'orders.id');
            })
            ->select(DB::raw('SUM(orders.`total`) as test_sum, SUM(orders.`partner_total`) as partner_sum, COUNT(orders.id) as cnt,orders.company_id,orders.partner_id,cc.status'))
            ->whereIn('cc.status', [1,3,4,9] )
            ->groupBy('orders.company_id','orders.partner_id','cc.status')
            ->orderBy('orders.company_id');


            // select SUM(orders.`total`) as test_sum, SUM(orders.`partner_total`) as partner_sum, COUNT(orders.id) as cnt,orders.company_id,cc.status
            // from `orders` as `orders`
            // left join `companies` as `c` on `c`.`id` = `orders`.`company_id`
            // left join `contracts` as `cc` on `cc`.`order_id` = `orders`.`id`
            // where cc.`status` in (1, 3,4,9) and `orders`.`created_at` between '2021-01-01 23:00:00' and '2021-09-10 23:00:00'
            // group by `orders`.`company_id`, `cc`.`status` order by `orders`.`company_id` asc


        report_filter($query,'orders.created_at');

        //$query->take(3);

        //echo $query->toSql();

        $orders = $query->get();

        //dd($orders);

        $data = [];

        $k=0;
        foreach ($orders as $order){

            if($order->status == 1 || $order->status == 9){
                $status = 1;
            }elseif($order->status == 3 || $order->status == 4){
                $status = 4;
            }else{
                exit('error status ' . $order->status);
            }


            // if( in_array($order->company_id,$vendors) ) $data[$status]['company_id'] = $order->company_id;
            $nds = isset($order->company->settings->nds) && $order->company->settings->nds==1 ? 1 : 0;

            $k= $order->company_id . '-' . $order->partner_id;

            if(!isset($data[$k][$status]['test_sum'])) $data[$k][$status]['test_sum'] = 0;
            if(!isset($data[$k][$status]['partner_sum'])) $data[$k][$status]['partner_sum'] = 0;
            if(!isset($data[$k][$status]['cnt']))         $data[$k][$status]['cnt'] = 0;
            if(!isset($data[$k][$status]['test_nds'])) $data[$k][$status]['test_nds'] = 0;
            if(!isset($data[$k][$status]['partner_nds'])) $data[$k][$status]['partner_nds'] = 0;

            $data[$k][$status]['test_sum'] += $order->test_sum;
            $data[$k][$status]['partner_sum'] += $order->partner_sum;
            $data[$k][$status]['name'] = $order->company->name;

            // dev_nurlan 07.04.2022
            $data[$k][$status]["generalCompany"] = isset($order->company->parent) ? "(родительская) " . $order->company->parent->generalCompany->name_ru : $order->company->generalCompany->name_ru;

            $data[$k][$status]['brand'] = $order->company->brand;
           // $data[$k][$status]['id'] = $order->company->id;

            if($nds==1) {
                $data[$k][$status]['test_nds'] += $order->test_sum / NdsStopgagHelper::getActualNdsPlusOne() * NdsStopgagHelper::getActualNds();
                $data[$k][$status]['partner_nds'] += $order->partner_sum / NdsStopgagHelper::getActualNdsPlusOne() * NdsStopgagHelper::getActualNds();
                //echo $order->test_sum . ' / ' . (0.15+1) * 0.15 . ' = ' .  $order->test_sum / (0.15+1) * 0.15;
                //exit;
            }

            $data[$k][$status]['company_id'] = $order->company_id;
            // $data[$k][$status]['partner_id'] = $order->partner_id;

            $data[$k][$status]['cnt'] += $order->cnt;
            //$data[$k][$status]['id'] = isset($order->company->parent) ? $order->company->parent->id : $order->company->id;
           // $k++;
        }

        // dd($data);

        /**
        Необходимо сделать отчет по продажам в разрезе вендоров (Филиалы необходимо показать отдельно) показывающий следующую информацию:
        1. ID вендора;
        2. Наименование вендора;
        3. Торговая компания (на рус.);    // dev_nurlan 07.04.2022
        4. Бренд
        5. Кол-во оформленных договоров (единой цифрой без разбивки на наименование товаров)
        6. Сумма покупки с НДС;
        7. Сумма продажи с НДС;
        8. Сумма оформленных договоров; (так же единой цифрой без разбивки на наименование товаров).
        9. Количество просроченных договоров;
        10. Сумма просроченных договоров
         */

        $header = 'Наименование вендора;Наименование филиала;Торговая компания (на рус.);ID Вендора;Кол-во договоров;Сумма покупки;Сумма продажи;НДС покупки;НДС продажи;Кол-во договоров в просрочке;Сумма покупки просрочено;Сумма продажи просрочено' /*НДС покупки просрочено;НДС продажи просрочено'*/ ."\n";
        $result = $body = '';
        foreach ($data as $key=>$item){
            $body1 = '';
            $body2 = '';
            foreach ($item as $status=>$order){

                $partner_sum = str_replace('.',',',$order['partner_sum']);
                $test_sum = str_replace('.',',',$order['test_sum']);
                $partner_nds = str_replace('.',',',$order['partner_nds']);
                $test_nds = str_replace('.',',',$order['test_nds']);

                $body = $order['name'] . ';' . ' '.$order['brand'] . ';' . $order['generalCompany'] . ';' . $order['company_id'] ;

                switch ($status){
                    case 1: // процесс и завершено
                    //case 9:

                        $body1 .= $order['cnt'] . ';' . $partner_sum . ';' . $test_sum . ';' . $partner_nds . ';' . $test_nds ;
                        break;

                    //case 3: // просрочники
                    case 4:

                        $body2 .= $order['cnt'] . ';' . $partner_sum . ';' . $test_sum ; //. ';' . $partner_nds . ';' . $test_nds;
                        break;
                }

            }

            $result .= $body . ';' . $body1 . ';' . $body2 ."\n";

        }

        $result = str_replace(';;',';',$result);

        return $header . $result;

    }

    public function query($data = [])
    {

        $query = Order::with(['company'])
            ->has('company')
            ->has('buyer')
            //->has('buyer.user')
            ->has('contract')
            /* ->leftJoin('orders', function($query){
                $query->on('orders.id', 'order_products.order_id');
            }) */
            ->select(DB::raw('SUM(`total`) as test_sum, SUM(`partner_total`) as partner_sum, COUNT(`id`) as cnt, company_id'))
            ->whereNotIn('status', [0,5] )
            ->groupBy('company_id')
            ->orderBy('company_id');


        report_filter($query);

        return $query;

    }

    public function map($order): array{

        return [
            $order->company_id,
            $order->company->name,
            $order->company->brand,
            $order->cnt,
            $order->partner_sum,
            $order->test_sum,
        ];
        /*
              $product= null;
              $contract_statuses = [
                  0=>'Не подтвержден',
                  1=>'Подтвержден',
                  3=>'Подтвержден',
                  4=>'Подтвержден',
                  5=>'Отменен',
              9=>'Подтвержден'
              ];

              /*
              // покупка
              // $nds = (int)$product->company->nds;
              $nds = @$product->order->partner->settings->nds >0 ? 0.15 : 0;
              $price = $product->price_discount * $product->amount;
              $priceNds = $nds>0 ? $price / ($nds+1)*$nds : 0;

              $priceNoNdsSum = $price - $priceNds;
              $priceNoNds = $priceNoNdsSum / $product->amount;

              // продажа test
              $prod_nds = 0.15;
              $prod_priceNds = ($product->price * $product->amount) / ($prod_nds+1)*$prod_nds ; // 0.1725
              $sum_price = $product->price * $product->amount;

              return [
                      $product->order->company->uniq_num,
                      $product->order->contract->prefix_act,
                      // $product->order->contract->confirmed,
                       $product->order->contract->period,
                       $product->order->contract->canceledDate,
                       $product->order->contract->created_at,
                       $product->order->company->name,
                       $product->order->company->brand,
                       $product->order->company->inn,
                       $product->name,
                       $product->amount, // J колво
                       $priceNoNds, //  $product->withoutNdsDiscount, // K = l5/j5 цена единицы
                       $priceNoNdsSum, // $product->withoutNdsDiscount*$product->amount, // L стоимость поставки O5-N5
                       $nds>0 ? '15%' : '0%', // M ндс %
                       $priceNds, // N=O5/(1+M5)*M5 $product->price_discount*$product->amount - $product->withoutNdsDiscount*$product->amount, // N
                       $price, // $product->price_discount*$product->amount, // O
                       $product->order->contract->id, // P id кредита
                       $product->order->buyer->user->fio, // Q
                       $contract_statuses[$product->order->contract->status],
                       / *$product->order->buyer->user->id,
                       $product->order->buyer->personals->innNoCrypt,
                       $product->name,
                       $product->amount, // U колво
                       ($sum_price-$prod_priceNds) / $product->amount, // $product->ndsPrice, // V =W5/U5  цена
                       $sum_price-$prod_priceNds, //  $product->ndsPrice*$product->amount, // W =Z5-Y5 стоимость поставки
                       '15%', // X ставка ндс 15%
                       $prod_priceNds, // $product->price - $product->ndsPrice, // Y =Z5/(1+X5)*X5  сумма ндс
                       $sum_price, // Z стоимость ндс
                       $product->order->contract->deposit, // AA
                       $product->order->contract->status_caption * /
                  ]; */

    }


    public function headings(): array
    {
        return [
            'ID Вендора',
            'Наименование вендора',
            'Бренд',
            'Кол-во договоров',
            'Сумма покупки',
            'Сумма продажи'
        ];

        /*
        return [
            'Договор',
            'Спецификация',
           // 'Оформлена',
            'Срок кредита',
            'Дата Отмены',
            'Создан',
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
            'Статус'

        ]; */

    }


    public function registerEvents(): array
    {
        return [


            AfterSheet::class => function(AfterSheet $event) {
                $event->sheet->getStyle('A1:F1')->applyFromArray([
                    'font' => [
                        'bold' => true
                    ],
                ]);


                $rows = $event->sheet->getDelegate()->getHighestRow();

                $event->sheet->getStyle('AC1:F'.$rows)->applyFromArray([
                    'borders' => [
                        'right' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                        ],
                    ]
                ]);


                $event->sheet->getStyle('A1:F'.$rows)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOTTED,
                        ],
                    ]
                ]);

                $event->sheet->getStyle('AC1:F'.$rows)->applyFromArray([
                    'borders' => [
                        'right' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                        ],
                    ]
                ]);

                $event->sheet->getStyle('A1:F1')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ]
                ])->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('e6e6eb');
                $event->sheet->setAutoFilter('A1:F1');

            }
        ];
    }

}
