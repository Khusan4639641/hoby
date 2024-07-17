<?php

namespace App\Models;

use App\Classes\Payments\Interfaces\IUser;
use App\Classes\Payments\PaymentException;
use App\Helpers\SmsHelper;
use App\Helpers\TelegramHelper;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laratrust\Traits\LaratrustUserTrait;
use phpDocumentor\Reflection\Types\Self_;

class User extends Authenticatable implements IUser
{
    use LaratrustUserTrait;
    use Notifiable;

    // TODO: Нужно убрать все константы из модели!!! (08.07.2022 DEV-277/DEV-289/feature)

    const STATUS_CARD_ADD = 5; // добавлена карта и пройден скоринг
    const STATUS_OCR_PASSPORT_STEP1 = 6; // загрузка фото первый раз
    const STATUS_OCR_PASSPORT_STEP2 = 7; // повторная загрузка фото

    // статусы от KYC оператора
    const KYC_STATUS_NULL                 = null;
    const KYC_STATUS_CREATE               = 0;  // создан
    const KYC_STATUS_EDIT                 = 1;  // редактируется
    const KYC_STATUS_UPDATE               = 2;  // изменен
    const KYC_STATUS_MODIFY               = 3;  // пользователь обновил данные
    const KYC_STATUS_VERIFY               = 4;  // верифицирован
    const KYC_STATUS_SCORING              = 5;  // скоринг пользователя
    const KYC_STATUS_SCORING_BLOCKED      = 6;  // скоринг не пройден
    const KYC_STATUS_SCORING_ROYXAT       = 7;  // скоринг не пройден Royxat
    const KYC_STATUS_BLOCKED              = 8;  // отказ верификации
    const KYC_STATUS_SCORING_PINFL        = 9;  // скоринг не пройден pinfl уже существует
    const KYC_STATUS_RESCORING            = 10; // REскоринг
    const KYC_STATUS_GUARANT              = 11; // добавил доверителя
    const KYC_SHOULD_ADD_GUARANT          = 12; // следует добавить доверителя (уже добавлена основная карта)
    const KYC_STATUS_KYC_MODIFY           = 19; // KYC оператор обновил данные

    const KYC_STATUS_IN_BLACK_LIST        = 125; // Пользователь в чёрном списке
    const KYC_STATUS_NOT_SOLVENT          = 126; // Не прошёл платёжеспособность

    const KYC_STATUS_SCORING_CARD_BALANCE = 16; // нет денег на карте
    const KYC_STATUS_SCORING_CARD         = 17; // не получилось списать с карты
    const SCORING_CARD_ERROR_500          = 18; // server error 500
    const KYC_STATUS_SCORING_MIB_BLOCKED  = 25; // MIB скоринг не пройден

    const RECOVER_CALL        = 200;
    const RECOVER_CALL_WAIT   = 201;
    const RECOVER_LETTER      = 202;
    const RECOVER_LETTER_WAIT = 203;
    const RECOVER_NOTARIUS    = 204;
    const RECOVER_MIB         = 205;
    const RECOVER_CONTROL     = 206;
    const RECOVER_COMPLETE    = 207;


    const CARD_ACTIVE    = 100; // активировал карту
    const CARD_INACTIVE  = 101; // инактивировал карту
    const CARD_DELETED   = 102; // удалил карту
    const CARD_ADD       = 103; // нажал на кнопку "добавить карты по пнфл"
    const CONTRACT_ADD   = 104; // создан контракт с банком


    // статусы пользователя
    public static $statuses = [
        0  => 'Создан',
        1  => 'Редактируется',
        2  => 'Изменен',
        3  => 'Пользователь обновил данные',
        4  => 'Верифицирован',
        5  => "Скоринг пользователя",
        6  => "Скоринг не пройден",
        7  => "Скоринг не пройден Royxat",
        8  => "Отказ верификации",
        9  => "Скоринг не пройден pinfl уже существует",
        10 => "REскоринг",
        11 => "Добавил доверителя",

        12 => 'Необходимо добавить доверителя', // 29.03.2022 new_feature.principal_absent_refusal_reason_for_KYC_moderation

        16 => "Нет денег на карте",
        17 => "Не получилось списать с карты",
        18 => "Server error 500",
        19 => 'KYC оператор обновил данные',

        200 => 'Обзвон',
        201 => 'Ожидание обзвона',
        202 => 'Письмо к отправке',
        203 => 'Ожидание по письму',
        204 => 'Передача нотариусу',
        205 => 'Передача в БПИ (МИБ)',
        206 => 'Контроль',
        207 => 'Завершен',
    ];

    // статусы покупателя
   /* const STATUS_NEW = 0; // новый
    const STATUS_EDIT = 1; // редактируется
    const STATUS_MODIFY = 2; // модерируется
    const STATUS_VERIFY = 4; // верифицирован */



    protected $table = 'users';
	//public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'surname', 'patronymic', 'seller_bonus_percent', 'region', 'local_region'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'api_token'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function scoringResult()
    {
        return $this->hasMany(ScoringResult::class, 'user_id', 'id')
            ->typed();
    }

    public function scoringResultMini()
    {
        return $this->hasMany(ScoringResultMini::class, 'user_id', 'id')
            ->typed();
    }

    public function getFioAttribute () {
        return trim("{$this->surname} {$this->name} {$this->patronymic}");
    }

    public function getFiAttribute () { // Имя и Фамилия
        return trim("{$this->name} {$this->surname}");
    }

    public function getFullNameAttribute () {
        return $this->fio;
    }

    public function getUpdatedDataAttribute () {
        return Carbon::parse( $this->attributes['updated_at'] )->format( 'd.m.Y H:i:s' );
    }

    public function setPhoneAttribute($value){
        $this->attributes['phone'] = preg_replace('/^\+/', '', $value);
    }

    public function avatar(){
        return $this->hasOne(File::class, 'element_id')->where('model', 'user')->where('type', 'avatar');
    }

    public function payments() {
        return $this->hasMany(ContractPaymentsSchedule::class, 'user_id');
    }

    public function paymentsDelays() {
        $date = date('Y-m-d :23:59:00');
        return $this->hasMany(ContractPaymentsSchedule::class, 'user_id')->where('status',0)->where('payment_date','<',$date)->orderBy('payment_date','ASC');
    }

    public function getPhoneAttribute() {
        return '+'.$this->attributes['phone'];
    }

    public function cartProducts() {
        return $this->hasMany(Cart::class);
    }

    public function cartProduct() {
        return $this->hasOne(Cart::class);
    }

    public function kyc() {
        return $this->hasOne(User::class, 'id', 'kyc_id');
    }

    public function history() {
        return $this->hasOne(KycHistory::class, 'user_id', 'id');
    }

    public function kycinfo() {
        return $this->hasOne(KycInfo::class, 'user_id', 'id');
    }

    // Информация о доходах от ГНК
    public function gnkSalary() {
        return $this->hasOne(BuyerGnkSalary::class, 'user_id', 'id');
    }

//  dev_nurlan
    public function invoices() {
        return $this->hasMany(ContractInvoice::class, 'user_id', 'id');
    }

    // смена статуса пользователя
    public static function changeStatus(&$user,$status){

        $msg = 'OCR buyer change user status ' . $user->id .' from: ' . $user->status . ' to: ' . $status;
        Log::info($msg);

            if($status == 2){

            if(!isset($user->settings)){
                $status = 1;
            }

            $link = $_SERVER['SERVER_NAME'] . '/ru/panel/buyers/' . $user->id;
            $msg = "У клиента id: <b>{$user->id}</b>\nИзменился статус на <b>ОЖИДАНИЕ ВЕРИФИКАЦИИ</b>!\n{$link}";

            TelegramHelper::send($msg);
           /* $txt = 'Cliend ID ' . $user->id . ' is waiting for kyc decision ' . $user->updated_at;
            TelegramHelper::sendByChatId('31590926',$txt); */
        }

        if($status==4){ // временно , если нет карты
            if( Card::where('user_id',$user->id)->first() ){
                $user->status = 4;
            }
        }else {
            // проверка на вип клиента - вип нельзя блочить, верифицируем
            if($status == 8 && $user->vip == 1) {
                $status = 4;
                // если клиент вип, отправим смс о верификации, вместо того, чтобы заблочить
                $msg = "Tabriklaymiz! Siz verifikatsiya bosqichidan o'tdingiz. Sizning limit " . @$user->settings->limit
                    . " sum. Xaridlarni amalga oshirishda telefon raqamingizdan foydalaning!"
                ;

                SmsHelper::sendSms($user->phone,$msg);
                Log::channel('katm')->info('vip : user_id: ' . $user->id . ' ' .   $msg);
            }
            $user->status = $status;
        }

        if($user->status==4){ // т.к. вверху временно, пока нет карты
            $msg = 'Верификация клиента <b>' . $user->id . "</b>\n" . 'Редактор: <b>' . Auth::user()->name . ' ' . Auth::user()->surname .'</b>';
            TelegramHelper::send($msg);

            // если имеется ссылка от вендора, отправка смс клиенту для перехода по ней
            if(isset($user->personals) && $user->personals->vendor_link!=''){

                $limit = $user->settings->limit;

                $msg = "Siz resusNasiya platformasida ro'yxatdan o'tdingiz. Limitingiz {$limit} so'm. Hamkorlarimizdan xaridni davom ettiring " . $user->personals->vendor_link;

                SmsHelper::sendSms($user->phone,$msg);
                Log::channel('users')->info($msg);
            }

        }

        if($user->status==8){ // т.к. вверху временно, пока нет карты

            $msg = 'Блокировка клиента <b>' . $user->id . "</b>\n" . 'Редактор: <b>' . Auth::user()->name . ' ' . Auth::user()->surname .'</b>';
            TelegramHelper::send($msg);
        }

        if($msg) Log::channel('users')->info($msg);

        $user->save();

    }

    // получить статус по id
    public static function getStatus($status){
        return self::$statuses[$status] ?? '';
    }



    // возвращает по условию
    /*public function scopeWhereIs($column,&$params){

        if(isset($params['status_loe'])){
            $condition = '<=';
            $value = $params['status_loe'];
        }else if(isset($params['status_moe'])){
            $condition = '>=';
            $value = $params['status_moe'];
        }else if(isset($params['status_less'])){
            $condition = '<';
            $value = $params['status_less'];
        }else if(isset($params['status_more'])){
            $condition = '>';
            $value = $params['status_more'];
        }else{
            return $this;
        }

        return $this->where($column,$condition,$value);

    }*/

    public function responsible_partner_companies()
    {
        return $this->hasMany(Company::class, 'manager_id', "id");
    }

    public function userID(): int
    {
        return $this->id;
    }

    public function balance(): float
    {
        $balance = 0;
        if ($this->settings) {
            $balance = $this->settings->personal_account;
        }
        return $balance;
    }

    /**
     * @throws PaymentException
     */
    public function refill(float $amount)
    {
        if ($this->settings) {
            $this->settings->personal_account += $amount;
            $this->settings->personal_account = round($this->settings->personal_account, 2);
            $this->settings->save();
        } else {
            throw new PaymentException("buyer_settings не найден",
                [
                    'ID Пользователя' => $this->id,
                ]);
        }
    }

    /**
     * @throws PaymentException
     */
    public function debit(float $amount): bool
    {
        if (!$this->settings) {
            throw new PaymentException("buyer_settings не найден",
                [
                    'ID Пользователя' => $this->id,
                ]);
        }
        if ($this->settings->personal_account < $amount) {
            return false;
        }
        $this->settings->personal_account -= $amount;
        $this->settings->personal_account = round($this->settings->personal_account, 2);
        $this->settings->save();
        return true;
    }

    public function getAgeAttribute(): int
    {
        return (new Carbon($this->birth_date))->diffInYears(Carbon::now());
    }

  public function bonusSharers()
  {
    return $this->hasMany(SellerBonusSharer::class, 'user_id', 'id');
  }

    public function cards()
    {
        return $this->hasMany(Card::class);
    }

    public function addressRegistration() {

        return $this->hasOne( BuyerAddress::class, 'user_id')->whereType('registration');

    }

}
