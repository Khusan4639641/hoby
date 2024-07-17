<?php

namespace App\Http\Controllers\Core;

use App\Facades\GradeScoring;
use App\Facades\OldCrypt;
use App\Helpers\EncryptHelper;
use App\Helpers\FileHelper;
use App\Helpers\GnkSalaryHelper;
use App\Helpers\KatmHelper;
use App\Models\Buyer;
use App\Models\BuyerAddress;
use App\Models\BuyerPersonal;
use App\Models\KatmMib;
use App\Models\KatmScoring;
use App\Models\KycHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use PDF;

class KatmController extends CoreController{
    //

    public function scoring(Request $request){
        Log::channel('katm')->info('KatmController->scoring()');

        // $savePersonal = false;
        $uid = Buyer::find($request->buyer_id);
        Log::channel('katm')->info( 'user-id: '.$request->buyer_id);
        Log::channel('katm')->info($uid);

        if(isset($uid->settings)) {
            $uid->settings->katm_region_id = $request->region_id;
            $uid->settings->katm_local_region_id = $request->local_region_id;
            $uid->settings->save();
        }


        $data = [
            'passport' => $request->passport ?? EncryptHelper::decryptData($uid->personals->passport_number),
            'pinfl' => $request->pinfl ?? EncryptHelper::decryptData($uid->personals->pinfl),
            'address' => 'address', // @$uid->addressResidential->string,
            'phone' => $uid->phone
        ];


        Log::channel('katm')->info($data);

        $rules = [
            'passport' => ['required', 'string'],
            'pinfl' => ['required', 'string'],
            'address' => ['required', 'string'],
            'phone' => ['required', 'string'],
        ];
        $validator = $this->validator($data, $rules);
        if($validator->fails()){
            $this->result['status'] = 'error';
            $this->result['response']['errors'] = $validator->errors();

        }else {
            // заявка
            $result = KatmHelper::registerKatm($uid, $request);
            //$result = KatmHelper::getKatm($config, $uid);

            $this->decodeResponse($result);

            // если пришел ответ
            /*

            if ($katm = KatmScoring::where('user_id', $uid->id)->orderBy('updated_at', 'desc')->first()) {
                $this->result['data'] = $katm->response;
            } */

            if (KatmMib::where('user_id', $request->buyer_id)->count() > 0) {
                $this->result['response']['data']['mib'] = 'success';
            }

            // если не прошел проверку МИБа (katm_mib->status == 0), выдаем соответствующий текст ошибки
            if(KatmMib::where('user_id', $uid->id)->first()->status == 0 && isset($this->result['response']['message'][0]['text']))
                    $this->result['response']['message'][0]['text'] = __('panel/buyer.did_not_pass_mib');

            // !!! возвращают весь объект целиком !!!
            $this->result['data'] = KatmScoring::where('user_id', $uid->id)->orderBy('updated_at', 'desc')->first();

        }

        return $this->result();
    }


    public function katmInfo(Request $request){

        return KatmHelper::getKatmInfo($request->buyer_id);

    }

    // получение отчета
    public function katmMib(Request $request){

        if($request->has('buyer_id')){
            return KatmHelper::sendReportMib($request);
        }

        return ['status'=>'error'];

    }

    public function katmStatus(Request $request){

        $result = KatmHelper::getKatmInfo($request->buyer_id);
        //$result['status'] = 0; // ДЛя теста всегда 0
        return $result['status'];

    }

    // получить отчет от katm
    public function getReport(Request $request){

        Log::channel('katm')->info('>>>>>>>>KATM getReport start>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>');

        $katm = KatmScoring::where('user_id', $request->buyer_id)->where('status', 1)/*->where('token', $request->token)*/->orderBy('id', 'desc')->first();
        if($katm != null) {

            $namePdf = '/katm/katm-report-' . $request->buyer_id . '.pdf';

            Log::channel('katm')->info('KATM buyer_id: ' . $request->buyer_id);
            Log::channel('katm')->info($request);

            $linkPdf = $namePdf;
            @unlink($linkPdf);

            $rawReport = json_decode($katm->response, 1)['report'];

            // обработка запроса
            $clientInfo = $rawReport['client'];


            Log::channel('katm')->info('clientInfo ' );
            Log::channel('katm')->info($clientInfo);

            $user = Buyer::find($request->buyer_id);

            // результат обработки информации

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

            foreach($rawReport['subject_claims'] as $k=>$v){
                unset($rawReport['subject_claims'][$k]);
                $rawReport['subject_claims'][] = $v;
            }

            if(isset($rawReport['subject_debts']) && $rawReport['subject_debts'] != '' ) {

                $debts = [];
                foreach ($rawReport['subject_debts'] as $k => $v) {
                    $debts[] = $v;
                }

                // закомментил , т.к. всегда в ответе масссив $rawReport['subject_debts']

                $_debts = [];
                if(isset($debts['debts']['curr_debts'])){ // 1 шт
                    $_debts[] = $debts['debts'];
                }else{
                    if(isset($debts[0])) {
                        if(isset($debts[0][0])){
                            $_debts =  $debts[0];
                        }else{
                            $_debts[] =  $debts[0];
                        }
                    }else{
                        $_debts[] = $debts;
                    }
                }

                $rawReport['subject_debts'] = $_debts;

            }

            // $report['average_monthly_payment'] = '0';

            $report = $this->replaceKeyToTitle($rawReport);
            FileHelper::generateAndUploadPDF($linkPdf, 'panel.buyer.katm', ['report' => $report]);
            $this->result['status'] = 'success';
            $this->result['data']['link'] = '/storage/' . $namePdf;

            Log::channel('katm')->info('katm pdf link: ' . '/storage/' . $namePdf);

            /**
            // ответ от  КАТМ
            {"report":{"subject_claims":{"retail_claims":{"granted_qty":0,"org_type":"Ритейл","rejected_qty":1,"claims_qty":7},"leasing_claims":{"granted_qty":0,"org_type":"Лизинговая компания","rejected_qty":0,"claims_qty":0},"mko_claims":{"granted_qty":2,"org_type":"Микрокредитная организация","rejected_qty":0,"claims_qty":2},"lombard_claims":{"granted_qty":130,"org_type":"Ломбард","rejected_qty":1,"claims_qty":149},"bank_claims":{"granted_qty":29,"org_type":"Коммерческий банк","rejected_qty":5,"claims_qty":38}},             * "sysinfo":{"date":"26.11.2020 12:47:38","bank":"RET","demand_id":"RET2020331303624","claim_id":345996784,"report_type":23,"declaration":"Внимание! Предоставление и использование кредитной информации регулируется Законом Республики Узбекистан \"Об обмене кредитной информацией\" № 301 от 04.10.2011 года.","branch":20037,"claim_date":"26.11.2020"},"subject_debts":{"debts":[{"curr_debts":491762311,"last_update":"25-NOV-20","all_debts":1419491815,"currency":"UZS","org_name":"ТОШКЕНТ Ш., ТИФ МИЛЛИЙ БАНКИНИНГ УЧТЕПА ФИЛИАЛИ"},{"curr_debts":30080648,"last_update":"25-NOV-20","all_debts":62323148,"currency":"UZS","org_name":"ТОШКЕНТ Ш., \"УЗСАНОАТКУРИЛИШБАНКИ\" АТБ ШАХРИСТОН ФИЛИАЛИ"},{"curr_debts":185873358,"last_update":"25-NOV-20","all_debts":1811626620,"currency":"UZS","org_name":"ТОШКЕНТ Ш., \"УЗСАНОАТКУРИЛИШБАНКИ\" АТБ МИРОБОД ФИЛИАЛИ"},{"curr_debts":457557481,"last_update":"25-NOV-20","all_debts":1275466520,"currency":"UZS","org_name":"ТОШКЕНТ Ш., \"УЗСАНОАТКУРИЛИШБАНКИ\" АТБ КАТОРТОЛ ФИЛИАЛИ"},{"curr_debts":210337009,"last_update":"25-NOV-20","all_debts":903727049,"currency":"UZS","org_name":"ТОШКЕНТ Ш., \"УЗСАНОАТКУРИЛИШБАНКИ\" АТБ АЛ-ХОРАЗМИЙ ФИЛИАЛИ"},{"curr_debts":1589105295,"last_update":"24-NOV-20","all_debts":1589105295,"currency":"UZS","org_name":"ТОШКЕНТ Ш., \"АГРОБАНК\" АТБ ТОШКЕНТ ШАХАР ФИЛИАЛИ"},{"curr_debts":404798230,"last_update":"24-NOV-20","all_debts":1585830829,"currency":"UZS","org_name":"КИБРАЙ Т., АТ ХАЛК БАНКИ КИБРАЙ ФИЛИАЛИ"},{"curr_debts":1182431547,"last_update":"24-NOV-20","all_debts":1183692513,"currency":"UZS","org_name":"ЯНГИЙУЛ Т., АТ ХАЛК БАНКИ ЯНГИЙУЛ ФИЛИАЛИ"},{"curr_debts":269533265,"last_update":"24-NOV-20","all_debts":2105960665,"currency":"UZS","org_name":"ТОШКЕНТ Ш., АТ ХАЛК БАНКИ МИРОБОД ФИЛИАЛИ"},{"curr_debts":298602735,"last_update":"24-NOV-20","all_debts":2146941517,"currency":"UZS","org_name":"ТОШКЕНТ Ш., АТ ХАЛК БАНКИ ТОШКЕНТ ШАХАР ФИЛИАЛИ АМАЛИЁТ БУЛИМИ"},{"curr_debts":295943647,"last_update":"24-NOV-20","all_debts":1917630032,"currency":"UZS","org_name":"ТОШКЕНТ Ш., АТ ХАЛК БАНКИ ТОШКЕНТ ШАХАР ФИЛИАЛИ АМАЛИЁТ БУЛИМИ"},{"curr_debts":17593809,"last_update":"20-NOV-20","all_debts":1448650904,"currency":"UZS","org_name":"ТОШКЕНТ Ш., ЧЕТ ЭЛ КАПИТАЛИ ИШТИРОКИДАГИ \"САВДОГАР\" АТБ ШАЙХОНТОХУР ФИЛИАЛИ"},{"curr_debts":105161016,"last_update":"25-NOV-20","all_debts":1812724392,"currency":"UZS","org_name":"БУСТОНЛИК Т., АТБ \"КИШЛОК КУРИЛИШ БАНК\"НИНГ ГАЗАЛКЕНТ ФИЛИАЛИ"},{"curr_debts":34946916,"last_update":"09-NOV-20","all_debts":1265352097,"currency":"UZS","org_name":"ЗАНГИОТА Т., \"ТУРОНБАНК\" АТ БАНКИНИНГ ЗАНГИОТА ФИЛИАЛИ"},{"curr_debts":202804591,"last_update":"25-NOV-20","all_debts":1395553280,"currency":"UZS","org_name":"ТОШКЕНТ Ш., ХАТ БАНКИ \"УНИВЕРСАЛ БАНК\" ТОШКЕНТ ФИЛИАЛИ"},{"curr_debts":402580845,"last_update":"25-NOV-20","all_debts":1132645402,"currency":"UZS","org_name":"ТОШКЕНТ Ш., \"КАПИТАЛБАНК\" АТ БАНКИНИНГ \"КАПИТАЛ 24\" ЧАКАНА БИЗНЕС ФИЛИАЛИ"}],"debts0":{"curr_debts":301284020,"last_update":"24-NOV-20","all_debts":1203842000,"currency":"UZS","org_name":"ТОШКЕНТ Ш., АТ ХАЛК БАНКИ ЮНУСОБОД ФИЛИАЛИ"},"debts1":{"curr_debts":166666700,"last_update":"24-NOV-20","all_debts":2001500000,"currency":"UZS","org_name":"ТОШКЕНТ Ш., АТ ХАЛК БАНКИ ОЛМАЗОР ФИЛИАЛИ"}
             * },
             * "client":{"duplicates":true,"document_number":8144058,"address":"г. Ташкент, Юнусабадский район, р-н, 11 квартал, дом 20. кв 31","subject":2,
             * "birth_date":"02.06.1990",
             * "inn":517519490,
             * "document_date":"30.12.2014","client_type":"08",
             * "document_serial":"AA","local_region":1,"katm_sir":"P2003720201126091602","old_name":"",
             * "phone":998990050400,"nibbd":"",
             * "name":"ABRALOV AZAMAT ASQAR O\u2018G\u2018LI","region":3,"name_change":"","document_type":6},"average_monthly_payment":5111357114}}
             */

            /**
            1)  ФИО;
            2) Дата рождения;
            3) Дата выдачи документа;
            4) ИНН;
            5) Прописка; */

            // результат по КАТМ
            if(!$user_personals = BuyerPersonal::where('user_id',$request->buyer_id)->first() ){ // isset($user_id->personals)){
                $user_personals = new BuyerPersonal();
                $user_personals->user_id = $user->id;
            }

            $user_personals->birthday = EncryptHelper::encryptData($clientInfo['birth_date']) ?? null;
            $user_personals->passport_date_issue = EncryptHelper::encryptData($clientInfo['document_date']) ?? null;
            if( isset($clientInfo['inn']) && !empty($clientInfo['inn']) ) $user_personals->inn = EncryptHelper::encryptData($clientInfo['inn']);

            $user_personals->save();

            $pnfl = EncryptHelper::decryptData($user_personals->pinfl);

            Log::channel('katm')->info('pinfl: '.$pnfl);

            $address = KatmHelper::getClientAddress($pnfl);

            Log::channel('katm')->info('address');
            Log::channel('katm')->info($address );


            // адрес проживания
            if (!$user_addresses = BuyerAddress::where('user_id', $user->id)->where('type', 'residential')->first()) {
                $user_addresses = new BuyerAddress();
                $user_addresses->user_id = $user->id;
                $user_addresses->type = 'residential';
            }
            $user_addresses->address = $address['data']['address'] ?? null;
            $user_addresses->save();

            $this->result['status'] = 'success';


			// 15,07 добавлено из БД
			$this->result['katm_status'] = $katm->katm_status;

            return $this->result();

        }else{
            // если отчет еще не получен с сервиса katm
            // запросить его

            Log::channel('katm')->info('KATM not found');

            $this->result['status'] = 'error';

            // 20.04.2021 - если нет записи , вызываем метод получения отчета из KATM
            $this->checkAndUpdateScoring($request); // этот метод также может выполняться из cron

        }


        /*$user = Buyer::find($request->buyer_id);
        if(!$user->vip){
            $this->result['katm_status'] = $this->katmStatus($request);
        }else{
            // если вендор сам платит за клиента
            //  КАТМ пройден, верифицируем
            $this->result['katm_status'] = 1;
            User::changeStatus($user,4);
            // добавляем в историю запись
            KycHistory::insertHistory($user->id,User::KYC_STATUS_VERIFY,User::KYC_STATUS_VERIFY);
        }*/
        // здесь нужно получить данные от КАТМ
        $this->result['katm_status'] = $this->katmStatus($request);

        Log::channel('katm')->info('<<<<<KATM getReport end<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');

        return $this->result();
    }



    public function checkAndUpdateScoring(Request $request){

        Log::channel('katm')->info('KATM repeat');

        $config = $this->config();
        $user_id = $request->user->id ?? $request->buyer_id;
        $result = KatmHelper::getKatm($config, $user_id);
        $this->decodeResponse($result);

        return ['status'=>'await'];

    }


    public function replaceKeyToTitle($report){
        $arTitle = [
            "katm_sir"=>"KATM-SIR",
            "duplicates"=>"Дубликаты",
            "name"=>"Наименование заёмщика",
            "old_name"=>"Старое наименование заёмщика",
            "name_change"=>"Измёненное наименование заёмщика",
            "subject"=>"Cубъект",
            "client_type"=>"Код типа клиента",
            "inn"=>"ИНН (идентификационный номер налогоплательщика)",
            "birth_date"=>"Дата рождения",
            "document_type"=>"Код удостоверения личности",
            "document_serial"=>"Серия документа",
            "document_number"=>"Номер документа",
            "document_date"=>"Дата выдачи удостоверяющего документа",
            "gender"=>"Пол",
            "nibbd"=>"Код клиента по НИББД (национальной информационной базы банковских депозиторов)",
            "region"=>"Код области прописки",
            "local_region"=>"Код района прописки",
            "address"=>"Адрес по прописке",
            "phone"=>"Номер телефона",
            "bank_claims"=>"Обращения по банкам",
            "leasing_claims"=>"Обращения по лизинговым компаниям",
            "lombard_claims"=>"Обращения по ломбардам",
            "mko_claims"=>"Обращения по микрокредитным организациям",
            "retail_claims"=>"Обращения по ритейлерам",
            "declaration"=>"Уведомление",
            "bank"=>"Пользователь кредитного отчёта",
            "branch"=>"Код Пользователя",
            "demand_id"=>"Запрос Пользователя на получение кредитного отчета",
            "date"=>"Дата запроса",
            "claim_id"=>"Кредитная заявка",
            "claim_date"=>"Дата подачи заявки",
            "report_type"=>"Тип отчёта",
        ];


        function recursiveReplace($report, $title){
            foreach($report as $key=>$value){
                if (isset($title[$key])) $newKey = $title[$key]; else $newKey = $key;
                if(is_array($value))
                    $newReport[$newKey] = recursiveReplace($value, $title);
                else
                    $newReport[$newKey] = $value;
            }
            return $newReport;
        }

        $newReport = recursiveReplace($report, $arTitle);
        return $newReport;
    }

    public function decodeResponse($code){
        switch($code){
            case '05000':
                $this->result['status'] = 'success';
                $this->message('success', __('katm.success_request_success'));
                break;
            case '05050':
                $this->result['status'] = 'await';
                $this->message('warning', __('katm.warning_report_await_success_operator'));
                break;
            case '05002':
                $this->result['status'] = 'error';
                $this->message('danger', __('katm.error_empty_required_field'));
                break;
            case '00004':
                $this->result['status'] = 'error';
                $this->message('danger', __('katm.mip_not_found'));
                break;
            default:
                $this->result['status'] = 'error';
                $this->message('danger', __('katm.error_unknown_see_log'));
        }
    }



    protected function config(){
        $config = [

        ];
        return $config;
    }

    // Отчет по клиентам для аналитики
    public function report(){

        if($katm = KatmScoring::select(DB::raw('DISTINCT (user_id),response,katm_status'))
            ->from('katm_scorings as ks')
            ->leftJoin('users as u',function($q){
                $q->on('u.id','ks.user_id');
            })
            ->with('buyer','buyer.settings','cardScoring')
            ->where('ks.katm_status','=',0)
            ->where('u.status',8)
            ->get() ){

            /**
            {"report":{
             * "subject_claims":
                * {"retail_claims":
                    * {"granted_qty":0,"org_type":"Ритейл","rejected_qty":0,"claims_qty":35},
                    * "leasing_claims":{"granted_qty":0,"org_type":"Лизинговая компания","rejected_qty":0,"claims_qty":0},
                    * "mko_claims":{"granted_qty":0,"org_type":"Микрокредитная организация","rejected_qty":0,"claims_qty":0},
                    * "lombard_claims":{"granted_qty":0,"org_type":"Ломбард","rejected_qty":0,"claims_qty":0},
                    * "bank_claims":{"granted_qty":1,"org_type":"Коммерческий банк","rejected_qty":1,"claims_qty":2}
                * },
                * "sysinfo":{"date":"27.05.2021 14:06:30","bank":"RET","demand_id":"RET2021147105042","claim_id":"3b5f4628d","report_type":23,"declaration":"Внимание! Предоставление и использование кредитной информации регулируется Законом Республики Узбекистан \"Об обмене кредитной информацией\" № 301 от 04.10.2011 года.","branch":20100,"claim_date":"27.05.2021"},
                * "subject_debts":{"debts":{"curr_debts":0,"last_update":"26-MAY-21","all_debts":165774194,"currency":"UZS","org_name":"ТОШКЕНТ Ш., \"TBC BANK\" АКЦИЯДОРЛИК ТИЖОРАТ БАНКИ"}
                * },
             * "client":{"duplicates":true,"document_number":2392933,"address":"address","subject":2,"birth_date":"01.10.1992","inn":"","document_date":"31.07.2013","client_type":"08","document_serial":"AA","local_region":203,"katm_sir":"P2010020210527609091","old_name":"","phone":998999117659,"nibbd":"","name":"BAYAXMEDOV SARDORBEK RUSTAMBEK O\u2018G\u2018LI","region":26,"name_change":"","document_type":6},
             * "average_monthly_payment":18441337}
             * }
             */

           // dd($katm);

            /**
            Дата регистрации;
            - ID;
            - ФИО;
            - Лимит;
            - Количество активных кредитов;
            - Количество запросов в ломбард;
            - Остаточная сумма по кредитам;
            - Ежемесячный платеж;
            - Просроченная задолженность;
            - Поступления на карту последние 6 месяцев;
            */

            $file = "ID;ФИО;Лимит;Кредиты;Ломбарды;Остаток по кредитам;Ежемесячный платеж;Просроченно;Поступления на карту\n";


            foreach ($katm as $item){

                if(isset($item->buyer) && ($item->buyer->status!=8 || $item->buyer->id<100000) ) continue;

                $lombard= '';
                $subjectDebts = '';
                $expired = '';
                $debt = '';
                $average_monthly_payment = '';

                if( $item->response /*$item->status==0*/ ) {
                    $katmInfo = json_decode($item->response,true);

                    $katmReport = $katmInfo['report'];

                    /*
                    $retail = $katmReport['subject_claims']['retail_claims']['claims_qty'];
                    $leasing = $katmReport['subject_claims']['leasing_claims']['claims_qty'];
                    $mko = $katmReport['subject_claims']['mko_claims']['claims_qty'];
                    $lombard = $katmReport['subject_claims']['lombard_claims']['claims_qty'];
                    $bank = $katmReport['subject_claims']['bank_claims']['claims_qty'];
                    */

                    $lombard = $katmReport['subject_claims']['lombard_claims']['claims_qty'];

                    $average_monthly_payment = $katmReport['average_monthly_payment'] / 100;

                    $debtsData['expired'] = 0;
                    $debtsData['debt'] = 0;
                    if(is_array($katmReport['subject_debts'])) {
                        $subjectDebts = count($katmReport['subject_debts']);
                        foreach ($katmReport['subject_debts'] as $debts) { // все кредиты  debts ?? debts0 ...

                            if (is_array($debts) && !isset($debts['curr_debts'])) {  // если массив        // } && count($debts) > 0) { // есть вложенные кредиты

                                foreach ($debts as $debt) {  // обход по всем вложенным кредитам

                                    if (!is_array($debt)) {
                                        continue;
                                    }

                                    //$debtsData['count']++;
                                    $debtsData['expired'] += @$debt['curr_debts'];
                                    $debtsData['debt'] += $debt['all_debts'];
                                }

                            } else { // нет вложенных
                                //$debtsData['count']++;
                                $debtsData['expired'] += @$debts['curr_debts'];
                                $debtsData['debt'] += $debts['all_debts'];
                            }

                        }

                        $expired = number_format($debtsData['expired'] / 100, 2, '.', ''); // просрочка в сум
                        $debt = number_format($debtsData['debt'] / 100, 2, '.', '');
                    }

                }

                if($item->cardScoring){
                    /**
                    {"jsonrpc":"2.0","id":"test.44729476","status":true,"origin":"card.scoring",
                     * "result":{"Aug-2021":208166000,"Jul-2021":140740000,"Jun-2021":965900,"May-2021":59392000,"Apr-2021":183066000,"Mar-2021":229822600},
                     * "host":{"host":"UniSoft","time_stamp":"2021-08-31 12:17:19"}}
                     */

                    $scorings = json_decode($item->cardScoring->response,true);
                    $cardScoring = [];
                    foreach ($scorings['result'] as $month=>$scoring){
                        $cardScoring[] = number_format($scoring/100,2,'.','');
                    }

                }else{
                    $cardScoring = [0,0,0,0,0,0];
                }

                $file .= $item->user_id . ';' .
                    @$item->buyer->name . ' ' .  @$item->buyer->surname . ' ' .  @$item->buyer->patronymic . ';' .
                    @$item->buyer->settings->limit . ';' .
                    $subjectDebts . ';' .
                    $lombard . ';' .
                    $debt . ';' .
                    $average_monthly_payment . ';' .
                    $expired . ';' .
                    implode(';' ,$cardScoring) . ";\n";
            }

            $filename = 'katm-report.csv';
            $file = iconv('utf-8','windows-1251//TRANSLIT',$file);

            file_put_contents($filename,$file);

            if(file_exists($filename)) {
                header( 'Content-type: '. mime_content_type($filename));
                header( 'Content-Disposition: attachment; filename=' . $filename );
                readfile($filename);
                exit;
            }else{
                return redirect('404');
            }


        }

    }


    public function cancel(){


        $t = microtime(true);
        $micro = sprintf("%03d",($t - floor($t)) * 1000);
        $utc = gmdate('2021-06-08\TH:i:s.', $t).$micro.'Z';

        $data = [
            'claim_id' => '46776df353',
            'date' => $utc, //  date("2021-06-08\T15:28:35.000Z"),
            'reason_id' => 8,
            'reason' => 'Otkaz zayemshchika v poluchenii kredita',   //'Отказ заемщика в получении кредита'
        ];

        $res = $this->cancelClient($data);

        print_r($res);

        $data = [
            'claim_id' => '384187c1a7',
            'date' => $utc, //date("2021-06-08 T 13:15:02.000Z"),
            'reason_id' => 8,
            'reason' => 'Otkaz zayemshchika v poluchenii kredita', //'Отказ заемщика в получении кредита'
        ];

        $res = $this->cancelClient($data);
        print_r($res);
        exit;
    }


    // отмена заявки
    private function cancelClient($data){

        $config = KatmHelper::config();

        $getReport = json_encode([
            'data' => [
                'pClaimId' => $data['claim_id'], //Уникальный ID заявки
                'pHead' => $config['phead'], //
                'pCode' => $config['pcode'], //Код организации
                'pRejectDate' => $data['date'], // Дата отказа(yyyy-MM-dd'T'HH:mm:ss.SSSZ)
                'pReasonId' => $data['reason_id'], // Код причины отказа
                'pReason' => $data['reason'], // Причина отказа
                'pIsUpdate' => 0, // Флаг обновления данных
            ],
            'security' => [
                'pLogin' => $config['login'], // Логин, предоставляется кредитным бюро
                'pPassword' => $config['password'] //Пароль, предоставляется кредитным бюро
            ],
        ]);

        $config['apiurl_cancel'] = 'http://192.168.1.143:8001/katm-api/v1/claim/rejection';

        Log::channel('katm')->info('--------CANCEL buyer start');
        Log::channel('katm')->info('reject url: '.$config['apiurl_cancel']);
        Log::channel('katm')->info(var_export($getReport, 1));

        //if(empty($config['apiurl_cancel']))

        $curl = curl_init($config['apiurl_cancel']);

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $getReport);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $curlEx = curl_exec($curl);
        Log::channel('katm')->info(var_export($curlEx, 1));
        curl_close($curl);
        $arr = json_decode($curlEx, true);
        //$arr2 = base64_decode($arr['data']['reportBase64']);

        //$info = json_decode($arr,true);
        Log::channel('katm')->info('--------CANCEL buyer end');

        return $arr;

    }



}
