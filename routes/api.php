<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});*/
Route::pattern( 'id', '^([0-9]+)?$' );
// compatible old
Route::group( [ 'namespace' => 'Core', 'middleware' => ['tojson', 'locale.api'] ], function () {

});

Route::group( [ 'namespace' => 'Core', 'prefix' => 'v1', 'name' => 'api.', 'middleware' => ['tojson', 'locale.api'] ], function () {

    Route::group( [ 'prefix' => 'news' ], function () {
        Route::get('/list', 'NewsController@list');
        Route::get( '/detail/{id}', 'NewsController@detail' );
    });

    Route::group( [ 'prefix' => 'action' ], function () {
        Route::post('/check-status', 'BuyerController@checkStatusAction');  // проверка статуса клиента - для участия в акции
    });

    Route::group( [ 'prefix' => 'partners' ], function () {
        Route::any('/list', 'PartnerController@list');
        Route::any( '/detail/{id}', 'PartnerController@detail' );
    });

    Route::group( [ 'prefix' => 'categories' ], function () {
        Route::any( '/list', 'CatalogCategoryController@list' );
        Route::any( '/detail/{id}', 'CatalogCategoryController@detail' );
    });

    Route::group( [ 'prefix' => 'slides' ], function () {
        Route::any( '/list', 'SlidesController@list' );
    });


    Route::group( [ 'namespace' => 'Auth' ], function () {

        //TODO: разобраться  с ANY, по возможности заменить GET или POST
        Route::group( [ 'prefix' => 'register' ], function () {
            Route::post( '/send-sms-code', 'RegisterController@sendSmsCode' );
            Route::post( '/check-sms-code', 'RegisterController@checkSmsCode' );
            Route::post( '/validate-form', 'RegisterController@validateForm' );
            Route::post( '/add', 'RegisterController@add' );
            Route::post( '/modify', 'RegisterController@modify' );
        });

        Route::group( [ 'prefix' => 'login' ], function () {
            Route::post( '/validate-form', 'LoginController@validateForm' );
            Route::post( '/send-sms-code', 'LoginController@sendSmsCode' ); // вход или регистрация для клиента
            Route::post( '/check-sms-code', 'LoginController@checkSmsCode' );
            Route::post( '/check-password', 'LoginController@checkPassword' );
            Route::post( '/auth', 'LoginController@auth' );
            Route::post( '/partners/resend-password', '\App\Http\Controllers\Core\PartnerController@resend' );
        });

    });

    // регистрация партнера из формы заявки
    Route::group(['prefix'=>'partner'], function(){
        Route::post('register', 'ApelsinController@apelsinCheck');
    });

    Route::group(['prefix'=>'apelsin'], function(){
        Route::post('check', 'ApelsinController@apelsinCheck');
        Route::post('pay', 'ApelsinController@apelsinPay');
        Route::post('cancel', 'ApelsinController@apelsinCancel');
    });

    Route::group(['prefix'=>'qiwi'], function(){
        Route::post('check', 'QiwiController@QiwiCheck');
        Route::post('pay', 'QiwiController@QiwiPay');
        Route::post('cancel', 'QiwiController@QiwiCancel');
    });

    Route::group(['prefix'=>'oclick'], function(){
        Route::any('/prepare', 'OclickController@init');
        Route::any('/complete', 'OclickController@init');
        Route::any('/create', 'OclickController@createPayment');
        Route::middleware(['auth:api'])->group(function () {
            Route::any('/delete', 'OclickController@deletePayment');
            Route::any('/accept', 'OclickController@acceptPayment');
        });
    });

    Route::group( [ 'prefix' => 'order' ], function () {
        Route::group( [ 'prefix' => 'shipping' ], function () {
            Route::any( '/list', 'OrderShippingController@list' );
        });
        Route::group( [ 'prefix' => 'payment', 'middleware' => 'auth:api' ], function () {
            Route::any( '/pay', 'OrderPaymentController@payment' );
            Route::any( '/repay', 'OrderPaymentController@repayment' );
            Route::any( '/delay', 'OrderPaymentController@delaypayment' );
        });
        Route::any( '/calculate', 'OrderController@calculate' );

        // Route for Bonus Calculation
        Route::any( '/calculate-bonus', 'OrderController@calculateBonus' );

        Route::any( '/marketplace-calculate', 'OrderController@MarketPlaceCalculate' );  // общая калькуляция для маркетплейс, без привязки к клиенту, со скидками вендора

    });

    Route::middleware( [ 'auth:api' ] )->group( function () {

        Route::group([ 'prefix' => 'letters'], function () {
            Route::post('/send', 'LetterController@send');
            Route::get('/letter-filling-data', 'LetterController@letterFillingData')->name('LetterFillingData');
            Route::get('/postal-regions-and-areas', 'LetterController@postalRegionsAndAreas')->name('PostalRegionsAndAreas');
        });

        Route::group( [ 'prefix' => 'general-companies' ], function () {
            Route::get( '/{general_company}', 'GeneralCompanyController@get' );
            Route::post( '/{general_company}', 'GeneralCompanyController@uploadPhoto' );
        });

        Route::any( '/correct-payment-remote', 'PaymentController@correctPaymentRemote' );

        Route::group( [ 'prefix' => 'regions' ], function () {
            Route::any( '/list', 'RegionController@list' );
        });
        Route::group( [ 'prefix' => 'areas' ], function () {
            Route::any( '/list', 'AreaController@list' );
        });
        Route::group( [ 'prefix' => 'cities' ], function () {
            Route::any( '/list', 'CityController@list' );
        });
        Route::group( [ 'prefix' => 'shipping' ], function () {
            Route::post( '/calculate/{{method}}', 'RegionController@list' );
        });
        Route::group( [ 'prefix' => 'cart' ], function () {
            Route::any( '/sort-products', 'CartController@sortProducts' );
        });
        Route::group( [ 'prefix' => 'employees' ], function () {
            Route::any( '/list', 'EmployeeController@list' );
            Route::any( '/delete/{employee}', 'EmployeeController@delete' );
            Route::any( '/activate/{employee}', 'EmployeeController@activate' );
            Route::any( '/deactivate/{employee}', 'EmployeeController@deactivate' );
            Route::post( '/get-info', 'EdEmployeeController@getDate')->name("getEntriesByType");
        });
        // upay
        Route::group( [ 'prefix' => 'pay' ], function () {
            Route::any( '/list', 'PayController@list' );
            Route::any( '/payment', 'PayController@payment' );
            Route::any( '/refund', 'CardController@refund' );
            Route::any( '/bank-amount-add', 'CardController@bankAmountAdd' ); // посадить банковский платеж
            Route::any( '/check-cards-transactions', 'CardController@checkTransactions' ); // найти потерянные платежи
            Route::any( '/set-cards-transactions', 'CardController@setTransactions' ); // добавить в бд потерянные платежи
        });

        Route::group( [ 'prefix' => 'news' ], function () {
            //Route::any( '/list', 'NewsController@list' );
            Route::any( '/delete/{news}', 'NewsController@delete' );
            Route::any( '/publish/{news}', 'NewsController@publish' );
            Route::any( '/archive/{news}', 'NewsController@archive' );
            // Route::any( '/detail/{id}', 'NewsController@detail' );
        });

        // O'zbekiston Pochtasi
        Route::group( [ 'prefix' => 'postal-regions' ], function () {
            Route::any( '/delete/{region}', 'PostalRegionController@delete' );
        });

        Route::group( [ 'prefix' => 'postal-areas' ], function () {
            Route::any( '/delete/{area}', 'PostalAreaController@delete' );
        });

        Route::group( [ 'prefix' => 'slides' ], function () {
            // Route::any( '/list', 'SlidesController@list' );
            Route::any( '/detail/{id}', 'SlidesController@detail' );
            Route::any( '/delete/{slide}', 'SlidesController@delete' );
        });
        Route::group( [ 'prefix' => 'faq' ], function () {
            Route::any( '/list', 'FaqController@list' );
            Route::any( '/delete/{faq}', 'FaqController@delete' );
            Route::any( '/publish/{faq}', 'FaqController@publish' );
            Route::any( '/archive/{faq}', 'FaqController@archive' );
        });
        Route::group( [ 'prefix' => 'orders' ], function () {
            Route::post( '/status', 'OrderController@changeStatus' );
            Route::any( '/list', 'OrderController@list' );


            Route::any( '/add', 'OrderController@add' );
            Route::post( '/send-sms-code', 'OrderController@sendSmsCode' );
            Route::post( '/check-sms-code', 'OrderController@checkSmsCode' );
            Route::post('/make-preview', 'OrderController@makePreview');
        });
        Route::group( [ 'prefix' => 'contracts' ], function () {
            Route::any( '/calculate', 'ContractController@calculate' );
            Route::any( '/upload/act', 'ContractController@uploadActApi' ); // загрузка акта через апи  - для интернет магазинов (не отдает путь и лишнюю информацию)
            Route::any( '/upload-act', 'ContractController@uploadAct' );
            Route::any( '/act-status', 'ContractController@changeActStatus' ); // статус акта
            Route::any( '/upload-cancel-act', 'ContractController@uploadCancelAct' ); // загрузить акт отмены
            Route::any( '/cancel-act-status', 'ContractController@changeCancelActStatus' ); // статус акта отмены
            Route::any( '/upload-imei', 'ContractController@uploadImei' ); // загрузить IMEI
            Route::any( '/imei-status', 'ContractController@changeImeiStatus' ); // статус IMEI
            Route::any( '/send-cancel-sms', 'ContractController@CancelContract' ); // отправка смс на отмену договора
            Route::any( '/check-cancel-sms', 'ContractController@CheckCancelSms' ); // проверка смс на отмену договора
            Route::any( '/upload-client-photo', 'ContractController@uploadClientPhoto' ); // загрузить фото клиента с товаром
            Route::any( '/client-photo-status', 'ContractController@changeClientPhotoStatus' ); // статус фото клиента
            Route::any( '/contract-cancel', 'ContractController@changeContractStatus' ); // зменить статус контракта
            Route::any( '/show-history-files', 'ContractController@showFiles' )->name('historyFiles'); // зменить статус контракта
            Route::post( '/sign', 'ContractController@signContract' ); // подписать контракт
            // Route::any( '/client-photo-status', 'ContractController@changeClientPhotoStatus' ); // статус фото клиента с товаром
        });
        Route::group( [ 'prefix' => 'lawsuit' ], function () {
            Route::any( '/add', 'LawsuitController@add' );
            Route::any( '/modify', 'LawsuitController@modify' );
            Route::any( '/add-collection-cost', 'LawsuitController@addCollectionCost' );
            Route::any( '/save-invoice-number', 'LawsuitController@saveInvoiceNumber' );
            Route::any( '/check-can-save-invoice-number', 'LawsuitController@checkCanSaveInvoiceNumber' );
            Route::any( '/get-collection-amount', 'LawsuitController@getCollectionAmount' );
            Route::get( '/get-notaries-list', 'LawsuitController@getNotariesList' );
            Route::post( '/store-executive-writing', 'LawsuitController@storeExecutiveWriting' )->name('StoreExecutiveWriting');
        });



        /* Route::group( [ 'prefix' => 'insurance-request' ], function () {
             Route::any( '/list', 'InsuranceRequestController@list' );
             Route::any( '/add', 'InsuranceRequestController@add' );
         } );*/

        Route::group( [ 'prefix' => 'discounts' ], function () {
            Route::any( '/list', 'DiscountController@list' );
            Route::any( '/delete/{discount}', 'DiscountController@delete' );
            Route::any( '/publish/{discount}', 'DiscountController@publish' );
            Route::any( '/archive/{discount}', 'DiscountController@archive' );
        } );

        Route::group( [ 'prefix' => 'buyers' ], function () {
            Route::any('/list', 'BuyerController@list');
            Route::any('/detail', 'BuyerController@detail');
            //TODO: Совместимость с API старой версии, удалить по мере внедрения новых API
            Route::any('/calculate-price', 'CompatibleApiController@CalculatePrice');
            Route::any('/credit/add', 'CompatibleApiController@addCredit');
            Route::any('/credit/cancel', 'CompatibleApiController@CancelContract');
            Route::any('/credit/get-order-id', 'CompatibleApiController@getId');  // возвращает order_id, который на самом деле contract_id
            Route::any('/verify', 'CompatibleApiController@verification');
            Route::any('/send-code-sms', 'CompatibleApiController@SendContractSmsCode');  // отправка смс кода из кабинета клиента и из кабинета вендора (по условию)
            Route::any('/check-code-sms', 'CompatibleApiController@CheckContractSmsCode'); // проверка смс кода из кабинета клиента
            Route::any('/check-user-sms', 'CompatibleApiController@CheckUserSms'); // проверка смс кода из апи | Подтверждение договора клиентом

            Route::any('/partner-confirm', 'CompatibleApiController@PartnerConfirm'); // подтверждение договора вендором из апи
            Route::any('/create-basket', 'CompatibleApiController@CreateBasket');

            Route::get('/get-buyers-delay','CronController@getBuyersDelay');
        });

        Route::group( [ 'prefix' => 'employee' ], function () {
            Route::group( [ 'prefix' => 'buyers' ], function () {
                Route::post( '/validate-form', 'EmployeeBuyerController@validateForm' );
                Route::post( '/check-pinfl', 'EmployeeBuyerController@checkPinfl' );
                Route::post( '/modify', 'EmployeeBuyerController@modify' );

                Route::get( '/get_buyer_personals_types', 'EmployeeBuyerController@getBuyerPersonalsTypes' );
                Route::post( '/add_additional_photo', 'EmployeeBuyerController@addAdditionalPhoto' );
                // route: "{BASE_URL}/api/v1/employee/buyers/search"
                Route::post('/search',   'EmployeeBuyerController@search');

                Route::post( '/action/verify', 'EmployeeBuyerController@setVerified' );
                Route::post( '/action/status', 'EmployeeBuyerController@changeStatus' );
                Route::post( '/scoring', 'CardController@scoring' );
                Route::post( '/scoring-universal', 'CardController@scoringUniversal' );
                // Route::post( '/katm-info', 'KatmController@info' );
                Route::post( '/katm-scoring', 'KatmController@scoring' );
                Route::post( '/katm-report', 'KatmController@getReport' );
                Route::post( '/payment', 'CardController@payment' );
                Route::post( '/balance', 'CardController@balance' );
                Route::post( '/get-balance', 'BuyerDelayController@cardBalance' );  // получить баланс карты по ее айди
                Route::post('/send-sms', 'EmployeeBuyerController@sendSms');

                Route::post('/{id}/report', 'BuyerController@report')->name('panel.buyer.report.pdf');

                Route::post('/check-credit', 'BuyerController@checkCredit');  // проверка статуса кредитов клиента
                Route::get('/show_overdue_contracts', 'EmployeeBuyerController@showOverdueContracts');
                Route::get('/show_overdue_contracts_buyer', 'EmployeeBuyerController@showOverdueContractsForBuyer');


                Route::any('/init-scoring', 'BuyerController@initScoring');
                Route::any('/check-scoring', 'BuyerController@checkScoring');
                Route::any('/check', 'BuyerController@check');  // скоринг клиента
                Route::post('/kyc-moderate', 'BuyerController@kycModerate');  // модерация клиента KYC оператором
                Route::post('/set-gender', 'BuyerController@setGender');  //
                Route::post('/set-birthdate', 'BuyerController@setBirthdate');  //

                Route::any( '/activate/{id}', 'CardController@activate' );  // активировать карту humo
                Route::any( '/change-status/{id}', 'CardController@changeStatus' );  // активировать/деактивировать карту humo
                Route::any( '/deactivate/{id}', 'CardController@deactivate' ); // деактивировать карту humo
                Route::any( '/delete/{id}', 'CardController@delete' );  // удалить карту HUMJ
                Route::any( '/add-humo/{buyer_id}', 'CardController@addCardsHumo' );  // добавить humo карты по ID
                Route::any( '/add-humo', '\App\Http\Controllers\Web\Panel\BuyerDelayController@addCardsHumo' );  // добавить humo карты всем

                Route::any( '/add-uzcard-pnfl/{buyer_id}', 'CardController@addCardsUzcard' );  // добавить uzcard карты по pnfl
                Route::any( '/activatePnflCard/{id}', 'CardController@activatePnflCard' );  // активировать карту PNFL
                Route::any( '/deactivatePnflCard/{id}', 'CardController@deactivatePnflCard' ); // деактивировать карту PNFL
                Route::any( '/deletePnflCard/{id}', 'CardController@deletePnflCard' );  // удалить карту PNFL
                Route::post('/record/add', 'RecordController@add');  // добавить комментарии
                Route::post('/similarity', 'BuyerController@checkSimilarity'); // сверка имен в таблице cards и users
            });

            Route::group( [ 'prefix' => 'partners' ], function () {
                Route::post( '/action/confirm', 'PartnerController@confirm' );
                Route::post( '/action/block', 'PartnerController@block' );
                Route::post( '/action/show-reasons', 'PartnerController@showReasons' );
                Route::post( '/action/show-history', 'PartnerController@showBlockHistory' );
                Route::post( '/action/resend', 'PartnerController@resend' );
            } );

        } );

        Route::group( [ 'prefix' => 'partner' ], function () {
            Route::group( [ 'prefix' => 'buyers' ], function () {
                Route::post( '/validate-form', 'PartnerBuyerController@validateForm' );
                Route::post( '/modify', 'PartnerBuyerController@modify' );


                Route::post( '/send-sms-code', 'PartnerBuyerController@sendSmsCodeAuth' );
                Route::post( '/check-sms-code', 'PartnerBuyerController@checkSmsCode' );
                Route::post( '/send-otp-code', 'PartnerBuyerController@sendOtpCode' );  // отправка отп для uzcard, либо смс с сайта для humo
                Route::post( '/check-otp-code', 'PartnerBuyerController@checkOtpCode' ); // проверка отп для uzcard, либо смс с сайта для humo

                Route::post( '/add', 'PartnerBuyerController@add' );
                Route::post( '/card/add', 'CardController@add' );
                Route::post( '/list', 'PartnerBuyerController@list' );

                Route::post('/get-status', 'BuyerController@getStatus');  // проверка статуса клиента

                Route::post( '/check-vip', 'PartnerBuyerController@checkVip' ); // вип переключалка при регестрации вендором

            } );
        } );

        // из кабинета вендора
        Route::group( [ 'prefix' => 'buyer' ], function () {
            Route::post( '/modify', 'BuyerProfileController@modify' );
            Route::post( '/profile', 'BuyerProfileController@store' );
            Route::post( '/card/add', 'CardController@add' );
            Route::post( '/card/list', 'CardController@list' );
            Route::post( '/check-sms-code', 'BuyerProfileController@checkSmsCode' );
            Route::post( '/send-sms-code', 'BuyerProfileController@sendSmsCode' );
            Route::post( '/verify/modify', 'BuyerProfileController@modifyVerification' );
            Route::post( '/save-address', 'BuyerProfileController@saveAddress' );
            Route::post( '/save/workplace-address', 'BuyerProfileController@saveWorkplaceAddress' );
            Route::post( '/verify/validateForm', 'BuyerProfileController@validateForm' );
            Route::post( '/verify/send', 'BuyerProfileController@sendVerification' );
            /*Route::post( '/send-sms-code-humo', 'HumoCardController@sendSmsCodeHumo' );
            Route::post( '/send-sms-code-uz', 'UZCardController@sendSmsCodeUz' );*/
            /*Route::post( '/check-sms-code-humo', 'HumoCardController@checkSmsCodeHumo' );
            Route::post( '/check-sms-code-uz', 'UZCardController@checkSmsCodeUz' );*/
            Route::post( '/refill-by-card', 'BuyerProfileController@refillAccountByCard' );

            Route::post( '/send-sms-code-humo', 'UniversalController@sendSmsCodeUniversal' );
            Route::post( '/send-sms-code-uz', 'UniversalController@sendSmsCodeUniversal' );
            Route::post( '/check-sms-code-humo', 'UniversalController@checkSmsCodeUniversal' );
            Route::post( '/check-sms-code-uz', 'UniversalController@checkSmsCodeUniversal' );

            Route::post( '/friend/add', 'BuyerController@addFriend' ); // пригласить друга
            Route::get( '/balance', 'BuyerController@balance' ); // баланс в кабинете
            Route::get( '/balance/{id}', 'BuyerController@balance' ); // баланс в кабинете
            Route::get( '/pay-systems', 'BuyerController@paySystems' ); // платежные системы
            Route::get( '/cards', 'BuyerController@cards' ); // все карты пользователя
            Route::get( '/cards/{id}', 'BuyerController@cards' ); // все карты пользователя
            Route::post( '/card-balance', 'BuyerController@cardBalance' ); // баланс выбранной карты
            Route::post( '/deposit/add', 'BuyerController@addDeposit' ); // пополнить баланс лицевого счета
            Route::get( '/contracts', 'BuyerController@contracts' ); // список контрактов
            Route::get( '/contracts/{id}', 'BuyerController@contracts' ); // список контрактов
            Route::post( '/contract', 'BuyerController@contract' ); // контракт
            Route::post( '/contract/pay', 'BuyerController@contractPay' ); // оплатить контракт досрочное погашение с ЛС или карты
            Route::post( '/contract/bonus-pay', 'BuyerController@contractPayByBonus' ); // оплатить контракт досрочное погашение с бонусного счета
            Route::get( '/catalog/list', 'BuyerController@catalog' ); // каталог категорий
            Route::get( '/catalog/partners/list', 'BuyerController@catalogPartners' ); // партнеры в каталоге категорий
            Route::post( '/catalog-partner', 'BuyerController@catalogPartner' ); // партнер в каталоге категорий

            Route::get( '/bonus-balance', 'BuyerController@bonusBalance' ); // бонусы
            Route::post( '/bonus-to-card', 'BuyerController@bonusToCard' ); // вывод бонусов на карту
            Route::post( '/bonus-to-card-confirm', 'BuyerController@bonusToCardConfirm' ); // подтверждение смской перевода бонусов на карту
            Route::get( '/pay-services/list', 'BuyerController@payServices' ); // платежные сервисы
            Route::post( '/pay-services/pay', 'BuyerController@payServicePayment' ); // платежные сервисы

            //Route::post( '/bonus-pay', 'BuyerController@bonusPay' ); // оплатить

            Route::get( '/notify/list', 'BuyerController@notify' ); //
            Route::post( '/notify/detail', 'BuyerController@notifyDetail' ); //

            Route::get( '/payments', 'BuyerController@payments' ); // список оплат клиента
            Route::get( '/payments/{id}', 'BuyerController@payments' ); // список оплат клиента
            Route::get( '/payments-types', 'BuyerController@paymentsTypes' ); // список типов оплат
            Route::post( '/payment/detail', 'BuyerController@paymentDetail' ); // етализация конкретного платежа

            Route::get( '/paycoin/list', 'BuyerController@paycoins' ); // список всех баллов по каждой категории: рассрочка, лимит, скидка
            Route::post( '/paycoin/pay', 'BuyerController@paycoinPay' ); // оплата баллами уровней по месяцам, лимитам, скидки
            Route::get( '/paycoin/balance', 'BuyerController@paycoinBalance' ); // етализация конкретного платежа

            Route::post( '/katm-scoring', 'BuyerController@katmScoring' );
            Route::post( '/katm-report', 'BuyerController@katmReport' );
            Route::post( '/katm-address', 'BuyerController@katmAddress' );

            Route::any('/detail', 'BuyerController@detail');
            Route::any('/detail/{id}', 'BuyerController@detail');
            Route::any('/check_status', 'BuyerController@check_status');  // проверка статуса клиента
            Route::any('/phone/{id}', 'BuyerController@getBuyer');  // проверка статуса клиента

            Route::any('/add-guarant', 'BuyerController@addGuarant');  // добавить доверителей
            Route::any('/phones-count', 'BuyerController@phonesCount');  // кол-во купленных телефонов клиентом

            Route::post('/add-cart-link', 'BuyerController@addCartLink');  // добавить url для возврата в корзину
            Route::get('/change-lang', 'BuyerController@changeLang');  // сменить язык клиента
            Route::get('/init-push', 'BuyerController@initPush');  // инициализация push уведомлений, язык клиента, устройство

        });

        Route::group( [ 'prefix' => 'catalog' ], function () {
            Route::group( [ 'prefix' => 'products' ], function () {
                Route::any( '/list', 'CatalogProductController@list' );
                Route::any( '/delete/{product}', 'CatalogProductController@delete' );
                Route::any( '/add', 'CatalogProductController@add' );
                Route::any( '/modify', 'CatalogProductController@modify' );
                Route::post( '/import', 'CatalogProductController@import' );

            } );
            Route::group( [ 'prefix' => 'categories' ], function () {
                //Route::any( '/list', 'CatalogCategoryController@list' );
                Route::any( '/fields/{category}', 'CatalogCategoryController@fields' );
                Route::any( '/delete/{category}', 'CatalogCategoryController@delete' );

            } );
            Route::group( [ 'prefix' => 'fields' ], function () {
                Route::any( '/list', 'CatalogProductFieldController@list' );
                Route::any( '/delete/{field}', 'CatalogProductFieldController@delete' );
                Route::any( '/add', 'CatalogProductFieldController@add' );

            } );
        } );

        Route::group( [ 'prefix' => 'partners' ], function () {
            //Route::any( '/list', 'PartnerController@list' );
            Route::any( '/validate-form', 'PartnerController@validateForm' );
            Route::any( '/modify', 'PartnerController@modify' );
        } );

        Route::group( [ 'prefix' => 'finance' ], function () {
            Route::group( [ 'prefix' => 'orders' ], function () {
                Route::any( '/add-receipt', 'FinanceController@addReceipt' );
                Route::any( '/list-receipt', 'FinanceController@listReceipt' );
            } );
        });

        Route::group( [ 'prefix' => 'statistics' ], function () {
            Route::post( '/partner', 'StatisticsController@partner' );
            Route::post( '/finance', 'StatisticsController@finance' );
        });

        // отчеты в 1С
        Route::group( [ 'prefix' => 'reports' ], function () {
            Route::post( '/orders', 'ReportsController@orders1' ); // список договоров по дате
            Route::post( '/history', 'ReportsController@history' ); // список пополнений личного счета (депозит)
            Route::post( '/payments', 'ReportsController@payments' ); // оплаты
            Route::post( '/canceled-contracts', 'ReportsController@canceledContracts1' ); // отмененные кредиты
            Route::post( '/bonuses', 'ReportsController@bonuses' ); // Начисленные бонусы
            Route::post( '/files-history', 'ReportsController@filesHistory' )->name('filesHistory'); // Начисленные бонусы
        });

        // продавцы
        Route::group( [ 'prefix' => 'sallers' ], function () {
            Route::any('/list', 'SallerController@list');
            Route::any( '/detail/{id}', 'SallerController@detail' );
        });

        Route::group( [ 'prefix' => 'card' ], function () {
            Route::any('/add', 'CardController@cardAdd');
            Route::any( '/confirm', 'CardController@cardConfirm' );
            Route::post( '/get-card-info', 'CardController@getCardInfo' );
        });

        // paynet
        Route::group( [ 'prefix' => 'paynet' ], function () {
            Route::any( '/perform', 'PaynetController@PerformTransaction' );
            Route::any( '/check', 'PaynetController@CheckTransaction' );
            Route::any( '/cancel', 'PaynetController@CancelTransaction' );
            Route::any( '/information', 'PaynetController@GetInformation' );
            Route::any( '/statement', 'PaynetController@GetStatement' );
        });

        // 17.12.2021 взыскание по договорам
        Route::group( [ 'prefix' => 'recovery' ], function () {
            Route::post( '/recovery-step', 'RecoveryController@recoveryStep' );
            Route::post( '/get-buyer-documents', 'RecoveryController@getBuyerDocuments' );
            Route::post('/get-debts', 'RecoveryController@getDebts' );
            Route::post('/buyer-comment', 'RecoveryController@buyerComment' );
            Route::post('/myid-status', 'RecoveryController@myIdStatus' );

            Route::group([ 'prefix' => 'collectors' ], function() {
                Route::get('', 'CollectorController@all');
                Route::post('/{collector}/katm-regions', 'CollectorController@attachKatmRegion');
                Route::post('/history-payment', 'CollectorController@showHistoryPayments')->name('historyPayment');
                Route::delete('/{collector}/katm-regions', 'CollectorController@detachKatmRegion');

            });

            Route::group([ 'prefix' => 'contracts' ], function() {
                Route::get('', 'ContractController@recoveryContracts');
                Route::patch('/{contract}/katm-region', 'ContractController@setKatmRegion');
            });

            Route::get('/collectors/{collector}/contracts/{contract}/transactions', 'CollectorTransactionController@get');

        } );

        Route::group([ 'prefix' => 'collector'], function() {
            Route::get('/local-regions', 'CollectorController@collectorLocalRegions');
            Route::get('/contracts', 'CollectorController@collectorContracts');
            Route::get('/contracts/{contract_id}', 'CollectorController@collectorContract');
            Route::post('/transactions', 'CollectorTransactionController@add');
        });

    });

    Route::group(['prefix'=>'payme'], function(){
        Route::any('/init', 'PaymeController@init');
    });

    Route::group(['prefix'=>'upay'], function(){
        Route::post('/pay', 'UpayController@pay');
        Route::post('/check', 'UpayController@check');

    });
    Route::group(['prefix'=>'myuzcard'], function(){
        Route::post('/pay', 'MyUzcardController@pay');
        Route::post('/get-token', 'MyUzcardController@getToken');
    });

    Route::any('/send-email', 'EmailController@sendEmail');
    Route::any('/buyer/check-payments', 'BuyerController@checkPayments');
    Route::post('/add-fake-transaction', '\App\Http\Controllers\Core\FakeController@add')->name('fake.transaction.send.data');
});




Route::group(['namespace' => 'Core', 'prefix' => 'v2', 'name' => 'api.', 'middleware' => ['tojson', 'locale.api']], function () {

    Route::middleware(['auth:api'])->group(function () {

        Route::group(['prefix' => 'card'], function () {
            Route::any('/add', 'CardController@cardAddV2');
            Route::any('/confirm', 'CardController@cardConfirmV2');
        });

        Route::group(['prefix' => 'buyer'], function() {
            Route::group(['prefix' => 'contract'], function() {
                Route::post('/pay', 'BuyerController@contractPayV2'); // Оплата договора
            });
        });

        // отчеты в 1С
        Route::group(['prefix' => 'reports'], function () {
            Route::post('/orders', 'ReportsController@orders2'); // список договоров по дате
            Route::post('/history', 'ReportsController@history'); // список пополнений личного счета (депозит)
            Route::post('/payments', 'ReportsController@payments'); // оплаты
            Route::post('/canceled-contracts', 'ReportsController@canceledContracts2'); // отмененные кредиты
            Route::post('/bonuses', 'ReportsController@bonuses'); // Начисленные бонусы
        });
    });

});

Route::group(['namespace' => 'Core', 'name' => 'api.', 'middleware' => ['tojson', 'auth:api']], static function () {
    Route::group(['prefix' => 'contracts'], function () {
        Route::post('/urls', 'ContractController@saveUrls');
    });
});

Route::group(['namespace' => 'Core', 'middleware' => 'ml_access'], static function () {
    Route::post('scoring/buyer/{buyerID}/mini', 'MLController@miniLimit')->name('ml.limit.mini');
    Route::post('scoring/buyer/{buyerID}/base', 'MLController@baseLimit')->name('ml.limit.base');
    Route::post('scoring/buyer/{buyerID}/extended', 'MLController@extendedLimit')->name('ml.limit.extended');
});



require __DIR__ . '/api_v3.php';
require __DIR__ . '/api_partners.php';
require __DIR__ . '/api_admin.php';
