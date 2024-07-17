<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class CatalogCategory extends Model
{

    const PARENT_CATEGORY_FILE_TYPE = 'parent_categories_0';

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    const PSIC_CODE_STATUS_UNCHECKED = 0;
    const PSIC_CODE_STATUS_ACTIVE = 1;
    const PSIC_CODE_STATUS_NOT_ACTIVE = 2;
    const PSIC_CODE_STATUS_INCORRECT = 3;

    // TODO: Убрать константу, когда удалят старые категории.
    public const LEGACY_CATEGORIES = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
    public const OTHERS_CATEGORY = 12;

    protected $fillable = [
        'marketplace_id',
        'psic_code',
        'psic_text',
        'status',
        'parent_id',
        'sort'
    ];

    public function languages()
    {
        return $this->hasMany(CatalogCategoryLanguage::class, 'category_id');
    }

    public function language($code = null)
    {
        if (is_null($code))
            $code = app()->getLocale();
        return $result = $this->hasOne(CatalogCategoryLanguage::class, 'category_id')->whereLanguageCode($code);
    }

    public function products()
    {
        return $this->belongsToMany(CatalogProduct::class);
    }

    public function image()
    {
        return $this->hasOne(File::class, 'element_id', 'id')->where('model', 'catalog-category')->where('type', 'image');
    }

    public function icon()
    {
        return $this->hasOne(File::class, 'element_id', 'id')->where('model', 'catalog-category')->where('type', self::PARENT_CATEGORY_FILE_TYPE);
    }


    public function parent()
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    public function child()
    {
        return $this->children()->with('child', 'language');
    }

    public function childCategories()
    {
        return $this->children()->with('childCategories', 'language', 'image');
    }

    public function fields()
    {
        return $this->belongsToMany(CatalogProductField::class)
            ->withPivot('sort')->orderBy('sort');
        //->withTimestamps();
    }
}
