<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class InvoicePartner extends Notification {
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
            case 'order-created':
                $result = [
                    'type'    => $this->type,
                    'time'    => $time,
                    'title_ru'  => 'Поступил новый договор',
                    'title_uz'  => 'Miqdor uchun yangi shartnoma',
                    'message_ru'=> "Поступил новый договор № {$this->data['order']->id} на сумму {$this->data['order']->total} сум.",
                    'message_uz'=>"Miqdor uchun yangi shartnoma № {$this->data['order']->id} qabul qilindi {$this->data['order']->total} so`m.",
                    /*'message' => __( 'notification.partner.order_created', [
                        'order_link' => localeRoute('billing.orders.show', $this->data['order']->id, false),
                        'order_total' => $this->data['order']->total,
                        'order_number' => $this->data['order']->id,
                    ] ), */
                ];
                break;
        }

        return $result;
    }
}
