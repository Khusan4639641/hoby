<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laratrust\Models\LaratrustRole;

class Role extends LaratrustRole
{
    const FULL_ADMIN = 'admin';
    const CLIENT_ROLE_ID = 11;
    const SALES_MANAGER_ROLE_ID = 18;
    const PARTNER_ROLE_ID = 13;
    const VENDOR_ROLE_ID = 25;
    const MEDIAPARK_SALES_MANAGER_ROLE_ID = 28;

    const VENDOR_ROLE_NAMES = ['partner','sales-manager','vendor','partner-eup','sales-manager-eup','vendor-eup'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'display_name', 'description'
    ];
}
