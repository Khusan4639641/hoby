<?php

namespace App\Http\Controllers\Web\Panel;

use App\Http\Controllers\Core\PaymentController as Controller;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{

    /**
     * @return Application|Factory|View
     */
    public function index() {
        // получить наш баланс на счету upay
        $res = new \App\Http\Controllers\Core\ZpayController();
        $upay_balance = $res->getBalance();
        $upay_balance = isset($upay_balance) ? $upay_balance : "try later";

        return view( 'panel.payment.index' , compact('upay_balance'));
    }


    /**
     * @param array $items
     * @return array
     */
    protected function formatDataTables ($items = []){

        $_statuses = [
         0=>'Ожидание',
          1=>'Подтвержден',
          2=>'Ожидание',
          3=>'Отозван',
          4=>'Возврат',
          -1=>'Возврат',
          -2=>'Вовзрат',

        ];
        $_payment_statuses = [
            'ACCOUNT'=>'Лицевой счет',
            'OCLICK'=>'Click',
            'PAYME'=>'Payme',
            'DEPOSIT'=>'Депозит',
            'UPAY'=>'Upay',
            'APELSIN'=>'Apelsin',
            'PAYNET'=>'Paynet',
            'BANK'=>'Банк',
            'BONUS_ACCOUNT'=>'Бонусный счет',
            'Paycoin'=>'Paycoin',

        ];

        $i = 0;
        $data = [];
        foreach ( $items as $item ) {

            if($item->status == 5 || $item->status == 7) continue;

            $month = null;
            if(isset($item->contract)){
                foreach($item->contract->schedule as $schedule){
                    if($schedule->id == $item->schedule_id){
                        $month = $schedule->payment_date;
                    }
                }
            }

            $data[$i][] = $item->contract?'<a target="_blank" href="'.localeRoute('panel.contracts.show', $item->contract->id).'">№ '.$item->contract->id.'</a>':" - ";
            $data[$i][] = ($item->schedule_id && $month) ? Carbon::parse($month)->format( 'd.m.Y' ) : '';
            $data[$i][] = '<a target="_blank" href="'.localeRoute('panel.buyers.show', $item->user_id).'">'.$item->buyer->surname.' '.$item->buyer->name.'</a>';
            $data[$i][] = number_format($item->amount,2,'.',' ');
            $data[$i][] = __('transaction.type_'.$item->type);
            $data[$i][] = $_payment_statuses[$item->payment_system]??$item->payment_system;
            $data[$i][] = $_statuses[$item->status];
            $data[$i][] = $item->created_at;

            if($item->type === 'refund' || in_array($item->payment_system,['ACCOUNT', 'OCLICK', 'PAYME', 'DEPOSIT','UPAY','APELSIN','PAYNET','BANK','BONUS_ACCOUNT', 'Paycoin', 'PNFL'])){
                $data[$i][] =  '';
            }elseif($item->status === -1){
                $data[$i][] =  'возвращено';
            }else{
                $data[$i][] =  '<button onclick="confirmRefund('.$item->id.')" type="button"
                                class="btn btn-sm btn-archive">'.__('app.btn_refund').'</button>';
            }

            $i ++;
        }

        return parent::formatDataTables($data);
    }

}
