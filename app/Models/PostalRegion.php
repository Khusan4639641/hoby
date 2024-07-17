<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PostalRegion extends Model
{
    protected $table = 'postal_regions';

    protected $fillable = [
        'external_id', 'name', 'katm_region'
    ];

    public static function getExternalIdByKatmRegion($katm_region) {

        $postal_region = self::where('katm_region', $katm_region)->first();

        return $postal_region ? $postal_region->external_id : null;
    }
}
