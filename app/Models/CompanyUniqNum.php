<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyUniqNum extends Model
{
    protected $fillable = [
        'company_id',
        'general_company_id',
        'uniq_num'
    ];

    public function company() {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function generalCompany() {
        return $this->belongsTo(GeneralCompany::class, 'general_company_id');
    }
}
