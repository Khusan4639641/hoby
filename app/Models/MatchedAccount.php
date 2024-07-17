<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MatchedAccount extends Model
{
    use SoftDeletes;

    protected $table = 'matched_accounts';
    public $fillable = [
        'mfo_mask',
        '1c_mask',
        'parent_id',
        'number',
        'mfo_account_name',
        'created_at'
    ];
    public function parent()
    {
        return $this->belongsTo(static::class, 'parent_id');
    }
}
