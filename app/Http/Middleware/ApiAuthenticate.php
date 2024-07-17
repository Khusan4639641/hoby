<?php

namespace App\Http\Middleware;

use App\Helpers\PermissionsHelper;
use App\Http\Response\BaseResponse;
use App\Models\V3\UserV3;
use App\Services\API\V3\BaseService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class ApiAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        switch ($this->authenticate($request)['code']) {
            case 400:
                return BaseService::errorJson(['bad response']);
            case 401:
                return BaseService::errorJson(['Unauthorized'], 'error',401);
            case 403:
                return BaseService::errorJson(['Access is denied'], 'error',403);
            case 406:
                return BaseService::errorJson(["Except Options [{$this->authenticate($request)['except']}]"], 'error',406);
        }
        return $next($request);
    }


    private function authenticate(Request $request): array
    {
        if ($user = $this->oltauthenticate($request)) {
            Auth::setUser($user);
        } elseif (Redis::exists("User:{$request->bearerToken()}") === 1) {
            $userId = Redis::get("User:{$request->bearerToken()}");
            Auth::setUser(UserV3::find($userId));
            if (Redis::ttl("User:{$request->bearerToken()}") !== -1) {
                Redis::set("User:{$request->bearerToken()}", $userId, "ex", 600);
            }

        }

        if (Auth::user()) {
            $result = PermissionsHelper::check($request, Auth::user());
            return $result;
        }

        $result['code'] = 401;
        return $result;
    }


    private function oltAuthenticate(Request $request)
    {
        return UserV3::where('api_token', $request->bearerToken())->first();
    }

}
