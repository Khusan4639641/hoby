<?php

namespace App\Models;

use App\Helpers\EncryptHelper;
use App\Models\V3\District;
use App\Models\V3\Region;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;


class Buyer extends User {

    const IN_BLACK_LIST = 1;
    const NOT_IN_BLACK_LIST = 1;
    const CONTRACT_KYC_FILE_TYPE = 'contract-kyc';
    const PASSPORT_SELFIE_FOR_CONTRACT='passport_selfie_for_contract';

    public function getRegistrationDateAttribute(){
        return Carbon::parse( $this->attributes['created_at'] )->format( 'd.m.Y H:i:s' );
    }

    public function getPhoneWithOutPlusAttribute() {
        return str_replace('+', '', $this->attributes['phone']);
    }

    public function actions() {
        return $this->hasManyThrough( Record::class, Contract::class ,'user_id');
    }

    public function personalData() {
        return $this->hasOne( BuyerPersonal::class, 'user_id' );
    }

    public function personals() {
        return $this->hasOne( BuyerPersonal::class, 'user_id' );
    }

    public function district() {
        return $this->belongsTo( District::class, 'local_region', 'cbu_id');
    }

    public function region() {
        return $this->belongsTo( Region::class, 'region', 'cbu_id');
    }

    public function partner() {
        return $this->hasOne( PartnerSetting::class, 'company_id' );
    }

    public function addressRegistration() {
        return $this->hasOne( BuyerAddress::class, 'user_id')->whereType('registration');
    }

    public function addressResidential() {
        return $this->hasOne( BuyerAddress::class, 'user_id')->whereType('residential');
    }

    public function addressesShipping() {
        return $this->hasMany( BuyerAddress::class, 'user_id')->whereType('shipping');
    }

    public function addressWorkplace() {
        return $this->hasOne( BuyerAddress::class, 'user_id')->whereType('workplace');
    }

    public function addresses() {
        return $this->hasMany( BuyerAddress::class, 'user_id');
    }

    public function settings() {
        return $this->hasOne( BuyerSetting::class, 'user_id' );
    }

    public function personalAccount() {
        return $this->hasOne( BuyerSetting::class, 'user_id' )->select('id','user_id','personal_account');
    }

    public function katm() {
        return $this->hasMany( KatmScoring::class, 'user_id' )->orderBy('updated_at', 'desc');
    }

    // первая карта
    public function card() {
        return $this->hasOne( Card::class, 'user_id' );
    }

    public function cards() {
        return $this->hasMany( Card::class, 'user_id' );
    }

    public function cardsActive() {
        return $this->hasMany( Card::class, 'user_id' )->where('status',1);
    }

    public function cardsInactive() {
        return $this->hasMany( Card::class, 'user_id' )->where('status',0);
    }

    public function cardsPnfl() {
        return $this->hasMany( CardPnfl::class, 'user_id' );
    }

    public function pnflContract() {
        return $this->hasOne( CardPnflContract::class, 'user_id' );
    }

    public function contracts() {
        return $this->hasMany(Contract::class, 'user_id');
    }


    public function payments() {
        return $this->hasMany(ContractPaymentsSchedule::class, 'user_id');
    }

    public function pay() {
        return $this->hasMany(Payment::class, 'user_id');
    }

    public function IsDebts() {
        return $this->hasMany(ContractPaymentsSchedule::class, 'user_id')->where('status', 0)->where('payment_date', '<', Carbon::now()->format("Y-m-d H:i:s"))->whereHas('contract', function (Builder $query) {
            $query->whereIn('status', [3,4]); // если клиент просрочник
        });
    }

    public function debts() {
        return $this->hasMany(ContractPaymentsSchedule::class, 'user_id')
            ->select('contract_payments_schedule.*')
            ->where('contract_payments_schedule.status', 0)
            ->where('contract_payments_schedule.payment_date', '<', Carbon::now()->format("Y-m-d 23:59:59"))
            ->whereIn('contracts.status', [1, 3, 4])
            ->whereColumn('contracts.user_id', '=', 'contract_payments_schedule.user_id')
            ->leftJoin('contracts', 'contract_payments_schedule.contract_id', '=', 'contracts.id')
            ->orderBy('contract_payments_schedule.created_at', 'desc')
        ;
    }

    public function full_debts() {
        return $this->hasMany(ContractPaymentsSchedule::class, 'user_id')
            ->select('contract_payments_schedule.*', 'contracts.status as contract_status', 'contracts.cancel_reason as cancel_reason', 'contracts.id as contract_id')
            ->where('contract_payments_schedule.status', 0)
            ->where('contract_payments_schedule.payment_date', '<', Carbon::now()->format("Y-m-d 23:59:59"))
            ->whereIn('contracts.status', [1, 3, 4, 9])
            ->whereColumn('contracts.user_id', '=', 'contract_payments_schedule.user_id')
            ->leftJoin('contracts', 'contract_payments_schedule.contract_id', '=', 'contracts.id')
            ->orderBy('contract_payments_schedule.created_at', 'desc')
        ;
    }

    public function deptOfMIB(){
        return $this->hasMany(CollectCost::class, 'user_id')
            ->where("status", 0)
        ;
    }

    public function deptOfAutopayHistory(){
        return $this->hasMany(AutopayDebitHistory::class, 'user_id')
            ->where("status", 0)
        ;
    }

    public function user() {
        return $this->hasOne(User::class, 'id', 'id');
    }

    public function kyc() {
        return $this->hasOne(User::class, 'id', 'kyc_id');
    }

    public function history() {
        return $this->hasOne(KycHistory::class, 'user_id', 'id')->orderBy('created_at', 'desc');
    }

    // несколько записей
    public function scoringLog() {
        return $this->hasMany(CardScoringLog::class, 'user_id', 'id');
    }

    // несколько доверителей
    public function guarants() {
        return $this->hasMany(BuyerGuarant::class, 'user_id', 'id')->latest('id');
    }

    public function royxats() {
        return $this->hasMany(RoyxatCredits::class, 'user_id', 'id');
    }

    public function taxes() {
        return $this->hasMany(BuyerGnkSalary::class, 'user_id', 'id');
    }

    public function mib() {
        return $this->hasMany(KatmMib::class, 'user_id', 'id');
    }

    public function katmDefault() {
        return $this->hasMany(KatmScoring::class, 'user_id', 'id');
    }

    public function katmInfoscore() {
        return $this->hasMany(KatmInfoscore::class, 'user_id', 'id');
    }

    public function katmInfoscoreReports()
    {
        return File::where([
            'element_id' => $this->id,
            'model' => File::MODEL_BUYER,
            'type' => File::TYPE_REPORT,
        ])
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    // сохранение распознанных паспортных данных пользователя от сервиса OCR
    public static function saveOcrData(Buyer &$buyer, Array &$request){
        // данные вводятся автоматически из OCR и хранятся в таблице.поле OCR.response
        // !данные вручную не изменяются!
        // данные ФИО, адреса, будут извлекаться из OCR и KATM и сохраняться в модель
        // если данные для изменения есть в запросе

        $buyer->name       = isset($request['names']) ? $request['names'] : '';
        $buyer->surname    = isset($request['surname']) ? $request['surname'] : '';
        $buyer->patronymic = ''; // $request->patronymic;
        //$buyer->status     = 2;
        $buyer->birth_date = isset($request['date_of_birth']) ? date('Y-m-d', strtotime( EncryptHelper::encryptData( $request['date_of_birth'] ) ) ) : '';

        $buyer->save();

        //Log::info($buyer);

        $buyerPersonals          = $buyer->personals ?? new BuyerPersonal();
        $buyerPersonals->user_id = $buyer->id;
        $buyerPersonals->birthday     = isset($request['date_of_birth']) ? EncryptHelper::encryptData( $request['date_of_birth'] ) : '';
        $buyerPersonals->passport_expire_date = isset($request['expiration_date']) ? EncryptHelper::encryptData( $request['expiration_date'] ) : '';
        $buyerPersonals->home_phone   = ''; // EncryptHelper::encryptData( $request->home_phone );
        $buyerPersonals->pinfl        = isset($request['personal_number']) ? EncryptHelper::encryptData( $request['personal_number'] ) : '';
        $buyerPersonals->pinfl_hash        = isset($request['personal_number']) ? md5( $request['personal_number'] ) : '';
        $buyerPersonals->work_company = ''; //EncryptHelper::encryptData( $request->work_company );
        $buyerPersonals->work_phone   = ''; //EncryptHelper::encryptData( $request->work_phone );
        $buyerPersonals->passport_number   = isset($request['number']) ? EncryptHelper::encryptData( $request['number'] ) : '';
        $buyerPersonals->passport_number_hash   = isset($request['number']) ? md5( $request['number'] ) : '';
        $buyerPersonals->save();

        /*
        $buyerAddress = $buyer->addressResidential ?? new BuyerAddress();
        $buyerAddress->user_id  = $buyer->id;
        $buyerAddress->type     = 'residential';
        //$buyerAddress->postcode = $request->address_postcode;
        //$buyerAddress->country  = $request->address_country;
        $buyerAddress->region = $request->address_region;
        $buyerAddress->area = $request->address_area;
        $buyerAddress->city = $request->address_city;
        $buyerAddress->address = $request->address;
        $buyerAddress->save(); */

    }

    // уровень для paycoin
    public function levels(){
        return $this->hasMany(PaycoinsType::class,'user_id','id');
    }

    /**
     * Get Buyer Info
     *
     * @param int $user_id
     * @return array
     */
    public static function getInfo(int $user_id): array
    {

        if (
            $buyer = self::select(
                    'id',
                    'lang',
                    'device_os',
                    'firebase_token_ios',
                    'firebase_token_android'
                )
                ->where('id',$user_id)
                ->first()
        ) {

            if ( $buyer->device_os === 'ios' ) {
                $token = $buyer->firebase_token_ios;
            }
            elseif ( $buyer->device_os === 'android' ) {
                $token = $buyer->firebase_token_android;
            }
            else {
                return [
                    'status' =>'error',
                    'Device not mobile is: ' . $buyer->device_os . ' ' .$user_id
                ];
            }
            return [
                'status' =>'success',
                'token'=>$token,
                'system'=>$buyer->device_os,
                'user_id'=>$user_id,
                'lang'=>$buyer->lang
            ];
        }

        return [
            'status' =>'error',
            'info'=>'Buyer not found: ' . $user_id
        ];
    }

    public function getGender() {

        if ($this->gender === 2) {
            return 'Женщина';
        }

        if ($this->gender === 1) {
            return 'Мужчина';
        }

        return 'Не задано';

    }

    // Get selected postal region external id for recovery letter dropdown
    public function getPostalRegionSelected() {

        if ($this->addressResidential && $this->addressResidential->postal_region) {
            return $this->addressResidential->postal_region;
        }

        if ($this->region) {
            return PostalRegion::getExternalIdByKatmRegion($this->region);
        }
    }

    // Get selected postal area external id for recovery letter dropdown
    public function getPostalAreaSelected() {

        if ($this->addressResidential && $this->addressResidential->postal_area) {
            return $this->addressResidential->postal_area;
        }

        if ($this->local_region) {
            return PostalArea::getExternalIdByKatmLocalRegion($this->local_region);
        }
    }

    public function autopayDebitHistory(){
        return $this->hasMany(AutopayDebitHistory::class, 'user_id', 'id');
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class,'user_id');
    }



}
