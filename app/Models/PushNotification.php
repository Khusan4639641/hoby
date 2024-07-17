<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $notification_data)
 */
class PushNotification extends Model
{
    protected $table = 'push_notifications';

    const TYPES = [
        'contract' => 'contract'
    ];

    const STATUSES = [ //statuses start with 11
        'notSent' => 1101,
        'sent' => 1102,
        'read' => 1103
    ];

    protected $fillable = ['user_id', 'title', 'message', 'type', 'element_id', 'status', 'fcm_token'];
}
