<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MyIDJob extends Model
{
    const TYPE_REGISTRATION = 'register';
    const TYPE_CONTRACT_ACTIVATION = 'contract';

    protected $table = 'myid_jobs';

    protected $fillable = [
        'user_id',
        'job_id',
        'pass_data',
        'pinfl',
        'birth_date',
        'agreed_on_terms',
        'external_id',
        'photo_from_camera',
        'comparison_value',
        'result_code',
        'result_note',
        'profile',
        'type',
        'contract_id',
        'status_code'
    ];

    protected $casts = ['profile' => 'array'];

    public function buyer()
    {
        return $this->belongsTo(Buyer::class,'user_id');
    }

    public function photo() : HasOne
    {
        return $this->hasOne(File::class,'photo_from_camera');
    }
}
