<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogCategoryLanguage extends Model {

    protected $fillable = [
        'language_code',
        'category_id',
        'title',
        'slug',
        'preview_text',
        'detail_text'
    ];
}
