<?php

namespace App\Models\V3;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin  Builder
 */
class MobileAppRelease extends Model
{
    protected $fillable = [
        'os',
        'bundle_name',
        'version'
    ];
}
