<?php

namespace App\Console\Commands;

use App\Helpers\UpayHelper;
use App\Http\Controllers\Core\CardController;
use App\Http\Controllers\Core\KatmController;
use App\Models\KatmScoring;
use Illuminate\Http\Request;
use Illuminate\Console\Command;
use App\Helpers\PaymentHelper;
use Illuminate\Support\Facades\Crypt;

use Illuminate\Support\Facades\Log;

class PaymentAction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:action {action?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $action = $this->argument('action');
        //$this->info('Display this on the screen '.$action);
        $permission = new \App\Models\Permission();
        foreach ($permission->get() as $k=>$v) {
            //if($k%2 == 1)
                //$v->delete();
            dump($v->id);
        }
        $role = new \App\Models\Role();
        foreach ($role->get() as $k=>$v) {
            //if($k%2 == 1)
            //$v->delete();
            dump($v->id);
        }

        exit();

        Log::channel('jobs')->info('start automatic.katm.start');
        $katmScoring = new KatmScoring();
        $katm = $katmScoring->where('status', 0)->orderBy('id', 'desc')->get();
        foreach ($katm as $item) {
            $katmController = new KatmController();
            $request = request();
            $request->merge(['user' => $item->buyer]);
            $result = $katmController->checkAndUpdateScoring($request);
            Log::channel('jobs')->info(var_export($result,1));
        }

        Log::channel('jobs')->info('start automatic.katm.end');

        //PaymentHelper::actionDelayPayment();

        /*$request = new Request();
        $request->merge(['contract_id'=>'1']);
        $insure = new InsureController();
        $insure->add($request);
        /*$account = '';
        $config = [
            'login'=> config('test.upay_login'),
            'password'=> OldCrypt::decryptString(config('test.upay_password')),
            'key'=> config('test.upay_key'),
            'credentials_login'=> config('test.upay_credentials_login'),
            'credentials_password'=> OldCrypt::decryptString(config('test.upay_credentials_password'))
        ];

        $client = UpayHelper::connectedUpay($config);
        //$result = UpayHelper::getServiceList($client, $config, 1);
        //$this->info(var_export($result,1 ));
        $result = UpayHelper::BankCheckAccount($client, $config, '4896', 149);
        $result = UpayHelper::BankPayment($client, $config, 40, '998909896144', 1000);
        $this->info(var_export($result,1 ));
        //PaymentHelper::actionRefund();*/
        /*$scheduleList = PaymentHelper::getScheduleList();
        //dd($scheduleList->toJson());
        if($scheduleList){
            $count = sizeof($scheduleList);
            for($c=0; $c<$count; $c++){
                PaymentHelper::actionPayment($scheduleList[$c]);
            }
        }*/
        /*
                $card = new CardController();

                $request = new Request();
                $request->merge(['payment_id'=>3]);
                $result = $card->refund($request);
                dd($result);
        */
        return 0;
    }
}
