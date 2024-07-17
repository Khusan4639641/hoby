<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Helpers\PaymentHelper;

class AutomaticPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(){

        return 'auto payment disabled!' . __FILE__;

        /*$log = '';
        Log::channel('jobs')->info('start automatic.payment.start');
        $scheduleList = PaymentHelper::getScheduleList();
        Log::channel('jobs')->info($scheduleList->toJson());
        if($scheduleList){
            $count = sizeof($scheduleList);
            for($c=0; $c<$count; $c++){
                $log = PaymentHelper::actionPayment($scheduleList[$c]);
            }
        }
        PaymentHelper::actionDelayPayment();
        Log::channel('jobs')->info($log);
        Log::channel('jobs')->info('start automatic.payment.end'); */
        //
    }
}
