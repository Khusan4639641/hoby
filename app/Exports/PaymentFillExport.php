<?php

namespace App\Exports;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;


class PaymentFillExport {

    /**
    * @return \Illuminate\Support\Collection
    */
    public static function report()
    {

        $query = Payment::select(DB::raw("SUM(amount) amount, DATE_FORMAT(created_at, '%Y.%m.%d') as date,payment_system,type"))
            ->whereIn('type', ['auto','refund','user','user_auto'/*,'upay'*/])
            ->whereNotIn('payment_system',['ACCOUNT','DEPOSIT','Paycoin','BANK'])
            ->groupBy('date','type','payment_system')
            ->orderBy('date','DESC')
        ;

        //print_r($query->getBindings());
        //dd($query->toSql());

        report_filter($query,'created_at');

        /// $query->take(50);
        ///
        // print_r($query->getBindings());
        // dd($query->toSql());


        $payments = $query->get();

        //dd($payments);

        $data = [];
        $_payments = [];
        foreach ($payments as $payment){

            $type = $payment->type=='user_auto' || $payment->type=='refund' ? 'auto' : $payment->type;

            if(!isset($data[$payment->date][$payment->payment_system][$type])) $data[$payment->date][$payment->payment_system][$type] = 0;
            $data[$payment->date][$payment->payment_system][$type] += $payment->amount;

            if(!in_array($payment->payment_system, $_payments)) $_payments[] = $payment->payment_system;

        }

        $res = [];
        $payHeader = '';
        foreach( $_payments as $payType ) { // по платежкам - заголовки
            $payHeader .= $payType .';';
        }
        $res[] = 'Дата;' . $payHeader . $payHeader . 'Сумма пополнения;Сумма списания'."\n";
        $res[] = ';Пополнение' . str_repeat(';',count($_payments)) . 'Списание' . "\n";

        //print_r($data);
        foreach ( $data as $date => $payment ){

            $pay = '';
            $fill = '';

            $fillSum = 0;
            $paySum = 0;

            foreach( $_payments as $payType ){ // по платежкам - заголовки

                if(isset($payment[$payType])){
                    if(isset($payment[$payType]['user'])) {
                        $fillSum += $payment[$payType]['user'];
                        $fill .= $payment[$payType]['user'] ;
                    }
                    if(isset($payment[$payType]['auto'])){
                        $paySum += $payment[$payType]['auto'];
                        $pay .= $payment[$payType]['auto'] ;
                    }

                }
                $pay .= ';';
                $fill .= ';';
            }
            $_tmp = str_replace('.',',',$fill . $pay . $fillSum . ';' . $paySum);

            $res[] = $date . ';' . $_tmp .  "\n";


        }

        //print_r($data);
        //dd($res);

        $res = implode('',$res);

        return $res;

    }


    public function headings(): array
    {
        return [
            'ID',
            'Оформлен',
            'Номер Договора',
            'Дата создания',
            'Сумма к списанию',
            'Списано',
            'Остаток',
            'Проверка',
            'ID Пользователя',
            'ФИО',
            'Долг',
            'zcoin',
            'Платежная система',
            'Тип',
            'Статус',

        ];

    }



}

?>
