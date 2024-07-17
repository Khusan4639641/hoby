<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsLanguage extends Model
{
    public function image(){
        return $this->hasOne(File::class, 'element_id', 'id')->where('model', 'news-language')->where('type', 'image');
    }
}
