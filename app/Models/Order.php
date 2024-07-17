<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Order extends Model
{
    protected $appends = ['status_caption'];
    protected $fillable = ['user_id', 'partner_id', 'total','company_id', 'partner_total', 'credit', 'debit', 'status',
                           'test', 'city', 'region', 'area', 'address', 'shipping_code', 'shipping_price', 'online'];

    const PRODUCT_WITH_VAT = 1;
    const PRODUCT_WITHOUT_VAT = 2;
    const PRODUCT_EXEMPTED = 3;


    public function getCreatedAtAttribute() {
        return Carbon::parse( $this->attributes['created_at'] )->format( 'd.m.Y' );
    }

    public function getStatusCaptionAttribute() {
        return __( 'order.status_' . $this->attributes['status'] );
    }

    /* Scopes */

    public function scopeActual($query) {
        return $query->where('status', '>=', 3)->where('status', '!=', 5);
    }

    public function buyer(){
        return $this->hasOne(Buyer::class, 'id', 'user_id' );
    }

    public function partner(){
        return $this->hasOne(Partner::class,  'id', 'partner_id' );
    }

    public function company(){
        return $this->hasOne(Company::class,  'id', 'company_id' );
    }

    public function user() {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function contract(){
        return $this->hasOne(Contract::class, 'order_id');
    }
    public function contractsDelay(){
        return $this->hasMany(Contract::class, 'order_id','id')->whereIn('status',[3,4]);
    }
    public function contractIn(){
        return $this->hasOne(Contract::class, 'order_id')->where('status',1);
    }

    public function products(){
        return $this->hasMany(OrderProduct::class, 'order_id');
    }

    public function partnerSettings(){
        return $this->hasOne(PartnerSetting::class,  'company_id', 'company_id' );
    }



    public function getShippingAddressAttribute(){
        $nameLocale = 'name' . ucfirst(app()->getLocale());

        $arrAddress = [];

        if(isset($this->regionCaption->$nameLocale))
            $arrAddress[] = $this->regionCaption->$nameLocale;

        if(isset($this->areaCaption->$nameLocale))
            $arrAddress[] = $this->areaCaption->$nameLocale;

        if(isset($this->cityCaption->$nameLocale))
            $arrAddress[] = $this->cityCaption->$nameLocale;

        if($this->address)
            $arrAddress[] = $this->address;


        return implode(', ', $arrAddress);
    }

    public function regionCaption(){
        return $this->hasOne(Region::class, 'regionid', 'region');
    }

    public function areaCaption(){
        return $this->hasOne(Area::class, 'areaid', 'area');
    }

    public function cityCaption(){
        return $this->hasOne(City::class, 'cityid', 'city');
    }
}
