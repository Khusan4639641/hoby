<?php

namespace App\Helpers;

use App\Models\V3\RoleV3;
use App\Models\V3\UserV3;
use Illuminate\Http\Request;

class PermissionsHelper
{
    public static function check(Request $request, UserV3 $user): array
    {

        $result['code'] = 200;


        $role = $user->roles()->get()->keyBy('name')->toArray();

        if (array_key_exists(RoleV3::FULL_ADMIN, $role)) {
            return $result;
        }

        $permissions = $user->roles->permissions()->pluck('route_name')->all();

        //todo проверка доступен ли роут пользователю (уточнить бизнес логику)
        if (!in_array($request->route()->getName(), $permissions)) {
            $result['code'] = 403;
        }
        return $result;
    }

}
