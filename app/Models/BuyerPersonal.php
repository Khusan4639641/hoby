<?php

namespace App\Models;

use App\Helpers\EncryptHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class BuyerPersonal extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',               // NOT NULL
        'birthday',              // NULL
        'city_birth',            // NULL
        'work_company',          // NULL
        'work_phone',            // NULL
        'passport_number',       // NULL
        'passport_number_hash',  // NULL
        'passport_date_issue',   // NULL
        'passport_issued_by',    // NULL
        'passport_expire_date',  // NULL
        'passport_type',         // NULL
        'home_phone',            // NULL
        'pinfl',                 // NULL
        'pinfl_hashIndex',       // NULL
        'pinfl_status',          // NOT NULL
        'inn',                   // NULL
        'mrz',                   // NULL
        'social_vk',             // NULL
        'social_facebook',       // NULL
        'social_linkedin',       // NULL
        'social_instagram',      // NULL
        'vendor_link',           // NULL
        'created_at',            // NULL
        'updated_at',            // NULL
    ];

    public function files(){
        return $this->hasMany(File::class, 'element_id')->where('model', 'buyer-personal');
    }

    public function passport_selfie(){
        return $this->hasOne(File::class, 'element_id')->where('model', 'buyer-personal')->where('type', 'passport_selfie')->latest();
    }

//    public function id_selfie(){
//        return $this->hasOne(File::class, 'element_id')->where('model', 'buyer-personal')->where('type', 'id_selfie')->latest();
//    }

    public function latest_id_card_or_passport_photo() {
        $query = $this->hasOne(File::class, 'element_id')->where('model', 'buyer-personal');
        $query->where(function ($query) {
            $query->where('type', 'passport_selfie')
                ->orWhere('type', 'id_selfie');
        });
        return $query->latest();
    }

    public function passport_first_page(){
        return $this->hasOne(File::class, 'element_id')->where('model', 'buyer-personal')->where('type', 'passport_first_page');
    }

    public function passport_with_address(){
        return $this->hasOne(File::class, 'element_id')->where('model', 'buyer-personal')->where('type', 'passport_with_address');
    }

    public function buyer(){
        return $this->hasOne(Buyer::class, 'id','user_id');
    }

}
