<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class InvoiceBuyer extends Notification {
    use Queueable;

    private $type;
    private $data;


    /**
     * Create a new notification instance.
     *
     * @param $type
     * @param null $data
     */
    public function __construct( $type, $data ) {
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
        return [ NotificationDB::class ];
        //return [ 'database' ];
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


        /*
         *
         * 'status_changed' => "Ваш статус изменен на <strong>:status</strong>",
        'reason' => "Причина отказа: <strong>:reason</strong>",
        'order_created' => "Договор <a target='_blank' href=':order_link'>№:order_number</a> на сумму :order_total сум. успешно создан",
        'order_status_changed' => "Статус договора <a target='_blank' href=':order_link'>№:order_number</a> изменен на <strong>:status_caption</strong>",
        'order_delay' => "Статус договора <a target='_blank' href=':order_link'>№:order_number</a> изменен на <strong>:status_caption</strong>",
         *
         *
         *         'status_changed' => "Sizning holatingiz o`zgartirildi <strong>:status</strong>",
        'reason' => "Rad etish sabablari: <strong>:reason</strong>",
        'order_created' => "Miqdor uchun <a target='_blank' href=':order_link'>№:order_number</a> shartnoma :order_total so`m. muvaffaqiyatli yaratilgan",
        'order_status_changed' => "Shartnoma holati <a target='_blank' href=':order_link'>№:order_number</a> o`zgartirildi <strong>:status_caption</strong>",
         * */

        switch ( $this->type ) {
            case 'change-status':

                /**
                 *  0 - новый
                    1 - не верифицирован
                    2 - ожидает верификации
                    3 - отказ верификации
                    4 - верифицирован
                    8 - заблокирован
                    9 - удален
                 */

                //$message = __( 'notification.buyer.status_changed', ['status' => __('user.status_' . $this->data['status'])] ) ;
                /* switch ($this->data['status']){
                    case 3:
                        $message .= "<br>" . __('notification.buyer.reason', ['reason' => $notifiable->verify_message]);
                        break;
                } */

                if ($this->data['status']==3){
                    $message_ru = 'Причина отказа: ' . $notifiable->verify_message;
                    $message_uz = 'Rad etish sabablari: ' . $notifiable->verify_message;
                    $title_ru = 'Причина отказа';
                    $title_uz = 'Rad etish sabablari';

                }else{
                    $message_ru = 'Ваш статус изменен на ' . $this->data['status'];
                    $message_uz = 'Sizning holatingiz o`zgartirildi ' . $this->data['status'];
                    $title_ru = 'Ваш статус изменен';
                    $title_uz = 'Sizning holatingiz o`zgartirildi';
                }


                $result = [
                    'hash'    => $this->data['hash'] ?? null,
                    'type'    => $this->type,
                    'time'    => $time,
                    'status'  => $this->data['status'],
                    'title_ru'  => $title_ru,
                    'title_uz'  => $title_uz,
                    'message_ru' => $message_ru,
                    'message_uz' => $message_uz,
                ];
                break;

            case 'order-created':
                $result = [
                    'hash'    => $this->data['hash'] ?? null,
                    'type'    => $this->type,
                    'time'    => $time,
                    'title_ru'  => 'Создан договор',
                    'title_uz'  => 'Shartnoma shakllantirildi',
                    /*'message' => __( 'notification.buyer.order_created', [
                        'order_total' => $this->data['order']->total,
                        'order_number' => $this->data['order']->id,
                    ] ), */
                    'message_ru' => 'Договор №' . $this->data['order']->id . ' на сумму ' . $this->data['order']->total . ' сум. успешно создан',
                    'message_uz' => 'Miqdor uchun №' . $this->data['order']->id . ' shartnoma ' . $this->data['order']->total . ' so`m. muvaffaqiyatli yaratilgan',
                    'order_link' => localeRoute('cabinet.orders.show', $this->data['order']->id, false),
                ];
                break;

            case 'order-status-changed':
                $result = [
                    'hash'    => $this->data['hash'] ?? null,
                    'type'    => $this->type,
                    'time'    => $time,
                    'title_ru'  => 'Статус договора',
                    'title_uz'  => 'Shartnoma holati',
                    'message_ru' => 'Статус договора №' . $this->data['order']->id . ' изменен на ' . $this->data['order']->status_caption,
                    'message_uz' => 'Shartnoma holati №' . $this->data['order']->id . ' o`zgartirildi ' . $this->data['order']->status_caption,
                    /* 'message' => __( 'notification.buyer.order_status_changed', [
                        'order_total' => $this->data['order']->total,
                        'order_number' => $this->data['order']->id,
                        'status_caption' => $this->data['order']->status_caption,
                    ] ),*/
                    'order_link' => localeRoute('cabinet.orders.show', $this->data['order']->id, false),
                ];
                break;

            case 'add-action': // админ добавляет акцию
                $result = [
                    'hash'    => $this->data['hash'] ?? null,
                    'type'    => $this->type,
                    'time'    => $time,
                    'title_ru'  => '',
                    'title_uz'  => '',
                    'message_ru' => '',
                    'message_uz' => '',
                    /*'message' => __( 'notification.buyer.order_status_changed', [
                        'image' => $this->data['image'],
                        'text' => $this->data['text'],
                    ] ), */
                    'link' => localeRoute('cabinet.orders.show', $this->data['order']->id, false),
                ];
                break;

            case 'order-delay': // KYC уведомляет о просрочке договора
                $result = [
                    'hash'    => $this->data['hash'] ?? null,
                    'type'    => $this->type,
                    'time'    => $time,
                    'title_ru'  => 'Договор просрочен',
                    'title_uz'  => 'Shartnoma to`lov muddati o`tgan.',
                    'message_ru' => 'Статус договора №' . $this->data['order']->id . ' изменен на ' . $this->data['order']->status_caption,
                    'message_uz' => 'Shartnoma holati №' . $this->data['order']->id . ' o`zgartirildi ' . $this->data['order']->status_caption,
                    /*'message' => __( 'notification.buyer.order_delay', [
                        'order_number' => $this->data['order_id'],
                        'status_caption' => $this->data['status'] ?? null,
                    ] ), */
                    'link' => localeRoute('cabinet.orders.show', $this->data['order']->id, false),
                ];
                break;
        }

        return $result;
    }

    public function toDatabase($notifiable)
    {
        $time   = Carbon::now()->format( 'H:i d.m.Y' );

        switch ( $this->type ) {
            case 'change-status':

                /**
                 *  0 - новый
                1 - не верифицирован
                2 - ожидает верификации
                3 - отказ верификации
                4 - верифицирован
                8 - заблокирован
                9 - удален
                 */

                //$message = __( 'notification.buyer.status_changed', ['status' => __('user.status_' . $this->data['status'])] ) ;
                /* switch ($this->data['status']){
                    case 3:
                        $message .= "<br>" . __('notification.buyer.reason', ['reason' => $notifiable->verify_message]);
                        break;
                } */

                if ($this->data['status']==3){
                    $message_ru = 'Причина отказа: ' . $notifiable->verify_message;
                    $message_uz = 'Rad etish sabablari: ' . $notifiable->verify_message;
                    $title_ru = 'Причина отказа';
                    $title_uz = 'Rad etish sabablari';

                }else{
                    $message_ru = 'Ваш статус изменен на ' . $this->data['status'];
                    $message_uz = 'Sizning holatingiz o`zgartirildi ' . $this->data['status'];
                    $title_ru = 'Ваш статус изменен';
                    $title_uz = 'Sizning holatingiz o`zgartirildi';
                }


                $result = [
                    'hash'    => $this->data['hash'] ?? null,
                    'type'    => $this->type,
                    'time'    => $time,
                    'status'  => $this->data['status'],
                    'title_ru'  => $title_ru,
                    'title_uz'  => $title_uz,
                    'message_ru' => $message_ru,
                    'message_uz' => $message_uz,
                ];
                break;

            case 'order-created':
                $result = [
                    'hash'    => $this->data['hash'] ?? null,
                    'type'    => $this->type,
                    'time'    => $time,
                    'title_ru'  => 'Создан договор',
                    'title_uz'  => 'Shartnoma shakllantirildi',
                    /*'message' => __( 'notification.buyer.order_created', [
                        'order_total' => $this->data['order']->total,
                        'order_number' => $this->data['order']->id,
                    ] ), */
                    'message_ru' => 'Договор №' . $this->data['order']->id . ' на сумму ' . $this->data['order']->total . ' сум. успешно создан',
                    'message_uz' => 'Miqdor uchun №' . $this->data['order']->id . ' shartnoma ' . $this->data['order']->total . ' so`m. muvaffaqiyatli yaratilgan',
                    'order_link' => localeRoute('cabinet.orders.show', $this->data['order']->id, false),
                ];
                break;

            case 'order-status-changed':
                $result = [
                    'hash'    => $this->data['hash'] ?? null,
                    'type'    => $this->type,
                    'time'    => $time,
                    'title_ru'  => 'Статус договора',
                    'title_uz'  => 'Shartnoma holati',
                    'message_ru' => 'Статус договора №' . $this->data['order']->id . ' изменен на ' . $this->data['order']->status_caption,
                    'message_uz' => 'Shartnoma holati №' . $this->data['order']->id . ' o`zgartirildi ' . $this->data['order']->status_caption,
                    /* 'message' => __( 'notification.buyer.order_status_changed', [
                        'order_total' => $this->data['order']->total,
                        'order_number' => $this->data['order']->id,
                        'status_caption' => $this->data['order']->status_caption,
                    ] ),*/
                    'order_link' => localeRoute('cabinet.orders.show', $this->data['order']->id, false),
                ];
                break;

            case 'add-action': // админ добавляет акцию
                $result = [
                    'hash'    => $this->data['hash'] ?? null,
                    'type'    => $this->type,
                    'time'    => $time,
                    'title_ru'  => '',
                    'title_uz'  => '',
                    'message_ru' => '',
                    'message_uz' => '',
                    /*'message' => __( 'notification.buyer.order_status_changed', [
                        'image' => $this->data['image'],
                        'text' => $this->data['text'],
                    ] ), */
                    'link' => localeRoute('cabinet.orders.show', $this->data['order']->id, false),
                ];
                break;

            case 'order-delay': // KYC уведомляет о просрочке договора
                $result = [
                    'hash'    => $this->data['hash'] ?? null,
                    'type'    => $this->type,
                    'time'    => $time,
                    'title_ru'  => 'Договор просрочен',
                    'title_uz'  => 'Shartnoma to`lov muddati o`tgan.',
                    'message_ru' => 'Статус договора №' . $this->data['order']->id . ' изменен на ' . $this->data['order']->status_caption,
                    'message_uz' => 'Shartnoma holati №' . $this->data['order']->id . ' o`zgartirildi ' . $this->data['order']->status_caption,
                    /*'message' => __( 'notification.buyer.order_delay', [
                        'order_number' => $this->data['order_id'],
                        'status_caption' => $this->data['status'] ?? null,
                    ] ), */
                    'link' => localeRoute('cabinet.orders.show', $this->data['order']->id, false),
                ];
                break;
        }

        return $result;

    }

}
