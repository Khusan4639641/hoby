<?php

namespace App\Http\Controllers\Web\Frontend;

use App\Helpers\EncryptHelper;
use App\Helpers\ImageHelper;
use App\Helpers\SmsHelper;
use App\Helpers\UniversalHelper;
use App\Http\Controllers\Core\UniversalPnflController;
use App\Http\Controllers\Web\WebController;
use App\Models\Buyer;
use App\Models\BuyerPersonal;
use App\Models\CancelContract;
use App\Models\Card;
use App\Models\CardPnflContract;
use App\Models\Contract;
use App\Models\ContractPaymentsSchedule;
use App\Models\KycHistory;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FrontendController extends WebController
{

    /**
     * @param Request $request
     * @return Application|RedirectResponse|Redirector
     */
    public function downloadFromUrl(Request $request)
    {
        $path = storage_path('app/public/') . $request->url;

        if (file_exists($path)) {
            if ($request->name) {
                $ext = substr(strrchr($path, '.'), 1);
                $find = array(',', ' ', '+');
                $filename = mb_strtolower(str_replace($find, '_', $_GET['name'])) . '.' . $ext;
            } else
                $filename = basename($path);

            //Устанаваливаем MIME- тип файла
            header('Content-type: ' . mime_content_type($path));

            // передаем в поток имя скачаваемого файла (подменяя им яфайла на диске на имя для скачивания)
            header('Content-Disposition: attachment; filename=' . $filename);

            // читаем и передаем в поток содержимое файла dd($path);
            readfile($path);
        } else {
            return redirect('404');
        }
    }

    public function form()
    {

        return view('file');
    }


    public function send(Request $request)
    {

        Log::info($request);

        if (count($request->file()) > 0) {

            $t1 = microtime(true);

            Log::info('save files' . __FILE__);

            $config = Config::get('test');

            foreach ($request->file() as $file) {
                $img = new ImageHelper($file);
                $img->resize($config['documents_size']['width'], $config['documents_size']['height']);
                $img->save($file->getRealPath(), 100, $file->extension());
            }

            Log::info($t1);

            $dt = number_format(microtime(true) - $t1, 3, '.', ' ');

            return ['time' => $dt];

        }

        return ['no files'];


    }


    /**
     * add uzcard cards(pnfl) to users cards to ALL users
     *
     * @param Request $request
     * @return mixed
     */
    public function cardpnfl(Request $request)
    {
        $rq = new Request();
        $card_ids = [];


        $date = date('Y-m-d 23:59:00', strtotime('-10 day'));
        $query = Buyer::query(); //user
        $query
            ->with('personals')  // ??
            ->whereHas('contracts', function ($query) use ($date) {
                $query->leftJoin('contract_payments_schedule as cps', function ($query) {
                    $query->on('cps.contract_id', 'contracts.id');
                })
                    ->where('cps.status', 0)
                    ->where('cps.payment_date', '<=', $date)
                    ->whereIn('contracts.status', [1, 3, 4]);
            });
        $buyers = $query->get();

        foreach($buyers as $buyer){

            // серия и номер паспорта
            if($buyer->personals){
                $passport = EncryptHelper::decryptData($buyer->personals->passport_number);
               // dd($passport);
                if(strpos($passport,' ')>0){
                    list($passportSeries, $passportNumber) = explode(' ', $passport);
                }else{
                    $passportSeries = mb_substr($passport, 0, 2);
                    $passportNumber = mb_substr($passport, 2, 7);
                }

                if (isset($buyer->cardsPnfl)) {    // если такая карта уже есть, не будем добавлять
                    foreach ($buyer->cardsPnfl as $card) {
                        $card_ids[] = $card->card_id;
                    }
                }

                $rq->merge([
                    'pnfl' => EncryptHelper::decryptData($buyer->personals->pinfl),
                    'lastName' => $buyer->name != '' ? $buyer->name : 'abc',
                    //'lastName' => $buyer->name,
                    'firstName' => $buyer->surname,
                    'middleName' => $buyer->patronymic,
                    'birthDate' => EncryptHelper::decryptData($buyer->personals->birthday),
                    'passportSeries' => $passportSeries,
                    'passportNumber' => $passportNumber,
                    'passportIssueDate' => EncryptHelper::decryptData($buyer->personals->passport_date_issue),
                    'passportExpDate' => EncryptHelper::decryptData($buyer->personals->passport_expire_date) > 0 ? EncryptHelper::decryptData($buyer->personals->passport_expire_date) : '11',
                ]);

                if(!$pnfl_contract = CardPnflContract::where('user_id', $buyer->id)->first()){

                    // получить clientId
                    $result = UniversalPnflController::getClientId($rq);

                    if($result['status'] == true){
                        // получить clientId
                        $pnfl_contract = new CardPnflContract();
                        $pnfl_contract->user_id = $buyer->id;
                        $pnfl_contract->clientId = $result['result']['clientId'];
                        $pnfl_contract->save();

                    }else{
                        // LOG $result['error']['message']
                        $info = [
                            'message' => isset($result['error']['message']) ?? $result,
                            'buyer_id' => $buyer->id
                        ];
                        $info = json_encode($info);
                        Log::channel('payment_pnfl')->info('CANT GET CLIENT ID ');
                        Log::channel('payment_pnfl')->info($info);
                        continue;
                    }
                }


                if(!$clientId = $pnfl_contract->clientId) continue;

                // получить все карты клиента
                $rq = new Request();
                $rq->merge([
                    'clientId' => $clientId,
                ]);

                $result = UniversalPnflController::getCards($rq);

                if(isset($result['status']) && $result['status'] == true){

                    if (isset($result['result'])) {
                        $new_cards = $result['result'];

                        foreach($new_cards as $key => $card){

                            if (!in_array($card['id'], $card_ids)) {

                                $user_cards = new CardPnfl();
                                $user_cards->user_id = $buyer->id;
                                $user_cards->card_id = $card['id'];
                                $user_cards->status = $card['status'];
                                $user_cards->pan = $card['pan'];
                                $user_cards->card_phone = $card['phone'];
                                $user_cards->fullName = $card['fullName'];
                                $user_cards->sms = $card['sms'];
                                $user_cards->state = 0;

                                if ($user_cards->save()) {
                                    $card_ids[] = $user_cards->id;
                                }else{
                                    // LOG $buyer->id  $user_cards Json
                                    $info = [
                                        'message' => isset($result['error']['message']) ?? $result,
                                        'buyer_id' => $buyer->id,
                                        'user_cards' => $user_cards,
                                    ];
                                    $info = json_encode($info);
                                    Log::channel('payment_pnfl')->info('CANT SAVE CLIENTS CARDS');
                                    Log::channel('payment_pnfl')->info($info);

                                }
                            }
                        }

                        if(isset($buyer->cardsPnfl)){
                            if($card->PnflContract->contract_id == null ){

                                // создаем контракт на клиента
                                $req = new Request();
                                $req->merge([
                                    'clientId' => $card->PnflContract->clientId,
                                    'pnfl' => EncryptHelper::decryptData($buyer->personals->pinfl),
                                    'lastName' => $buyer->name != '' ? $buyer->name : 'abc',
                                    //'lastName' => $buyer->name,
                                    'firstName' => $buyer->surname,
                                    'middleName' => $buyer->patronymic,
                                    'birthDate' => EncryptHelper::decryptData($buyer->personals->birthday),
                                    'passportSeries' => $passportSeries,
                                    'passportNumber' => $passportNumber,
                                    'passportIssueDate' => EncryptHelper::decryptData($buyer->personals->passport_date_issue),
                                    'passportExpDate' => EncryptHelper::decryptData($buyer->personals->passport_expire_date) > 0 ? EncryptHelper::decryptData($buyer->personals->passport_expire_date) : '11',
                                ]);

                                $result = UniversalPnflController::createContractId($req);

                                if($result['status'] == true){
                                    $card->PnflContract->contract_id = $result['contract_id'];
                                    $card->PnflContract->save();

                                    $card->state = Card::CARD_ACTIVE;
                                    $card->save();
                                }else{
                                    // LOG $result['error']['message']
                                    $info = [
                                        'message' => isset($result['error']['message']) ?? $result,
                                        'buyer_id' => $buyer->id
                                    ];
                                    $info = json_encode($info);
                                    Log::channel('payment_pnfl')->info('CANT GET CONTRACT ');
                                    Log::channel('payment_pnfl')->info($info);
                                }
                            }else{
                                $info = [
                                    'contract_id' => $card->PnflContract->contract_id,
                                    'buyer_id' => $buyer->id
                                ];
                                $info = json_encode($info);
                                Log::channel('payment_pnfl')->info('CLIENT ALREADY HAS CONTRACT ID');
                                Log::channel('payment_pnfl')->info($info);
                                continue;
                            }

                        }
                    }
                }else{

                    // получить все карты клиента
                    // LOG $result['error']['message']
                    $info = [
                        'result' => $result,
                        'buyer_id' => $buyer->id
                    ];
                    $info = json_encode($info);
                    Log::channel('payment_pnfl')->info('CANT GET CARDS ');
                    Log::channel('payment_pnfl')->info($info);
                    continue;
                }
            }
        }
        echo 'done!';

    }

    public function bonus(Request $request)
    {

            $sellers = \App\Models\SellerBonus::where('status', 0)
                ->with('contract')
                ->whereHas('contract', function ($q) {
                    $q->whereIn('status', [1,3,4,9]);   // только подтвержденные
                })
                ->get();

            foreach ($sellers as $seller) {
                // d($seller->id . ' = ' . $seller->contract->id . ' + ' . $seller->contract->status);
                if($sett = \App\Models\BuyerSetting::where('user_id', $seller->seller_id)->first()) {
                    // dd($sett);
                    if (isset($sett)) {

                        $sett->zcoin += $seller->amount;
                        $sett->save();
                        $seller->status = 1;
                        $seller->save();
                    } else {
                        d($seller->user_id);
                    }
                }
            }

            dd('done');

    }

    // test - приводим баланс в баланс
    public function doit(Request $request)
    {
        $contracts = Contract::whereIn('status', [1,3,4,9])->with('buyer', 'payments')->get();
        foreach($contracts as $contract){
            $buyer = $contract->buyer;
            $payments = $contract->payments;

            // ск сняли с карт
            // и ск с ЛС
            // (пополнения - депозит)
            // сначала найдем сумму (пополнения - депозит) - ( списания с ЛС + Лс)
            // если она бьется, тогда поправляем баланс, если нет, пропускаем
            // потому что есть списания с ЛС на которые не было пополнений
            foreach($payments as $payment){
                //сумма всех пополнений
                    if($payment->status = 1) {
                        $user_pay += $payment->amount;
                    }
            }


            $schedules = $contract->schedule;
            $sum = 0;
            foreach($schedules as $schedule){
                if($schedule->status == 1){
                    $sum += $schedule->total;
                }else{
                    // если была оплата, то разница > 0
                    $diff = $schedule->total - $schedule->balance;
                    if($diff > 0){
                        $sum += $diff;
                    }
                }
            }


            if($sum > 0){
                // если остаток долга не соответствует бд - перезапишем
                $cntr_debt = $contract->total - $sum;
                if($contract->balance != $cntr_debt){
                    $contract->balance = $cntr_debt;
                    $contract->save();
                }
            }

        }

    }


    public function score(Request $request)
    {
        if ($request->isMethod('POST')) {
            echo '<pre>';
            $data = [];
            foreach ($request->m as $item) {
                $data[] = intval($item) * 100;
            }
            echo '<br><b>Результат:</b> ';
            Log::channel('cards')->info('scoring from: ' . __CLASS__ . ' ' . __METHOD__ . ' ' . __LINE__);
            $scoring = UniversalHelper::scoringScore($data);
            echo number_format($scoring['scoring'], '0', '.', ' ');
            echo '</pre>';
        }
        return view('score', ['m' => $request->m]);

    }

    public function extra(Request $request)
    {


        if ($request->has('passport')) {
            if ($buyer = BuyerPersonal::where('user_id',$request->user_id)->first()) {
                echo EncryptHelper::decryptData($buyer->passport_number) . '<br>';
            }
        }
        if ($request->has('pinfl')) {
            if ($buyer = BuyerPersonal::where('user_id',$request->user_id)->first()) {
                echo EncryptHelper::decryptData($buyer->pinfl) . '<br>';
            }
        }

        if ($request->has('card_id')) {
            if ($card = Card::find($request->card_id)) {
                echo EncryptHelper::decryptData($card->card_number) . ' ' . EncryptHelper::decryptData($card->card_valid_date) . '<br>' . ' user_id: ' . $card->user_id;;
            }
        }
        if ($request->has('card')) {
            if ($card = Card::where('guid', md5($request->card) )->first() ) {
                echo $card->id . ' user_id: ' . $card->user_id;
            }
        }
        if ($request->has('guid')) {
            if ($card = Card::where('guid', $request->guid)->first()) {
                echo EncryptHelper::decryptData($card->card_number) . ' ' . EncryptHelper::decryptData($card->card_valid_date) . '<br>' . ' user_id: ' . $card->user_id;;
            }
        }
        if($request->has('phone')){
            if($buyer = Buyer::where('phone',$request->phone)->first()){
                echo $buyer->id;
            }

        }

        if ($request->has('user_id')) {
            if ($buyer = Buyer::where('id',$request->user_id)->with('personals','cards','cardsPnfl')->first()) {
                echo 'id: ' . $buyer->id . '<br>';
                echo 'телефон: ' . $buyer->phone . '<br>';

                echo 'passport: ' . EncryptHelper::decryptData($buyer->personals->passport).'<br>';
                echo 'pinfl: ' . EncryptHelper::decryptData($buyer->personals->pinfl) .'<br>';

                foreach($buyer->cards as $card) {
                    echo 'карта: ' . $card->id .  ', ' .  EncryptHelper::decryptData($card->card_number) . ' type: ' .  EncryptHelper::decryptData($card->type) .' phone: ' .  EncryptHelper::decryptData($card->phone) . ' guid: ' . $card->guid . '<br>';
                }

                foreach($buyer->cardsPnfl as $card) {
                    echo 'карта PINFL: ' . $card->id .  ', ' . $card->pan . '<br>'; //  EncryptHelper::decryptData($card->card_number) . ' ' .  EncryptHelper::decryptData($card->type) .' ' .  EncryptHelper::decryptData($card->phone) . ' ' . $card->guid . '<br>';
                }

            }
        }


    }


    // отправка смс просрочникам
    public function sendMessagePaymentDelay()
    {

        $date = date('Y-m-d 23:59:59',time());

//        $cps = DB::select("SELECT cps.balance, u.id, u.phone, cps.payment_date
//				FROM `contract_payments_schedule` AS cps
//				left JOIN `users` as u ON cps.user_id=u.id
//				inner JOIN contracts as c ON cps.contract_id=c.id
//				WHERE c.status IN (1,3,4) AND cps.status=0 AND cps.`payment_date` < '{$date}' "
//        );
        $cps = false;         //  Заглушка, так как массовой рассылкой не пользуемся, 01.08.2022 dev_nurlan_production_hotfix_bonus_to-card_methods

        if ($cps) {

            $success = 0;
            $error = 0;

            $data = [];
            foreach ($cps as $item) {
                if(!isset($data[$item->id])) {
                    $data[$item->id]['balance'] = 0;
                    $data[$item->id]['date'] = null;
                    $data[$item->id]['phone'] = $item->phone;
					$data[$item->id]['days'] = 0;
                }
                $data[$item->id]['balance']+= $item->balance;

				$day = ( time() - strtotime($item->payment_date) ) / 86400;

				if($data[$item->id]['days']<$day) $data[$item->id]['days'] = $day;

            }

//            foreach ($data as $id=>$item) {
//
//                if($item['days'] < 15 || $item['balance']<=0) continue;
//
//
//                $res = SmsHelper::sendSms($item['phone'], $msg);
//
//                if ($res == SmsHelper::SMS_SEND_SUCCESS) {
//                    Log::channel('sms')->info('SUCCESS: SMSPayDelay for ' . $id . ' ' . $item['phone'] . ' sum: ' . $sum);
//                    $success++;
//                } else {
//                    Log::channel('sms')->info('ERROR: SMSPayDelay for ' . $id . ' ' . $item['phone'] . ' sum: ' . $sum);
//                    $error++;
//                }
//
//            }
            Log::channel('sms')->info('success:' . $success . ' error: ' . $error);

        }

        exit('done');

    }

    // mассовая рассылка смс
    public function sendMessageAll()
    {

//        $cps =  Buyer::whereNotIn('status', [8])->get();
        $cps = false; //  Заглушка, так как массовой рассылкой не пользуемся, 01.08.2022 dev_nurlan_production_hotfix_bonus_to-card_methods

        if ($cps) {

            $success = 0;
            $error = 0;

//            foreach ($cps as $item) {


//                $res = SmsHelper::sendSms($item->phone, $msg);
//                $res2 = SmsHelper::sendSms($item->phone, $msg2);
//
//                if ($res == SmsHelper::SMS_SEND_SUCCESS) {
//                    Log::channel('sms')->info('SUCCESS: SMStoAllUsers for ' . $item->id . ' ' . $item->phone);
//                    $success++;
//                } else {
//                    Log::channel('sms')->info('ERROR: SMStoAllUsers for ' . $item->id . ' ' . $item->phone);
//                    $error++;
//                }

                // $success++; // двойной счетчик, четкий!!!

//            }
            Log::channel('sms')->info('success:' . $success . ' error: ' . $error);

        }

        exit('done');

    }

    public function debts(){

       // error_reporting(E_ALL);

        if( $buyers = Buyer::with('contracts','contracts.schedule')->has('contracts')->where('status',4)->get() ) {

            /* SELECT DISTINCT cps . `user_id`,u . `phone`
            FROM contract_payments_schedule cps
            LEFT JOIN users u ON u . id = cps . user_id
            WHERE cps . `status` = 1 and cps . `payment_date` >= '2021-12-15 23:00:00' and u . `status` = 4 */

            $res = [];
            try {
                foreach ($buyers as $buyer) {

                    if ($buyer->contracts) {

                        $debts = false;

                        foreach ($buyer->contracts as $contract) {

                            if ($contract->schedule) {

                                foreach ($contract->schedule as $schedule) {

                                    if ($schedule->status == 0 && strtotime($schedule->payment_date) <= time()) {
                                        $debts = true;
                                        break ;
                                    }

                                }

                                if($debts) break;

                            }

                        }

                        if (!$debts) $res[$buyer->id] = $buyer->id . ';' . $buyer->phone . "\n";

                    }
                }
            }catch (\Exception $e){
                dd($e);
            }


            $result = implode('',$res);

            $filename = 'buyers-ok.csv';
            $file = iconv('utf-8','windows-1251//TRANSLIT',$result);

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

        exit('no buyers');


    }


}
