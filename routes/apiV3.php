<?php

use Illuminate\Support\Facades\Route;

Route::group(['namespace' => '\App\Http\Controllers\Core\Auth\V3', 'prefix' => 'v3',], function () {

    Route::group(['namespace' => '\App\Http\Controllers\Core\Auth\V3', 'name' => 'api.', 'middleware' => []], function () {
        Route::post('/cabinet/login', 'LoginV3Controller@cabinetLogin')->name('V3cabinetLogin');
//        Route::post('/panel/login', 'LoginV3Controller@panelLogin');
        Route::post('/billing/login', 'LoginV3Controller@billingLogin')->name('V3billingLogin');;
//        Route::post('/cabinet/register', 'LoginV3Controller@cabinetRegister');
//        Route::post('/panel/register', 'LoginV3Controller@panelRegister');
    });

    Route::group(['namespace' => '\App\Http\Controllers\Core\V3', 'name' => 'api.', 'middleware' => ['api.authenticate']], function () {
        Route::post('/katm/register/{user}', 'KatmV3Controller@register');
        Route::post('/katm/report/{user}', 'KatmV3Controller@report');
        Route::post('/katm/status/', 'KatmV3Controller@status');
    });
});
