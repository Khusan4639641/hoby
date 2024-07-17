<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Company extends Model
{
    const PINFL_LENGTH = 14;
    const resus_COMPANY_ID = [216449, 216817];

    public $image_path = '/images/companies/';


    protected $casts = [
        'date_pact' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id', 'company_id');
    }


//    public function getManagerFioAttribute() {
//        $manager = User::find($this->attributes['manager_id']);
//        return $manager->fio;
//    }

    public function accounts(): HasMany
    {
        return $this->hasMany(CompanyAccount::class, 'company_id', 'id');
    }

    public function isresus()
    {
        return in_array($this->id, self::resus_COMPANY_ID);
    }

    public function isIndividualEntrepreneur(): bool
    {
        return strlen($this->inn) === self::PINFL_LENGTH;
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id', 'id');
    }

    public function tariffs()
    {
        return $this->belongsToMany(AvailablePeriod::class, 'company_available_periods', 'company_id', 'period_id')
            ->withTimestamps();
    }

    public function getCreatedAtAttribute()
    {
        return Carbon::parse($this->attributes['created_at'])->format('d.m.Y');
    }

    public function logo()
    {
        return $this->hasOne(File::class, 'element_id', 'id')->where('model', 'company')->where('type', 'logo');
    }

    public function settings()
    {
        return $this->hasOne(PartnerSetting::class, 'company_id');
    }

    public function affiliates()
    {
        return $this->hasMany(Company::class, 'parent_id');
    }

    public function categories()
    {
        return $this->hasMany(CatalogPartners::class, 'partner_id', 'id'); //->select('catalog_id as id');
    }

    public function region()
    {
        return $this->hasOne(Region::class, 'regionid', 'region_id'); //->select('regionid as id','nameRu as name');
    }

    public function parent()
    {
        return $this->hasOne(Company::class, 'id', 'parent_id');
    }

    public function generalCompany()
    {
        return $this->hasOne(GeneralCompany::class, 'id', 'general_company_id');
    }

    public function currentUniqNum()
    {

        return $this->hasOne(CompanyUniqNum::class, 'company_id')->where('general_company_id', $this->general_company_id);
        //return $this->generalCompany()->where('general_company_id', $this->general_company_id);
    }

    public function blockReason()
    {
        return $this->hasOne(BlockingHistory::class, 'company_id')->orderByDesc('id');
    }

    public function childrens()
    {
        return $this->hasMany(__CLASS__, 'parent_id');
    }
}
