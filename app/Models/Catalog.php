<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Catalog extends Model
{

    public $image_path = '/images/catalog/';

    // изображение каталога
    public function getImage(){
        $previewPath = $this->image_path . $this->attributes['img'];
        return Storage::exists($previewPath) ? Storage::url($previewPath) : Storage::url('/empty.png');
    }

    // все компании с данной категорией
    public function companies(){
       return $this->belongsToMany(Companies::class, 'catalog_partners', 'catalog_id', 'partner_id');
    }




}
