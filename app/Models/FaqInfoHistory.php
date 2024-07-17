<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaqInfoHistory extends Model
{
    protected $table = 'faq_info_histories';

    protected $fillable = [
                'faq_id',
                'previous_uz',
                'previous_ru',
                'user_id',
            ];

    public function info(){
        return $this->belongsTo(FaqInfo::class,'faq_id');
    }
}
