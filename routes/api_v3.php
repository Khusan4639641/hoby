<?php

use Illuminate\Support\Facades\Route;

Route::pattern('id', '^([0-9]+)?$');

Route::group(['namespace' => 'V3', 'prefix' => 'v3', 'name' => 'api.', 'middleware' => ['tojson', 'locale.api']], function () {

    //News
    Route::group(['prefix' => 'news'], function () {
        Route::get('/list', 'NewsController@list');
        Route::get('/detail/{id}', 'NewsController@detail');
    });

    Route::group( [ 'prefix' => 'faq-info' ], function () {
        Route::get( '/list', 'FaqInfoController@show' )->name('FaqList');
    });

    Route::group(['prefix' => 'categories'], function () {
        Route::get('/list', 'CatalogCategoryController@list');
        Route::get('tree/list', 'CatalogCategoryController@treeList');
        Route::get('/panel-list', 'CatalogCategoryController@panelList')->name('panelList');
        Route::get('/detail/{id}', 'CatalogCategoryController@detail');
        Route::get('/search-by-psic_code', 'CatalogCategoryController@searchByPsicCode')->name('V3CategoriesSearchByPsicCode');
        Route::get('/get-categories-hierarchy', 'CatalogCategoryController@getCategoriesHierarchy')->name('V3CategoriesGetCategoriesHierarchy');
    });

    Route::group(['prefix' => 'units', 'as' => 'units.'], function () {
        Route::get('/list', 'UnitController@list')->name('list');
    });

    Route::group(['prefix' => 'partners'], function () {
        Route::any('/list', 'PartnerController@list');
        Route::any('/detail/{id}', 'PartnerController@detail');
    });

    Route::group(['prefix' => 'slides'], function () {
        Route::get('/list', 'SlidesController@list');
        Route::get('/detail/{id}', 'SlidesController@detail');
    });

    Route::group(['namespace' => 'Auth'], function () {
        Route::group(['prefix' => 'login'], function () {
            Route::post('/send-sms-code', 'LoginController@sendSmsCode'); // вход или регистрация для клиента
            Route::post('/auth', 'LoginController@auth');
        });
    });

    Route::group(['namespace' => 'resus', 'prefix' => 'resus'], function () {
        Route::group(['prefix' => 'login'], function () {
            Route::post('/send-sms-code', 'LoginController@sendSmsCode'); // вход или регистрация для клиента
            Route::post('/auth', 'LoginController@auth');
        });
    });

    Route::middleware(['auth:api'])->group(function () {

        Route::get('/config/app', 'ConfigController@app');

        Route::group(['prefix' => 'partners'], function () {
            Route::get('/{id}/settings', 'PartnerController@settings');
        });

        Route::group(['prefix' => 'contract-verify', 'as' => 'contractVerify.'], function () {
            Route::post('/verify', 'ContractVerifyController@verify')->name('verify');
            Route::get('/list', 'ContractVerifyController@list')->name('list');
        });

        Route::group(['prefix' => 'panel','namespace' => 'Panel'], function () {
            Route::post('generate-control-key', 'PanelController@generateAccountWithControlKey')->name('V3GenerateAccountNumber');
        });

        Route::group(['prefix' => 'catalog-categories'], function () {
            Route::get('', 'CatalogCategoryController@all')->name('all');
            Route::post('', 'CatalogCategoryController@add')->name('add');

            Route::get('/{catalog_category}', 'CatalogCategoryController@get')->name('get');
            Route::patch('/{catalog_category}', 'CatalogCategoryController@update')->name('update');
            Route::delete('/{catalog_category}', 'CatalogCategoryController@delete')->name('delete');
        });

        Route::group(['prefix' => 'catalog-category-translations'], function () {
            Route::patch('/{catalog_category_translation}', 'CatalogCategoryTranslationController@update')->name('update');
        });

        // из кабинета вендора
        Route::group(['prefix' => 'buyer'], function () {
            Route::get('/change-lang', 'BuyerController@changeLang');  // сменить язык клиента
            Route::get('/catalog/list', 'BuyerController@catalog'); // каталог категорий
            Route::get('/catalog/partners/list', 'BuyerController@catalogPartners'); // партнеры в каталоге категорий
            Route::post('/catalog-partner', 'BuyerController@catalogPartner'); // партнер в каталоге категорий
            Route::get('/detail', 'BuyerController@detail');
            Route::post('/send-sms-code-uz', 'UniversalController@sendSmsCodeUniversal');
            Route::post('/check-sms-code-uz', 'UniversalController@checkSmsCodeUniversal');
            Route::post('/verify/modify', 'BuyerProfileController@modifyVerification');
            Route::post('/add-guarant', 'BuyerController@addGuarant');  // добавить доверителей
            Route::get('/check_status', 'BuyerController@check_status');  // проверка статуса клиента
            Route::get('/balance', 'BuyerController@balance'); // баланс в кабинете
            Route::get('/cards', 'BuyerController@cards'); // все карты пользователя
            Route::get('/payments', 'BuyerController@payments'); // список оплат клиентас
            Route::get('/notify/list', 'BuyerController@notify');
            Route::get('/contracts', 'BuyerController@contracts'); // список контрактов
            Route::post('/contract', 'BuyerController@contract'); // контракт
            Route::get('/contracts/notifications', 'BuyerController@contractsNotifications'); //Получение нотификаций  по контрактам, они отличаются от обычных нотификаций
            // Route::post('/contract/pay', 'BuyerController@contractPay'); // оплатить контракт досрочное погашение с ЛС или карты
            Route::get('/contract/check-status/{id}', 'BuyerController@checkContract');
            Route::get('/paycoin/list', 'BuyerController@paycoins'); // список всех баллов по каждой категории: рассрочка, лимит, скидка
            Route::get('/bonus-balance', 'BuyerController@bonusBalance'); // бонусы
            Route::get('/pay-services/list', 'BuyerController@payServices'); // платежные сервисы
            Route::post('/pay-services/pay', 'BuyerController@payServicePayment'); // платежные сервисы
            Route::post('/deposit/add', 'BuyerController@addDeposit'); // пополнить баланс лицевого счета
            Route::post('/bonus-to-card', 'BuyerController@bonusToCard'); // вывод бонусов на карту
            Route::post('/bonus-to-card-confirm', 'BuyerController@bonusToCardConfirm'); // подтверждение смской перевода бонусов на карту
            Route::get('contracts/autopay', 'BuyerController@expiredContractsAutopay')->name('expiredContractsAutopay');
            Route::post('address/upload','BuyerController@uploadAddress')->name('V3uploadAddress');
            Route::get('limits', 'BuyerController@limits')->name('V3GetUserLimits');

        });

        Route::group(['prefix' => 'cards'], function () {
            Route::post('/add', 'CardController@add');
            Route::post('/confirm', 'CardController@confirm');

            Route::post('/add-secondary', 'CardController@addSecondary');
            Route::post('/confirm-secondary', 'CardController@confirmSecondary');
        });

        Route::group(['prefix' => 'card'], function () {
            Route::post('/add', 'CardController@cardAdd');
            Route::post('/confirm', 'CardController@cardConfirm');
        });

        //MyID
        Route::group(['prefix' => 'myid'], function () {
            Route::get('/token', 'MyIDController@token');
            Route::post('/job', 'MyIDController@job');
            Route::post('/job/get-status', 'MyIDController@jobStatus');
            Route::get('/job/report/{id}', 'MyIDController@report');
        });

        // resus
        Route::group(['namespace' => 'resus', 'prefix' => 'resus'], function () {

            Route::group(['prefix' => 'user-verification'], function () {
                Route::post('/init', 'UserVerificationController@init');
                Route::post('/init-mini-scoring', 'UserVerificationController@initMiniScoring');
            });

            Route::group(['prefix' => 'buyer'], function () {
                Route::post('/add-card', 'UniversalController@sendSmsCodeUniversal');
                Route::post('/check-card-otp', 'UniversalController@checkSmsCodeUniversal');
            });
        });

        Route::group(['prefix' => 'buyers'], function () {
            Route::post('/send-code-sms', 'CompatibleApiController@SendContractSmsCode');  // отправка смс кода из кабинета клиента и из кабинета вендора (по условию)
            Route::post('/check-code-sms', 'CompatibleApiController@CheckContractSmsCode'); // проверка смс кода из кабинета клиента
        });

        Route::group(['prefix' => 'contracts'], function () {
            Route::post('/sign', 'ContractController@signContract'); // подписать контракт
            Route::post('/cancel', 'ContractController@cancel')->name('panel.cancelContract');
        });

        Route::group(['prefix' => 'fcm'], function () {
            Route::post('/update-token', 'FcmController@updateToken'); // update fireabse device token
        });

        Route::group(['namespace' => 'Auth'], function () {
            Route::get('/me', 'LoginController@me');
            Route::group(['prefix' => 'logout'], function () {
                Route::post('', 'LoginController@logout');
            });
        });

        // dev_nurlan 09.06.2022
        Route::group(['prefix' => 'contract'], function () {
            Route::group(['prefix' => 'prepay'], function () {
                Route::group(['prefix' => 'free-pay'], function () {
                    Route::post('/', 'PrepayController@prePayFreePay');  // "/api/v3/contract/prepay/free-pay"
                    Route::post('/confirm', 'PrepayController@prePayFreePay');  // "/api/v3/contract/prepay/free-pay/confirm"
                });
                Route::group(['prefix' => 'month'], function () {
                    Route::post('/', 'PrepayController@prePayMonth');  // "/api/v3/contract/prepay/month"
                    Route::post('/confirm', 'PrepayController@prePayMonth');  // "/api/v3/contract/prepay/month/confirm"
                });
                Route::group(['prefix' => 'several-month'], function () {
                    Route::post('/', 'PrepayController@prePaySeveralMonths');  // "/api/v3/contract/prepay/several-month
                    Route::post('/confirm', 'PrepayController@prePaySeveralMonths');  // "/api/v3/contract/prepay/several-month/confirm"
                });
            });
        });

        Route::get('regions', 'RegionController@all');
        Route::get('districts', 'DistrictController@all');

        Route::namespace('DebtCollect')->group(function () {

            Route::prefix('debt-collect-leader')->namespace('Leader')->group(function () {
                Route::prefix('curators')->group(function () {
                    Route::get('', 'CuratorController@all');

                    Route::get('/{curator}/regions', 'CuratorController@getRegions');
                    Route::patch('/{curator}/districts', 'CuratorController@syncDistricts');
                });
                Route::get('/curator-districts', 'CuratorController@getCuratorsDistricts');

                Route::prefix('collectors')->group(function () {
                    Route::get('', 'CollectorController@all');
                    Route::get('/{debt_collector}', 'CollectorController@getCollector');
                    Route::get('{debt_collector}/debtors', 'CollectorController@getActualDebtors');

                    Route::get('/{collector}/regions', 'CollectorController@getRegions');
                    Route::patch('/{collector}/districts', 'CollectorController@syncDistricts');
                });
                Route::get('/collector-districts', 'CollectorController@getCollectorsDistricts');

                Route::get('/contracts/{contract}',           'DebtorController@getContract');
                Route::get('/contracts/{contract}/schedules', 'DebtorController@getSchedules');

                Route::get( '/debtors',                          'DebtorController@all');
                Route::get( '/debtors/{debtor}',                 'DebtorController@getDebtor');
                Route::get( '/debtors/{debtor}/contracts',       'DebtorController@getContracts');
                Route::post('/debtors/{debtor}/district',        'DebtorController@attachDistrict');
                Route::post('/debtors/{debtor}/update-district', 'DebtorController@updateDistrict'); // test-1450 Nurlan

                Route::get('/analytic/collectors',          'CollectorController@analytic');
                Route::get('/analytic/collectors/payments', 'CollectorController@analyticPayments');
                Route::get('/analytic/debtors',             'DebtorController@analytic');
                Route::get('/analytic/export',              'AnalyticController@export');
                Route::get('/analytic/letters/export',      'AnalyticController@exportLettersReport');
                Route::get('/analytic/letters',             'AnalyticController@letters');
                Route::get('/analytic/letter-senders',      'AnalyticController@letterSenders');
            });

            Route::prefix('debt-collect-curator')->namespace('Curator')->group(function () {
                Route::get('/collectors/{debt_collector}', 'CollectorController@getCollector');
                Route::get('/collectors/{debt_collector}/potential-debtors', 'CollectorController@getPotentialDebtors');
                Route::get('/collectors/{debt_collector}/debtors', 'CollectorController@getActualDebtors');
                Route::post('/collectors/{debt_collector}/debtors', 'CollectorController@attachDebtors');
                Route::delete('/collectors/{debt_collector}/debtors', 'CollectorController@detachDebtors');

                Route::get('/debtors/{debtor}', 'DebtorController@getDebtor');
                Route::post('/debtors/{debtor}/actions', 'DebtorController@addDebtorAction');

                Route::get('/debtors/{debtor}/contracts', 'DebtorController@getContracts');
                Route::get('/contracts/{contract}', 'DebtorController@getContract');
                Route::get('/contracts/{contract}/schedules', 'DebtorController@getSchedules');

                Route::get('/analytic/collectors', 'CollectorController@getCollectors');
                Route::get('/analytic/debtors', 'DebtorController@analytic');
            });

            Route::prefix('debt-collect-curator-extended')->namespace('CuratorExtended')->group(function () {
                Route::get(   '/collectors/{debt_collector}',                   'CollectorController@getCollector');
                Route::get(   '/collectors/{debt_collector}/potential-debtors', 'CollectorController@getPotentialDebtors');
                Route::get(   '/collectors/{debt_collector}/debtors',           'CollectorController@getActualDebtors');
                Route::post(  '/collectors/{debt_collector}/debtors',           'CollectorController@attachDebtors');
                Route::delete('/collectors/{debt_collector}/debtors',           'CollectorController@detachDebtors');

                Route::get( '/debtors',                         'DebtorController@all');
                Route::get( '/debtors/{debtor}',                'DebtorController@getDebtor');
                Route::post('/debtors/{debtor}/actions',        'DebtorController@addDebtorAction');
                Route::get( '/debtors/{debtor}/contracts',      'DebtorController@getContracts');
                Route::post('/debtors/{debtor}/update-district','DebtorController@updateDistrict'); // test-1450 Nurlan

                Route::get('/contracts/{contract}',           'DebtorController@getContract');
                Route::get('/contracts/{contract}/schedules', 'DebtorController@getSchedules');

                Route::get('/analytic/collectors', 'CollectorController@getCollectors');
                Route::get('/analytic/debtors',    'DebtorController@analytic');

                Route::get( '/regions',                              'DebtorController@getUzbRegions');         // test-1450 Nurlan
                Route::post('/regions/search',                       'DebtorController@searchUzbRegions');      // test-1450 Nurlan
                Route::get( '/regions/{region_id}/districts',        'DebtorController@getRegionDistricts');    // test-1450 Nurlan
                Route::post('/regions/{region_id}/districts/search', 'DebtorController@searchRegionDistricts'); // test-1450 Nurlan
            });

            Route::prefix('debt-collector')->namespace('Collector')->group(function () {
                Route::get('', 'CollectorController@profile');
                Route::get('/districts', 'CollectorController@getDistricts');

                Route::get('/debtors', 'DebtorController@getDebtors');
                Route::get('/debtors/export_by_district', 'DebtorController@exportDebtorByDistrict');
                Route::get('/debtors/{debtor}/actions', 'DebtorController@promisedPaymentsByDebtor');
                Route::get('/debtors/{debtor}', 'DebtorController@getDebtor');
                Route::post('/debtors/{debtor}/actions', 'DebtorController@addDebtorAction');
                Route::post('/contracts/{contract}/actions', 'ContractController@addContractAction');
                Route::get('/contracts', 'DebtorController@getContracts');
                Route::get('/contracts/{contract}', 'ContractController@getContract');
                Route::get('/contracts/{contract}/schedules', 'ContractController@getSchedules');
                Route::get('/contracts/{contract}/actions', 'DebtorController@promisedPaymentsByContract');
            });

        });

        Route::group(['prefix' => 'kyc', 'namespace' => 'KYC'], function () {
            Route::group(['prefix' => 'docs'], function () {
                Route::post('/', 'KYCOrderController@uploadDocuments');
                Route::get('/', 'KYCOrderController@getList');
                Route::get('/{id}', 'KYCOrderController@getById');
                Route::post('/approve', 'KYCOrderController@approveKYCMyidRequest');
                Route::post('/reject', 'KYCOrderController@rejectKYCMyidRequest');
            });
        });

        Route::group(['prefix' => 'accounts', 'namespace' => 'Account'], function () {
            Route::resource('/', 'AccountController')->only(['index', 'store']);
            Route::patch('{account_1c_mfo_account}', 'AccountController@update');
            Route::delete('{account_1c_mfo_account}', 'AccountController@destroy');
        });
    });
    //Route for getting information about releases
    Route::get('/app-version', 'Mobile\AppReleaseController@getReleaseVersion');
});



