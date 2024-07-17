<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MfoSettings extends Model
{
    protected $table = "mfo_settings";

    protected $fillable = ['general_company_id',
                            'loan_type_code',
                            'credit_object_code',
                            'currency_code',
                            'bank_code',
                            'contract_type_code',
                            'subject_type_code',
                            'borrower_type_code',
                            'reason_early_termination',
                            'disclaimer_note',
                            'issuance_form',
                            'payment_purpose',
                            'type_loan_collateral'];

    protected $hidden   = ['created_at', 'updated_at'];

    public function generalCompany() {
        return $this->belongsTo(GeneralCompany::class, 'general_company_id');
    }
}
