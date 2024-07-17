<?php

namespace App\Exports;


use App\Helpers\NdsStopgagHelper;
use App\Models\Company;
use App\Models\OrderProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\AfterSheet;


// филиалы с отмененными договорами
class FilialsCancelExport implements
    ShouldAutoSize,
    WithHeadings,
    FromCollection,
    WithEvents,
    WithStrictNullComparison
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {

        $user =  Auth::user();

        $companies = Company::select('id')->where('parent_id',$user->company_id)->with('user')->get();

        $partners = [];
        foreach($companies as $company){
            $partners[] = $company->id;
        }
        $partners[] = $user->company_id;

        $query = OrderProduct::with(['order.contract','order.company','order.buyer','order.buyer.user','order','order.contract.cancel'])
            ->has('order.company')
            ->has('order.buyer')
            ->has('order.buyer.user')
            ->has('order.contract')
            ->leftJoin('orders',function($query){
                $query->on('orders.id', 'order_products.order_id');
            })
            /*
            ->leftJoin('contracts',function($query){
                $query->on('contracts.order_id', 'orders.id')->where('contracts.status',5);
            })
            ->where('contracts.status',5)*/
            ->whereIn('orders.company_id',  $partners)
            ->select('order_products.*', 'orders.partner_id',/*'contracts.canceled_at','contracts.status'*/)
           //->orderBy('order_products.created_at', 'desc');
        ;

        report_filter($query );


        Log::channel('report')->info($query->toSql());
        Log::channel('report')->info($query->getBindings());

        $products = $query->get();

        $contract_statuses = [
            0=>'На модерации',
            2=>'На подтверждении',
            1=>'В рассрочке',
            3=>'В рассрочке',
            4=>'В рассрочке',
            5=>'В рассрочке (cancel)',
            9=>'Закрыт'
        ];

        $data = [];
        $cancel = [];

        foreach ($products as $product){
            if($product->order && $product->order->contract && $product->order->contract->cancel) {
                $_contract = @$product->order->contract;
                $cancel[$_contract->id] = 1;
            }
        }

        try {

            foreach ($products as $product) {

                // покупка
                // $nds = (int)$product->company->nds;
                $nds = @$product->order->partner->settings->nds > 0 ? NdsStopgagHelper::getActualNds($product->order->created_at) : 0;
                $price = $product->price_discount * $product->amount;
                $priceNds = $nds > 0 ? $price / ($nds + 1) * $nds : 0;

                $priceNoNdsSum = /*$product->withoutNdsDiscount ??*/
                    $price - $priceNds;
                $priceNoNds = $priceNoNdsSum / $product->amount;

                // продажа test
                //$prod_nds = 0.15;
                //$prod_priceNds = ($product->price * $product->amount) / ($prod_nds+1)*$prod_nds ; // 0.1725
                //$sum_price = $product->price * $product->amount;

                $is_cancel = isset($cancel[$product->order->contract->id]);


                $product_name = str_replace(';',',',$product->name);

                $_data = [
                    $product->order->company->id,
                    $product->order->company->uniq_num,
                    $product->order->contract->prefix_act,
                    $product->order->contract->period,
                    // '', //$product->order->contract->canceledDate,
                    date('Y-m-d', strtotime($product->order->contract->created_at)),
                    $product->order->company->name,
                    $product->order->contract->generalCompany->name_ru,  // Торговая компания (на русском)  // dev_nurlan 07.04.2022
                    $product->order->company->brand,
                    $product->order->company->inn,
                    $product_name,
                    str_replace('.', ',', $product->amount), // J колво
                    str_replace('.', ',', $priceNoNds), //  $product->withoutNdsDiscount, // K = l5/j5 цена единицы
                    str_replace('.', ',', $priceNoNdsSum), // $product->withoutNdsDiscount*$product->amount, // L стоимость поставки O5-N5
                    $nds > 0 ? NdsStopgagHelper::getActualNdsValue($product->order->created_at) . '%' : '0%', // M ндс %
                    str_replace('.', ',', $priceNds), // N=O5/(1+M5)*M5 $product->price_discount*$product->amount - $product->withoutNdsDiscount*$product->amount, // N
                    str_replace('.', ',', $price), // $product->price_discount*$product->amount, // O
                    $product->order->contract->id, // P id кредита
                    $product->order->buyer->user->fio, // Q
                    $product->order->buyer->phone,
                    $is_cancel ? 'В рассрочке (cancel)' : $contract_statuses[$product->order->contract->status],
                    // $product->order->contract->canceled_at,
                ];

                $data[] = $_data;

                if ($is_cancel) {

                    $_data = [
                        $product->order->company->id,
                        $product->order->company->uniq_num,
                        $product->order->contract->prefix_act,
                        $product->order->contract->period,
                        //'', //$product->order->contract->canceledDate,
                        date('Y-m-d', strtotime($product->order->contract->canceled_at)),
                        $product->order->company->name,
                        $product->order->contract->generalCompany->name_ru,  // Торговая компания (на русском)  // dev_nurlan 07.04.2022
                        $product->order->company->brand,
                        $product->order->company->inn,
                        $product_name,
                        str_replace('.', ',', $product->amount * -1), // J колво
                        str_replace('.', ',', $priceNoNds * -1), //  $product->withoutNdsDiscount, // K = l5/j5 цена единицы
                        str_replace('.', ',', $priceNoNdsSum * -1), // $product->withoutNdsDiscount*$product->amount, // L стоимость поставки O5-N5
                        $nds > 0 ? NdsStopgagHelper::getActualNdsValue($product->order->created_at) . '%' : '0%', // M
                        // ндс %
                        str_replace('.', ',', $priceNds * -1), // N=O5/(1+M5)*M5 $product->price_discount*$product->amount - $product->withoutNdsDiscount*$product->amount, // N
                        str_replace('.', ',', $price * -1), // $product->price_discount*$product->amount, // O
                        $product->order->contract->id, // P id кредита
                        $product->order->buyer->user->fio, // Q
                        $product->order->buyer->phone,
                        'Отменен',
                        //$product->order->contract->canceled_at,

                    ];
                    $data[] = $_data;

                }

            }

            $data = collect($data);

        }catch (\Exception $e){
            Log::channel('report')->info('ERROR report filialsCancel');
            Log::channel('report')->info($e);

        }

        return $data;

    }

    public function headings() :array   {
        $header = [
            'Партнер ID',
            'Договор',
            'Спецификация',
            'Срок кредита',
            //'Дата Отмены',
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
            'Телефон',
            'Статус',
        ];


        return $header;

    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {

                $rows = $event->sheet->getDelegate()->getHighestRow();

                $event->sheet->getStyle('A2:T'.$rows)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOTTED,
                        ],
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ]
                    ]
                ]);

                $event->sheet->getStyle('A1:T1')->applyFromArray([
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
                $event->sheet->setAutoFilter('A1:T1');

            }
        ];
    }

}
