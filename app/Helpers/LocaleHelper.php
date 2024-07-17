<?php

namespace App\Helpers;

use App\Models\Language;
use Illuminate\Database\Eloquent\Collection;

class LocaleHelper {

    /**
     * @return mixed
     */
    public static function language() {
        return Language::whereCode(app()->getLocale())->first();
    }


    /**
     * @return Language[]|Collection
     */
    public static function languages(){
        return Language::all()->sortBy('sort');
    }


    /**
     * @param $fields
     * @param $localeRules
     * @param $defaultLocale
     * @return array
     */
    public static function prepareFieldsAndRules($fields, $localeRules, $defaultLocale){

        //Паттерн для обработки названий
        $languages = self::languages();
        $languagePattern = '';
        foreach($languages as $language)
            $languagePattern .= ($languagePattern == '' ? '' : '|') . $language->code;
        $pattern = '/('.$languagePattern.')\_(.*)/is';

        //Определяем, какие языки нужно проверять
        $result = [];
        $codes = [];
        foreach($fields as $key => $value) {
            preg_match($pattern, $key, $matches);
            if(count($matches) > 0 && $value !== null) {
                $result['fields'][$matches[1]][$matches[2]] = $value;
                $codes[$matches[1]] = $matches[1];
            }
        }
        $codes[$defaultLocale] = $defaultLocale;

        //Формируем список правил
        foreach($codes as $prefix)
            foreach ($localeRules as $name => $rule)
                $result['rules'][$prefix.'_'.$name] = $rule;

        return $result;
    }

}
