<?php


namespace App\Helpers;


use App\Models\BuyerSallaries;
use App\Models\BuyerPersonal;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Request;

class SallaryHelpers
{

    const login = 'solutions';
    const password = '7SYhA[~t{VWNZF@B';

    // получение зп
    public static function getSallary(Request $request){


        Log::channel('katm')->info('getSallary');
        $options['data'] = [
            "tin"=>"",
            "pinfl"=>$request->pinfl ?? null,
            "number_passport"=> $request->passport ?? null,
            "series_passport"=> $request->serial ?? null,
            "lang"=>"uz"
        ];

        Log::channel('katm')->info($options);

        if(!isset($options['data']['pinfl']) && (!isset($options['data']['number_passport']) && !isset($options['data']['series_passport'])) ){
            $error = 'pinfl or passport number|serial not set!';
            Log::info($error);
            return ['status'=>'error','info'=>$error];

        }

        $options['url'] = 'https://ws.soliqservis.uz/gnk/data/fiz-salary';
        $options['method'] = 'POST';
        $options['basic'] = true;
        $options['login'] = self::login;
        $options['password'] = self::password;

        $result = \App\Helpers\CurlHelper::send($options,true);

        $res = $result['data'];

        Log::channel('katm')->info('result sallary');
        Log::channel('katm')->info($result);

        $buyer_personal = BuyerPersonal::where('pinfl_hash',md5($request->pinfl))->first();

        if(!$buyer_sallaries = BuyerSallaries::where('user_id',$buyer_personal->user_id)->first()){
            $buyer_sallaries = new BuyerSallaries();
            $buyer_sallaries->user_id = $buyer_personal->user_id;
        }
        $buyer_sallaries->request = EncryptHelper::encryptData(json_encode($options['data'],JSON_UNESCAPED_UNICODE));

        if(isset($res['data'])) {

            $year = array_column($res['data'], 'year');
            $period = array_column($res['data'], 'period');

            // Сортируем данные по возрастанию
            array_multisort($year, SORT_DESC, $period, SORT_DESC, $res['data']);

            if($buyer_personal){

                $buyer_sallaries->response = json_encode($res,JSON_UNESCAPED_UNICODE);
                $buyer_sallaries->save();

            }

            return ['status'=>'success','data'=> $res['data'] ];

        }else{

            if($buyer_personal){

                $buyer_sallaries->response = json_encode($result,JSON_UNESCAPED_UNICODE);
                $buyer_sallaries->save();

            }

        }


        return ['status'=>'error','info'=> 'pinfl not found!'];

    }

    // скоринг по зп
    public static function scoringSallary($data=null){

        if($data){

            $year  = array_column($data, 'year');
            $period = array_column($data, 'period');

            // Сортируем данные по возрастанию
            array_multisort($year, SORT_DESC, $period, SORT_DESC, $data);


            Log::channel('katm')->info('scoring salary data');
            Log::channel('katm')->info($data);


            $items = [];
            foreach ($data as $item) {
                if(!isset($items[ $item['year'].'.'.$item['period'] ])) $items[ $item['year'].'.'.$item['period'] ] = 0;
                $items[ $item['year'].'.'.$item['period'] ] += $item['salary'] - $item['salary_tax_sum'];
            }

            Log::channel('katm')->info('scoring sallary correct data');
            Log::channel('katm')->info($items);

            $sum = [
                1 => 0,
                2 => 0,
                3 => 0,
                4 => 0,
                5 => 0,
                6 => 0
            ];

            $s = 0;

            $m=0;
            foreach ($items as $summ){
                $m++;

                // проверка , если сумма соответствует минимальной, то учитывать данный месяц
                if( $summ < 350000 ){
                    continue; // пропускаем, сумма за последний месяц меньше допустимой
                }

                // сумма передается в сумм
                $sum[1] += (int)($summ >= 350000);  // для 1 млн
                $sum[2] += (int)($summ >= 750000);  // для 3 млн
                $sum[3] += (int)($summ >= 2000000);  // для 6 млн
                $sum[4] += (int)($summ >= 3000000);  // для 9 млн
                $sum[5] += (int)($summ >= 4000000);  // для 12 млн
                $sum[6] += (int)($summ >= 5000000);  // для 15 млн

                $s += $summ; // общая сумма
                if($m>=6) break;

            }
            Log::channel('katm')->info('кол-во итераций: ' . $m . "\nсумма: " . $s);
            Log::channel('katm')->info('баллы скоринга налоговой:');
            $res = '';
            $limit = [1=>'1M',2=>'3M',3=>'6M',4=>'9M',5=>'12M',6=>'15M'];
            foreach ($sum as $k=>$item){
                $res .= $limit[$k] . ': ' . $item . "\n";
            }
            Log::channel('katm')->info($res);

            if ($sum[6] > 4 && $s>= 15000000) return ['scoring'=>15000000,'ball'=>$sum[6]];
            if ($sum[5] > 4 && $s>= 12000000) return ['scoring'=>12000000,'ball'=>$sum[5]];
            if ($sum[4] > 4 && $s>= 9000000 ) return ['scoring'=>9000000,'ball'=>$sum[4]];
            if ($sum[3] > 4 && $s>= 6000000 ) return ['scoring'=>6000000,'ball'=>$sum[3]];
            if ($sum[2] > 4 && $s>= 5000000 ) return ['scoring'=>3000000,'ball'=>$sum[2]];
            if ($sum[1] > 4 && $s>= 2000000 ) return ['scoring'=>1000000,'ball'=>$sum[1]];

        }

        return ['scoring'=>0,'ball'=>0];

    }



}
