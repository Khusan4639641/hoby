<?php

namespace App\Models;

use App\Libs\KycHistoryLibs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class KycHistory extends Model
{
    protected $table = 'kyc_history';


    public function kyc()
    {
        return $this->hasOne(User::class, 'id', 'kyc_id');
    }

    // запись истории покупателя
    public static function insertHistory(
        $buyer_id,
        $status,
        $kyc_status = null,
        $reason = null,
        $_title = null,
        $image = null,
        $old_phone = null,
        $old_address = null,
        $operatorID = 0
    )
    {

        if ($reason && is_string($reason)) $reason = KycHistoryLibs::getKycReasonIndex($reason); // получить id причины, если причина строка

        if (Auth::check()) {
            $operatorID = Auth::user()->id;
        }

        $kycHistory = new KycHistory();
        $kycHistory->user_id = $buyer_id; // покупатель
        $kycHistory->kyc_id = $operatorID; //  KYC оператор
        $kycHistory->status = $status ?? null; // статус покупателя новый, редактируется, изменен, модерация, верифицирован, заблокирован
        $kycHistory->kyc_status = $kyc_status ?? null; // статус отказа KYC
        $kycHistory->reason = $reason ?? null; // причина отказа
        $kycHistory->title = $_title ?? null; // номер карты, если операция с картами

        $kycHistory->image = $image ?? null; // фото
        $kycHistory->old_phone = $old_phone ?? null; // старый номер телефона
        $kycHistory->old_address = $old_address ?? null; // старый адрес

        $kycHistory->save();

    }

}
