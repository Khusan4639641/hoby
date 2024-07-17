<?php
namespace App\Helpers;

use Carbon\Carbon;
use Log;

class MRZHelper{

    const HASHES = [7,3,1];

    //общая запись
    private static $all_records = [];
    private static $mrz = '';

    //числовое представление
    const CHARS_IN_NUMBER = [
        'A' => 10,
        'B' => 11,
        'C' => 12,
        'D' => 13,
        'E' => 14,
        'F' => 15,
        'G' => 16,
        'H' => 17,
        'I' => 18,
        'J' => 19,
        'K' => 20,
        'L' => 21,
        'M' => 22,
        'N' => 23,
        'O' => 24,
        'P' => 25,
        'Q' => 26,
        'R' => 27,
        'S' => 28,
        'T' => 29,
        'U' => 30,
        'V' => 31,
        'W' => 32,
        'X' => 33,
        'Y' => 34,
        'Z' => 35,
    ];

    public static function getMrz(string $seria_number,string $birthday,string $date_of_expire,int $gender,string $pinfl)
    {
        try {
            //clear static data before starting
            self::$mrz = '';
            self::$all_records = [];
            //Passport seria and number
            $document_number = self::getDocumentNumber($seria_number);
            self::$mrz .= strtoupper($seria_number).self::getHash($document_number);;
            //Country code
            self::$mrz .= 'UZB';
            //Birthday
            $reversed_birthday = self::reverseDate($birthday);
            self::$mrz .= $reversed_birthday.self::getHash(str_split($reversed_birthday));
            //Gender
            self::$mrz .= self::getGender($gender);
            //Passport expire
            $reversed_date_of_expire = self::reverseDate($date_of_expire);
            self::$mrz .= $reversed_date_of_expire.self::getHash(str_split($reversed_date_of_expire));
            //PINFL
            self::$mrz .= $pinfl.self::getHash(str_split($pinfl));
            //общая запись
            self::$mrz .= self::getHash(self::$all_records);
            if(strlen(self::$mrz) == 44){
                return self::$mrz;
            }
            return false;
        } catch (\Throwable $th) {
            Log::info('MRZHelper::getMrz request: '.'seria_number '.$seria_number.' birthday '.$birthday.' date_of_expire '.$date_of_expire.' gender '.$gender.' pinfl '.$pinfl);
            Log::info('MRZHelper::getMrz error: '.$th->getMessage());
            return false;
        }
    }

    public static function getHash(array $array)
    {
        /** 
         * $string = 860724
         * $str_to_array = [7 => [8,7],3 => [6,2],1 => [0,4]]
         * $total = 133
         * $remainder = 3
        */
        $total = 0;
        $str_to_array = array_chunk($array,count(self::HASHES));
        foreach($str_to_array as $key => $array){
            foreach($array as $k => $value){
                if(is_numeric($value)){
                    $total += self::HASHES[$k] * $value;
                    array_push(self::$all_records,(int)$value);
                }
            }
        }
        $remainder = $total % 10;
        array_push(self::$all_records,(int)$remainder);
        return $remainder;
    }

    public static function getDocumentNumber(string $seria_number) : array
    {
        //KA1202682
        $result = [];
        foreach(str_split($seria_number) as $seria){
            if(ctype_alpha($seria)){
                $result[] = array_key_exists(strtoupper($seria),self::CHARS_IN_NUMBER) ? self::CHARS_IN_NUMBER[strtoupper($seria)] : '';
            }else{
                $result[] = (int)$seria;
            }
        }
        //[20,10,1,2,0,2,6,8,2]
        return $result;
    }

    public static function reverseDate(string $date)
    {
        $is_valid_date = self::isValidDate($date);
        if($is_valid_date){
            $year = mb_substr($date,8,2);
            $month =  mb_substr($date,3,2);
            $day =  mb_substr($date,0,2);
            return $year.$month.$day;
        }
        return '';
    }

    public static function getGender(int $number)
    {
        switch($number){
            case 1:
                $gender = 'M';
                break;
            case 2:
                $gender = 'F';
                break;
            default:
                $gender = '';
        }
        return $gender;
    }

    public static function isValidDate(string $date)
    {
        try {
            Carbon::parse($date);
            return true;
        } catch (\Throwable $th) {
            Log::info('MRZHelper isValidDate error: '.$th->getMessage());
            return false;
        }
    }
}