<?php

namespace App\Http\Controllers\Web\Panel;

use App\Http\Controllers\Controller;
use App\Services\resusBank\AccountHistoryService;
use Illuminate\Support\Facades\Auth;

class EdEmployeeController extends Controller
{
    private AccountHistoryService $accountHistoryService;

    public function __construct(AccountHistoryService $accountHistoryService)
    {
        $this->accountHistoryService = $accountHistoryService;
    }

    public function index()
    {
        if (Auth::user()->hasRole(['admin', 'ed_employee'])){
            $this->accountHistoryService->syncInMinutes(1);
            return view('panel.ed_employee.content');
        }else{
            abort("403");
        }
    }
}
