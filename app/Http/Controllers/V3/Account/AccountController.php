<?php

namespace App\Http\Controllers\V3\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\V3\Account\StoreAccountRequest;
use App\Http\Requests\V3\Account\UpdateAccountRequest;
use App\Models\Account1CMFOAccount;
use App\Models\Role;
use App\Services\API\V3\Account\AccountService;
use App\Services\API\V3\BaseService;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public static function authenticate()
    {
        if (Role::where('name', 'business-analyst')->exists() && !Auth::user()->hasRole('business-analyst')) {
            BaseService::handleError(['Unauthorized']);
        }
    }

    public static function index()
    {
        self::authenticate();
        return AccountService::index();
    }

    public static function update(Account1CMFOAccount $account_1c_mfo_account, UpdateAccountRequest $request): void
    {
        self::authenticate();
        AccountService::update($account_1c_mfo_account, $request->mfo_account_dto, $request->account_1c_dto);
    }

    public static function store(StoreAccountRequest $request): void
    {
        self::authenticate();
        AccountService::store($request->mfo_account_dto, $request->account_1c_dto);
    }

    public static function destroy(Account1CMFOAccount $account_1c_mfo_account): void
    {
        self::authenticate();
        AccountService::destroy($account_1c_mfo_account);
    }
}
