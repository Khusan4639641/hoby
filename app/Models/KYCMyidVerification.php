<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KYCMyidVerification extends Model
{

    protected $table = 'kyc_myid_verifications';

    CONST CREATED_TYPE = 0;
    CONST APPROVE_TYPE = 1;
    CONST REJECTED_TYPE = 2;


    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class, 'id', 'contract_id');
    }
    public function contract_status(): HasOne
    {
        return $this->hasOne(ContractStatus::class, 'contract_id', 'contract_id');
    }

    public function buyer(): HasOne
    {
        return $this->hasOne(Buyer::class, 'id', 'buyer_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'element_id', 'id')->where('model', Buyer::CONTRACT_KYC_FILE_TYPE);
    }

    public function myIdSelfie(): HasOne
    {
        return $this->hasOne(File::class, 'user_id', 'buyer_id')->where('model', 'buyer-personal')->where('type', Buyer::PASSPORT_SELFIE_FOR_CONTRACT)->orderBy('id', 'DESC');;
    }
}
