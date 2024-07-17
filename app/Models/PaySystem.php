<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PaySystem extends Model
{
    protected $fillable = [
        'title', 'url','status'
    ];

    public $image_path = '/images/payments/';

    // изображение - логотип платежной системы
    public function getImage(){
        $previewPath = $this->image_path . $this->attributes['img'];
        return Storage::exists($previewPath) ? Storage::url($previewPath) : Storage::url('/empty.png');
    }

}
