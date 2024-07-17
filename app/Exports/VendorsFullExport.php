<?php

namespace App\Exports;


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


class VendorsFullExport implements
    FromQuery,
    ShouldAutoSize,
    WithMapping,
    WithHeadings,
    WithEvents
{
    use Exportable;

    /**
      Общий отчет по продажам

    Необходимо сделать отчет по продажам в разрезе вендоров (Необходимо учитывать и филиалы вендоров в одном бренде) показывающий следующую информацию:
    1. ID Вендора;
    2. Наименование вендора;
    3. Бренд
    4. Кол-во оформленных договоров (единой цифрой без разбивки на наименование товаров)
    5. Сумма покупки с НДС;
    6. Сумма продажи с НДС;
    7. Сумма оформленных договоров (так же единой цифрой без разбивки на наименование товаров).

    Цель видеть информацию в сводном виде какой вендор на какую сумму продает товар за выбранный период времени.
     */

    public static function report(){

        $vendors = Company::whereNull('parent_id')->pluck('id')->toArray();

        $query = Order::with(['company','company.parent'])
            ->from('orders as orders')
            ->has('company')
            ->has('buyer')
            ->has('buyer.user')
            ->has('contract')
            ->leftJoin('companies as c', function($query) use ($vendors){
                $query->on('c.id', 'orders.company_id');
            })
            ->leftJoin('contracts as cc', function($query) use ($vendors){
                $query->on('cc.order_id', 'orders.id');
            })
            ->select(DB::raw('SUM(orders.`total`) as test_sum, SUM(orders.`partner_total`) as partner_sum, COUNT(orders.id) as cnt,orders.company_id'))
            ->whereIn('cc.status', [1,3,4,9] )
            ->groupBy('orders.company_id')
            ->orderBy('orders.company_id');


        report_filter($query,'orders.created_at');

        $orders = $query->get();

        $data = [];

        foreach ($orders as $order){
            $name = str_replace('«','"',$order->company->name);
            $name = str_replace('»','"',$name);

            if( in_array($order->company_id,$vendors) ) $data[$name]['company_id'] = $order->company_id;

            $nds = isset($order->company->settings->nds) && $order->company->settings->nds==1 ? 1 : 0;

            if(!isset($data[$name]['test_sum'])) $data[$name]['test_sum'] = 0;
            if(!isset($data[$name]['partner_sum'])) $data[$name]['partner_sum'] = 0;
            if(!isset($data[$name]['test_nds'])) $data[$name]['test_nds'] = 0;
            if(!isset($data[$name]['partner_nds'])) $data[$name]['partner_nds'] = 0;
            if(!isset($data[$name]['cnt'])) $data[$name]['cnt'] = 0;

            $data[$name]['test_sum'] += $order->test_sum;
            $data[$name]['partner_sum'] += $order->partner_sum;
            if($nds==1) {
                $data[$name]['test_nds'] += $order->test_sum / NdsStopgagHelper::getActualNdsPlusOne
                    ($order->getRawOriginal('created_at'))
                    * NdsStopgagHelper::getActualNds($order->getRawOriginal('created_at'));
                $data[$name]['partner_nds'] += $order->partner_sum / NdsStopgagHelper::getActualNdsPlusOne
                    ($order->getRawOriginal('created_at'))
                    * NdsStopgagHelper::getActualNds($order->getRawOriginal('created_at'));
            }
            $data[$name]['cnt'] += $order->cnt;
            $data[$name]['parent'] = isset($order->company->parent) ? $order->company->parent->id : $order->company->id;
            $data[$name]["generalCompany"] = isset($order->company->parent) ? "(родительская) " . $order->company->parent->generalCompany->name_ru : $order->company->generalCompany->name_ru;

        }

//        $header = 'ID Вендора;Наименование вендора;Кол-во договоров;Сумма покупки;Сумма продажи;НДС покупки; НДС продажи;' ."\n";
        // dev_nurlan 07.04.2022
        $header = 'ID Вендора;Торговая компания (на рус.);Наименование вендора;Кол-во договоров;Сумма покупки;Сумма продажи;НДС покупки; НДС продажи;' ."\n";
        $body = '';
        foreach ($data as $company=>$item){
            $partner_sum = str_replace('.',',',$item['partner_sum']);
            $test_sum = str_replace('.',',',$item['test_sum']);

            $partner_nds = str_replace('.',',',$item['partner_nds']);
            $test_nds = str_replace('.',',',$item['test_nds']);

             $body .= $item['parent'] . ';' . $item["generalCompany"] . ';' . $company . ';' . $item['cnt'] . ';' . $partner_sum.';'.$test_sum.';'.$partner_nds.';'.$test_nds ."\n";
        }

        return $header . $body;

    }


    public function query($data = [])
    {

        return 'no data';

        $query = Order::with(['company'])
                ->from('orders as orders')
                ->has('company')
                ->has('buyer')
                ->has('buyer.user')
                ->has('contract')
                ->leftJoin('companies as c', function($query){
                    $query->on('c.id', 'orders.company_id');
                })
                ->select(DB::raw('SUM(`total`) as test_sum, SUM(`partner_total`) as partner_sum, COUNT(orders.id) as cnt,company_id'))
                ->whereNotIn('orders.status', [0,5] )
                ->groupBy('company_id')
                ->orderBy('company_id');

        report_filter($query);

        return $query;

    }

    public function map($order): array{

        return [
            $order->company_id,
            $order->company->name,
            $order->company->generalCompany->name_ru,  // dev_nurlan 07.04.2022 (this function doesn't work)
            @$order->company->brand,
            $order->cnt,
            $order->partner_sum,
            $order->test_sum,
        ];

        $product= null;

        $contract_statuses = [
            0=>'На модерации',
            2=>'На подтверждении',
            1=>'В рассрочке',
            3=>'В рассрочке',
            4=>'В рассрочке',
            5=>'Отменен',
            9=>'Закрыт'
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
            "Торговая компания (на рус.)",  // dev_nurlan 07.04.2022 (this function doesn't work)
            'Бренд',
            'Кол-во договоров',
            'Сумма покупки',
            'Сумма продажи'
        ];

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
