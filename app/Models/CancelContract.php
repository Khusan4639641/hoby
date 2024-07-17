<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CancelContract extends Model {

    /* Attributes */

    protected $appends = ['status_caption'];

	//public $incrementing = false;

    public function getCanceledAtAttribute() {
        return Carbon::parse( $this->attributes['canceled_at'] )->format( 'd.m.Y' );
    }

    public function getCreatedAtAttribute() {
        return Carbon::parse( $this->attributes['created_at'] )->format( 'd.m.Y' );
    }

    public function getUpdatedAtAttribute() {
        return Carbon::parse( $this->attributes['updated_at'] )->format( 'd.m.Y' );
    }

    public function buyer() {
        return $this->belongsTo(Buyer::class, 'user_id');
    }



}
