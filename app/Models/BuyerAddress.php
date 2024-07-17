<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuyerAddress extends Model
{

    const TYPE_REGISTRATION = 'registration';
    const TYPE_RESIDENTIAL = 'residential';
    const TYPE_WORKPLACE = 'workplace';

    protected $appends = ['string'];

    protected $fillable = [
        'user_id', 'type', 'postcode', 'country', 'region', 'area', 'city', 'postal_region', 'postal_area', 'address', 'address_myid'
    ];

    public function getStringAttribute()
    {
        $nameLocale = 'name' . ucfirst(app()->getLocale());
        $arrAddress = [];

        if (isset($this->regionCaption->$nameLocale))
            $arrAddress[] = $this->regionCaption->$nameLocale;

        if (isset($this->areaCaption->$nameLocale))
            $arrAddress[] = $this->areaCaption->$nameLocale;

        if (isset($this->cityCaption->$nameLocale))
            $arrAddress[] = $this->cityCaption->$nameLocale;

        if ($this->address)
            $arrAddress[] = $this->address;


        return implode(', ', $arrAddress);
    }

    public function regionCaption()
    {
        return $this->hasOne(Region::class, 'regionid', 'region');
    }

    public function areaCaption()
    {
        return $this->hasOne(Area::class, 'areaid', 'area');
    }

    public function cityCaption()
    {
        return $this->hasOne(City::class, 'cityid', 'city');
    }

    public function postalArea()
    {
        return $this->hasOne(PostalArea::class, 'external_id', 'postal_area');
    }

    public function postalRegion()
    {
        return $this->hasOne(PostalRegion::class, 'external_id', 'postal_region');
    }

}
