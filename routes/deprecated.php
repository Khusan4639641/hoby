<?php

use App\Helpers\PushHelper;
use App\Http\Controllers\Web\Panel\PaymentController;
use App\Models\PaymentsData;
use Illuminate\Http\Request;
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

//web.php
Route::group(['namespace' => 'Web', 'prefix' => '{locale?}', 'middleware' => 'locale'], function () {


    /* Frontend */
    Route::group(['namespace' => 'Frontend'], function () {

        // авто уведомление клиентов о предстоящем списании
        Route::get('autonotify', function (Request $request) {

            $date_from = date('Y-m-d 00:00:00', strtotime('+3 day '));
            $date_to = date('Y-m-d 23:59:59', strtotime('+3 day'));

            $cps = \App\Models\ContractPaymentsSchedule::select('user_id', 'balance', 'contract_id')->whereBetween('payment_date', [$date_from, $date_to])->where('status', 0)->get();
            echo $date_from . ' ' . $date_to;

            $options = [
                'type' => PushHelper::TYPE_NEWS_ALL,
                'title_uz' => 'Предстоящая оплата',
                'title_ru' => 'Предстоящая оплата',
                'user_id' => null,
                'text_ru' => null,
                'text_uz' => null,
            ];
            foreach ($cps as $item) {
                $sum = number_format($item->balance, 2, '.', ' ');
                $options['user_id'] = $item->user_id;
                $options['text_uz'] = "Уведомление о предстоящей оплате договора №{$item->contract_id} на сумму {$sum} сум через 3 дня  !";
                $options['text_ru'] = "Уведомление о предстоящей оплате договора №{$item->contract_id} на сумму {$sum} сум через 3 дня  !";
                PushHelper::sendTest($options);
            }

        });

        Route::get('autopayment-many', function () {  // ручное автосписание НЕ УДАЛЯТЬ!!!
            $pay = new PaymentController();
            try {
                $res = $pay->autopaymentMany();
            } catch (Exception $e) {

                if ($payment_data = PaymentsData::find(1)) {
                    $payment_data->last_id = 1;
                    $payment_data->save();
                }

                dd($e);

            }

            print_r($res);
            exit('done');
        });

        Route::any('/score', 'FrontendController@score')->name('score'); // эмулятор скоринга
        Route::get('/doit', 'FrontendController@doit')->name('doit');  // всякие временные задачи
        Route::post('/send', 'FrontendController@send')->name('send');
        Route::get('/logme', 'LoginController@logme')->name('logme');

        Route::get('/send-message-payment-delay', 'FrontendController@sendMessagePaymentDelay')->name('sendMessagePaymentDelay');
        Route::get('/send-message-users', 'FrontendController@sendMessageAll')->name('sendMessageAll');  // массовая отправка смс
//        Отрубили, так как массовой рассылкой не пользуемся, 01.08.2022 dev_nurlan_production_hotfix_bonus_to - card_methods
        Route::get('/search', 'SearchController@index')->name('search');


        Route::any('/autopayment-cron', '\App\Http\Controllers\Core\CronController@autopaymentCron')->name('autopaymentCron'); // оплата крон
        Route::any('/autopayment-test', '\App\Http\Controllers\Core\CronController@autopaymentTest')->name('autopaymentTest'); // оплата крон тест
        Route::any('/correct-payment', '\App\Http\Controllers\Core\CronController@correctPayment')->name('correctPayment'); // оплата крон тест
        Route::any('/push-test', '\App\Http\Controllers\Core\CronController@pushTest')->name('pushTest');
        Route::any('/sallary-test', '\App\Http\Controllers\Core\CronController@sallaryTest')->name('sallaryTest');

        Route::get('/check-days', '\App\Http\Controllers\Web\Panel\RecoveryController@checkDays'); // проверка времени ожидания отсрочки

        Route::get('/migrate', 'MigrateController@migrate')->name('migrate');
        Route::get('/clear', 'MigrateController@clear')->name('clear');

//         Cart
        Route::group(['prefix' => 'cart'], function () {
            Route::get('/', 'CartController@index')->name('cart');
            Route::name('cart.')->group(function () {
                Route::any('/add/', 'CartController@add')->name('add');
                Route::any('/update/', 'CartController@update')->name('update');
                Route::any('/delete/', 'CartController@delete')->name('delete');
                Route::name('settings.')->group(function () {
                    Route::any('/save', 'CartController@saveSettings')->name('save');
                    Route::any('/load', 'CartController@loadSettings')->name('load');
                });
            });
        });

        Route::group(['middleware' => ['guest']], function () {
            Route::get('/panel/login', 'LoginController@panelLogin')->name('panel.login');
            Route::get('/invite/{user_id}', '\App\Http\Controllers\Core\Auth\LoginController@invite')->name('invite');

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

        Route::group(['namespace' => 'Sales-manager', 'prefix' => 'sales-manager'], function () {
            Route::name('sales-manager.')->group(function () {
                Route::post('/deny_cancellation', '\App\Http\Controllers\Web\Billing\OrderController@denyCancellation')->name('deny_cancellation');
            });
        });

        /* Panel (Employee tools)*/
        Route::group(['namespace' => 'Panel'], function () {

            Route::name('panel.')->prefix('panel')->group(function () {

                //index page
                Route::get('/', 'IndexController@index')->name('index');

                //news
                Route::any('/news/index', '\App\Http\Controllers\Web\Panel\NewsController@index')->name('news.index');
                Route::get('/contracts/letter-to-court', 'ContractController@letterToCourt')->name('contracts.letterToCourt');
//                 Reports
                Route::get('/reports', 'ReportsController@index')->name('reports.index');
                Route::post('/reports/export', 'ReportsController@export')->name('reports.export');
                Route::any('/system/cron', '\App\Http\Controllers\Core\SystemController@cron')->name('system.cron'); // отчет катм
                Route::any('/system/set-cron-status', '\App\Http\Controllers\Core\SystemController@setCronStatus')->name('system.setCronStatus'); // отчет катм

                Route::any('/recovery/index', '\App\Http\Controllers\Web\Panel\RecoveryController@index')->name('recovery.contracts-delay');
                Route::any('/graph/requests', '\App\Http\Controllers\Web\Panel\GraphController@requests')->name('graph.requests');
                Route::get('/monitoring/contracts/old', '\App\Http\Controllers\Web\Panel\MonitoringController@contractsByOldAlgorithm')->name('monitoring.contracts.old');
            });

        });

    });
});


//apiV3.php //Ивана часть с добавлением ролей/ пермишеннов и юзеров
Route::group(['namespace' => '\App\Http\Controllers\Core\Auth\V3', 'middleware' => ['api.authenticate']], function () {
    Route::get('/permission/list', 'PermissionV3Controller@list')->name('PermissionV3List');
    Route::get('/permission/{permission}', 'PermissionV3Controller@get')->name('PermissionV3Get');
    Route::put('/permission/{permission}/update', 'PermissionV3Controller@update')->name('PermissionV3Update');
    Route::post('/permission/create', 'PermissionV3Controller@create')->name('PermissionV3Create');
    Route::delete('/permission/{permission}', 'PermissionV3Controller@delete')->name('PermissionV3Delete');

    Route::get('/role/list', 'RoleV3Controller@list')->name('RoleV3List');
    Route::get('/role/{role}', 'RoleV3Controller@get')->name('RoleV3Get');
    Route::put('/role/{role}/update', 'RoleV3Controller@update')->name('RoleV3Update');
    Route::post('/role/create', 'RoleV3Controller@create')->name('RoleV3Create');
    Route::delete('/role/{role}', 'RoleV3Controller@delete')->name('RoleV3Delete');

    Route::get('/user/list', 'UserV3Controller@list')->name('UserV3List');
    Route::get('/user/{user}', 'UserV3Controller@get')->name('UserV3Get');
    Route::put('/user/{user}/update', 'UserV3Controller@update')->name('UserV3Update');
    Route::post('/user/create', 'UserV3Controller@create')->name('UserV3Create');
});

