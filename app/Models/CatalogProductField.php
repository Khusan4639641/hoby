<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class CatalogProductField extends Model {

    protected $appends = ['title'];

    public function categories() {
        return $this->belongsToMany(CatalogCategory::class);
    }

    public function getTitleAttribute() {
        $currentLocale = app()->getLocale();

        $json = $this->name;
        $_arr = json_decode($json,true);

        return $_arr[$currentLocale] ?? $_arr[Config::get('app.fallback_locale')] ?? '-';
    }

}
