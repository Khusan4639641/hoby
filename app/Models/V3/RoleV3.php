<?php

namespace App\Models\V3;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;


/**
 * @property Collection|mixed $permissions
 */
class RoleV3 extends Model
{
    protected $table = 'roles';
    const FULL_ADMIN = 'admin';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(PermissionV3::class, 'permission_role','role_id','permission_id');
    }
}
