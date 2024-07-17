<?php

namespace App\Console\Commands;

use App\Models\ContractNotification;
use App\Models\Notifications;
use App\Models\PushNotification;
use App\Models\User;
use App\Services\API\V3\FcmService;
use Illuminate\Console\Command;
use Log;
use Str;

class ContractPushNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contract:notify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications to mobile devices when contract closed,payment-expired,payment-day-is-coming';

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
        $this->alert('CONTRACT PUSH NOTIFICATIONS');
        Log::info('CONTRACT PUSH NOTIFICATIONS');
        $data = $this->prepareQuery();
        if(count($data) > 0){
            $this->send($data);
        }
        return 0;
    }

    private function prepareQuery()
    {
        $query = ContractNotification::with('user')
                                ->where('status',ContractNotification::STATUS_PENDING)
                                ->orderBy('priority','ASC')
                                ->get();
        return $query;
    }

    private function getDeviceToken($user)
    {
        switch ($user->device_os) {
            case 'android':
                $device_token = $user->firebase_token_android;
                break;
            case 'ios':
                $device_token = $user->firebase_token_ios;
                break;
            default:
                $device_token = null;
                break;
        }
        return $device_token;
    }

    private function prepareNotificationData($item)
    {
        $notification_data = [
            'id' => Str::uuid(),
            'type' => self::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $item->user_id,
            'data' => json_encode([
                'type' => 'contract',
                'time' => date('H:i:s Y-m-d'),
                'title_ru' => $item->title_ru,
                'title_uz' => $item->title_uz,
                'message_uz' => $item->message_uz,
                'message_ru' => $item->message_ru,
            ])
        ];
        return $notification_data;
    }

    private function send(iterable $data)
    {
        Log::info('Count of notifications: '.count($data));
        $device_token_count = 0;
        $sent_items_ids = [];
        foreach($data as $item){
            try {
                $user = $item->user;
                $device_token = ($user && $user->device_os) ? $this->getDeviceToken($user) : null;
                if ($device_token) {
                    $lang = $user->lang ?? 'uz';
                    $title = 'title_'.$lang;
                    $message = 'message_'.$lang;
                    $notification_data = $this->prepareNotificationData($item);
                    Notifications::create($notification_data);
                    $is_send = FcmService::send($device_token,$item->{$title}, $item->{$message}, ['type' => PushNotification::TYPES['contract'], 'element_id' => $item->contract_id],false);
                    //if send grab item id
                    if($is_send) $sent_items_ids[] = $item->id;
                    $device_token_count++;
                    //Show info in terminal on every 100 items sent
                    if(count($sent_items_ids) % 100 == 0){
                        $this->info('Successfull operations count: '.count($sent_items_ids));
                    }
                }
            } catch (\Throwable $th) {
                Log::info($th->getMessage());
            }
        }
        //If items sent successfully update items status
        if(count($sent_items_ids) > 0 ){
            ContractNotification::whereIn('id',$sent_items_ids)->update(['status' => ContractNotification::STATUS_SEND]);
        }
        Log::info('DEVICES: '.$device_token_count.' SENDED: '.count($sent_items_ids));
    }
}
