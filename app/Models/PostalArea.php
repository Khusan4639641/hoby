<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PostalArea extends Model
{
    protected $table = 'postal_areas';

    protected $fillable = [
        'external_id', 'name', 'postal_region_id', 'katm_local_region'
    ];

    public static function getExternalIdByKatmLocalRegion($katm_local_region) {

        $postal_area = self::where('katm_local_region', $katm_local_region)->first();

        return $postal_area ? $postal_area->external_id : null;
    }
}
