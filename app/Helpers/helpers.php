<?php

use App\Helpers\TelegramHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

if (!function_exists('localeRoute')) {
    /**
     * Generate the URL to a named route with locale.
     *
     * @param array|string $name
     * @param mixed $parameters
     * @param bool $absolute
     */
    function localeRoute($name, $parameters = [], $absolute = true)
    {
        $locale = app()->getLocale();

        if (!is_array($parameters)) {
            $params[] = $parameters;
        } else {
            $params = $parameters;
        }

        if (session('locale')) {
            $locale = session('locale');
        }

        if (empty($params['locale'])) {
            $params['locale'] = $locale;
        }

        return app('url')->route($name, $params, $absolute);
    }
}

if (!function_exists('d')) {
    function d($data, $exit = false)
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        echo '<hr>';
        if ($exit) exit;
    }
}

if (!function_exists('monthCompare')) {

    function monthCompare($a, $b)
    {
        $months = ['JAN' => 1, 'FEB' => 2, 'MAR' => 3, 'APR' => 4, 'MAY' => 5, 'JUN' => 6, 'JULY' => 7, 'AUG' => 8, 'SEP' => 9, 'OCT' => 10, 'NOV' => 11, 'DEC' => 12];
        if ($a[0] == $b[0]) {
            return 0;
        }
        return ($months[$a[0]] > $months[$b[0]]) ? 1 : -1;
    }
}

if (!function_exists('correct_phone')) {

    function correct_phone($phone)
    {

        $phone = preg_replace('/[^0-9]/', '', $phone);
        return $phone;

    }
}

if (!function_exists('valid_phone')) {

    function valid_phone($phone)
    {
        preg_match('/^[\+]{0,1}(?:998)?[\s]*[\(]{0,1}([0-9]{2})[\)]{0,1}[\s]*([0-9]{3})[-]*([0-9]{2})[-]*([0-9]{2})$/', $phone, $phoneInfo);

        if ($phoneInfo && count($phoneInfo) > 0) {
            array_shift($phoneInfo);
            return '998' . implode('', $phoneInfo);
        }

        return false;

    }
}

if (!function_exists('count_digits')) {
    function count_digits(string $number): int
    {
        return strlen($number);
    }
}

if (!function_exists('is_passport')) {

    function is_passport($passport)
    {

        return preg_match('/^([a-zA-Z]{2})([0-9]{7})$/', '', $passport);

    }
}

if (!function_exists('upFirstLetter')) {
    function upFirstLetter($str, $encoding = 'UTF-8')
    {
        if (mb_strlen($str)) {
            $str = mb_strtolower($str, $encoding);
            return mb_strtoupper(mb_substr($str, 0, 1)) . mb_substr($str, 1);
        }
        return '';
    }
}

if (!function_exists('russianDateNow')) {
    function russianDateNow()
    {
        $date = explode(".", date("d.m.y"));
        switch ($date[1]) {
            case 1:
                $m = 'января';
                break;
            case 2:
                $m = 'февраля';
                break;
            case 3:
                $m = 'марта';
                break;
            case 4:
                $m = 'апреля';
                break;
            case 5:
                $m = 'мая';
                break;
            case 6:
                $m = 'июня';
                break;
            case 7:
                $m = 'июля';
                break;
            case 8:
                $m = 'августа';
                break;
            case 9:
                $m = 'сентября';
                break;
            case 10:
                $m = 'октября';
                break;
            case 11:
                $m = 'ноября';
                break;
            case 12:
                $m = 'декабря';
                break;
        }

        switch ($date[0]) {
            case 1:
                $day = 'Первое';
                break;
            case 2:
                $day = 'Второе';
                break;
            case 3:
                $day = 'Третье';
                break;
            case 4:
                $day = 'Четвертое';
                break;
            case 5:
                $day = 'Пятое';
                break;
            case 6:
                $day = 'Шестое';
                break;
            case 7:
                $day = 'Седьмое';
                break;
            case 8:
                $day = 'Восьмое';
                break;
            case 9:
                $day = 'Девятое';
                break;
            case 10:
                $day = 'Десятое';
                break;
            case 11:
                $day = 'Одиннадцатое';
                break;
            case 12:
                $day = 'Двенадцатое';
                break;
            case 13:
                $day = 'Тринадцатое';
                break;
            case 14:
                $day = 'Четырнадцатое';
                break;
            case 15:
                $day = 'Пятнадцатое';
                break;
            case 16:
                $day = 'Шестнадцатое';
                break;
            case 17:
                $day = 'Семнадцатое';
                break;
            case 18:
                $day = 'Восемнадцатое';
                break;
            case 19:
                $day = 'Девятнадцатое';
                break;
            case 20:
                $day = 'Двадцатое';
                break;
            case 21:
                $day = 'Двадцать первое';
                break;
            case 22:
                $day = 'Двадцать второе';
                break;
            case 23:
                $day = 'Двадцать третье';
                break;
            case 24:
                $day = 'Двадцать четвертое';
                break;
            case 25:
                $day = 'Двадцать пятое';
                break;
            case 26:
                $day = 'Двадцать шестое';
                break;
            case 27:
                $day = 'Двадцать седьмое';
                break;
            case 28:
                $day = 'Двадцать восьмое';
                break;
            case 29:
                $day = 'Двадцать девятое';
                break;
            case 30:
                $day = 'Тридцатое';
                break;
            case 31:
                $day = 'Тридцать первое';
                break;
        }

        switch ($date[2]) {
            case 1:
                $year = 'первого';
                break;
            case 2:
                $year = 'второго';
                break;
            case 3:
                $year = 'третьего';
                break;
            case 4:
                $year = 'четвертого';
                break;
            case 5:
                $year = 'пятого';
                break;
            case 6:
                $year = 'шестого';
                break;
            case 7:
                $year = 'седьмого';
                break;
            case 8:
                $year = 'восьмого';
                break;
            case 9:
                $year = 'девятого';
                break;
            case 10:
                $year = 'десятого';
                break;
            case 11:
                $year = 'одиннадцатого';
                break;
            case 12:
                $year = 'двенадцатого';
                break;
            case 13:
                $year = 'тринадцатого';
                break;
            case 14:
                $year = 'четырнадцатого';
                break;
            case 15:
                $year = 'пятнадцатого';
                break;
            case 16:
                $year = 'шестнадцатого';
                break;
            case 17:
                $year = 'семнадцатого';
                break;
            case 18:
                $year = 'восемнадцатого';
                break;
            case 19:
                $year = 'девятнадцатого';
                break;
            case 20:
                $year = 'двадцатого';
                break;
            case 21:
                $year = 'двадцать первого';
                break;
            case 22:
                $year = 'двадцать второго';
                break;
            case 23:
                $year = 'двадцать третьго';
                break;
            case 24:
                $year = 'двадцать четвертого';
                break;
            case 25:
                $year = 'двадцать пятого';
                break;
            case 26:
                $year = 'двадцать шестого';
                break;
            case 27:
                $year = 'двадцать седьмого';
                break;
            case 28:
                $year = 'двадцать восьмого';
                break;
            case 29:
                $year = 'двадцать девятого';
                break;
            case 30:
                $year = 'тридцатого';
                break;
            case 31:
                $year = 'тридцать первого';
                break;
            case 32:
                $year = 'тридцать второго';
                break;
            case 33:
                $year = 'тридцать третьго';
                break;
            case 34:
                $year = 'тридцать четвертого';
                break;
            case 35:
                $year = 'тридцать пятого';
                break;
            case 36:
                $year = 'тридцать шестого';
                break;
            case 37:
                $year = 'тридцать седьмого';
                break;
            case 38:
                $year = 'тридцать восьмого';
                break;
            case 39:
                $year = 'тридцать девятого';
                break;
            case 40:
                $year = 'сорокового';
                break;
            default:
                $year = $date[2];
                break;
        }

        return $day . ' ' . $m . ', ' . 'две тысячи ' . $year . ' года';
    }
}

// Переводит численную дату в буквенную форму на узбекском языке
if (!function_exists('uzbekDateNow')) {
    function uzbekDateNow()
    {
        $date = explode(".", date("d.m.y"));
        switch ($date[1]) {
            case 1:
                $m = 'январь';
                break;
            case 2:
                $m = 'февраль';
                break;
            case 3:
                $m = 'март';
                break;
            case 4:
                $m = 'апрель';
                break;
            case 5:
                $m = 'май';
                break;
            case 6:
                $m = 'июнь';
                break;
            case 7:
                $m = 'июль';
                break;
            case 8:
                $m = 'август';
                break;
            case 9:
                $m = 'сентябрь';
                break;
            case 10:
                $m = 'октябрь';
                break;
            case 11:
                $m = 'ноябрь';
                break;
            case 12:
                $m = 'декабрь';
                break;
        }

        switch ($date[0]) {
            case 1:
                $day = 'Биринчи';
                break;
            case 2:
                $day = 'Иккинчи';
                break;
            case 3:
                $day = 'Учинчи';
                break;
            case 4:
                $day = 'Туртинчи';
                break;
            case 5:
                $day = 'Бешинчи';
                break;
            case 6:
                $day = 'Олтинчи';
                break;
            case 7:
                $day = 'Еттинчи';
                break;
            case 8:
                $day = 'Саккизинчи';
                break;
            case 9:
                $day = 'Туққизинчи';
                break;
            case 10:
                $day = 'Унинчи';
                break;
            case 11:
                $day = 'ун биринчи';
                break;
            case 12:
                $day = 'ун иккинчи';
                break;
            case 13:
                $day = 'ун учинчи';
                break;
            case 14:
                $day = 'ун туртинчи';
                break;
            case 15:
                $day = 'ун бешинчи';
                break;
            case 16:
                $day = 'ун олтинчи';
                break;
            case 17:
                $day = 'ун йеттинчи';
                break;
            case 18:
                $day = 'ун саккизинчи';
                break;
            case 19:
                $day = 'ун туққизинчи';
                break;
            case 20:
                $day = 'йигирманчи';
                break;
            case 21:
                $day = 'йигирма биринчи';
                break;
            case 22:
                $day = 'йигирма';
                break;
            case 23:
                $day = 'йигирма учинчи';
                break;
            case 24:
                $day = 'йигирма туртинчи';
                break;
            case 25:
                $day = 'йигирма бешинчи';
                break;
            case 26:
                $day = 'йигирма олтинчи';
                break;
            case 27:
                $day = 'йигирма йеттинчи';
                break;
            case 28:
                $day = 'йигирма саккизинчи';
                break;
            case 29:
                $day = 'йигирма туққизинчи';
                break;
            case 30:
                $day = 'уттизинчи';
                break;
            case 31:
                $day = 'уттиз биринчи';
                break;
        }

        switch ($date[2]) {
            case 1:
                $year = 'биринчи';
                break;
            case 2:
                $year = 'иккинчи';
                break;
            case 3:
                $year = 'учинчи';
                break;
            case 4:
                $year = 'туртинчи';
                break;
            case 5:
                $year = 'бешинчи';
                break;
            case 6:
                $year = 'олтинчи';
                break;
            case 7:
                $year = 'йеттинчи';
                break;
            case 8:
                $year = 'саккизинчи';
                break;
            case 9:
                $year = 'туққизинчи';
                break;
            case 10:
                $year = 'унинчи';
                break;
            case 11:
                $year = 'ун биринчи';
                break;
            case 12:
                $year = 'ун иккинчи';
                break;
            case 13:
                $year = 'ун учинчи';
                break;
            case 14:
                $year = 'ун туртинчи';
                break;
            case 15:
                $year = 'ун бешинчи';
                break;
            case 16:
                $year = 'ун олтинчи';
                break;
            case 17:
                $year = 'ун йеттинчи';
                break;
            case 18:
                $year = 'ун саккизинчи';
                break;
            case 19:
                $year = 'ун туққизинчи';
                break;
            case 20:
                $year = 'йигирманчи';
                break;
            case 21:
                $year = 'двадцать первого';
                break;
            case 22:
                $year = 'йигирма иккинчи';
                break;
            case 23:
                $year = 'йигирма учинчи';
                break;
            case 24:
                $year = 'йигирма туртинчи';
                break;
            case 25:
                $year = 'йигирма бешинчи';
                break;
            case 26:
                $year = 'йигирма олтинчи';
                break;
            case 27:
                $year = 'йигирма йеттинчи';
                break;
            case 28:
                $year = 'йигирма саккизинчи';
                break;
            case 29:
                $year = 'йигирма туққизинчи';
                break;
            case 30:
                $year = 'уттизинчи ';
                break;
            case 31:
                $year = 'уттиз биринчи';
                break;
            case 32:
                $year = 'уттиз иккинчи';
                break;
            case 33:
                $year = 'уттиз учинчи';
                break;
            case 34:
                $year = 'уттиз туртинчи';
                break;
            case 35:
                $year = 'уттиз бешинчи';
                break;
            case 36:
                $year = 'уттиз олтинчи';
                break;
            case 37:
                $year = 'уттиз йеттинчи';
                break;
            case 38:
                $year = 'уттиз саккизинчи';
                break;
            case 39:
                $year = 'уттиз туққизинчи';
                break;
            case 40:
                $year = 'қириқинчи';
                break;
            default:
                $year = $date[2];
                break;
        }

        return 'икки минг ' . $year . ' йил ' . $day . ' ' . $m;
    }
}


if (!function_exists('date2str')) {
    function date2str($date)
    {
        $dates = ['первое', 'второе', 'третье', 'четвертое', 'пятое', 'шестое', 'седьмое', 'восьмое', 'девятое', 'десятое', 'одиннадцатое', 'двеннадцатое', 'треннадцатое', 'четырнадцатое', 'пятнадцатое', 'шестнадцатое', 'семнадцатое', 'восемнадцатое', 'девятнадцатое', 'двадцатое', 'двадцать первое', 'двадцать второе', 'двадцать третье', 'двадцать четвертое', 'двадцать пятое', 'двадцать шестое', 'двадцать седьмое', 'двадцать восьмое', 'двадцать девятое', 'тридцатое', 'тридцать первое'];
        $months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
        $years1 = ['двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят', 'восемьдесят', 'девяносто'];
        $years2 = ['первого', 'второго', 'третьего', 'четвертого', 'пятого', 'шестого', 'сельмого', 'восьмого', 'девятого'];

        $d = date('d', $date);
        $m = date('m', $date);
        $y = substr(date('Y', $date), 3);

        $res = $dates[$d] . ' ' . $months[$m] . ' две тысячи ' . $years1[$y[2]] . ' ' . $years2[$y[3]];

        return $res;

    }
}


if (!function_exists('num2str')) {
    function num2str($inn, $stripkop = false, $forUzbek = false)
    {
        $nol = 'ноль';
        // Для перевода численных значений в буквенные для узбекского языка
        if ($forUzbek) {
            $str[100] = array('', 'бир юз', 'икки юз', 'уч юз', 'турт юз', 'беш юз', 'олти юз', 'йетти юз', 'саккиз юз', 'туққиз юз');
            $str[11] = array('', 'ун', 'ун бир', 'ун икки', 'ун уч', 'ун турт', 'ун беш', 'ун олти', 'ун йетти', 'ун саккиз', 'ун туққиз', 'йигирма');
            $str[10] = array('', 'ун', 'йигирма', 'уттиз', 'қириқ', 'эллик', 'олтмуш', 'йетмуш', 'саксон', 'туқсон');
            $sex = array(
                array('', 'бир', 'икки', 'уч', 'турт', 'беш', 'олти', 'йетти', 'саккиз', 'туққиз'),// m
                array('', 'бир', 'икки', 'уч', 'турт', 'беш', 'олти', 'йетти', 'саккиз', 'туққиз') // f
            );
            $forms = array(
                array('тийин', 'тийин', 'тийин', 1), // 10^-2
                array('сум', 'сум', 'сум', 0), // 10^ 0
                array('минг', 'минг', 'минг', 1), // 10^ 3
                array('миллион', 'миллион', 'миллион', 0), // 10^ 6
                array('миллиард', 'миллиард', 'миллиард', 0), // 10^ 9
                array('триллион', 'триллион', 'триллион', 0), // 10^12
            );
        } else {
            $str[100] = array('', 'сто', 'двести', 'триста', 'четыреста', 'пятьсот', 'шестьсот', 'семьсот', 'восемьсот', 'девятьсот');
            $str[11] = array('', 'десять', 'одиннадцать', 'двенадцать', 'тринадцать', 'четырнадцать', 'пятнадцать', 'шестнадцать', 'семнадцать', 'восемнадцать', 'девятнадцать', 'двадцать');
            $str[10] = array('', 'десять', 'двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят', 'восемьдесят', 'девяносто');
            $sex = array(
                array('', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'),// m
                array('', 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять') // f
            );
            $forms = array(
                array('тийин', 'тийин', 'тийин', 1), // 10^-2
                array('сум', 'сума', 'сумов', 0), // 10^ 0
                array('тысяча', 'тысячи', 'тысяч', 1), // 10^ 3
                array('миллион', 'миллиона', 'миллионов', 0), // 10^ 6
                array('миллиард', 'миллиарда', 'миллиардов', 0), // 10^ 9
                array('триллион', 'триллиона', 'триллионов', 0), // 10^12
            );
        }

        $out = $tmp = array();
        // Поехали!
        $tmp = explode('.', str_replace(',', '.', $inn));
        $rub = number_format($tmp[0], 0, '', '-');
        if ($rub == 0) $out[] = $nol;
        // нормализация копеек
        $kop = isset($tmp[1]) ? substr(str_pad($tmp[1], 2, '0', STR_PAD_RIGHT), 0, 2) : '00';
        $segments = explode('-', $rub);
        $offset = sizeof($segments);
        if ((int)$rub == 0) { // если 0 рублей
            $o[] = $nol;
            $o[] = morph(0, $forms[1][0], $forms[1][1], $forms[1][2]);
        } else {
            foreach ($segments as $k => $lev) {
                $sexi = (int)$forms[$offset][3]; // определяем род
                $ri = (int)$lev; // текущий сегмент
                if ($ri == 0 && $offset > 1) {// если сегмент==0 & не последний уровень(там Units)
                    $offset--;
                    continue;
                }
                // нормализация
                $ri = str_pad($ri, 3, '0', STR_PAD_LEFT);
                // получаем циферки для анализа
                $r1 = (int)substr($ri, 0, 1); //первая цифра
                $r2 = (int)substr($ri, 1, 1); //вторая
                $r3 = (int)substr($ri, 2, 1); //третья
                $r22 = (int)$r2 . $r3; //вторая и третья
                // разгребаем порядки
                if ($ri > 99) $o[] = $str[100][$r1]; // Сотни
                if ($r22 > 20) {// >20
                    $o[] = $str[10][$r2];
                    $o[] = $sex[$sexi][$r3];
                } else { // <=20
                    if ($r22 > 9) $o[] = $str[11][$r22 - 9]; // 10-20
                    elseif ($r22 > 0) $o[] = $sex[$sexi][$r3]; // 1-9
                }
                // Рубли
                $o[] = morph($ri, $forms[$offset][0], $forms[$offset][1], $forms[$offset][2]);
                $offset--;
            }
        }
        // Копейки
        if (!$stripkop) {
            $inn * 1000 % 10 >= 5 ? $kop++ : ''; // Если копейки округлились в большую сторону, например 75.6 стало 76, то добавляем 1, т.к. мы тут получаем (int)75.6, то есть 75
            $o[] = str_replace(['сумов', 'сума', 'сум'], '', num2str($kop, true, $forUzbek));
            $o[] = morph($kop, $forms[0][0], $forms[0][1], $forms[0][2]);
        }
        return preg_replace("/\s{2,}/", ' ', implode(' ', $o));
    }
}

if (!function_exists('callCenterNumber')) {
    function callCenterNumber(int $type): string
    {
        $call_center = config('test.help_phone', "+998 (78) 777 1515");  //"+998 (78) 777 1515"
        $call_center_phone_number = $call_center;
        if ($type === 1) {
            $call_center_phone_number = "787771515";
        }
        if ($type === 2) {
            $call_center_phone_number = "78 7771515";
        }
        if ($type === 3) {
            $call_center_phone_number = "(78) 777-15-15";
        }
        if ($type === 4) {
            $call_center_phone_number = "+998 78 777 1515";
        }
        if ($type === 5) {
            $call_center_phone_number = "(78) 777 15 15";
        }

        return $call_center_phone_number;
    }
}


if (!function_exists('morph')) {
    /**
     * Склоняем словоформу
     */
    function morph($n, $f1, $f2, $f5)
    {
        $n = abs($n) % 100;
        $n1 = $n % 10;
        if ($n > 10 && $n < 20) return $f5;
        if ($n1 > 1 && $n1 < 5) return $f2;
        if ($n1 == 1) return $f1;
        return $f5;
    }
}


if (!function_exists('report_filter')) {

    function report_filter(&$query, $filterBy = false)
    {
        $request = request();

        if ($query) {

            $sql = $query->toSql();

            if (!$filterBy) {

                $created_at = 'created_at';
                // проверка на использование таблицы order_products

                if (strpos($sql, 'order_products') > 0) {
                    $created_at = 'order_products.created_at';
                }

            } else {

                $created_at = $filterBy;
            }
        }

        switch ($request->type) {

            case 'custom':

                if (is_array($request->date)) {

                    $date_from = $request->date[0];
                    $date_to = $request->date[1];

                } else {

                    list($date_from, $date_to) = explode(',', $request->date);
                }

                if (!empty($date_from)) {

                    $date_from = date('Y-m-d 00:00:00', strtotime($date_from));
                }

                if (!empty($date_to)) {

                    $date_to = date('Y-m-d 23:59:59', strtotime($date_to));
                }

                if ($query && !is_null($date_from) && !is_null($date_to)) {
                    $query->whereBetween($created_at, [$date_from, $date_to]); // confirmed_at - дата подтверждения ??
                }

                break;

            case 'last_7_days': // за последние 7 дней

                $date_from = date('Y-m-d', strtotime('-6 days'));
                $date_to = date('Y-m-d');

                if ($query) $query->whereBetween($created_at, [$date_from, $date_to]); // confirmed_at - дата подтверждения ??

                break;

            case 'last_week': // за неделю

                $w = date('w');

                if ($w == 0) {

                    $dt = 6;

                } else {

                    $dt = $w - 1;
                }

                $date_from = date('Y-m-d 00:00:00', strtotime('-' . $dt . ' days'));
                $date_to = date('Y-m-d 23:59:59');

                if ($query) $query->whereBetween($created_at, [$date_from, $date_to]); // confirmed_at - дата подтверждения ??

                break;

            case 'last_month': // за месяц

                $m = date('m');
                $date_from = date('Y-' . $m . '-01 00:00:00');
                $date_to = date('Y-m-d 23:59:59');

                if ($query) $query->whereBetween($created_at, [$date_from, $date_to]); // confirmed_at - дата подтверждения ??

                break;

            case 'last_half_year': // за полгода

                $date_from = date('Y-m-d H:i:s', strtotime('-6 months'));
                $date_to = date('Y-m-d 23:59:59');

                if ($query) $query->whereBetween($created_at, [$date_from, $date_to]); // confirmed_at - дата подтверждения ??

                break;

            case 'last_day': // текущий день

            default:

                $date_from = date('Y-m-d 00:00:00', time());
                $date_to = date('Y-m-d 23:59:59', time());

                if ($query) $query->whereBetween($created_at, [$date_from, $date_to]); // confirmed_at - дата подтверждения ??
        }

        Log::channel('report')->info($request);

        if ($query) Log::channel('report')->info($query->toSql());
        if ($query) Log::channel('report')->info($query->getBindings());

        return ['date_from' => $date_from, 'date_to' => $date_to];
    }

    if (!function_exists('system_dump')) {
        function system_dump($exception)
        {

            $error_msg = $exception->getMessage();
            $em = explode('updated_at', $error_msg);

            $error_hash = md5($em[0]);

            if (!Redis::exists($error_hash) && $error_msg) {

                $msg = '';
                foreach ($exception->getTrace() as $e) {
                    if (!isset($e['file']) || !isset($e['function']) || !isset($e['line'])) continue;
                    //if (!strpos($e['file'], 'cabinet.test.uz')>0) continue;
                    //if (!strpos($e['file'], 'dev.test.uz')>0) continue;
                    //if (!strpos($e['file'], 'OpenServer538')) continue;
                    if (strpos($e['function'], 'upload') > 0 && strpos($e['file'], 'FileHelper.php') > 0) continue;
                    if (strpos($e['file'], 'index.php') > 0) continue;
                    if (strpos($e['file'], 'vendor') > 0) continue;
                    if (strpos($e['function'], 'lluminate') > 0) continue;
                    // $file = explode('test',$e['file']);
                    // $file = explode('www',$e['file']);
                    $msg .= '<b>File:</b> ' . $e['file'] . "\n<b>Line:</b> " . $e['line'] . "\n<b>Function:</b> " . $e['function'] . "\n";
                }

                if ($msg != '') {

                    $msg .= '<b>UserID:</b> ' . Auth::id() . "\n" . $error_msg;

                    Redis::set($error_hash, 1);
                    Redis::expire($error_hash, 3200);

                    TelegramHelper::sendByChatId('1726060082', $msg);
                }

            }
        }
    }

    if (!function_exists('partner_phone_short')) {
        function partner_phone_short($phone)
        {
            return mb_substr(correct_phone($phone), 3, 9);
        }
    }

    if (!function_exists('partner_phone')) {
        function partner_phone($phone)
        {
            return '998' . $phone;
        }
    }

    if (!function_exists('currency_format')) {
        function currency_format(float $amount)
        {
            return number_format($amount, 2, '.', ' ');
        }
    }

    if (!function_exists('formatToLangVariable')) {
        function formatToLangVariable(string $response)
        {
            return str_replace(' ', '_', strtolower(trim($response)));
        }
    }

    if (!function_exists('get_value_if_set')) {
        function get_value_if_set($value)
        {
            return isset($value) && $value ? $value : null;
        }
    }

}

