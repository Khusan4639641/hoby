<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogPartners extends Model
{
    // нет даты
    public $timestamps = false;

    public function categoryNames() {
        return $this->hasMany(CatalogCategoryLanguage::class, 'category_id', 'catalog_id');
    }
}
