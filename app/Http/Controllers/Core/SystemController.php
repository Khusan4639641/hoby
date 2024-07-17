<?php


namespace App\Http\Controllers\Core;


use App\Models\CronData;
use App\Models\CronInit;
use App\Models\CronPayment;
use App\Models\CronUsersDelays;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemController extends CoreController
{

    // информация о планировщике
    public function cron(){

        $date_from = date('Y-m-d 00:00:00', time() ); // дата нужна 00
        $date_to = date('Y-m-d 23:59:59', time());

        $cronData = CronData::get();
        $cronInit = CronInit::first();
        $cronUsers = CronUsersDelays::count();
        $cronUsersReady = CronUsersDelays::where('status',1)->count();
        $cronUsersReadyPart = CronUsersDelays::where('status',0)->where('updated_at','>',$date_from)->count();
        //$payment = CronPayment::select(DB::raw('SUM(amount) as balance'))->whereBetween('created_at',[$date_from,$date_to])->first();
        $debts = CronUsersDelays::select(DB::raw('SUM(balance) as balance'))->first();
        $payment = CronUsersDelays::select(DB::raw('SUM(pa_amount) - SUM(balance) as balance'))->first();

        return view('panel.system.cron',compact('cronData','cronInit','cronUsers','cronUsersReady','cronUsersReadyPart','payment','debts'));

    }

    // задать статус планировщика
    public function setCronStatus(Request $request){

        $errors = [];
        if(!$request->has('id')) $errors[] = 'id not set';
        if(!$request->has('status')) $errors[] = 'status not set';

        if(count($errors)==0){
            if($cron = CronData::where('id',$request->id)->first()){
                $cron->status = $request->status;
                $cron->cnt = $request->status;
                $cron->save();

                $this->result['status'] = 'success';
                $this->result['data'] = ['status'=>$cron->status];
            }
        }else{
            $this->result['status'] = 'error';
            $this->result['messages'] = $errors;

        }

        return $this->result();

    }



}
