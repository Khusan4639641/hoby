<?php


namespace App\Notifications;

use Illuminate\Notifications\Notification;

class NotificationDB
{


    public function send($notifiable, Notification $notification)
    {
        $data = $notification->toDatabase($notifiable);
        $hash = null;
        if( isset($data['hash']) && strlen($data['hash'])>0 ) {
            $hash = $data['hash'];
            unset($data['hash']);
        }

        return $notifiable->routeNotificationFor('database')->create([
            'id' => $notification->id,
            'hash' => $hash,
            'type' => get_class($notification),
            'data' => $data,
            'read_at' => null,
        ]);
    }

}
