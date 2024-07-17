<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class CatalogProduct extends Model {

    public function setFieldsAttribute($value){
        $this->attributes['fields'] = json_encode($value);
    }

    public function getFieldsAttribute(){
        return json_decode($this->attributes['fields'], true);
    }

    public function getFieldAttr($value = null){
        if(!is_null($value)){
            $fields = json_decode($this->attributes['fields'], true);

            if(!is_null($fields)){
                if(count($fields) > 0) {

                    $result = $fields[$value][app()->getLocale()]?? $fields[$value][Config::get('app.fallback_locale')]?? [];

                    if(count($result) > 0){
                        return $result['value'] ?? null;
                    }
                }
            }

        }

        return null;

    }

    public function languages() {
        return $this->hasMany( CatalogProductLanguage::class, 'product_id' )->orderBy('language_code');
    }

    public function language($code = null) {
        if(is_null($code))
            $code = app()->getLocale();
        return $this->hasOne(CatalogProductLanguage::class, 'product_id')->whereLanguageCode($code);
    }

    public function categories() {
        return $this->belongsToMany( CatalogCategory::class);
    }

    public function category() {
        return $this->belongsToMany( CatalogCategoryLanguage::class,'catalog_category_catalog_product','catalog_product_id','catalog_category_id',null,'category_id' )->orderBy('language_code');
    }

    public function images() {
        return $this->hasMany(File::class, 'element_id')->where('model', 'product')
                    ->where('type', 'like', 'image%');
    }

    public function partner(){
        return $this->hasOne(Partner::class,  'id', 'user_id' );
    }

   /* public function company(){
        return $this->hasOne(Company::class,  'id', 'company_id' );
    }*/

}
