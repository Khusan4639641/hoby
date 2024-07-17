<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KatmInfoscore extends Model
{

    public $table = 'katm_infoscore';

    public function buyer()
    {
        return $this->belongsTo(Buyer::class, 'user_id');
    }

}
