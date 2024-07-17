<?php


namespace App\Helpers;

use App\Models\Buyer;
use App\Models\Notifications;
use App\Models\User;
use App\Notifications\InvoiceBuyer;
use App\Notifications\InvoiceKYC;
use App\Notifications\InvoicePartner;
use Illuminate\Support\Facades\Log;

class NotificationHelper {

    //Статус был изменен в кабинете KYC
    public static function buyerStatusChangedByKYC($data, $locale = 'uz'){

        $data['buyer']->notify((new InvoiceBuyer('change-status', $data))->locale($locale));
    }

    //Покупатель отправил свои данные на верификацию
    public static function buyerSendVerification($data, $locale = 'uz'){

        $users = User::whereRoleIs('kyc')->get();

        foreach ($users as $user){
            $user->notify((new InvoiceKYC('buyer-verify', $data))->locale($locale));
        }
    }

    //При создании договора
    public static function orderCreated($data, $locale = 'uz') {

        $data['partner']->notify((new InvoicePartner('order-created', $data))->locale($locale));
    }

    //При изменении статуса договора
    public static function orderStatusChanged($data, $locale = 'uz') {

        $data['buyer']->notify((new InvoiceBuyer('order-status-changed', $data))->locale($locale));
    }

    // 05.05 Админ добавляет акцию
    public static function addAction($data, $locale = 'uz') {

        $data['buyer']->notify((new InvoiceBuyer('add-action', $data))->locale($locale));
    }

    // 05.05 - KYC уведомляет о просрочке договора
    // 23.07 - добавлен hash
    public static function orderDelay($data, $locale = 'uz') {

        if( ! $notification = Notifications::where('hash',$data['hash'])->where('notifiable_id',$data['buyer_id'])->first() ) {

            if($buyer = Buyer::find($data['buyer_id'])) {
                $buyer->notify((new InvoiceBuyer('order-delay', $data))->locale($locale));
                Log::channel('cronpayment')->info('send notify ' . $data['buyer_id']);
            }else{
                Log::channel('autopayment')->info('orderDelay buyer not found ' . $data['buyer_id']);
            }
        }else{
            // Log::channel('autopayment')->info('orderDelay SKIP ' . $data['buyer_id']);
        }

    }


}
