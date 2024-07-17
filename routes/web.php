<?php

use App\Helpers\CardHelper;
use App\Helpers\EncryptHelper;
use App\Helpers\PaymentHelper;
use App\Helpers\PushHelper;
use App\Helpers\SmsHelper;
use App\Helpers\TelegramHelper;

use App\Http\Controllers\Core\UniversalPnflController;
use App\Http\Controllers\Core\ZpayController;
use App\Http\Controllers\Web\Panel\PaymentController;
use App\Http\Controllers\Web\Frontend\PageController;
use App\Models\Card;
use App\Models\CardPnfl;
use App\Models\Contract;
use App\Models\KycHistory;
use App\Models\PaymentsData;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['namespace' => 'Web', 'prefix' => '{locale?}', 'middleware' => 'locale'], function () {



    Route::any('/paynet', '\App\Http\Controllers\Web\Frontend\PaynetController@pay')->name('paynetIndex');

    Route::get('/pay-instruction', '\App\Http\Controllers\Web\Frontend\PageController@payInstruction')->name('payInstruction');

    Route::get('/apple-app-site-association', '\App\Http\Controllers\Core\MobileController@association')->name('association');

    Route::any('/katm-get-infoscore', '\App\Http\Controllers\Core\KatmController@katmGetInfoscore')->name('katmGetInfoscore');

    Route::get('/contract/{id}', '\App\Http\Controllers\Core\OrderController@printAct');  // печать акта (вместо пдф)


    /* Frontend */
    Route::group(['namespace' => 'Frontend'], function () {

        Route::get('cardlist', function () {  // добавить с помощью КРОНА дополнительные хумо карты задолжникам

            $res = new \App\Http\Controllers\Core\UniversalController();
            $result = $res->addCardsHumo();
            if ($result) {
                echo 'done';
            }

        });

        Route::get('/', 'IndexController@index')->name('home');
        Route::any('/extra', 'FrontendController@extra')->name('extra');
        Route::get('/form', 'FrontendController@form')->name('form');
        Route::get('/cardpnfl', 'FrontendController@cardpnfl')->name('cardpnfl'); // добавление карт по пнфл всем задолжникам
        Route::get('/bonus', 'FrontendController@bonus')->name('bonus');  // начисление бонусов продавцам
        Route::resource('/news', 'NewsController');
        Route::resource('/discounts', 'DiscountController');
        Route::get('/faq', 'FaqController@index')->name('faq.index');
        Route::any('/download', 'FrontendController@downloadFromUrl')->name('download.url');
        Route::group(['prefix' => 'page'], function () {
            Route::name('page.')->group(function () {
                Route::get('/about', 'PageController@render')->name('about');
                Route::get('/payment', 'PageController@render')->name('payment');
                Route::get('/installment', 'PageController@render')->name('installment');
                Route::get('/bonus', 'PageController@render')->name('bonus');
            });
        });
        // Order
        Route::group(['prefix' => 'order'], function () {
            Route::name('order.')->group(function () {
                Route::get('/{type}/', 'OrderController@processing')->where('type', '(direct|credit)')->name('processing');
            });

        });

        //Catalog
        Route::group(['prefix' => 'catalog'], function () {
            Route::name('catalog.')->group(function () {

                Route::get('/', 'CatalogController@index')->name('index');

                Route::group(['prefix' => 'category'], function () {
                    Route::name('category.')->group(function () {

                        Route::get('/{slug}-{id}', 'CatalogCategoryController@show')->where(['id' => '[0-9]+$', 'slug' => '.*'])->name('show');

                    });
                });

                Route::group(['prefix' => 'product'], function () {
                    Route::name('product.')->group(function () {

                        Route::get('/{slug}-{id}', 'CatalogProductController@show')->where(['id' => '[0-9]+$', 'slug' => '.*'])->name('show');

                    });
                });

            });
        });

        Route::get('/partners/welcome', 'PartnerController@welcome')->name('partners.welcome');
        Route::get('/register', 'RegisterController@index')->name('register');

        Route::group(['middleware' => ['guest']], function () {
            Route::get('/partners/register', 'PartnerController@register')->name('partners.register');
            Route::get('/partners/login', 'PartnerController@login')->name('partners.login');
            Route::get('/panel/in', 'LoginController@panelLogin')->name('panel.login');
            Route::get('/login', 'LoginController@panelLogin')->name('panel.log');
            Route::post('/auth', '\App\Http\Controllers\Core\Auth\LoginController@auth')->name('auth');
            Route::get('/invite', '\App\Http\Controllers\Core\Auth\LoginController@invite')->name('invite');

        });

        Route::resource('/partners', 'PartnerController');

        Route::group(['middleware' => ['auth']], function () {
            Route::get('/logout', 'LoginController@logout')->name('logout');
        });
    });


    Route::middleware(['global', 'auth', 'entry'])->group(function () {

        /* Cabinet (Buyer tools) */
        Route::group(['namespace' => 'Cabinet', 'prefix' => 'cabinet', 'middleware' => 'profile'], function () {
            Route::name('cabinet.')->group(function () {

                //front page
                Route::get('/', 'IndexController@index')->name('index');

                //front page
                Route::get('/account/refill', 'IndexController@refill')->name('account.refill');

                //profile
                Route::group(['prefix' => 'profile'], function () {
                    Route::name('profile.')->group(function () {

                        Route::get('/', 'ProfileController@show')->name('show');
                        Route::get('/verify', 'ProfileController@verify')->name('verify');
                        Route::get('/edit', 'ProfileController@edit')->name('edit');
                        Route::any('/update', 'ProfileController@update')->name('update');

                    });
                });

                Route::get('/cards', 'CardController@index')->name('cards.index');

                //orders
                Route::any('/orders/list', 'OrderController@list')->name('orders.list');
                Route::resource('/orders', 'OrderController');

                //payments
                Route::any('/payments/list', 'PaymentController@list')->name('payments.list');
                Route::resource('/payments', 'PaymentController');


                //payment
                Route::get('/pay/', 'PayController@index')->name('pay.index');

                Route::get('/notifications', 'NotificationController@index')->name('notification.index');
            });
        });

        /* Billing (Partner tools) */
        Route::group(['namespace' => 'Billing', 'prefix' => 'billing'], function () {
            Route::name('billing.')->group(function () {

                //front page
                Route::get('/', 'IndexController@index')->name('index');
                Route::get('/user-status', 'IndexController@userStatus')->name('user.status');

                //orders
                Route::any('/orders/{id}/account', 'OrderController@account')->name('orders.account');
                Route::any('/orders/list', 'OrderController@list')->name('orders.list');
                Route::resource('/orders', 'OrderController');

                Route::get('/order/{id}/{type}', 'OrderController@cancel')->name('orders.cancel');
                Route::get('/order/calculator', 'OrderController@calculator')->name('orders.calculator');
                Route::post('/order/contract_cancellation', 'OrderController@uploadContractCancellation')->name('orders.contract_cancellation');
                Route::post('/deny_cancellation', 'OrderController@denyCancellation')->name('deny_cancellation');
                Route::get('/contract_for_cancellation/{contract_id}', '\App\Http\Controllers\Web\Billing\OrderController@sendCancellationAct')->name('contract_for_cancellation');
                Route::get('/contracts_for_cancellation', 'OrderController@showOrdersForCancellation')->name('contracts_for_cancellation');

                //affiliates
                Route::any('/affiliates/list', 'AffiliateController@list')->name('affiliates.list');
                Route::resource('/affiliates', 'AffiliateController');

                //catalog
                Route::name('catalog.')->prefix('catalog')->group(function () {
                    Route::get('/', 'CatalogProductController@index')->name('index');
                });

                //profile
                Route::name('profile.')->prefix('profile')->group(function () {
                    Route::get('/', 'ProfileController@show')->name('index');
                    Route::get('/edit', 'ProfileController@edit')->name('edit');
                    Route::get('/settings', 'ProfileController@settings')->name('settings');
                    Route::any('/update', 'ProfileController@update')->name('update');
                    Route::any('/update-settings', 'ProfileController@updateSettings')->name('update-settings');
                });

                //buyers
                Route::get('/buyers/list', '\App\Http\Controllers\Web\Billing\BuyerController@list')->name('buyers.list');
                Route::resource('/buyers', 'BuyerController')->except('delete', 'edit');


                Route::get('/notifications', 'NotificationController@index')->name('notification.index');
                Route::get('/statistics', 'StatisticsController@index')->name('statistics.index');

                Route::get('/reports/{model7}/export', 'ReportsController@export')->name('reports.vendors.export');
                Route::get('/reports/{model8}/export', 'ReportsController@export')->name('reports.filials.export');
                Route::get('/reports/vendorsCancel/export', 'ReportsController@export')->name('reports.vendorsCancel.export');
                Route::get('/reports/filialsCancel/export', 'ReportsController@export')->name('reports.filialsCancel.export');
                Route::get('/reports/delays/export', 'ReportsController@export')->name('reports.delays.export');
            });
        });

        /* Panel (Employee tools)*/
        Route::group(['namespace' => 'Panel'], function () {

            Route::name('cco.')->prefix('panel')->group(function () {
                Route::get('/reports/cco', 'ReportsController@commercialIndex')->name('report.index');
            });

            Route::name('panel.')->prefix('panel')->group(function () {


                Route::group(['prefix' => 'accounts', 'namespace' => 'Account'], function () {
                    Route::get('/', 'AccountController@index')->name('accounts.index');
                    Route::get('/create', 'AccountController@create')->name('accounts.create');
                    Route::get('/create-with-mask', 'AccountController@createWithMask')->name('accounts.create-mask');
                });

                Route::get('/', function () {
                    // TODO: Требуется серьезный рефакторинг работы главной страницы
                    $user = \Illuminate\Support\Facades\Auth::user();

                    $role = \App\Models\Role::find($user->role_id);
                    $routeName = null;

                    switch ($role->name) {
                        case 'debt-collect-leader':
                            $routeName = 'debt-collect-leader';
                            break;
                        case 'debt-collect-curator':
                            $routeName = 'debt-collect-curator';
                            break;
                        case 'debt-collect-curator-extended':
                            $routeName = 'debt-collect-curator-extended';
                            break;
                    }
                    if ($routeName !== null) {
                        return redirect(localeRoute($routeName));
                    }

                    if ($user->hasRole('employee') && $user->hasRole('recover')) {
                        return redirect(localeRoute('panel.recovery.index'));
                    }
                    if ($user->hasRole('employee') && $user->hasRole('editor')) {
                        return redirect(localeRoute('panel.news.index'));
                    }


                    if ($user->hasRole('admin'))
                        return redirect(localeRoute('panel.employees.index'));

                    elseif ($user->hasRole('owner'))
                        return redirect(localeRoute('panel.buyer.report'));
                    elseif ($user->hasRole('cco'))
                        return redirect(localeRoute('cco.report.index'));
                    elseif ($user->hasRole('kyc'))
                        return redirect(localeRoute('panel.buyers.index'));
                    elseif ($user->hasRole('debt-lawyer-ext'))
                        return redirect(localeRoute('panel.buyers.index'));
                    elseif ($user->hasRole('sales'))
                        return redirect(localeRoute('panel.partners.index'));
                    elseif ($user->hasRole('finance'))
                        return redirect(localeRoute('panel.finances.index'));
                    elseif ($user->hasRole('call-center'))
                        return redirect(localeRoute('tickets.index'));
                    elseif ($user->hasRole('ed_employee'))
                        return redirect(localeRoute('panel.employees.ed-get'));
                })->name('index');

                // Payment info by ID
                Route::get('/payment-info/payment-by-id', function () {
                    return view('panel.payment_info.payment_id');
                })->name('payment-info.payment-by-id');
                //news
                // Route::any('/news/index', '\App\Http\Controllers\Web\Panel\NewsController@index')->name('news.index');
                Route::any('/news/list', 'NewsController@list')->name('news.list');
                Route::resource('/news', 'NewsController');

                // Report Files
                Route::any('/report-files/exportTest', 'ReportFileController@exportTest')->name('report-files.exportTest');
                Route::any('/report-files/list', 'ReportFileController@list')->name('report-files.list');
                Route::any('/report-files/export', 'ReportFileController@export')->name('report-files.export');
                Route::resource('/report-files', 'ReportFileController');

                // O'zbekiston Pochtasi
                Route::any('/postal-regions/list', 'PostalRegionController@list')->name('postal-regions.list');
                Route::resource('/postal-regions', 'PostalRegionController');

                Route::any('/postal-areas/list', 'PostalAreaController@list')->name('postal-areas.list');
                Route::resource('/postal-areas', 'PostalAreaController');

                Route::any('/letters/list', 'LetterController@list')->name('letters.list');
                Route::any('/letters/create-letter', 'LetterController@createLetter')->name('letters.create-letter');
                Route::resource('/letters', 'LetterController');

                Route::any('/payments/list', 'PaymentController@list')->name('payments.list');
                Route::resource('/payments', 'PaymentController');

                //slides
                Route::resource('/slides', 'SlidesController')->except('index', 'show', 'create');
                Route::name('slides.')->prefix('slides')->group(function () {
                    Route::any('/list', 'SlidesController@list')->name('list');
                    Route::any('/{id}', 'SlidesController@index')->name('index');
                    Route::any('/create/{id}', 'SlidesController@create')->name('create');
                });

                //faq
                Route::any('/faq/list', 'FaqController@list')->name('faq.list');
                Route::resource('/faq', 'FaqController');

                //discounts
                Route::any('/discounts/list', 'DiscountController@list')->name('discounts.list');
                Route::resource('/discounts', 'DiscountController');

                //employees
                Route::get('/employees/ed', 'EdEmployeeController@index')->name('employees.ed-get');
                Route::any('/employees/list', 'EmployeeController@list')->name('employees.list');
                Route::resource('/employees', 'EmployeeController');

                //buyers
                Route::get('/buyers/list', '\App\Http\Controllers\Web\Panel\BuyerController@list')->name('buyers.list');
                Route::any('/buyers/delay', '\App\Http\Controllers\Web\Panel\BuyerDelayController@list')->name('buyers.delay');  // только просрочники
                Route::resource('/buyers', 'BuyerController');
                Route::get('/buyers/{id}/report/{reportID}', '\App\Http\Controllers\Core\BuyerController@report')->name('buyers.report');
                Route::get('/buyers/{id}/scoring-report/{reportID}', '\App\Http\Controllers\Core\BuyerController@scoringReport')->name('buyers.scoring.report');
                Route::get('/buyers/{id}/myid-report', '\App\Http\Controllers\V3\MyIDController@reportView')->name('buyers.scoring.myid.report');
                //partners
                Route::get('/partners/list', 'PartnerController@list')->name('partners.list');
                Route::get('/partners/sallers', 'PartnerController@sallers')->name('partners.sallers');
                Route::get('/partners/sallers/create', 'PartnerController@sallersCreate')->name('partners.sallers.create');
                Route::resource('/partners', 'PartnerController');

                //cards
                Route::any('/cards/list', 'CardController@list')->name('cards.list');
                Route::resource('/cards', 'CardController');

                //cards_pnfl
                Route::any('/cards-pnfl/list', 'CardPnflController@list')->name('cards-pnfl.list');
                Route::resource('/cards-pnfl', 'CardPnflController');

                //records
                Route::any('/record/list', 'RecordController@list')->name('record.list');
                Route::resource('/record', 'RecordController');

                //contracts
                Route::any('/contracts/list', 'ContractController@list')->name('contracts.list');
//                Route::get('/contracts/letter-to-court', 'ContractController@letterToCourt')->name('contracts.letterToCourt');
                Route::resource('/contracts', 'ContractController');
                Route::get('/contracts/executive-letter-first/{id}/{notary_id}', 'ContractController@executiveLetterFirst')->name('ExecutiveLetterFirst');
                Route::get('/contracts/executive-letter-second/{id}/{notary_id}', 'ContractController@executiveLetterSecond')->name('ExecutiveLetterSecond');
                Route::get('/contracts/executive-letter-third/{id}/{notary_id}', 'ContractController@executiveLetterThird')->name('ExecutiveLetterThird');
                Route::get('/contracts/executive-letter-fourth/{id}/{notary_id}', 'ContractController@executiveLetterFourth')->name('ExecutiveLetterFourth');

                Route::post('/contracts/fourth-letter-generate-word/{contract}/{notary}',
                    'ContractController@letterGenerateWordDocument')->name('letterGenerateWordDocument');

                Route::get('/contracts/court-contract-print-form/{contract_id?}', 'ContractController@courtContractPrintForm')->name('courtContractPrintForm');
//                Route::get('/contracts/letter-to-workplace/{id}', 'ContractController@workplaceLetter')->name('letterToWorkplace');
                Route::get('/contracts/letter-to-residency-2/{id}', 'ContractController@residencyLetterTwo')->name('letterToResidencyTwo');
                Route::get('/contracts/requirement/{id}', 'ContractController@requirement')->name('requirement');
                Route::get('/contracts/letter-to-enforcement-agency/{id}', 'ContractController@enforcementAgencyLetter')->name('letterToEnforcementAgency');
                Route::get('/contracts/letter-to-residency/{id}', 'ContractController@residencyLetter')->name('letterToResidency');
                Route::get('/contracts/myid/form-1/{myid}/docx', 'ContractController@myIdFormOneDocX')->name('myIdFormDocx');
                Route::get('/contracts/myid/form-1/{myid}/pdf', 'ContractController@myIdFormOnePdf')->name('myIdFormPdf');
                Route::get('/contracts/myid/form-1/{myid}/{contract_id?}', 'ContractController@myIdFormOne')->name('myIdFormOne')->where('contract_id', '[0-9]+');
                // Contract verification
                Route::any('/contract-verify/list', 'ContractVerifyController@list')->name('contractVerify.list');
                Route::resource('/contract-verify', 'ContractVerifyController');

                //contracts
                Route::any('/finance/list', 'FinanceController@list')->name('finances.list');
                Route::any('/finance/order/{order}', 'FinanceController@order')->name('finances.order');
                Route::resource('/finances', 'FinanceController');

                //call center
                Route::resource('/callcenter', 'CallCenterController');

                //catalog
                Route::name('catalog.')->prefix('catalog')->group(function () {
                    Route::get('/', 'CatalogCategoryController@index')->name('index');
                    Route::resource('/categories', 'CatalogCategoryController')->except('show');
                    Route::get('/categories/list', '\App\Http\Controllers\Web\Panel\CatalogCategoryController@list')->name('categories.list');

                    Route::resource('/fields', 'CatalogProductFieldController')->except('show');
                    Route::name('fields.')->prefix('fields')->group(function () {
                        Route::get('/list', '\App\Http\Controllers\Web\Panel\CatalogProductFieldController@list')->name('list');
                    });
                });

                Route::get('/notifications', 'NotificationController@index')->name('notification.index');
                Route::get('/statistics', 'StatisticsController@index')->name('statistics.index');

                //pay-systems
                Route::any('/pay-system/list', 'PaySystemController@list')->name('pay-system.list');
                Route::resource('/pay-system', 'PaySystemController');

                // Reports
                // отчеты
                Route::get('/reports', 'ReportsController@index')->name('reports.index');
                Route::get('/reports/{model}/export', 'ReportsController@export')->name('reports.orders.export');
                Route::get('/reports/{model2}/export', 'ReportsController@export')->name('reports.payments.export'); // списание
                Route::get('/reports/{model3}/export', 'ReportsController@export')->name('reports.history.export'); //
                Route::get('/reports/{model4}/export', 'ReportsController@export')->name('reports.verified.export');
                Route::get('/reports/{model5}/export', 'ReportsController@export')->name('reports.contracts.export');
                Route::get('/reports/{model6}/export', 'ReportsController@export')->name('reports.delays.export'); // просрочники
                Route::get('/reports/{model9}/export', 'ReportsController@export')->name('reports.delaysEx.export');
                Route::get('/reports/{model10}/export', 'ReportsController@export')->name('reports.paymentDate.export');
                Route::get('/reports/{model11}/export', 'ReportsController@export')->name('reports.vendorsFull.export');
                Route::get('/reports/{model12}/export', 'ReportsController@export')->name('reports.vendorsFillial.export');
                Route::get('/reports/{model13}/export', 'ReportsController@export')->name('reports.contractsCancel.export');
                Route::get('/reports/{model15}/export', 'ReportsController@export')->name('reports.ordersCancel.export');
                Route::get('/reports/{model16}/export', 'ReportsController@export')->name('reports.paymentFill.export');
                Route::get('/reports/{model7}/export', 'ReportsController@export')->name('reports.transactions.export'); // транзакции
                Route::get('/reports/{model17}/export', 'ReportsController@export')->name('reports.bonus.export');
                Route::get('/reports/{model18}/export', 'ReportsController@export')->name('reports.bonusClients.export');
                Route::get('/reports/{model19}/export', 'ReportsController@export')->name('reports.detailedContracts.export');
                Route::get('/reports/{model20}/export', 'ReportsController@export')->name('reports.ordersCancelNew.export');
                Route::get('/reports/{model21}/export', 'ReportsController@export')->name('reports.debtors.export');
                Route::get('/reports/{model22}/export', 'ReportsController@export')->name('reports.filesHistory.export');
                Route::get('/reports/{model23}/export', 'ReportsController@export')->name('reports.edTransaction.export');
                Route::get('/reports/{model25}/export', 'ReportsController@export')->name('reports.comparativeDocument.export');
                Route::get('/reports/{model26}/export', 'ReportsController@export')->name('reports.reverseBalance.export');
                Route::post('/reports/debt-collectors-filtered-export', 'ReportsController@debtCollectorsFilteredExport')->name('reports.debtCollectorsFilteredExport');

                Route::any('/buyer/rescoring', 'BuyerController@rescoring')->name('buyer.rescoring');
                Route::any('/buyer/report', 'BuyerController@report')->name('buyer.report');  // отчет по клиентам
                Route::any('/soliq/report', 'SoliqController@index')->name('soliq.report');
                Route::any('/mko/report', 'BuyerController@mkoReport')->name('mko.report');

                Route::any('/buyer/buyer_delay', 'BuyerDelayController@index')->name('buyer.buyer_delay');  // только просрочники
                Route::any('/buyer/card_delay', 'BuyerDelayController@cards')->name('buyer.card_delay');  // только просрочники

                Route::get('/sallers/list', 'SallerController@list')->name('sallers.list');
                Route::resource('/sallers', 'SallerController');

                Route::any('/katm-parse', '\App\Http\Controllers\Core\CronController@katmParse')->name('katmParse');

                Route::any('/katm-report', '\App\Http\Controllers\Core\KatmController@report')->name('katmReport'); // отчет катм
                Route::any('/cancel', '\App\Http\Controllers\Core\KatmController@cancel')->name('katmCancel'); // отчет катм
                Route::any('/katm-mib', '\App\Http\Controllers\Core\KatmController@katmMib')->name('katmMib');
                Route::any('/katm-mib-report', '\App\Http\Controllers\Core\KatmController@katmMibReport')->name('katmMibReport');
                Route::get('/katm-credit-report', '\App\Http\Controllers\Web\Panel\KatmController@creditReport')->name('katmCreditReport');


                Route::any('/system/user-payment', '\App\Http\Controllers\Core\PaymentController@userPayment')->name('userPayment'); // повторные списания

                // взимание долга
                Route::name('recovery.')->prefix('recovery')->group(function () {
                    Route::name('collectors.')->prefix('collectors')->group(function () {
                        Route::get('/', '\App\Http\Controllers\Web\Panel\CollectorController@collectors')->name('collectors');
                        Route::get('/contracts', '\App\Http\Controllers\Web\Panel\CollectorController@contracts')->name('contracts');
                        Route::get('/contracts/{contract}/transactions', '\App\Http\Controllers\Web\Panel\CollectorTransactionController@get')->name('transactions');
                    });
                });
                Route::any('/recovery/contracts-recovery', '\App\Http\Controllers\Web\Panel\RecoveryController@contractsRecovery')->name('recovery.contracts-recovery');
                Route::any('/recovery/contracts-recoveries', '\App\Http\Controllers\Web\Panel\RecoveryController@contractsRecoveries')->name('recovery.contracts-recoveries');
                Route::any('/recovery/contracts-report', '\App\Http\Controllers\Web\Panel\RecoveryController@contractsReport')->name('recovery.contracts-report');
                Route::get('/recovery/list', 'RecoveryController@list')->name('recovery.list');
                Route::get('/recovery/get-document/{element_id}/{type}', 'RecoveryController@getDocument');
                Route::resource('/recovery', 'RecoveryController');

                //chamge phone numbher

                Route::resource('/change/phone-number', 'ChangePhoneNumberController');


                Route::any('/graph', '\App\Http\Controllers\Web\Panel\GraphController@index')->name('graph.index');
                Route::any('/graph/contracts', '\App\Http\Controllers\Web\Panel\GraphController@contracts')->name('graph.contracts');
                Route::any('/graph/clients', '\App\Http\Controllers\Web\Panel\GraphController@clients')->name('graph.clients');
                Route::any('/graph/clients/{id}', '\App\Http\Controllers\Web\Panel\GraphController@client')->name('graph.client');


                Route::get('/monitoring', '\App\Http\Controllers\Web\Panel\MonitoringController@index')->name('monitoring.index');
                Route::get('/monitoring/contracts', '\App\Http\Controllers\Web\Panel\MonitoringController@contracts')->name('monitoring.contracts');
                Route::get('/monitoring/accounts', '\App\Http\Controllers\Web\Panel\MonitoringController@accounts')->name('monitoring.accounts');
                Route::get('/monitoring/accounts/list', '\App\Http\Controllers\Web\Panel\MonitoringController@accountsList')->name('monitoring.accounts.list');
                Route::get('/monitoring/accounts/user/{id}', '\App\Http\Controllers\Web\Panel\MonitoringController@user')->name('monitoring.user');
                Route::get('/monitoring/accounts/cards/{id}', '\App\Http\Controllers\Web\Panel\MonitoringController@userCards')->name('monitoring.cards');
                Route::get('/monitoring/deposits', '\App\Http\Controllers\Web\Panel\MonitoringController@deposits')->name('monitoring.deposits');
                Route::get('/monitoring/bonuses', '\App\Http\Controllers\Web\Panel\MonitoringController@bonuses')->name('monitoring.bonuses');

                Route::post('/monitoring/accounts/user/{id}/cache/clear', '\App\Http\Controllers\Web\Panel\MonitoringController@cacheClear')->name('monitoring.user.cache.clear');


                Route::get('/universal/autopayment', 'UniversalAutopaymentController@index')->name('universal.autopayment.index');
                Route::get('/universal/transactions', 'UniversalAutopaymentController@transactions')->name('universal.autopayment.transactions');
                Route::get('/universal/debtors', 'UniversalAutopaymentController@debtors')->name('universal.autopayment.debtors');
                Route::get('/universal/debtors/{id}/transaction', 'UniversalAutopaymentController@debtorTransactions')->name('universal.autopayment.debtor.transactions');

                Route::get('/faq', 'FaqInfoController@index')->name('faqinfo.index');
                Route::get('/fake/create/transactions', '\App\Http\Controllers\Web\Panel\FakeController@index')->name('fake.transaction');
                Route::get('/fake/export/transactions', '\App\Http\Controllers\Web\Panel\FakeController@export')->name('fake.import.transaction');
                Route::post('/fake/export', '\App\Http\Controllers\Core\FakeController@export')->name('fakeExport');
                Route::get('/download-act/{contract_id?}', '\App\Http\Controllers\Core\ContractController@DownloadAct')->name('downloadAct');
                Route::get('/generate-act/{contract}/{type}', '\App\Http\Controllers\Core\ContractController@GenerateAct')->name('generateAct');

                Route::get('/katm/reports/contracts', '\App\Http\Controllers\Web\Panel\KatmReportController@contractsPageView')->name('katm-report.contracts');

                Route::get('/katm/reports/contracts/summary', '\App\Http\Controllers\Web\Panel\KatmReportController@contractsSummaryPageView')->name('katm-report.contracts.summary');
                Route::get('/katm/reports/contracts/{contractID}', '\App\Http\Controllers\Web\Panel\KatmReportController@showPageView')->name('katm-report.contract');

                Route::get('/katm/reports/sending/{reportID}', '\App\Http\Controllers\Web\Panel\KatmReportController@showSendingReportPageView')->name('katm-report.sending.report');
                Route::get('/katm/reports/receiving/{reportID}', '\App\Http\Controllers\Web\Panel\KatmReportController@showReceivingReportPageView')->name('katm-report.receiving.report');
                Route::get('/katm/reports/receiving/{reportID}/decorated', '\App\Http\Controllers\Web\Panel\KatmReportController@showReportView')->name('katm-report.receiving.report.decorated');



            });

        });

    });

    Route::get('/collector/{all?}', 'Collector\CollectorController@frontend')->where('all', '.*')->name('collector.frontend');
    Route::group(['middleware' => ['auth']], function () {
        Route::get('/panel/catalog-categories/{all?}', function () {
            return view('panel/frontend_layout');
        })->where('all', '.*')->name('panel.frontend');

        Route::prefix('panel')->name('panel.')->group(function () {
            Route::prefix('debt-collect-leader')->name('debtCollectLeader.')->group(function () {
                Route::get('/curators', function () {
                    return view('panel/frontend_layout');
                })->name('curators');
                Route::get('/collectors', function () {
                    return view('panel/frontend_layout');
                })->name('collectors');
                Route::get('/debtors', function () {
                    return view('panel/frontend_layout');
                })->name('debtors');

                Route::prefix('analytic')->name('analytic.')->group(function () {
                    Route::get('/collectors', function () {
                        return view('panel/frontend_layout');
                    })->name('collectors');
                    Route::get('/debtors', function () {
                        return view('panel/frontend_layout');
                    })->name('debtors');
                    Route::get('/letters', function () {
                        return view('panel/frontend_layout');
                    })->name('letters');
                });
            });

            Route::prefix('debt-collect-curator')->name('debtCollectCurator.')->group(function () {
                Route::get('/collectors', function () {
                    return view('panel/frontend_layout');
                })->name('collectors');

                Route::prefix('analytic')->name('analytic.')->group(function () {
                    Route::get('/collectors', function () {
                        return view('panel/frontend_layout');
                    })->name('collectors');
                    Route::get('/debtors', function () {
                        return view('panel/frontend_layout');
                    })->name('debtors');
                });
            });

            Route::prefix('debt-collect-curator-extended')->name('debtCollectCuratorExtended.')->group(function () {
                Route::get('/collectors', function () {
                    return view('panel/frontend_layout');
                })->name('collectors');
                Route::get('/debtors', function () {
                    return view('panel/frontend_layout');
                })->name('debtors');

                Route::prefix('analytic')->name('analytic.')->group(function () {
                    Route::get('/collectors', function () {
                        return view('panel/frontend_layout');
                    })->name('collectors');
                    Route::get('/debtors', function () {
                        return view('panel/frontend_layout');
                    })->name('debtors');
                });
            });
        });

        Route::get('/panel/debt-collect-leader/{all?}', function () {
            return view('panel/frontend_layout');
        })->where('all', '.*')->name('debt-collect-leader');
        Route::get('/panel/debt-collect-curator/{all?}', function () {
            return view('panel/frontend_layout');
        })->where('all', '.*')->name('debt-collect-curator');
        Route::get('/panel/debt-collect-curator-extended/{all?}', function () {
            return view('panel/frontend_layout');
        })->where('all', '.*')->name('debt-collect-curator-extended');
    });
});

