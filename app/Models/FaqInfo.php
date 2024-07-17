<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaqInfo extends Model
{
    protected $table = 'faq_info';

    protected $fillable = [
                'user_id',
                'sort',
                'answer_ru',
                'answer_uz',
                'question_uz',
                'question_ru',
                'status'
            ];
    const STATUS_SHOW = 1;
    const STATUS_HIDDEN = 0;
    public function history(){
        return $this->hasMany(FaqInfoHistory::class, 'faq_id');
    }

}
