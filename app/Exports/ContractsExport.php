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

// договора
class ContractsExport implements
    FromQuery,
    ShouldAutoSize,
    WithMapping,
    WithHeadings,
    WithEvents
{
    use Exportable;

    public static $cancel = [];

    public function query()
    {

        //url https://test.uz/uz/panel/reports/orders/export?from=2021-04-28&to=2021-05-03

//            $data = [request()->get('from'),request()->get('to')];
//            $item  = !empty($data) ? OrderProduct::query()
//                ->whereBetween('created_at',$data) : OrderProduct::query();

        $query = Contract::with(['buyer.user','buyer.personals','company', 'partner', 'order','order.products','schedule','debts'])
            ->has('company')
            ->has('buyer.user')
            ->has('buyer.personals')
            ->has('partner')
            ->has('order')
            ->orderBy('created_at', 'desc');

        report_filter($query);

        return $query;
    }

    public function map($contract): array{
        $nds = @$contract->order->partner->settings->nds >0 ? NdsStopgagHelper::getActualNds($contract->created_at) : 0;

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

        /* if($contract->schedule){
            foreach ($contract->schedule as $item){
                if($item->status==1){
                    $_schedule[1] += $item->total - $item->balance;
                }

            }
        } */

        // --------------------------

        $cury = date('Y');
        $curm = date('n');
        $y = 2021;
        $m = 7;
        $_header = [];
        $_header['sum'] = 0;

        while ($y<=$cury || $m<=$curm){
            $_header[$y.'.'.$m] = 0;
            $m++;
            if ($m == 13) {
                $m=1;
                $y++;
                if($y>$cury) break;
            }
            if($m>$curm && $y==$cury) break;
        }


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
            $contract->company->manager->fio ?? "",    // dev_nurlan 14.04.2022
            $contract->period,
            $contract->created_at,
            $contract->company->name,
            $contract->generalCompany->name_ru,  // Торговая компания (на русском)  // dev_nurlan 06.04.2022
            $contract->company->brand,
            $contract->company->inn,
//            $contract->order->productsName,

            $priceNoNdsSum,
            $nds>0 ? NdsStopgagHelper::getActualNdsValue($contract->created_at) . '%' : '0%',
            $priceNds,

            $contract->order->partner_total,
            $contract->id,
            $contract->buyer->user->fio,
            $contract->buyer->user->gender==1 ? 'М' : 'Ж' ,
            number_format((time()-strtotime($contract->buyer->user->birth_date)) / 365 / 86400 ,1,',',''),
            $contract->buyer->user->id,
            $contract->buyer->personals->innNoCrypt,
            //$contract->order->productsName,

            $sum_price-$prod_priceNds,
            NdsStopgagHelper::getActualNdsValue($contract->created_at). '%',                          // Ставка ндс 15%
            $prod_priceNds,                 // Сумма НДС

            $contract->order->total,        // Цена Окончательная
            $contract->deposit,             // Депозит
            $contract->status_caption,      // Статус
            $contract->canceled_at,         // Дата отмены
//            $_schedule[0],                  // ---
            $_schedule[1]                   // Погашено
        ];

        $data = array_merge($data,$_header);

        return $data;
    }

    public function headings(): array
    {
        $header = [
            'Оферта',
            'Ответственный менеджер',  // dev_nurlan 14.04.2022
            'Срок кредита',
            'Создан',
            'Компания',
            "Торговая компания (на рус.)",  // dev_nurlan 06.04.2022
            'Бренд',
            'ИНН Поставщика',
            //'Наименование Товара',

            'Стоимость поставки',
            'НДС',
            'Сумма НДС',

            'Цена Покупная',
            'ID кредита',
            'Покупатель',
            'Пол',
            'Возраст',
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
        $_header = [];
        $_header['sum'] = 'Всего долг';

        $months = ['','январь','февраль','март','апрель','май','июнь','июль','август','сентябрь','октябрь','ноябрь','декабрь'];

        while ($y<=$cury || $m<=$curm){
            $_header[$y.'.'.$m] = 'Долг за ' . $months[$m] . ' ' . $y;
            $m++;
            if ($m == 13) {
                $m=1;
                $y++;
                if($y>$cury) break;
            }
            if($m>$curm && $y==$cury) break;
        }


        $header = array_merge($header,$_header);

        return $header;

    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {

                $rows = $event->sheet->getDelegate()->getHighestRow();

                $event->sheet->getStyle('A2:AA'.$rows)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOTTED,
                        ],
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ]
                    ]
                ]);

                $event->sheet->getStyle('A1:AA1')->applyFromArray([
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
                $event->sheet->setAutoFilter('A1:AA1');
            }
        ];
    }

}
