<?php

use Illuminate\Support\Facades\Route;

Route::pattern('id', '^([0-9]+)?$');

Route::group(['namespace' => 'Partners', 'prefix' => 'v3', 'name' => 'api.', 'middleware' => ['tojson', 'locale.api']], function () {

    Route::group(['namespace' => 'Auth'], function () {
        Route::post('auth', 'LoginController@auth')->name('V3PartnerAuth');
    });

    Route::middleware(['api.authenticate'])->group(function () {

        Route::post('resus/buyer/check-status', 'PartnerBuyerController@checkBuyerStatus')->name('V3PartnerCheckBuyerStatus');

        Route::group(['prefix' => 'billing'], function () {
            Route::get('reports/vendors/export', 'ReportsController@vendorReportToExcel')->name('V3VendorReportToExcel');
        });

        Route::group(['prefix' => 'order'], function () {
            Route::get('list', 'OrderController@list')->name('V3OrderList');
            Route::post('calculate', 'OrderController@calculate')->name('V3OrderCalculate');
            Route::post('calculate-bonus', 'OrderController@calculateBonus')->name('V3CalculateBonus');
        });

        Route::group(['prefix' => 'orders'], function () {
            Route::post('add', 'OrderController@add')->name('V3OrdersAdd');
        });

        Route::group(['prefix' => 'regions'], function () {
            Route::get('list', 'RegionController@list')->name('V3RegionsList');
        });

        Route::group(['prefix' => 'buyers'], function () {
            Route::get('list', 'BuyerController@list')->name('V3BuyersList');
            Route::post('verify', 'BuyerController@verify')->name('V3BuyersVerify');
            Route::get('/{buyer}/actions', 'BuyerController@actionsByBuyer')->name('V3ActionsByBuyer');
        });

        Route::group(['prefix' => 'buyer'], function () {
            Route::get('phones-count', 'BuyerController@phonesCount')->name('V3PhonesCount');
        });

        Route::group(['prefix' => 'partner'], function () {
            Route::group(['prefix' => 'buyers'], function () {
                Route::post('send-sms-code', 'PartnerBuyerController@sendSmsCode')->name('V3PartnerBuyersSendSmsCode');
                Route::post('check-sms-code', 'PartnerBuyerController@checkSmsCode')->name('V3PartnerBuyersCheckSmsCode');
                Route::post('check-vip', 'PartnerBuyerController@checkVip')->name('V3PartnerBuyersCheckVip');
            });
            Route::group(['prefix' => 'buyer'], function () {
                Route::post('add-guarant', 'PartnerBuyerController@addGuarant')->name('V3PartnerBuyerAddGuarant');
                Route::post('upload-passport-docs', 'PartnerBuyerController@uploadPassportDocs')->name('V3UploadPassportDocs');
            });
            Route::get('/detail', 'PartnerBuyerController@getPartnerDetailInformation')->name('V3getPartnerDetailInformation');
        });

        Route::group(['prefix' => 'contracts'], function () {
            Route::post('send-cancel-sms', 'ContractController@CancelContract')->name('V3SendCancellationContractSMS');
            Route::post('check-cancel-sms', 'ContractController@CheckCancelSms')->name('V3CheckCancellationContractSMS');
            Route::post('upload-act', 'ContractController@uploadAct')->name('V3ContractsUploadAct');
            Route::post('upload-imei', 'ContractController@uploadImei')->name('V3ContractsUploadImei');
            Route::post('upload-client-photo', 'ContractController@uploadClientPhoto')->name('V3ContractsUploadClientPhoto');
            //Запрос на отмену контракта у филлиала
            Route::post('/create-cancel-request', 'ContractController@cancellationContractRequest')->name('V3CreateContractCancellationRequest');
            //Отказ запроса на отмену у головного от филлиала
            Route::post('/reject-cancel-request', 'ContractController@rejectCancellationContractRequest')->name('V3RejectContractCancellationRequest');

            Route::post('/detail', 'ContractController@contractDetail')->name('V3ContractDetail');
            Route::post('/{contract}/actions', 'ContractController@storeAction')->name('V3StoreAction');
        });

        Route::group(['prefix' => 'mfo', 'namespace' => 'MFO'], function () {
            Route::post('order', 'OrderController@createOrder')->name('V3MFOCreateOrder');
            Route::post('calculate', 'OrderController@calculate')->name('V3MFOCalculate');
            Route::post('sign', 'OrderController@signContract')->name('V3MFOSignContract');
            Route::post('myid', 'OrderController@myid')->name('V3MFOmyid');
            Route::post('check-status', "OrderController@checkContractStatus")->name('V3MFOCheckStatus');

            Route::group(['prefix' => 'cancel-contract'], function () {
                Route::post('send', 'OrderController@cancelContractMFOSendSms');
                Route::post('check', 'OrderController@cancelContractMFOCheckSms');
            });
        });

        Route::group(['prefix' => 'resus','namespace' => 'resus'], function () {
            Route::post('/calculate','OrderController@calculate')->name('V3resusCalculate');
            Route::post('/order','OrderController@createOrder')->name('V3resusCreateOrder');
            Route::post('contract-partly-cancel','OrderController@partlyCancel')->name('V3resusPartlyCancel');
            Route::post('contract-confirm','OrderController@contractConfirm')->name('V3resusContractConfirm');
        });
    });

    Route::group(['prefix' => 'mfo', 'namespace' => 'MFO'], function () {
        Route::group(['prefix' => 'auth'], function () {
            Route::post('login', 'AuthController@login')->name('V3MFOAuth');
            Route::post('verify', 'AuthController@verifyLogin')->name('V3MFOAuthVerify');
        });
    });
});
