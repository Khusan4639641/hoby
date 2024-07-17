<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        "param",
        "value"
    ];

    static private function createOption($key, $value)
    {
        $option = new Setting();
        $option->param = $key;
        $option->value = $value;
        $option->save();
        return $option;
    }

    static public function getParam($key, $default = '')
    {
        $option = Setting::where('param', $key)->first();
        if (!$option) {
            $option = Setting::createOption($key, $default);
        }
        return $option->value;
    }

    static public function setParam($key, $value)
    {
        $option = Setting::where('param', $key)->first();
        if (!$option) {
            Setting::createOption($key, $value);
            return;
        }
        $option->value = $value;
        $option->save();
    }

}
