<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimilarityCheck extends Model
{
    protected $table    = 'similarity_checks';
    protected $fillable = ['user_id',
                           'user_name',
                           'user_surname',
                           'user_patronymic',
                           'card_id',
                           'card_name',
                           'card_number',
                           'card_valid_date',
                           'similarity_percent_fw',
                           'similarity_percent_rev',
                           'min_percent'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }
}
