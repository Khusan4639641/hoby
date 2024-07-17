<?php

namespace App\Models\V3;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @method static paginate()
 * @property mixed $id
 * @property mixed $phone
 */
class UserV3 extends Authenticatable
{
    protected $table = 'users';
    protected $fillable = [
        'role_id'
    ];
    protected $hidden = [
        'password',
        'phone',
        'remember_token',
        'api_token',
        'token_generated_at',
        'firebase_token_android',
        'firebase_token_ios',
    ];

    // А почему не role() ? Связь же ведь один к одному (inverse).
    public function roles(): BelongsTo
    {
        return $this->belongsTo(RoleV3::class, 'role_id');
    }


}
