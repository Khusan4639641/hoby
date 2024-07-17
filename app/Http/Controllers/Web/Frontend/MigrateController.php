<?php

namespace App\Http\Controllers\Web\Frontend;

use App\Helpers\EncryptHelper;
use App\Helpers\ImageHelper;
use App\Http\Controllers\Core\Auth\AuthController;
use App\Http\Controllers\Core\CoreController;
use App\Models\Buyer;
use App\Models\BuyerAddress;
use App\Models\BuyerPersonal;
use App\Models\BuyerSetting;
use App\Models\Card;
use App\Models\Company;
use App\Models\Contract;
use App\Models\ContractPaymentsSchedule;
use App\Models\File;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Partner;
use App\Models\PartnerSetting;
use App\Models\User;
use App\Services\API\V3\UserPayService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class MigrateController extends CoreController
{

    private $config;
    private $lastID = 215009; //Последний айдишник юзера на текущий момент

    public function __construct(){
        $this->config = Config::get('test');
    }

    public function migrateBuyers(){
        return;
        //Получили всех покупателей (в старой таблице role = 1)
        //$oldUsers = OldUser::where('role', 1)->with('kyc')->with('cards')->get();

        /*$oldUsers = OldUser::whereNotIn('id', function ($query) {
            $query->select('id')
                ->from('users');
        })->where('role', 1)->with('kyc')->with('cards')->get();*/

        $oldUsers = OldUser::where('id', '>', $this->lastID)->where('role', 1)->with('kyc')->with('cards')->get();

        foreach ($oldUsers as $oldUser) {

            if(mb_strlen($oldUser->username) > 20){
                $_arr = explode(' ', $oldUser->username);
                $oldUser->username = $_arr[1] ?? '';
                $oldUser->lastname = $_arr[0] ?? '';
                $oldUser->patronymic = $_arr[2] ?? '';
            }

            if(
                mb_strlen($oldUser->phone) <=15 &&
                mb_strlen($oldUser->username) <=20 &&
                mb_strlen($oldUser->lastname) <=45 &&
                mb_strlen($oldUser->patronymic) <=45
            ){
                //Создаем пользователя
                //------------------------------------------
                $buyer = new Buyer();

                //Статус
                $buyer->status = ($oldUser->kyc && $oldUser->kyc->status === 1? 4:1);

                if($buyer->status == 4) {
                    //Если верифицирован
                    $buyer->verified_at = date("Y:m:d H:i:s",  $oldUser->kyc->date_verify);
                }
                $buyer->id = $oldUser->id;

                //Фио
                $buyer->name = $oldUser->username;
                $buyer->surname = $oldUser->lastname ;
                $buyer->patronymic = $oldUser->patronymic;

                //Телефон
                $buyer->phone = $oldUser->phone;

                if($oldUser->kyc){

                    $reasons = [
                        1 => 'Верифицирован',
                        2 => 'Недостаточно документов/фото ждём',
                        3 => 'Нужно поступление 1 месяц',
                        4 => 'Много кредитов',
                        5 => 'Есть просрочки по кредитам',
                        6 => 'МИП-4 Ошибка',
                        7 => 'СМС от узкард',
                        8 => 'Недостаточно месяцев',
                        9 => 'Номера разные',
                        10 => 'Возраст',
                        11 => 'Клиент отказался брать',
                        12 => 'ИНН НУЖНО',
                        13 => 'ПРОВЕРИТЬ КЛИЕНТА',
                        14 => 'ДУБЛЬ 2',
                        15 => 'Держатель карты не соответствует'
                    ];

                    if($oldUser->kyc->refuse_reason){
                        $buyer->verify_message = $reasons[$oldUser->kyc->refuse_reason];
                    }


                }

                if($oldUser->created_at){
                    $buyer->created_at = (string)$oldUser->created_at;
                }


                if($oldUser->updated_at){
                    $buyer->updated_at = (string)$oldUser->updated_at;
                }

                if($buyer->save()){

                    //Апи токен
                    AuthController::generateApiToken($buyer);

                    //Сохраняем соответсвие в доп таблицу (старый ID - новый ID)
                    $oldUserBuyer = new OldUserBuyer();
                    $oldUserBuyer->old_user_id = $oldUser->id;
                    $oldUserBuyer->user_id = $buyer->id;
                    $oldUserBuyer->save();

                    //Настройки покупателя
                    //------------------------------------------
                    if(!$buyerSettings = BuyerSetting::where('user_id',$buyer->id)->first()) {
                        $buyerSettings = new BuyerSetting();
                    }
                    $buyerSettings->user_id          = $buyer->id;

                    $buyerSettings->rating           = 0;
                    $buyerSettings->zcoin            = 0;
                    $buyerSettings->personal_account = $oldUser->summ??0;
                    $buyerSettings->period           = Config::get( 'test.buyer_defaults.period' );
                    $buyerSettings->limit            = $oldUser->kyc->credit_year??Config::get( 'test.buyer_defaults.limit' );

                    $buyerSettings->balance          = $buyerSettings->limit;

                    $buyerSettings->save();
                    (new UserPayService)->createClearingAccount($buyerSettings->user_id);

                    //Персональные данные с шифрованием
                    //------------------------------------------
                    $buyerPersonals = new BuyerPersonal();
                    $buyerPersonals->user_id                = $buyer->id;
                    if($oldUser->birthday && $oldUser->birthday !== '' && $this->checkDate($oldUser->birthday)){
                        $buyerPersonals->birthday               = EncryptHelper::encryptData(Carbon::createFromFormat('Y-m-d', $oldUser->birthday)->format('d.m.Y'));
                    }

                    $buyerPersonals->passport_number        = EncryptHelper::encryptData($oldUser->passport_serial.' '.$oldUser->passport_id);
                    if($oldUser->passport_date && $oldUser->passport_date !== '' && $this->checkDate($oldUser->passport_date)){
                        $buyerPersonals->passport_date_issue    = EncryptHelper::encryptData(Carbon::createFromFormat('Y-m-d', $oldUser->passport_date)->format('d.m.Y'));
                    }

                    $buyerPersonals->passport_issued_by     = EncryptHelper::encryptData($oldUser->passport_issuer);

                    $buyerPersonals->pinfl                  = EncryptHelper::encryptData($oldUser->pnfl);

                    if($oldUser->pnfl != ''){
                        $buyerPersonals->pinfl_hash =   md5($oldUser->pnfl);
                    }
                    $buyerPersonals->work_company           = EncryptHelper::encryptData($oldUser->company);
                    $buyerPersonals->inn                    = EncryptHelper::encryptData($oldUser->inn);

                    $buyerPersonals->home_phone             = EncryptHelper::encryptData($oldUser->phone_home);

                    $buyerPersonals->save();

                    //Файлы

                    $_arrFiles = [
                        'passport_self' => 'passport_selfie',
                        'passport_address' => 'passport_with_address',
                        'passport_main' => 'passport_first_page'
                    ];

                    $path = storage_path('app/public/') . "buyer-personal/{$buyerPersonals->id}/";

                    foreach ($_arrFiles as $key => $type){
                        if($oldUser->$key && $oldUser->$key != ''){

                            if(!file_exists($path))
                                \Illuminate\Support\Facades\File::makeDirectory($path);

                            $fileInfo = pathinfo( $oldUser->$key );

                            $fileName = md5( $oldUser->$key . time() . uniqid() ) . "." . $fileInfo['extension'];
                            $fullPath = $path . $fileName;

                            $sourcePath = storage_path('app/public/') . "old_users/uploads/users/{$oldUser->id}/{$oldUser->$key}";

                            if(file_exists($sourcePath)){

                                if(copy($sourcePath, $fullPath)){

                                    $_arrAvailableFormats = [
                                        'image/gif',
                                        'image/png',
                                        'image/jpeg'
                                    ];

                                    $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
                                    $isImage = in_array(finfo_file($finfo, $fullPath), $_arrAvailableFormats);
                                    finfo_close($finfo);

                                    if($isImage){
                                        $image     = new ImageHelper( $fullPath );
                                        $image->resize( $this->config['documents_size']['width'], $this->config['documents_size']['height'] );
                                        $image->save( $fullPath );

                                        $file                   = new File();
                                        $file->element_id       = $buyerPersonals->id;
                                        $file->model            = 'buyer-personal';
                                        $file->type             = $type;
                                        $file->language_code    = null;
                                        $file->path             = "buyer-personal/{$buyerPersonals->id}/$fileName";
                                        $file->name             = $fileName;
                                        $file->user_id          = $buyer->id;

                                        $file->save();
                                    } else {
                                        unlink($fullPath);
                                    }
                                }
                            }

                        }
                    }

                    //Адреса пользователей

                    $buyerAddresses = new BuyerAddress();
                    $buyerAddresses->user_id = $buyer->id;
                    $buyerAddresses->type = 'registration';
                    $buyerAddresses->address = $oldUser->address;
                    $buyerAddresses->save();

                    $buyerAddresses = new BuyerAddress();
                    $buyerAddresses->user_id = $buyer->id;
                    $buyerAddresses->type = 'residential';
                    $buyerAddresses->address = $oldUser->permanent_adddress;
                    $buyerAddresses->save();

                    //Карты пользователей

                    if($oldUser->cards){

                        $oldCards = OldScoring::where('user_id', $oldUser->id)->orderBy('id', 'desc')->get();

                        if($oldCards){
                            foreach ( $oldCards as $oldCard ) {

                                $token = null;

                                if($oldCard->pan != '' && $oldCard->token != ''){
                                    $type = 'UZCARD';
                                    $cardNumber = $oldCard->pan;
                                    $token = EncryptHelper::encryptData($oldCard->token);
                                } else {
                                    $type = 'HUMO';
                                    $cardNumber = '9860' . $oldCard->bank_c . $oldCard->card_h;
                                }

                                $hashedCardNumber = md5($cardNumber);

                                $card = Card::where('guid', $hashedCardNumber)->where('user_id', $buyer->id)->first();

                                if(!$card){
                                    $card = new Card();
                                    $card->user_id = $buyer->id;
                                    $card->card_name = EncryptHelper::encryptData($oldCard->fullname);

                                    if($token)
                                        $card->token = $token;

                                    $card->card_number = EncryptHelper::encryptData($cardNumber);
                                    $card->type = EncryptHelper::encryptData($type);

                                    $validDate = '';

                                    if($oldCard->exp != ''){

                                        $year = substr($oldCard->exp, 0, 2);
                                        $month = substr($oldCard->exp, -2, 2);

                                        $validDate = "$month/$year";
                                    }

                                    $card->card_valid_date = EncryptHelper::encryptData($validDate);

                                    if($oldCard->phone)
                                        $card->phone = EncryptHelper::encryptData($oldCard->phone);

                                    $card->guid = $hashedCardNumber;

                                    $card->save();
                                }
                            }
                        }


                    }

                    $buyer->user->attachRole('buyer');
                }

            }

            //echo $buyer->name.' '.$buyer->surname.'- migrated<br/>';
        }
    }


    public function migrateVendors($is_affiliate = null){

        //Получили всех продавцов (в старой таблице role = 2)
        /*if($is_affiliate == 1)
            $oldUsers = OldUser::where('role', 2)->where('filial', 1)->whereStatus(1)->orderBy('id', 'ASC')->get();
        else
            $oldUsers = OldUser::where('role', 2)->where('filial', null)->whereStatus(1)->orderBy('id', 'ASC')->get();*/


        /*if($is_affiliate == 1)
            $oldUsers = OldUser::whereNotIn('id', function ($query) {
                $query->select('id')
                    ->from('users');
            })->where('role', 2)->where('filial', 1)->whereStatus(1)->orderBy('id', 'ASC')->get();
        else
            $oldUsers = OldUser::whereNotIn('id', function ($query) {
                $query->select('id')
                    ->from('users');
            })->where('role', 2)->where('filial', null)->whereStatus(1)->orderBy('id', 'ASC')->get();*/

        if($is_affiliate == 1)
            $oldUsers = OldUser::where('id', '>', $this->lastID)->where('role', 2)->where('filial', 1)->whereStatus(1)->orderBy('id', 'ASC')->get();
        else
            $oldUsers = OldUser::where('id', '>', $this->lastID)->where('role', 2)->where('filial', null)->whereStatus(1)->orderBy('id', 'ASC')->get();

        foreach ($oldUsers as $oldUser) {

            if(mb_strlen($oldUser->username) > 20){
                $_arr = explode(' ', $oldUser->username);
                $oldUser->username = $_arr[1] ?? '';
                $oldUser->lastname = $_arr[0] ?? '';
                $oldUser->patronymic = $_arr[2] ?? '';
            }

            if (
                mb_strlen($oldUser->phone) <= 15 &&
                $oldUser->company &&
                mb_strlen($oldUser->username) <= 20 &&
                mb_strlen($oldUser->lastname) <= 45 &&
                mb_strlen($oldUser->patronymic) <= 45
            ) {
                //Создаем компанию партнера
                $company = new Company();
                $company->id          	= $oldUser->id;
                $company->name          = $oldUser->company;
                $company->inn           = $oldUser->inn;
                $company->address       = $oldUser->address_filial;
                $company->status        = 1;

                $oldNew = null;

                if($oldUser->filial == 1) {
                    //Выбираем родителя из старой таблицы
                    $oldFilial = OldFilial::where('supplier_id', $oldUser->id)->first();

                    if($oldFilial) {
                        //Находим родителя в новой таблице
                        $oldNew = OldUserBuyer::where('old_user_id', $oldFilial->parent_id)->first();

                        if($oldNew) {
                            //Подставляем новый ID родителя в поле parent_id
                            $company->parent_id = $oldNew->user_id;
                        }
                    }
                }

                if($is_affiliate == null || ($is_affiliate != null && $oldNew)){
                    if($company->save()){
                        //Настройки  компании
                        $partnerSettings = new PartnerSetting();
                        $partnerSettings->company_id         = $company->id;

                        $plans = Config::get('test.plans');

                        $partnerSettings->markup_3      = $oldUser->margin_three ?? $plans[3];
                        $partnerSettings->markup_6      = $oldUser->margin_six ?? $plans[6];
                        $partnerSettings->markup_9      = $oldUser->margin_nine ?? $plans[9];
                        $partnerSettings->markup_12      = $oldUser->margin_twelve ?? $plans[12];

                        $partnerSettings->discount_3    = 0;
                        $partnerSettings->discount_6    = 0;
                        $partnerSettings->discount_9    = 0;
                        $partnerSettings->discount_12    = 0;

                        $partnerSettings->nds           = $oldUser->nds_state;

                        $partnerSettings->save();

                        //Непосредственно пользователь. связанный с компанией
                        //------------------------------------------
                        $seller = new User();
                        $seller->id = $oldUser->id;
                        //Статус
                        $seller->status = 1;
                        $seller->company_id         = $company->id;



                        //Фио
                        $seller->name               = $oldUser->username;
                        $seller->surname            = $oldUser->lastname ;
                        $seller->patronymic         = $oldUser->patronymic;

                        //Телефон
                        $seller->phone =            $oldUser->phone;

                        if($seller->save()) {

                            //Апи токен
                            AuthController::generateApiToken($seller);

                            //Сохраняем соответсвие в доп таблицу (старый ID - новый ID)
                            $oldUserBuyer = new OldUserBuyer();
                            $oldUserBuyer->old_user_id = $oldUser->id;
                            $oldUserBuyer->user_id = $seller->id;
                            $oldUserBuyer->save();

                            //Прикрепляем роль партнера к пользователю
                            $seller->attachRole('partner');
                        }
                    }
                }
            }
        }
    }


    public function migrateContracts()
    {

        return true;

        $oldCredits = OldCredit::where('service_type', 0)->with('items', 'canceled')->get();

        foreach ($oldCredits as $oldCredit) {
            //Находим пользователя кредита
            $buyer = Buyer::find($oldCredit->user_id);

            //Находим продавца кредита
            $partner = Partner::find($oldCredit->supplier_id);

            //Если и партнер и покупатель есть в системе
            if($buyer != null && $partner != null) {

                //Определяем статус договора и договора
                $orderStatus = $creditStatus = null;
                if($oldCredit->user_confirm == 1) {
                    //Договор завершен, так как уже передан в магазине
                    $orderStatus = 9;
                    //Статус =1 активен, =9 закрыт
                    $creditStatus = ($oldCredit->status == 0?1:9);
                }elseif($oldCredit->user_confirm == 0 && $oldCredit->canceled != null) {
                    if($oldCredit->canceled->status == 1) {
                        $orderStatus = 5;
                        $creditStatus = 5;
                    }
                }

                //Если статус договора и договора определены
                if($orderStatus != null && $creditStatus != null) {

                    //Создаем договор
                    $order = new Order();
                    $order->user_id         = $buyer->id;
                    $order->partner_id      = $partner->id;
                    $order->company_id      = $partner->company_id;
                    $order->total           = $oldCredit->price;
                    $order->partner_total   = 0;
                    $order->credit          = 0; //Долг перерд продавцом
                    $order->debit           = 0; //Долг продавца, при миграции всегда = 0
                    $order->status          = $orderStatus;
                    $order->created_at      = $oldCredit->created_at;
                    $order->save();

                    //Обрабатываем товары договора
                    $partner_total = 0;
                    foreach($oldCredit->items as $item) {
                        $orderProduct = new OrderProduct();
                        $orderProduct->order_id         = $order->id;       //ID договора
                        $orderProduct->name             = $item->title;     //Название товара
                        $orderProduct->amount           = $item->quantity;  //Количество
                        $orderProduct->price            = round($item->price/$item->quantity, 2);
                        $orderProduct->price_discount     = $item->discount_sum;//Цена товара
                        $orderProduct->save();

                        //Вычисляем сумму продавца
                        $partner_total += $item->quantity*$item->amount;
                    }

                    //Обновлем договор
                    $order->partner_total   = $partner_total;               //Сумма продавца
                    $order->save();

                    //Записываем соответствие старый договор новый договор
                   /* $oldCreditOrder = new OldCreditOrder();
                    $oldCreditOrder->old_credit_id      = $oldCredit->id;
                    $oldCreditOrder->order_id           = $order->id;
                    $oldCreditOrder->save(); */

                    //Создаем кредитный договор
                    $contract = new Contract();
                    $contract->id                   = $oldCredit->id;
                    $contract->confirmation_code    = $oldCredit->code_confirm;
                    $contract->user_id              = $buyer->id;                   //ИД покупатель
                    $contract->partner_id           = $partner->id;                 //ИД продавца
                    $contract->company_id           = $partner->company_id;         //ИД компании продавца
                    $contract->order_id             = $order->id;                   //ИД договора
                    $contract->total                = $oldCredit->price;            //Сумма рассрочки
                    $contract->period               = $oldCredit->credit_limit;     //Срок рассрочки
                    $contract->balance              = $oldCredit->credit;           //Остаток по кредиту
                    $contract->status               = $creditStatus;
                    $contract->created_at           = $oldCredit->created_at;
                    $contract->faktoring            = $oldCredit->credit_type;
                    $contract->faktoring_amount     = $oldCredit->faktor_amount;
                    $contract->deposit              = $oldCredit->deposit_first;
                    $contract->save();

                    //Создаем график платежей
                    foreach($oldCredit->history as $history) {
                        $contractPayment = new ContractPaymentsSchedule();
                        $contractPayment->user_id       = $contract->user_id;
                        $contractPayment->contract_id   = $contract->id;
                        $contractPayment->status        = $history->payment_status==1?1:0;                      //=1  оплачен, =0 не оплачен
                        $contractPayment->payment_date  = $history->credit_date;                        //Дата ожидаемой оплаты
                        if( $history->payment_date != null)
                            $contractPayment->paid_at       = $history->payment_date;                       //Дата фактической оплаты
                        $contractPayment->price         = round($order->partner_total/$contract->period, 2); //Чистая ежемесячная сумма для возврата в лимит рассрочки
                        $contractPayment->total         = $history->price;                              //Сумма платежа
                        $contractPayment->balance       = $history->payment_status==1?0:$history->price;        //Остаток платежа
                        $contractPayment->save();

                        //Если не оплачен, то отнимаем остаток платежа от текущего остатка суммы рассрочки покупателя
                        if($history->payment_status == 0) {
                            if($buyer->settings){
                                $buyer->settings->balance -= $contractPayment->price;
                                //$buyer->settings->balance -= $order->partner_total;
                                if($buyer->settings->balance < 0)
                                    $buyer->settings->balance = 0;

                                $buyer->settings->save();
                            }
                        }
                    }


                    $oldPolise = OldPolises::where('credit_id', $contract->id)->first();

                    if($oldPolise){
                      /*  $contractInsurance = new ContractInsurance();
                        $contractInsurance->contract_id = $contract->id;
                        $contractInsurance->user_id = $buyer->id;
                        $contractInsurance->number = $oldPolise->contractRegistrationID;
                        $contractInsurance->status = $oldPolise->status == 1 ? 1:0;
                        $contractInsurance->polis_series = $oldPolise->polisSeries;
                        $contractInsurance->polis_number = $oldPolise->polisNumber;
                        $contractInsurance->term = $contract->period;
                        $contractInsurance->save(); */
                    }

                    /*$oldAsko = OldAsko::where('credit_id', $contract->id)->first();

                    if($oldAsko){
                        $contractInsurance = new ContractInsurance();
                        $contractInsurance->contract_id = $contract->id;
                        $contractInsurance->user_id = $buyer->id;
                        $contractInsurance->number = $oldAsko->Contract_number;
                        $contractInsurance->status = $oldAsko->Status == 'Create' ? 1:0;
                        $contractInsurance->term = $oldAsko->Term;
                        $contractInsurance->insurance_premium = $oldAsko->Insurance_premium;
                        $contractInsurance->save();
                    } */
                }

            }
        }
    }

    public function correct(){

		$buyers = Buyer::with('old')->with('personals')->get();

        foreach ($buyers as $buyer) {
        	if($buyer->old){
				if ($buyer->personals) {
					$buyer->personals->inn = EncryptHelper::encryptData($buyer->old->inn);
					$buyer->personals->save();
				}
			}

        }
        /*$partners = Partner::whereNotNull('company_id')->get();

        foreach ($partners as $partner){

            if($partner->company->settings){
                $partner->company->settings->company_id = $partner->id;
                $partner->company->settings->save();
            }

            if($partner->company){
                $partner->company->id = $partner->id;
                $partner->company->save();
            }

            $partner->company_id = $partner->id;

            $partner->save();
        }*/


        /*$buyers = Buyer::with('old.kyc')->with('settings')->with('old.cards')->get();

        foreach ($buyers as $buyer){
            if($buyer->settings){
                $buyer->settings->balance =  $buyer->settings->limit;
                $buyer->settings->save();
            }

            if($buyer->old){

                /*if($buyer->old->cards){

                    $oldCards = OldScoring::where('user_id', $buyer->id)->orderBy('id', 'desc')->get();

                    if(count($oldCards) > 0){

                        foreach ( $oldCards as $oldCard ) {
                            $token = null;

                            if($oldCard->pan != '' && $oldCard->token != ''){
                                $type = 'UZCARD';
                                $cardNumber = $oldCard->pan;
                                $token = EncryptHelper::encryptData($oldCard->token);
                            } else {
                                $type = 'HUMO';
                                $cardNumber = '9860' . $oldCard->bank_c . $oldCard->card_h;
                            }

                            $hashedCardNumber = md5($cardNumber);

                            $card = Card::where('guid', $hashedCardNumber)->where('user_id', $buyer->id)->first();

                            if(!$card){
                                $card = new Card();
                                $card->user_id = $buyer->id;
                                $card->card_name = EncryptHelper::encryptData($oldCard->fullname);

                                if($token)
                                    $card->token = $token;

                                $card->card_number = EncryptHelper::encryptData($cardNumber);
                                $card->type = EncryptHelper::encryptData($type);

                                $validDate = '';

                                if($oldCard->exp != ''){

                                    $year = substr($oldCard->exp, 0, 2);
                                    $month = substr($oldCard->exp, -2, 2);

                                    $validDate = "$month/$year";
                                }

                                $card->card_valid_date = EncryptHelper::encryptData($validDate);

                                if($oldCard->phone)
                                    $card->phone = EncryptHelper::encryptData($oldCard->phone);

                                $card->guid = $hashedCardNumber;

                                $card->save();
                            }

                        }
                    }


                }*/

        /*if($buyer->old->created_at){
            $buyer->created_at = (string)$buyer->old->created_at;
        }


        if($buyer->old->updated_at){
            $buyer->updated_at = (string)$buyer->old->updated_at;
        }


        $buyer->save();*/

        /*if($buyer->old->kyc){

            $reasons = [
                1 => 'Верифицирован',
                2 => 'Недостаточно документов/фото ждём',
                3 => 'Нужно поступление 1 месяц',
                4 => 'Много кредитов',
                5 => 'Есть просрочки по кредитам',
                6 => 'МИП-4 Ошибка',
                7 => 'СМС от узкард',
                8 => 'Недостаточно месяцев',
                9 => 'Номера разные',
                10 => 'Возраст',
                11 => 'Клиент отказался брать',
                12 => 'ИНН НУЖНО',
                13 => 'ПРОВЕРИТЬ КЛИЕНТА',
                14 => 'ДУБЛЬ 2'
            ];

            if($buyer->old->kyc->refuse_reason){
                $buyer->verify_message = $reasons[$buyer->old->kyc->refuse_reason];
                $buyer->save();
            }


        }
    }


}*/

    }


    public function migrate(){
        echo 'Migration start!<br/>';
        echo '---------------------------------<br/>';

        echo 'Vendors migration start!<br/>';
        $this->migrateVendors();
        echo 'Vendors migration complete!<br/>';

        echo 'Vendors affiliates migration start!<br/>';
        $this->migrateVendors(1);
        echo 'Vendors affiliates migration complete!<br/>';

        echo 'Buyers migration start!<br/>';
        $this->migrateBuyers();
        echo 'Buyers migration complete!<br/>';


        echo 'Credits migration start!<br/>';
        $this->migrateContracts();
        echo 'Credits migration complete!<br/>';


        echo '---------------------------------<br/>';
        echo 'Migration complete!<br/>';

    }


    public function clear(){
        echo 'Deleting buyer start...<br/>';

        //Удаляем все по договорам и кредитам
        $this->clearOrders();


        $this->clearUsers();

        echo 'Delete complete!';

    }


    public function clearOrders(){
        //Выбирам все импортированные договоры
        $orderId = OldCreditOrder::all()->pluck('order_id')->toArray();

        //Выбирам все кредиты
        $contractId = Contract::whereIn('order_id', $orderId)->pluck('id')->toArray();

        //Удаляем графики платежей
        $scheduleId = ContractPaymentsSchedule::whereIn('contract_id', $contractId)->pluck('id')->toArray();
        ContractPaymentsSchedule::destroy($scheduleId);
        echo 'Contract schedule delete complete!<br/>';

        //Удаляем страховки
       /* $insuranceId = ContractInsurance::whereIn('contract_id', $contractId)->pluck('id')->toArray();
        ContractInsurance::destroy($insuranceId);
        echo 'Contract insurances delete complete!<br/>'; */

        //Удаляем все кредиты
        Contract::destroy($contractId);
        echo 'Contract delete complete!<br/>';

        //Удаляем все товары в договорах
        $productId = OrderProduct::whereIn('order_id', $orderId)->pluck('id')->toArray();
        OrderProduct::destroy($productId);
        echo 'Order delete complete!<br/>';

        //Удаляем все договоры
        Order::destroy($orderId);
        echo 'Order delete complete!<br/>';


        //Удаляем соответствия
        $OldCreditOrder = OldCreditOrder::all()->pluck('id')->toArray();
        OldCreditOrder::destroy($OldCreditOrder);

        $buyers = Buyer::with('old.kyc')->with('settings')->get();

        foreach ($buyers as $buyer){
            if($buyer->settings){
                $buyer->settings->limit =  $buyer->old->kyc->credit_year??Config::get( 'test.buyer_defaults.limit' );
                $buyer->settings->balance =  $buyer->settings->limit;
                $buyer->settings->save();
            }
        }
    }


    public function clearUsers(){
        //Выбираем всех импортированных покупателей
        $id = OldUserBuyer::all()->pluck('user_id')->toArray();

        //Настройки покупателей
        $buyerSettings = BuyerSetting::whereIn('user_id', $id)->pluck('id')->toArray();
        BuyerSetting::destroy($buyerSettings);

        //Персональные данные покупателей
        $buyerPersonals = BuyerPersonal::whereIn('user_id', $id)->pluck('id')->toArray();
        BuyerPersonal::destroy($buyerPersonals);

        //Адреса покупателей
        $buyerAddresses = BuyerAddress::whereIn('user_id', $id)->pluck('id')->toArray();
        BuyerAddress::destroy($buyerAddresses);

        //Карты покупателей
        $buyerCards = Card::whereIn('user_id', $id)->pluck('id')->toArray();
        Card::destroy($buyerCards);

        echo 'Buyer delete complete!<br/>';

        if(count($id) > 0) {
            //Выбираем всех импортированных компании
            $companyId = Partner::whereIn('id', $id)->pluck('company_id')->toArray();
            Company::destroy($companyId);


            //Настройки покупателей
            $partnerSettings = PartnerSetting::whereIn('company_id', $companyId)->pluck('id')->toArray();
            PartnerSetting::destroy($partnerSettings);

            echo 'Partner delete complete!<br/>';

        }

        //Удаляем роль
        $users = User::whereIn('id', $id)->get();

        if($users){
            foreach ($users as $user){
                $user->detachRole('buyer');
                $user->detachRole('partner');
            }
        }

        //Удаляем файлы пользователя

        $files = File::whereIn('element_id', $buyerPersonals)->pluck('id')->toArray();
        File::destroy($files);
        foreach ($buyerPersonals as $buyerPersonal){
            Storage::deleteDirectory("buyer-personal/{$buyerPersonal}/");
        }

        //Удаляем пользователей
        Buyer::destroy($id);

        //Удаляем соответствия
        $oldUserBuyer = OldUserBuyer::all()->pluck('id')->toArray();
        OldUserBuyer::destroy($oldUserBuyer);
    }


    private function checkDate($date = false){
        $dt = \DateTime::createFromFormat('Y-m-d', $date);
        $date_errors = \DateTime::getLastErrors();
        $isValid = true;
        if ($date_errors['warning_count'] + $date_errors['error_count'] > 0) {
            $isValid = false;
        }

        return $isValid;
    }
}
