<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralCompany extends Model
{
    const MFO_COMPANY_ID = 3;
    const MFO_COMPANY = 1;

    protected $fillable = [
        "is_tpp"
    ];

    const TYPE_INSTALLMENT_PAYMENTS = 1;
    const TYPE_MFI = 2;

    public function contracts() {
        return $this->hasMany(Contract::class);
    }

    public function isMFO(): bool
    {
        return $this->is_mfo === self::MFO_COMPANY;
    }
}
