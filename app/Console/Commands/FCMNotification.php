<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\ContractPaymentsSchedule;
use App\Models\Notifications;
use App\Models\PushNotification;
use App\Models\User;
use App\Services\API\V3\FcmService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Str;

class FCMNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fcm:notify {--type=all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications to mobile devices about contract';

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
        $this->info($this->option('type'));
        if ($this->option('type') == 'all' || empty($this->option('type'))) {
            $this->paymentIn5Days();
            $this->paymentTomorrow();
            $this->paymentExpiry10Days();
            $this->paymentExpiry15Days();
        } else {
            switch ($this->option('type')) {
                case 'in-5':
                    $this->paymentIn5Days();
                    break;
                case 'tomorrow':
                    $this->paymentTomorrow();
                    break;
                case 'expiry-10':
                    $this->paymentExpiry10Days();
                    break;
                case 'expiry-15':
                    $this->paymentExpiry15Days();
                    break;
            }
        }
        return 0;
    }

    private function paymentIn5Days()
    {
        $this->alert('FCM NOTIFICATION ( IN 5 DAYS )');
        Log::channel('notifications')->info(str_repeat('*',20).'FCM NOTIFICATION ( IN 5 DAYS )'.str_repeat('*',20));
        $device_token_count = 0;
        $now = Carbon::now();
        $start = $now->addDays(5)->format('Y-m-d');
        $end = Carbon::now()->addDays(6)->format('Y-m-d');
        $contracts = $this->prepareQuery(1,0,$start,$end);
        Log::info('start: '.$start.' end: '.$end.' now: '.$now);
        if (count($contracts) > 0) {
            foreach ($contracts as $contract) {
                try {
                    $device_token = $this->getDeviceToken($contract);
                    if ($device_token) {
                        $message = FcmService::getMessages('pay_in_10_days');
                        $lang = $contract->lang ?? 'uz';
                        if ($message) {
                            $message[$lang]['body'] = str_replace('{sum}', $contract->balance, $message[$lang]['body']);
                            $message[$lang]['body'] = str_replace('{date}', $contract->payment_date, $message[$lang]['body']);
                            $message[$lang]['body'] = str_replace('{contract_number}', $contract->contract_id, $message[$lang]['body']);
                            $notification_data = $this->prepareNotificationData($message, $lang, $contract, $device_token);
                            $push = PushNotification::create($notification_data);
                            $sent = FcmService::send($device_token, $message[$lang]['title'], $message[$lang]['body'], $notification_data,false);
                            if($sent){
                                $push->update(['status' => PushNotification::STATUSES['sent']]);
                                $device_token_count++;
                            }
                        }
                    }
                    Log::channel('notifications')->info("FCMNotification info", ['contract_id' => $contract->contract_id, 'device_token' => $device_token, 'sent' => $sent ?? false]);
                } catch (\Throwable $th) {
                    Log::channel('notifications')->info("FCMNotification error: " . $th);
                }
            }
        }
        Log::info('PAYMENTS: ' . count($contracts) . ' | DEVICES: ' . $device_token_count.' FINISH!!!');
        $this->info('PAYMENTS: ' . count($contracts) . ' | DEVICES: ' . $device_token_count.' FINISH!!!');
    }

    private function paymentTomorrow()
    {
        $this->alert('FCM NOTIFICATION ( TOMORROW )');
        Log::channel('notifications')->info(str_repeat('*',20).'FCM NOTIFICATION ( TOMORROW )'.str_repeat('*',20));
        $device_token_count = 0;
        $now = Carbon::now();
        $start = $now->addDays(1)->format('Y-m-d');
        $end = Carbon::now()->addDays(2)->format('Y-m-d');
        $contracts = $this->prepareQuery(1,0,$start,$end);
        Log::info('start: '.$start.' end: '.$end.' now: '.$now);
        if (count($contracts) > 0) {
            foreach ($contracts as $contract) {
                try {
                    $device_token = $this->getDeviceToken($contract);
                    if ($device_token) {
                        $message = FcmService::getMessages('pay_tomorrow');
                        $lang = $contract->lang ?? 'uz';
                        if ($message) {
                            $message[$lang]['body'] = str_replace('{sum}', $contract->balance, $message[$lang]['body']);
                            $message[$lang]['body'] = str_replace('{contract_number}', $contract->contract_id, $message[$lang]['body']);
                            $notification_data = $this->prepareNotificationData($message, $lang, $contract, $device_token);
                            $push = PushNotification::create($notification_data);
                            $sent = FcmService::send($device_token, $message[$lang]['title'], $message[$lang]['body'], $notification_data,false);
                            if($sent){
                                $push->update(['status' => PushNotification::STATUSES['sent']]);
                                $device_token_count++;
                            }
                        }
                    }
                    Log::channel('notifications')->info("FCMNotification info", ['contract_id' => $contract->contract_id, 'device_token' => $device_token, 'sent' => $sent ?? false]);
                } catch (\Throwable $th) {
                    Log::channel('notifications')->info("FCMNotification error: " . $th);
                }
            }
        }
        Log::info('PAYMENTS: ' . count($contracts) . ' | DEVICES: ' . $device_token_count.' FINISH!!!');
        $this->info('PAYMENTS: ' . count($contracts) . ' | DEVICES: ' . $device_token_count.' FINISH!!!');
    }

    private function paymentExpiry10Days()
    {
        $this->alert('FCM NOTIFICATION ( EXPIRED 10 DAYS )');
        Log::channel('notifications')->info(str_repeat('*',20).'FCM NOTIFICATION ( EXPIRED 10 DAYS )'.str_repeat('*',20));
        $device_token_count = 0;
        $now = Carbon::now();
        $start = $now->subDays(11)->format('Y-m-d');
        $end = Carbon::now()->subDays(10)->format('Y-m-d');
        $contracts = $this->prepareQuery(1,2,$start,$end);
        Log::info('start: '.$start.' end: '.$end.' now: '.$now);
        if (count($contracts) > 0) {
            foreach ($contracts as $contract) {
                try {
                    $device_token = $this->getDeviceToken($contract);
                    if ($device_token) {
                        $message = FcmService::getMessages('pay_expiry_10');
                        $lang = $contract->lang ?? 'uz';
                        if ($message) {
                            $message[$lang]['body'] = str_replace('{sum}', $contract->balance, $message[$lang]['body']);
                            $message[$lang]['body'] = str_replace('{contract_number}', $contract->contract_id, $message[$lang]['body']);
                            $notification_data = $this->prepareNotificationData($message, $lang, $contract, $device_token);
                            $push = PushNotification::create($notification_data);
                            $sent = FcmService::send($device_token, $message[$lang]['title'], $message[$lang]['body'], $notification_data,false);
                            if($sent){
                                $push->update(['status' => PushNotification::STATUSES['sent']]);
                                $device_token_count++;
                            }
                        }
                    }
                    Log::channel('notifications')->info("FCMNotification info", ['contract_id' => $contract->contract_id, 'device_token' => $device_token, 'sent' => $sent ?? false]);
                } catch (\Throwable $th) {
                    Log::channel('notifications')->info("FCMNotification error: " . $th);
                }
            }
        }
        Log::info('PAYMENTS: ' . count($contracts) . ' | DEVICES: ' . $device_token_count.' FINISH!!!');
        $this->info('PAYMENTS: ' . count($contracts) . ' | DEVICES: ' . $device_token_count.' FINISH!!!');
    }

    private function paymentExpiry15Days()
    {
        $this->alert('FCM NOTIFICATION ( EXPIRED 15 DAYS )');
        Log::channel('notifications')->info(str_repeat('*',20).'FCM NOTIFICATION ( EXPIRED 15 DAYS )'.str_repeat('*',20));
        $device_token_count = 0;
        $now = Carbon::now();
        $start = $now->subDays(15)->format('Y-m-d');
        $end = Carbon::now()->subDays(14)->format('Y-m-d');
        $contracts = $this->prepareQuery(1,2,$start,$end);
        Log::info('start: '.$start.' end: '.$end.' now: '.$now);
        if (count($contracts) > 0) {
            foreach ($contracts as $contract) {
                try {
                    $device_token = $this->getDeviceToken($contract);
                    if ($device_token) {
                        $message = FcmService::getMessages('pay_expiry_15');
                        $lang = $contract->lang ?? 'uz';
                        $message[$lang]['body'] = str_replace('{contract_number}', $contract->contract_id, $message[$lang]['body']);
                        $notification_data = $this->prepareNotificationData($message, $lang, $contract, $device_token);
                        $push = PushNotification::create($notification_data);
                        $sent = FcmService::send($device_token, $message[$lang]['title'], $message[$lang]['body'], $notification_data,false);
                        if($sent){
                            $push->update(['status' => PushNotification::STATUSES['sent']]);
                            $device_token_count++;
                        }
                    }
                    Log::channel('notifications')->info("FCMNotification info", ['contract_id' => $contract->contract_id, 'device_token' => $device_token, 'sent' => $sent ?? false]);
                } catch (\Throwable $th) {
                    Log::channel('notifications')->info("FCMNotification error: " . $th);
                }
            }
        }
        $this->info('PAYMENTS: ' . count($contracts) . ' | DEVICES: ' . $device_token_count);
        Log::info('PAYMENTS: ' . count($contracts) . ' | DEVICES: ' . $device_token_count.' FINISH!!!');
    }

    private function prepareQuery($contract_status,$schedule_status,$start,$end)
    {
        $query = ContractPaymentsSchedule::select('contract_payments_schedule.*', 'contracts.status AS contract_status', 'users.device_os', 'users.firebase_token_android', 'users.firebase_token_ios', 'users.lang')
        ->leftJoin('contracts', 'contracts.id', '=', 'contract_payments_schedule.contract_id')
        ->leftJoin('users', 'users.id', '=', 'contract_payments_schedule.user_id')
        ->whereBetween('contract_payments_schedule.payment_date', [$start, $end])
        ->where('contracts.status', '=', $contract_status)
        ->where('contract_payments_schedule.status', '=', $schedule_status)
        ->get();
        return $query;
    }

    private function prepareNotificationData($message, $lang, $contract, $device_token = null): array
    {
        return [
            'user_id' => $contract->user_id,
            'title' => $message[$lang]['title'],
            'message' =>  $message[$lang]['body'],
            'type' => PushNotification::TYPES['contract'],
            'element_id' =>  $contract->contract_id,
            'status' => PushNotification::STATUSES['notSent'],
            'fcm_token' => $device_token,
        ];
    }

    private function getDeviceToken($user)
    {
        switch ($user->device_os) {
            case 'ios':
                $device_token = $user->firebase_token_ios;
                break;
            case 'android':
            default:
                $device_token = $user->firebase_token_android;
                break;
        }
        return $device_token;
    }
}
