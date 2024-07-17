<?php

namespace App\Exports;

use App\Helpers\NdsStopgagHelper;
use App\Models\Contract;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

// договора с отмененными
class ContractsCancelExport {
    use Exportable;

    public static function report()
    {
        $query = Contract::with(['buyer.user','buyer.personals','company', 'partner', 'order','order.products','schedule','debts','cancel'])
            ->has('company')
            ->has('buyer.user')
            ->has('buyer.personals')
            ->has('partner')
            ->has('order')
            ->orderBy('created_at', 'desc');


        report_filter($query);

        // вытащить все отмененные
        $contracts = $query->get();

        $cancel = [];

        foreach ($contracts as $_contract){
            if($_contract->cancel) {
                $cancel[$_contract->id] = [
                    'total' => $_contract->cancel->total,
                    'balance' => $_contract->cancel->balance,
                    'deposit' => $_contract->cancel->deposit,
                    'canceled_at' => $_contract->cancel->canceled_at,
                ];
            }
        }

        foreach ($contracts as $i => $contract) {
            $nds = @$contract->order->partner->settings->nds > 0 ? NdsStopgagHelper::getActualNds($contract->created_at) : 0;

            $price = 0;
            $price_prod = 0;
            $priceNoNdsSum = 0 ;
            $prod_priceNds = 0 ;
            $sum_price = 0;

            if($contract->order->products) {
                foreach ($contract->order->products as $product) {
                    $price += $product->price_discount * $product->amount;
                    //$priceNoNds = $priceNoNdsSum / $contract->amount;
                    $price_prod += $product->price * $product->amount;
                }

                // покупка
                $priceNds = $nds > 0 ? $price / ($nds + 1) * $nds : 0;
                $priceNoNdsSum = $price - $priceNds;

                // продажа test
                $prod_nds = NdsStopgagHelper::getActualNds($contract->created_at);
                $prod_priceNds = ($price_prod) / ($prod_nds + 1) * $prod_nds; // 0.1725
                $sum_price = $price_prod; //$product->price * $product->amount;

            }

            $_schedule = [
                0 => $contract->delaySum,
                1 => 0
            ];

            // --------------------------

            $cury = date('Y');
            $curm = date('n');
            $y = 2021;
            $m = 7;
            $_dates = [];

            while ($y<=$cury && $m<=$curm){
                $_dates[$y.'.'.$m] = 0;
                $m++;
                if ($m == 12) {
                    $m=0;
                    $y++;
                    if($y>$cury) break;
                }
                if($m>$curm && $y==$cury) break;
            }

            $_dates['sum'] = 0;

            if($contract->schedule) {
                foreach ($contract->schedule as $item) {

                    $date = date('Y.n', strtotime($item->payment_date) );

                    if($item->status==1){
                        $_schedule[1] += $item->total - $item->balance ;
                        continue;
                    }

                    if ( strtotime($item->payment_date) > time()) continue;

                    if ( !isset($_dates[$date]) ) $_dates[$date] = 0;

                    $_dates[$date] = $item->balance > 0 ? $item->balance :'' ;
                    $_dates['sum'] += $item->balance;

                }
            }



            // --------------------------


            if(isset($cancel[$contract->id])){
                $is_cancel = true;
            }else{
                $is_cancel = false;
            }

            $_data = [
                $contract->id,
                $contract->period,
                $contract->created_at,
                $contract->company->name,
                $contract->company->brand,
                $contract->company->inn,
                //$contract->order->productsName,

                $priceNoNdsSum,
                $nds>0 ? NdsStopgagHelper::getActualNdsValue($contract->created_at) . '%' : '0%',
                $priceNds,

                $contract->order->partner_total,
                $contract->id,
                $contract->buyer->user->fio,
                $contract->buyer->user->id,
                $contract->buyer->personals->innNoCrypt,
                //$contract->order->productsName,

                $sum_price-$prod_priceNds,
                NdsStopgagHelper::getActualNdsValue($contract->created_at) . '%', // ставка ндс 15%
                $prod_priceNds,

                $contract->order->total,
                $contract->deposit,

                $is_cancel ? 'В рассрочке cancel' : $contract->status_caption  ,
                $is_cancel ? null : $contract->canceled_at,

                // $_schedule[0],

                $_schedule[1]

            ];

            $data[] = array_merge($_data,$_dates);


            if($is_cancel){
                $_data = [
                    $contract->id,
                    $contract->period,
                    $contract->created_at,
                    $contract->company->name,
                    $contract->company->brand,
                    $contract->company->inn,
                    //$contract->order->productsName,

                    $priceNoNdsSum,
                    $nds>0 ? NdsStopgagHelper::getActualNdsValue($contract->created_at) . '%' : '0%',
                    $priceNds,

                    $contract->order->partner_total,
                    $contract->id,
                    $contract->buyer->user->fio,
                    $contract->buyer->user->id,
                    $contract->buyer->personals->innNoCrypt,
                    //$contract->order->productsName,

                    $sum_price-$prod_priceNds,
                    NdsStopgagHelper::getActualNdsValue($contract->created_at) . '%', // ставка ндс 15%
                    $prod_priceNds,

                    $cancel[$contract->id]['total'], // $contract->order->total,
                    $cancel[$contract->id]['deposit'], //$contract->deposit,

                    'Отменен',
                    $cancel[$contract->id]['canceled_at'],

                    // $_schedule[0],

                    $_schedule[1]

                ];

                $_dates['sum'] =  $cancel[$contract->id]['balance'];

                $data[] = array_merge($_data,$_dates);

            }

        }

        $sort = array_column($data, 2);
        array_multisort($sort, SORT_DESC, $data);

        $sdata = '';
        foreach ($data as $dat) {
            $sdata .= implode(';', $dat) ."\n";
        }

        $data = self::headings() . "\n" . $sdata;

        return $data;

    }



    public static function headings()
    {
        $header = [
            'Оферта',
            'Срок кредита',
            'Создан',
            'Компания',
            'Бренд',
            'ИНН Поставщика',
            //'Наименование Товара',

            'Стоимость поставки',
            'НДС',
            'Сумма НДС',

            'Цена Покупная',
            'ID кредита',
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
            //'Просрочка',
            'Погашено'
        ];

        $cury = date('Y');
        $curm = date('n');
        $y = 2021;
        $m = 7;
        $_dates = [];

        $months = ['','январь','февраль','март','апрель','май','июнь','июль','август','сентябрь','октябрь','ноябрь','декабрь'];

        while ($y<=$cury && $m<=$curm){
            $_dates[$y.'.'.$m] = 'Долг за ' . $months[$m] . ' ' . $y;
            $m++;
            if ($m == 12) {
                $m=0;
                $y++;
                if($y>$cury) break;
            }
            if($m>$curm && $y==$cury) break;
        }

        $_dates['sum'] = 'Всего долг';

        $header = implode(';',array_merge($header,$_dates));

        return $header;

    }

    /*
    public static function map($contract): array{

        $nds = @$contract->order->partner->settings->nds >0 ? 0.15 : 0;

        $price = 0;
        $price_prod = 0;
        $priceNoNdsSum = 0 ;
        $prod_priceNds = 0 ;
        $sum_price = 0;

        if($contract->order->products) {
            foreach ($contract->order->products as $product) {
                $price += $product->price_discount * $product->amount;
                //$priceNoNds = $priceNoNdsSum / $contract->amount;
                $price_prod += $product->price * $product->amount;
            }

            // покупка
            $priceNds = $nds > 0 ? $price / ($nds + 1) * $nds : 0;
            $priceNoNdsSum = $price - $priceNds;

            // продажа test
            $prod_nds = 0.15;
            $prod_priceNds = ($price_prod) / ($prod_nds + 1) * $prod_nds; // 0.1725
            $sum_price = $price_prod; //$product->price * $product->amount;

        }

        $_schedule = [
            0 => $contract->delaySum,
            1 => 0
        ];

        / * if($contract->schedule){
            foreach ($contract->schedule as $item){
                if($item->status==1){
                    $_schedule[1] += $item->total - $item->balance;
                }

            }
        } * /

        // --------------------------

        $cury = date('Y');
        $curm = date('n');
        $y = 2021;
        $m = 7;
        $_header = [];

        while ($y<=$cury && $m<=$curm){
            $_header[$y.'.'.$m] = 0;
            $m++;
            if ($m == 12) {
                $m=0;
                $y++;
                if($y>$cury) break;
            }
            if($m>$curm && $y==$cury) break;
        }

        $_header['sum'] = 0;

        if($contract->schedule) {
            foreach ($contract->schedule as $item) {
                $date = date('Y.n', strtotime($item->payment_date));

                if($item->status==1){
                    $_schedule[1] += $item->total - $item->balance;
                }

                if ( !isset($_header[$date]) ) $_header[$date] = 0;
                if ( $item->status == 1 || strtotime($item->payment_date) > time()) continue;

                $_header[$date] = $item->balance;
                $_header['sum'] += $item->balance;


            }
        }

        // --------------------------


        $data = [
            $contract->id,
            $contract->period,
            $contract->created_at,
            $contract->company->name,
            $contract->company->brand,
            $contract->company->inn,
            //$contract->order->productsName,

            $priceNoNdsSum,
            $nds>0 ? '15%' : '0%',
            $priceNds,

            $contract->order->partner_total,
            $contract->id,
            $contract->buyer->user->fio,
            $contract->buyer->user->id,
            $contract->buyer->personals->innNoCrypt,
            //$contract->order->productsName,

            $sum_price-$prod_priceNds,
            '15%', // ставка ндс 15%
            $prod_priceNds,

            $contract->order->total,
            $contract->deposit,
            $contract->status_caption,
            $contract->canceled_at,
            // $_schedule[0],
            $_schedule[1]
        ];

        $data = array_merge($data,$_header);

        return $data;


    }
    */

    /*public function registerEvents(): array
    {
        return [

            AfterSheet::class => function(AfterSheet $event) {
                $event->sheet->getStyle('A1:AB1')->applyFromArray([
                    'font' => [
                        'bold' => true
                    ],
                ]);


                $rows = $event->sheet->getDelegate()->getHighestRow();

                $event->sheet->getStyle('AC1:AB'.$rows)->applyFromArray([
                    'borders' => [
                        'right' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                        ],
                    ]
                ]);


                $event->sheet->getStyle('A1:AB'.$rows)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOTTED,
                        ],
                    ]
                ]);

                $event->sheet->getStyle('AC1:AB'.$rows)->applyFromArray([
                    'borders' => [
                        'right' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                        ],
                    ]
                ]);

                $event->sheet->getStyle('A1:AB1')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ]
                ])->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('e6e6eb');;
                $event->sheet->setAutoFilter('A1:AB1');

            }
        ];
    }*/

}
