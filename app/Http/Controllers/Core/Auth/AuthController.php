<?php

namespace App\Http\Controllers\Core\Auth;

use App\Models\User;
use App\Http\Controllers\Core\CoreController;
use App\Models\UserCreator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends CoreController {
    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {

    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public static function generateApiToken($user){
        if($user){
            $now = date('Y-m-d H:i:s');

            $user->token_generated_at = $now;
            $user->api_token = md5(Hash::make($user->phone . $now));
            $user->save();

            //Create a record to know, from which app user came up
            UserCreator::create([
                'user_id' => $user->id,
                'creator_id' => \request()->has('creator_id') ? \request()->get('creator_id') : 1001,
                'ip_address' => \request()->ip(),
            ]);

            return true;
        }

        Log::info('no user - no token');

        return false;
    }

}
