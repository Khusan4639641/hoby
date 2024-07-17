<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockingHistory extends Model
{
    protected $table = 'blocking_history';
    protected $fillable = [
                    'id',
                    'company_id',
                    'type',
                    'user_id',
                    'manager_id',
                    'reason_id',
                    'comment',
                    'created_at'
            ];

    public function reson() {
        return $this->hasOne(BlockingReasons::class, 'id','reason_id');
    }
}
