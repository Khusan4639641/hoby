<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class InvoiceKYC extends Notification {
    use Queueable;

    private $type;
    private $data;

    /**
     * Create a new notification instance.
     *
     * @param $type
     * @param null $data
     */
    public function __construct( $type, $data = null ) {
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function via( $notifiable ) {
        return [ 'database' ];
    }


    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function toArray( $notifiable ) {
        $result = [];
        $time   = Carbon::now()->format( 'H:i d.m.Y' );

        switch ( $this->type ) {
            case 'buyer-verify':
                $result = [
                    'type'    => $this->type,
                    'time'    => $time,
                    'title_ru'  => 'Верификация',
                    'title_uz'  => 'Tekshirish',
                    'message_ru' => "Пользователь {$this->data['buyer']->id} отправил свои данные на верификацию",
                    'message_uz' => "Foydalanuvchi {$this->data['buyer']->id} ma`lumotlarimni tekshirish uchun yubordi",
                    /* 'message' => __( 'notification.kyc.buyer_verify', [
                        'buyer_link' => localeRoute('panel.buyers.edit', $this->data['buyer']->id, false),
                        'buyer_name' => $this->data['buyer']->fio
                    ] ), */
                    'buyer_link' => localeRoute('panel.buyers.edit', $this->data['buyer']->id, false),
                ];
                break;
        }

        return $result;
    }
}
