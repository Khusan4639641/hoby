<?php

namespace App\Http\Controllers\V3\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Core\Auth\RegisterController as CoreRegisterController;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    protected CoreRegisterController $controller;

    public function __construct()
    {
        parent::__construct();
        $this->controller = new CoreRegisterController();
    }

    public function validateForm(Request $request)
    {
        return $this->controller->validateForm($request);
    }

    public function sendSmsCode(Request $request)
    {
        return $this->controller->sendSmsCode($request);
    }

    public function checkSmsCode(Request $request)
    {
        return $this->controller->checkSmsCode($request);
    }
}
