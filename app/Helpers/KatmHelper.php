<?php

namespace App\Helpers;

use App\Facades\GradeScoring;
use App\Facades\OldCrypt;
use App\Models\Buyer;
use App\Models\BuyerAddress;
use App\Models\BuyerPersonal;
use App\Models\BuyerSallaries;
use App\Models\CardScoringLog;
use App\Models\KatmInfoscore;
use App\Models\KatmMib;
use App\Models\KatmScoring;
use App\Models\KycHistory;
use App\Models\Setting;
use App\Classes\Scoring\KatmScoringData;
use App\Classes\Scoring\ScoreCalculate;
use App\Classes\Scoring\ScoringData;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Extensions\ExSoapClient;
use Illuminate\Support\Str;

class KatmHelper{

    /*
    KATM_APIURL=http://192.168.1.143:8001/katm-api/v1/claim/registration
    KATM_APIURL_MANUAL=http://192.168.1.143:8001/katm-api/v1/claim/registration/ext
    KATM_APIURL2=http://192.168.1.143:8001/katm-api/v1/credit/report
    KATM_APIURL3=http://192.168.1.143:8001/katm-api/v1/credit/report/status
    KATM_APIURL_ADDRESS=http://192.168.1.143:8001/katm-api/v1/client/address
    KATM_CANCEL=http://192.168.1.143:8001/katm-api/v1/claim/rejection
    * */


    public static function config(){
        $config = [
            'apiurl' => config('test.katm_apiurl'),
            'apiurl_manual' => config('test.katm_apiurl_manual'),
            'apiurl2' => config('test.katm_apiurl2'),
            'apiurl3' => config('test.katm_apiurl3'),
            'apiurl_cancel' => config('test.katm_cancel'),
            'apiurl_address' => config('test.katm_apiurl_address'),
            'login' => OldCrypt::decryptString(config('test.katm_login')),
            'password' => OldCrypt::decryptString(config('test.katm_password')),
            'pcode' => config('test.katm_pcode'),
            'phead' => config('test.katm_phead'),
        ];
        return $config;
    }

    public static function registerKatm($user, $request){
        Log::channel('katm')->info('Log start katm');
        Log::channel('katm')->info($request);

        $katm = KatmScoring::where('user_id', $user->id)->where('status',0)->first();

        $claimId = mb_substr($user->id . md5('pm-' . time()), 0, 20);
        $agreeId = mb_substr($claimId, 0, 10);

        $report_id = 23; //rand(1,2555);
        $t = microtime(true);
        $micro = sprintf("%03d",($t - floor($t)) * 1000);
        $utc = gmdate('Y-m-d\TH:i:s.', $t).$micro.'Z';
        $passport = $request->passport ?? EncryptHelper::decryptData($user->personals->passport_number);

        // убрать пробел
        $passport = preg_replace('/\s/','', $passport );

        $passport_type = $user->personals->passport_type;

        $pinfl = $request->pinfl ?? EncryptHelper::decryptData($user->personals->pinfl);
        $passportSerial = mb_substr($passport, 0, 2);
        $passportNumber = mb_substr($passport, 2, mb_strlen($passport));
        $address = $user->addressResidential->string ?? 'address';
        if($address=='') $address = 'address';
        $phone = $user->phone;

        $config = self::config();

        $katm_type = $request->katm_method; // auto/manual

        //dd($request);
        if($katm_type=='manual') { // РЕГИСТРАЦИЯ РУЧНОЙ ЗАЯВКИ KATM

            //$utc_issue_date = gmdate('Y-m-d\TH:i:s.', strtotime($request->issue_date)).'000Z';
           // $utc_exp_date = gmdate('Y-m-d\TH:i:s.', strtotime($request->exp_date)).'000Z';
            //$utc_birth_date = gmdate('Y-m-d\TH:i:s.', strtotime($request->birth_date)).'000Z';

            $mrz_head = 'P<UZB'.$request->first_name .'<<'.$request->last_name;
            $mrz_head .= str_repeat('<',44-strlen($mrz_head));
            $mrz = strtoupper($mrz_head . $request->mrz);


            $send = json_encode([
                'data' => [
                    'pAddress' => $address, //Адрес клиента
                    'pAgreementDate' => $utc, //Дата согласия клиента(yyyy-MM-dd'T'HH:mm:ss.SSSZ)
                    'pAgreementId' => $agreeId, //Уникальный код согласия
                    'pClaimDate' => $utc, //Дата заявки(yyyy-MM-dd'T'HH:mm:ss.SSSZ)
                    'pClaimId' => $claimId, //Уникальный ID заявки
                    'pCode' => $config['pcode'], //Код организации
                    'pCreditAmount' => 0,
                    'pCreditEndDate' => $utc, //Дата завершения кредита (yyyy-MM-dd'T'HH:mm:ss.SSSZ)
                    'pCurrency' => '860',     //Код валюты
                    //'pDocNumber' => $passportNumber, //$user['passport_id'], //Номер паспорта клиента
                   // 'pDocSeries' => $passportSerial, //$user['passport_serial'], //Серия паспорта клиента
                    'pDocType' => $passport_type, // $passport_type, //Тип документа(0-ID карта, 6-Биометрический паспорт)
                    'pIsUpdate' => 0,     //Флаг обновления данных (0-по умолчанию,1-обновление)
                    'pLocalRegion' => (int)$request->local_region_id, //Код района
                    'pPhone' => $phone,       //Телефон клиента
                    //'pPinfl' => $pinfl, //ПИНФЛ код клиента
                    'pRegion' => (int)$request->region_id,    //Код региона
                    //'pInn' => $request->inn,
                    'pFirstName' => $request->first_name,
                    'pLastName' => $request->last_name,
                    'pMiddleName' => $request->patronymic,
                   // 'pBirthDate' => $utc_birth_date,
                    //'pIssueDocDate' => $utc_issue_date,
                    //'pExpireDocDate' => $utc_exp_date,
                    //'pDocType' => 6,
                    //'pMale' => $request->male,
                    'pMrz' => $mrz,

                ],
                'security' => [
                    'pLogin' => $config['login'], // Логин, предоставляется кредитным бюро
                    'pPassword' => $config['password'], //Пароль, предоставляется кредитным бюро
                ],

            ]);
            $katm_url = $config['apiurl_manual'];

            $user->personals->mrz = $request->mrz;
            $user->personals->save();

            Log::channel('katm')->info('KATM MRZ: ' . $mrz . ' ' . strlen($mrz));


        }else{ // авто

            // РЕГИСТРАЦИЯ АВТОМАТИЧЕСКОЙ ЗАЯВКИ KATM
            $send = json_encode([
                'data' => [
                    'pAddress' => $address, //Адрес клиента
                    'pAgreementDate' => $utc, //Дата согласия клиента(yyyy-MM-dd'T'HH:mm:ss.SSSZ)
                    'pAgreementId' => $agreeId, //Уникальный код согласия
                    'pClaimDate' => $utc, //Дата заявки(yyyy-MM-dd'T'HH:mm:ss.SSSZ)
                    'pClaimId' => $claimId, //Уникальный ID заявки
                    'pCode' => $config['pcode'], //Код организации
                    'pCreditAmount' => 0,
                    'pCreditEndDate' => $utc, //Дата завершения кредита (yyyy-MM-dd'T'HH:mm:ss.SSSZ)
                    'pCurrency' => '860',     //Код валюты
                    'pDocNumber' => $passportNumber, //$user['passport_id'], //Номер паспорта клиента
                    'pDocSeries' => $passportSerial, //$user['passport_serial'], //Серия паспорта клиента
                    'pDocType' => $passport_type, // $passport_type, //Тип документа(0-ID карта, 6-Биометрический паспорт)
                    'pIsUpdate' => 0,     //Флаг обновления данных (0-по умолчанию,1-обновление)
                    'pLocalRegion' => (int)$request->local_region_id, //Код района
                    'pPhone' => $phone,       //Телефон клиента
                    'pPinfl' => $pinfl, //ПИНФЛ код клиента
                    'pRegion' => (int)$request->region_id,    //Код региона
                ],
                'security' => [
                    'pLogin' => $config['login'], // Логин, предоставляется кредитным бюро
                    'pPassword' => $config['password'], //Пароль, предоставляется кредитным бюро
                ],

            ]);
            $katm_url = $config['apiurl'];

        }


        Log::channel('katm')->info('KATM-url: ' . $katm_url);
        Log::channel('katm')->info('sending data KATM-METHOD: ' . $katm_type);
        Log::channel('katm')->info($send);
        $curl = curl_init($katm_url);


        //Log::channel('katm')->info( 'KATM-REQUEST');
        //Log::channel('katm')->info(var_export($send,1));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $send);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $curl_result = curl_exec($curl);
        Log::channel('katm')->info( 'KATM-RESULT');
        Log::channel('katm')->info( var_export($curl_result,1));
        curl_close($curl);

        $res = json_decode($curl_result,true);

        if (isset($res['data']) && $res['data']['result'] == '00004') { // МИП

            Log::channel('katm')->info('ERROR 00004');

            Log::channel('katm')->info('Обработка от катм: МИП данные не найдены!');

            // мип данные не найдены
            $katm = $katm ?? new KatmScoring();
            $katm->user_id = $user->id;
            $katm->claim_id = $claimId;
            $katm->request = $send;
            $katm->response = null;
            $katm->status = 2;
            $katm->token = null; //$res['data']['token'];
            $katm->save();

            $msg = "Hurmatli mijoz, siz resusNasiya platformasida ro'yxatdan o'tmadingiz. Tel: " . callCenterNumber(2);
            SmsHelper::sendSms($user->phone, $msg);
            Log::channel('katm')->info($msg);

            User::changeStatus($user,User::KYC_STATUS_BLOCKED);

            return '00004';

        }

        if (isset($res['data']) && isset($res['code']) && $res['data'] == '' && $res['code'] == 500 ) { // МИП ошибка при получении

            Log::channel('katm')->info('ERROR 500 МИП');

            Log::channel('katm')->info('Обработка от катм: ошибка при получении МИП!');

            // мип данные не найдены
            $katm = $katm ?? new KatmScoring();
            $katm->user_id = $user->id;
            $katm->claim_id = $claimId;
            $katm->request = $send;
            $katm->response = null;
            $katm->status = 2;
            $katm->token = null; //$res['data']['token'];
            $katm->save();

            //$msg = "Vy ne proshli registraciyu na platforme test. Siz test platformasida ro'yxatdan o'tmadingiz.";
            //SmsHelper::sendSms($user->phone, $msg);
            //Log::channel('katm')->info($msg);
            User::changeStatus($user,User::KYC_STATUS_BLOCKED);

            return '0000';

        }

        $req = new Request();
        $req->merge([
            'report_id' => 39,
            'buyer_id' => $user->id,
            'claim_id' => $claimId,
        ]);

        $result = KatmHelper::sendReportMib($req);

        if( $result['status'] != 'success' ){

            Log::channel('katm' )->info( 'ERROR MIB FOR ' . $user->id );
            Log::channel('katm' )->info( $result );

            User::changeStatus($user,User::KYC_STATUS_BLOCKED );
            KycHistory::insertHistory($user->id,User::KYC_STATUS_BLOCKED, User::KYC_STATUS_SCORING_MIB_BLOCKED);


        }

        //ПОДАЧА ЗАЯВКИ НА ОТЧЕТ
        $sendReport = json_encode([
            'data' => [
                'pCode' => $config['pcode'], //Код организации,
                'pHead' => $config['phead'],
                'pLegal' => 1,
                'pClaimId' => $claimId, //Уникальный ID заявки
                'pQuarter' => 0,
                'pReportFormat' => 1,
                'pReportId' => $report_id, //23,
                'pYear' => 0

            ],
            'security' => [
                'pLogin' => $config['login'], // Логин, предоставляется кредитным бюро
                'pPassword' => $config['password'] //Пароль, предоставляется кредитным бюро
            ],

        ]);

        Log::channel('katm')->info(var_export($sendReport,1));

        $curl = curl_init($config['apiurl2']);

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $sendReport);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $curl_result = curl_exec($curl);

        Log::channel('katm')->info(var_export($curl_result,1));

        curl_close($curl);

        $curlJson = json_decode($curl_result, true);

        Log::channel('katm')->info('Log end katm');

        $arr2 = base64_decode($curlJson['data']['reportBase64']);
        $info = json_decode($arr2,true);



        // ------
        if(isset($info['report'])) {
            if(isset($info['report']['client'])) {
                $clientInfo = $info['report']['client'];
                //$user = Buyer::find($user_id);
                // результат обработки информации
                // $user->status = $katm->katm_status==1 ? 1 : 0;

                $fio = explode(" ", $clientInfo['name']);

                $ogly = isset($fio[3]) ? ' ' . upFirstLetter($fio[3]) : ''; // если есть

                $name = upFirstLetter($fio[0]);
                $surname = upFirstLetter($fio[1]);
                $patronymic = upFirstLetter($fio[2]) . $ogly;

                // сохраняем фио, др, Дата выдачи документа
                $user->name = $name ?? null;
                $user->surname = $surname ?? null;
                $user->patronymic = $patronymic ?? null;

                $gender = null;
                if(isset($clientInfo['gender'])){
                    if($clientInfo['gender']=='Ж'){
                        $gender = 2;
                    }elseif($clientInfo['gender']=='М'){
                        $gender = 1;
                    }
                }
                $user->gender = $gender;
                $user->birth_date = date('Y-m-d',strtotime($clientInfo['birth_date']));
                $user->region = $clientInfo['region'];
                $user->local_region = $clientInfo['local_region'];

                $user->save();
                // -------------------------
            }

        }

        //СОХРАНЕНИЕ ОТЧЕТА В БАЗУ
        if($curlJson['data']['result'] == '05000') {

            $katm = $katm ?? new KatmScoring();
            $katm->user_id = $user->id;
            $katm->request = $sendReport;
            $katm->response = $arr2; // @$curlJson['data']['reportBase64'];
            $katm->claim_id = $claimId;
            $katm->status = 1;
            $katm->token = $curlJson['data']['token'];
            $katm->save();

            KatmScoring::where('user_id', $user->id)->where('status',1)->delete();

            return '05000';


        }elseif($curlJson['data']['result'] == '05050') { // await

            //СОХРАНЕНИЕ ТОКЕНА ДЛЯ ПРОВЕРКИ ОТЧЕТА
            $katm = $katm ?? new KatmScoring();
            $katm->user_id = $user->id;
            $katm->claim_id = $claimId;
            $katm->request = $sendReport;
            $katm->response = null;
            $katm->status = 0;
            $katm->token = $curlJson['data']['token'];
            $katm->save();

            KatmScoring::where('user_id', $user->id)->where('status',1)->delete();

            return '05050';
        }elseif($curlJson['data']['result'] == '05002'){

            $katm = $katm ?? new KatmScoring();
            $katm->user_id = $user->id;
            $katm->claim_id = $claimId;
            $katm->request = $sendReport;
            $katm->response = null;
            $katm->status = 2;
            $katm->token = $curlJson['data']['token'];
            $katm->save();

            return '05002';



        }else{ // ошибка в КАТМ

            Log::channel('katm')->info($curlJson);

            $katm = $katm ?? new KatmScoring();
            $katm->user_id = $user->id;
            $katm->claim_id = $claimId;
            $katm->request = $sendReport;
            $katm->response = null;
            $katm->status = 2;
            $katm->token = $curlJson['data']['token'];
            $katm->save();

            if(isset($curlJson['code'])){
                return $curlJson['code']; // 19.05 - здесь возвращаем коды ошибок
            }

            return 0;

        }

    }

    public static function getKatm($config, $user) {

        // ПРОВЕРКА И ПОЛУЧЕНИЕ ОТЧЕТА

        if(is_numeric($user)){
            $user_id = $user;
            $user = Buyer::find($user_id);
        }else{
            $user_id = $user->id;
        }

        $katm = KatmScoring::where('user_id', $user_id)->where('status', 0)->first();

        if($katm != null) {
            $getReport = json_encode([
                'data' => [
                    'pHead' => $config['phead'], //Код банка
                    'pCode' => $config['pcode'], //МФО БАНКА
                    'pToken' => $katm->token,
                    'pClaimId' => $katm->claim_id,
                    'pReportFormat' => 1,
                ],
                'security' => [
                    'pLogin' => $config['login'], // Логин, предоставляется кредитным бюро
                    'pPassword' => $config['password'] //Пароль, предоставляется кредитным бюро
                ],
            ]);
            Log::channel('katm')->info(var_export($getReport, 1));
            $curl = curl_init($config['apiurl3']);

            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $getReport);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $curlEx = curl_exec($curl);
            Log::channel('katm')->info(var_export($curlEx, 1));
            curl_close($curl);
            $arr = json_decode($curlEx, true);
            $arr2 = base64_decode($arr['data']['reportBase64']);

            $info = json_decode($arr2,true);

            if(isset($info['report'])) {
                if(isset($info['report']['client'])) {
                    $clientInfo = $info['report']['client'];
                    //$user = Buyer::find($user_id);

                    $fio = explode(" ", $clientInfo['name']);

                    $ogly = !empty($fio[3]) ? ' ' . upFirstLetter($fio[3]) : ''; // если есть

                    $name = upFirstLetter($fio[0]);
                    $surname = upFirstLetter($fio[1]);
                    $patronymic = upFirstLetter($fio[2]) . $ogly;

                    // сохраняем фио, др, Дата выдачи документа
                    $user->name = $name ?? null;
                    $user->surname = $surname ?? null;
                    $user->patronymic = $patronymic ?? null;

                    $gender = null;
                    if(isset($clientInfo['gender'])){
                        if($clientInfo['gender']=='Ж'){
                            $gender = 2;
                        }elseif($clientInfo['gender']=='М'){
                            $gender = 1;
                        }
                    }
                    $user->gender = $gender;
                    $user->birth_date = date('Y-m-d',strtotime($clientInfo['birth_date']));
                    $user->region = $clientInfo['region'];
                    $user->local_region = $clientInfo['local_region'];

                    $user->save();
                    // -------------------------
                }

            }

            // успешный запрос
            if ($arr['data']['result'] == '05000') {

                //СОХРАНЕНИЕ ОТЧЕТА В БАЗУ

                /** ** *
                'client' =>
                array (
                'duplicates' => true,
                'document_number' => 8144058,
                'address' => 'г. Ташкент, Юнусабадский район, р-н, 11 квартал, дом 20. кв 31',
                'subject' => 2,
                'birth_date' => '02.06.1990',
                'inn' => 517519490,
                'document_date' => '30.12.2014',
                'client_type' => '08',
                'document_serial' => 'AA',
                'local_region' => 1,
                'katm_sir' => 'P2003720201126091762',
                'old_name' => '',
                'phone' => 998990050400,
                'nibbd' => '',
                'name' => 'ABRALOV AZAMAT ASQAR O‘G‘LI',
                'region' => 3,
                'name_change' => '',
                'document_type' => 6,
                ),
                 * */

                Log::channel('katm')->info('SUCCESS 05000');


                $katm->user_id = $user_id;
                $katm->request = $getReport;
                $katm->response = $arr2;
                $katm->status = 1;

                $katm->save();


                return '05000';

            } else if ($arr['data']['result'] == '05004') {

                Log::channel('katm')->info('ERROR 05004');
                // unknown error

                User::changeStatus($user,User::KYC_STATUS_BLOCKED);

                return '05004';
            } else if ($arr['data']['result'] == '05002') {

                Log::channel('katm')->info('ERROR 05002');
                //'katm.error_empty_required_field';

                $katm->user_id = $user_id;
                $katm->request = $getReport;
                $katm->response = $arr2;
                $katm->status = 2;

                $katm->save();

                User::changeStatus($user,User::KYC_STATUS_BLOCKED);


                return '05002';
            } else if ($arr['data']['result'] == '00004') {

                Log::channel('katm')->info('ERROR 00004');

                Log::channel('katm')->info('Обработка от катм: МИП данные не найдены!');
                Log::channel('katm')->info($arr);

                // мип данные не найдены
                $katm->user_id = $user_id;
                $katm->request = $getReport;
                $katm->response = $arr2;
                $katm->status = 2;

                $katm->save();

                $msg = "Hurmatli mijoz, siz resusNasiya platformasida ro'yxatdan o'tmadingiz. Tel: " . callCenterNumber(2);
                SmsHelper::sendSms($user->phone, $msg);

                Log::channel('katm')->info($msg);

                User::changeStatus($user,User::KYC_STATUS_BLOCKED);


                return 0;
            } else if ($arr['data']['result'] == '05050') {

                // await - Ожидание

                return '05050';
            } else {
                // др ошибки

                Log::channel('katm')->info('ERROR UNKNOWN');

                $katm->user_id = $user_id;
                $katm->request = $getReport;
                $katm->response = $arr2;
                $katm->status = 2;
                $katm->save();

                User::changeStatus($user,User::KYC_STATUS_BLOCKED);

                return 0;
            }

        }

        return 0;

    }

    // адрес клиента
    public static function getClientAddress($pnfl) {


            $input = json_encode([
                'pPin' => $pnfl,
            ]);

            $config = KatmHelper::config();

            $curl = curl_init($config['apiurl_address']);

            // $curl = curl_init('http://192.168.1.143:8001/katm-api/v1/client/address');

            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $input);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $curl_result = json_decode(curl_exec($curl), true);

            curl_close($curl);

            Log::channel('katm')->info('KATM address result, user-pnfl: ' . $pnfl);
            Log::channel('katm')->info($curl_result);

            if (isset($curl_result['data'])) {
                return ['status' => 'success', 'data' => $curl_result['data']];
            } else {
                return ['status' => 'error'];
            }

        return ['status'=>'error','info'=>'user_not_have_pinfl'];

    }


    public static function sendReportMib( Request $request ){

        $claimId = $request->claim_id; // mb_substr( md5('pm-' . time() . uniqid('pm')), 1, 10);

        if(!$user = Buyer::with('personals')->where('id',$request->buyer_id)->first()){
            return ['status'=>'false','error'=>'buyer not found'];
        }

        $config = self::config();

        // запрос отчета 39 по claim_id

        $report_id = $request->report_id ?? 39;

        $sendReport = json_encode([
            'data' => [
                'pCode' => $config['pcode'], //Код организации,
                'pHead' => $config['phead'],
                'pLegal' => 1,
                'pClaimId' => $claimId, //Уникальный ID заявки
                'pQuarter' => 0,
                'pReportFormat' =>1,
                'pReportId' => $report_id,
                'pYear' => 0

            ],
            'security' => [
                'pLogin' => $config['login'], // Логин, предоставляется кредитным бюро
                'pPassword' => $config['password'] //Пароль, предоставляется кредитным бюро
            ],

        ]);

        Log::channel('katm')->info('katm-mib get-report: ' . $config['apiurl2']);
        Log::channel('katm')->info(var_export($sendReport,1));

        $curl = curl_init($config['apiurl2']); // /report

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $sendReport);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $curl_result = curl_exec($curl);

        //echo 'запрос ' . $config['apiurl2'] . '<br>';
        //echo $sendReport;
        //echo 'ответ2<br>';
        //print_r($curl_result);

        curl_close($curl);

        $curlJson = json_decode($curl_result, true);

        //dd($curl_result);

        if(isset($curlJson['data']['reportBase64'])) {
            $response = base64_decode($curlJson['data']['reportBase64']);
            $info = json_decode($response, true);
        }else{

            return [
                'status'=>'error',
                'info' => $curl_result,
                'response'=> [
                    'data'=> [
                        'status' => 'error',
                        'mib' => 'error'
                    ]
                ]
            ];

        }


        try {

            $katm_mib = new KatmMib();
            $katm_mib->user_id = $request->buyer_id;
            $katm_mib->claim_id = $request->claim_id;
            $katm_mib->request = $sendReport;
            $katm_mib->response = $response;
            $katm_mib->status = 1;
            $katm_mib->save();

        }catch (\Exception $e){
            return ['status'=>'false', 'error'=>json_encode($e,JSON_UNESCAPED_UNICODE)];
        }

        if( isset($info['report']) ){
            if( isset($info['report']['allDebtSum']) && $info['report']['allDebtSum'] > 0 ){
                $katm_mib->status = 0;
                $katm_mib->save();
                return ['status'=>'false','allDebtSum'=>$info['report']['allDebtSum']];
            }
            /*  if(isset($info['report']['debts']) && $info['report']['debts']>0){
                return ['status'=>'false','allDebtSum'=>$info['report']['debts']];
            }*/
        }
        // echo 'Всего долг: ' .$info['report']['allDebtSum'] . ' долг: ' . $info['report']['debts'];

        return [
            'status'=>'success',
            'response'=> [
                'data'=> [
                    'status' => 'error',
                    'mib' => 'error'
                ]
            ]
        ];

    }

    public static function sendReportInfoscore( Request $request ){

        if(!$katm = KatmScoring::where('user_id',$request->buyer_id)->first()){
            return ['status'=>'error','error buyer katm not found'];
        }

        $report_id = 177;


        $claimId = $katm->claim_id;

        if(!$user = Buyer::with('personals')->where('id',$request->buyer_id)->first()){
            return ['status'=>'false','error'=>'buyer not found'];
        }

        $config = self::config();


        $sendReport = json_encode([
            'data' => [
                'pCode' => $config['pcode'], //Код организации,
                'pHead' => $config['phead'],
                'pLegal' => 1,
                'pClaimId' => $claimId, //Уникальный ID заявки
                'pQuarter' => 0,
                'pReportFormat' =>0,
                'pReportId' => $report_id,
                'pYear' => 0

            ],
            'security' => [
                'pLogin' => $config['login'], // Логин, предоставляется кредитным бюро
                'pPassword' => $config['password'] //Пароль, предоставляется кредитным бюро
            ],

        ]);

        Log::channel('katm')->info('katm-infoscoring get-report: ' . $config['apiurl2']);
        Log::channel('katm')->info(var_export($sendReport,1));

        $curl = curl_init($config['apiurl2']); // /report

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $sendReport);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $curl_result = curl_exec($curl);

        //echo 'запрос ' . $config['apiurl2'] . '<br>';
        //echo $sendReport;
        //echo 'ответ2<br>';
        //print_r($curl_result);

        curl_close($curl);

        $curlJson = json_decode($curl_result, true);
        Log::channel('katm')->info($curl_result);



			try {

                $katm_mib = new KatmInfoscore();
                $katm_mib->user_id = $request->buyer_id;
                $katm_mib->claim_id = $claimId;
                $katm_mib->request = $sendReport;
                $katm_mib->response = $curl_result;
                $katm_mib->status = 1;

                if(!$katm_mib->save()){
					return ['status'=>'error','errors'=>'error save katm_infoscore'];
				}

				return [
					'status'=>'success',
					'info' => $curlJson['data'],
					'token' => $curlJson['data']['token'] ?? null,
					'claim_id' => $claimId
				];

            }catch (\Exception $e){
                return ['status'=>'false', 'error'=>json_encode($e,JSON_UNESCAPED_UNICODE)];
            }

        if(isset($curlJson['data']['reportBase64'])) {
            $response = base64_decode($curlJson['data']['reportBase64']);
            $info = json_decode($response, true);

        }else{

            return [
                'status'=>'error',

                'response'=> [
                    'data'=> [
                        'status' => 'error',
                    ]
                ]
            ];

        }


        return [
            'status'=>'success',
            'response'=> [
                'data'=> [
                    'status' => 'error',
                    'mib' => 'error'
                ]
            ]
        ];

    }

    public static function getReportMib( Request $request ){

                $config = self::config();


                header('Content-Type:text/html');


                //ПОДАЧА ЗАЯВКИ НА ОТЧЕТ
                $sendReport = json_encode([
                        'data' => [
                                'pHead' => $config['phead'], //Код банка
                                'pCode' => $config['pcode'], //МФО БАНКА
                                'pClaimId' => $request->claim_id, //Уникальный ID заявки
                                'pReportFormat' => 0,
                                'pReportId' => $request->report,
                                'pToken' => $request->token,
                                'pLegal' => 1,
                                'pQuarter' => 0,
                                'pYear' => 0
                                ],
                        'security' => [
                                'pLogin' => $config['login'], // Логин, предоставляется кредитным бюро
                                'pPassword' => $config['password'] //Пароль, предоставляется кредитным бюро
                                ],

                   ]);

                $curl = curl_init($config['apiurl3']); //

                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $sendReport);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                $curl_result = curl_exec($curl);
                curl_close($curl);

		Log::channel('katm')->info('katm-infoscoring get-report-mib: ' . $config['apiurl3']);
        Log::channel('katm')->info(var_export($sendReport,1));
        Log::channel('katm')->info($curl_result);

               //echo 'запрос ' . $config['apiurl3'] . '<br>';

       // echo $sendReport;

       // echo 'ответ<br>';

        //echo $curl_result;

        //exit;

        $arr = json_decode($curl_result, true);
        $report = isset($arr['data']['reportBase64']) ? base64_decode($arr['data']['reportBase64']) : 'Wait for 3-5 seconds!' ;


        echo $report;
        exit;



    }




    // получение информации katm для клиента
    // 0 - не прошел
    // 1 - ОК
    // 2 - Не найден или от сервера нет ответа
    // 3 -

    public static function getKatmInfo($user_id){

        Log::channel('katm')->info('getKATM-INFO user_id: ' . $user_id);

        if( $katmScoring = KatmScoring::where('user_id', $user_id)->where('status', 1)/*->orderBy('id','DESC')*/->first() ) {

            //Log::channel('katm')->info('Katm scoring inner');

            if ( !empty($katmScoring['response']) ) {

                $request = request();
                //Log::channel('katm')->info('KATM has response ');

                $katm = json_decode($katmScoring->response, true);
                $debtsData = [];
                $katmReport = $katm['report'];

                //Log::channel('katm')->info($katmReport);

                $user = Buyer::find($user_id);

                /*
                $retail = $katmReport['subject_claims']['retail_claims']['claims_qty'];
                $leasing = $katmReport['subject_claims']['leasing_claims']['claims_qty'];
                $mko = $katmReport['subject_claims']['mko_claims']['claims_qty'];
                $lombard = $katmReport['subject_claims']['lombard_claims']['claims_qty'];
                $bank = $katmReport['subject_claims']['bank_claims']['claims_qty'];
                */

//                $allowedPawnshopClaims = GradeScoring::getAllowedPawnshopClaimsForKatm();
//                $buyerPawnshopClaims = GradeScoring::buyerPawnshopClaimsByKatm($user->id);
//                if ($buyerPawnshopClaims > $allowedPawnshopClaims) {
//                    // отправить смс о том, что клиент НЕ прошел верификацию
//                    if($request->send_katm_sms){
//                        $msg = "Vy ne proshli registraciyu na platforme test. Siz test platformasida ro'yxatdan o'tmadingiz.";
//                        SmsHelper::sendSms($user->phone, $msg);
//                        Log::channel('katm')->info($msg);
//                    }
//                    // поиск клиента с данным id
//                    Log::channel('katm')->info('KATM status: 0');
//
//                    // если у клиента статус 1 или 2
//                    if(in_array($user->status,[1,2])){
//                        // логируем текущий баланс пользователя
//                        Log::channel('katm')->info('KATM reset balance');
//                        Log::channel('katm')->info($user->id . ', balance:' . $user->settings->balance . ', limit:' .  $user->settings->limit );
//                        // очищаем назначенный баланс
//                        /* $user->settings->balance = 0;
//                        $user->settings->limit = 0;
//                        $user->settings->save(); */
//                    }
//
//                    // заблокировать пользователя
//                    User::changeStatus($user,8);
//
//                    $katmScoring->katm_status = 0;
//                    $katmScoring->save();
//
//                    // добавляем в историю запись
//                    KycHistory::insertHistory($user->id,User::KYC_STATUS_BLOCKED,User::KYC_STATUS_BLOCKED);
//
//                    $debtsData['status'] = 0;
//
//                    return ['status'=>'error'];
//                }


                $subjectDebts = $katmReport['subject_debts'] ;
                if (is_array($katmReport['subject_debts']) && count($katmReport['subject_debts']) > 0) {
                    Log::channel('katm')->info('katmReport[subject_debts]: ');
                    Log::channel('katm')->info( $subjectDebts);

                    //$debtsData['count'] = 0;
                    $debtsData['expired'] = 0;
                    //$debtsData['debt'] = 0;
                    foreach ($subjectDebts as $debts) { // все кредиты  debts ?? debts0 ...

                        if (is_array($debts) && !isset($debts['curr_debts']) ){  // если массив        // } && count($debts) > 0) { // есть вложенные кредиты

                            foreach ($debts as $debt) {  // обход по всем вложенным кредитам

                                if (!is_array($debt)) {
                                    continue;
                                }

                                //$debtsData['count']++;
                                $debtsData['expired'] += @$debt['curr_debts'];
                                //$debtsData['debt']+=$debt['all_debts'];
                            }

                        } else { // нет вложенных
                            //$debtsData['count']++;
                            $debtsData['expired'] += @$debts['curr_debts'];
                            //$debtsData['debt'] += $debts['all_debts'];
                        }

                    }

                    $debtsData['expired'] /= 100; // просрочка в сум

                    //$debtsData['debt'] /=100; // задлолженность
                    //$debtsData['average_monthly_payment'] = $katmReport['average_monthly_payment'] / 100;
                    //$debtsData['status'] = $debtsData['expired'] > 0 ? 0 : 1;

				    $average_monthly_payment = $katmReport['average_monthly_payment'] / 100;

                    //$debtsData['debt'] /=100; // задлолженность
                    //$debtsData['average_monthly_payment'] = $katmReport['average_monthly_payment'] / 100;
                    //$debtsData['status'] = $debtsData['expired'] > 0 ? 0 : 1;

                    Log::channel('katm')->info('KATM ' . $user_id . ' expire sum: ' . number_format($debtsData['expired'],2,'.',' ') . ' сум. ср.мес.платеж: '  . number_format($average_monthly_payment,2,'.',' ') .  ' сум. status: ' . $debtsData['status']);

                    $katmScoring->katm_status = $debtsData['status'];
                    $katmScoring->save();

                    // если катм не проходит = 0,        --сбросить limit и баланс у клиента в 0
                    // у клиента статус должен быть не 4
                    if( $debtsData['status'] == 0 ){

                        // отправить смс о том, что клиент НЕ прошел верификацию
                        if($request->send_katm_sms){
                            $msg = "Hurmatli mijoz, siz resusNasiya platformasida ro'yxatdan o'tmadingiz. Tel: " . callCenterNumber(2);
                            SmsHelper::sendSms($user->phone, $msg);
                            Log::channel('katm')->info($msg);
                        }

                        // поиск клиента с данным id
                        Log::channel('katm')->info('KATM status: 0');

                        // если у клиента статус 1 или 2
                        if(in_array($user->status,[1,2])){
                            // логируем текущий баланс пользователя
                            Log::channel('katm')->info('KATM reset balance');
                            Log::channel('katm')->info($user->id . ', balance:' . $user->settings->balance . ', limit:' .  $user->settings->limit );
                            // очищаем назначенный баланс
                            /* $user->settings->balance = 0;
                            $user->settings->limit = 0;
                            $user->settings->save(); */
                        }

                        // заблокировать пользователя
                        User::changeStatus($user,8);
                        // добавляем в историю запись
                        KycHistory::insertHistory($user->id,User::KYC_STATUS_BLOCKED,User::KYC_STATUS_BLOCKED);

                    }else{  // 24.05  $debtsData['status'] = 1

                        Log::channel('katm')->info('KATM status: ?');

                        // доп проверка. скоринг с вычетом среднемес выплаты

                        /**
                        {"jsonrpc":"2.0","id":"test_182161006660af46a84bd018.62980169","status":true,"origin":"card.scoring",
                         * "result":{
                         * "May-2021":603689109,
                         * "Apr-2021":350311806,
                         * "Mar-2021":973253267,
                         * "Feb-2021":814439583,
                         * "Jan-2021":433187500,
                         * "Dec-2020":1445100000},
                         * "host":{"host":"UniSoft","time_stamp":"2021-05-27 12:13:44"}}
                         */
                        if(CardScoringLog::where('user_id',$user->id)->count() == 0) {
                            Log::channel('katm')->info('KATM status: 8 buyer BLOCKED SCORING LOG NOT FOUND ' . $user->id);

                            // заблокировать пользователя
                            User::changeStatus($user,8);

                            // добавляем в историю запись
                            KycHistory::insertHistory($user->id,User::KYC_STATUS_BLOCKED,User::KYC_STATUS_BLOCKED);

                            $debtsData['status'] = 0;
                        }



                    }

                    $result = ['status'=>$debtsData['status']];

                } else { // нет задолженностей

                    Log::channel('katm')->info('KATM status: ?');


                    /**
                    {"jsonrpc":"2.0","id":"test_182161006660af46a84bd018.62980169","status":true,"origin":"card.scoring",
                     * "result":{
                     * "May-2021":603689109,
                     * "Apr-2021":350311806,
                     * "Mar-2021":973253267,
                     * "Feb-2021":814439583,
                     * "Jan-2021":433187500,
                     * "Dec-2020":1445100000},
                     * "host":{"host":"UniSoft","time_stamp":"2021-05-27 12:13:44"}}
                     */
                    if(CardScoringLog::where('user_id',$user->id)->count() == 0) {
                        Log::channel('katm')->info('KATM status: 8 buyer BLOCKED SCORING LOG NOT FOUND ' . $user->id);
                        // заблокировать пользователя
                        User::changeStatus($user,8);
                        // добавляем в историю запись
                        KycHistory::insertHistory($user->id,User::KYC_STATUS_BLOCKED,User::KYC_STATUS_BLOCKED);

                        $debtsData['status'] = 0;
                        $result = ['status' => 0];

                    }

                }

                $clientInfo = $katmReport['client'];

                Log::channel('katm')->info($clientInfo);
                Log::channel('katm')->info('KATM result:');
                Log::channel('katm')->info($clientInfo);

                $fio = explode(" ", $clientInfo['name']);

                $ogly = !empty($fio[3]) ? ' ' . upFirstLetter($fio[3]) : ''; // если есть

                $name = upFirstLetter($fio[0]);
                $surname = upFirstLetter($fio[1]);
                $patronymic = upFirstLetter($fio[2]) . $ogly;

                // сохраняем фио, др, Дата выдачи документа
                $user->name = $name ?? null;
                $user->surname = $surname ?? null;
                $user->patronymic = $patronymic ?? null;

                $gender = null;
                if(isset($clientInfo['gender'])){
                    if($clientInfo['gender']=='Ж'){
                        $gender = 2;
                    }elseif($clientInfo['gender']=='М'){
                        $gender = 1;
                    }
                }
                $user->gender = $gender;
                $user->birth_date = date('Y-m-d',strtotime($clientInfo['birth_date']));
                $user->region = $clientInfo['region'];
                $user->local_region = $clientInfo['local_region'];

                $user->save();

                $pinfl = EncryptHelper::decryptData($user->personals->pinfl);

                if(!$user_personals = BuyerPersonal::where('user_id',$user_id)->first() ){ // isset($user_id->personals)){
                    $user_personals = new BuyerPersonal();
                    $user_personals->user_id =$user_id;
                }

                $user_personals->birthday = EncryptHelper::encryptData($clientInfo['birth_date']) ?? null;
                $user_personals->passport_date_issue = EncryptHelper::encryptData($clientInfo['document_date']) ?? null;
                if( isset($clientInfo['inn']) && !empty($clientInfo['inn']) ) $user_personals->inn = EncryptHelper::encryptData($clientInfo['inn']);
                $user_personals->save();

                $address = KatmHelper::getClientAddress($pinfl);

                Log::channel('katm')->info('address');
                Log::channel('katm')->info($address);


                // адрес проживания
                if (!$user_addresses = BuyerAddress::where('user_id', $user_id)->where('type', 'registration')->first()) {
                    $user_addresses = new BuyerAddress();
                    $user_addresses->user_id = $user_id;
                    $user_addresses->type = 'registration'; // 'residential';
                }
                $user_addresses->address = $address['data']['address'] ?? null;
                $user_addresses->save();

                // выполняем проверку ЗП клиента
                if( $katmScoring->katm_status==1 ){

                    Log::channel('katm')->info('before scoring buyer ' . $user_id . ' SOLIQ. katm status = 1');

                    $day = 25; // date('d'); // выполнять до даты
                    $send_sallary = false;

                    // корректировка лимита и баланса клиента
                   if( $send_sallary && ($user->settings->limit < 12000000  && $day < 12) ) { // не скорим клиентов, у которых скоринг по карте более 9млн
                        $request->merge(['pinfl' => $pinfl]);


                        if($send_sallary) {

                            Log::channel('katm')->info('buyer limit < 12M user_id: ' . $user_id);

                            $salary_result = SallaryHelpers::getSallary($request);

                            if ($salary_result['status'] == 'success') {

                                $scoring_result = SallaryHelpers::scoringSallary($salary_result['data']);

                                if (!$buyer_sallaries = BuyerSallaries::where('user_id', $user->id)->first()) {
                                    $buyer_sallaries = new BuyerSallaries();
                                    $buyer_sallaries->user_id = $user->id;
                                }

                                $buyer_sallaries->scoring = $scoring_result['scoring'];
                                $buyer_sallaries->ball = $scoring_result['ball'];
                                $buyer_sallaries->save();

                                // если по ЗП скоринг выдал больше сумму, чем по Карте, то установить ее
                                if ( $user->vip == 0) {  //  если клиент вип - не меняем лимит и баланс
                                    Log::channel('katm')->info('scoring SOLIQ change balance for ' . $user->id . ': from ' . $user->settings->balance . ' to ' . $scoring_result['scoring']);
                                    $user->settings->balance = $scoring_result['scoring'];
                                    $user->settings->limit = $scoring_result['scoring'];
                                    $user->settings->save();
                                }

                            }
                        } // send_sallary

                   } // $user->settings->limit > 9М

                }

                return $result;

            } // вообще нет данных о клиенте

            Log::channel('katm')->info('KATM no data: status = 0');

            $katmScoring->katm_status = 2;
            $katmScoring->save();

        }

        return ['status'=>2];

    }

    public static function decodeResponse($code){
        switch($code){
            case '05000':
                $result['status'] = 'success';
                $result['message'] = __('katm.success_request_success');
                break;
            case '05050':
                $result['status'] = 'await';
                $result['message'] = __('katm.warning_report_await_success_operator');
                break;
            case '05002':
                $result['status'] = 'error';
                $result['message'] = __('katm.error_empty_required_field');
                break;
            case '05020':
                $result['status'] = 'error';
                $result['message'] = 'Phone is not correct';
                break;
            case '500':
                $result['status'] = 'error';
                $result['message'] = __('katm.server_error');
                break;

            case '0000':
                $result['status'] = 'error';
                $result['message'] = __('katm.mip_error');
                break;
            case '00001':
                $result['status'] = 'error';
                $result['errors'] = __('katm.mib_false');
                $result['message'] = __('katm.mib_false');
                break;
            default:
                $result['status'] = 'error';
                $result['code'] = $code;
                $result['message'] = __('katm.error_unknown_see_log');
        }
        return $result;
    }

}
