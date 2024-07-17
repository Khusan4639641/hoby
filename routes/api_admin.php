<?php

Route::group(['namespace' => 'Admin', 'prefix' => 'v3/admin', 'name' => 'api.', 'middleware' => ['tojson', 'locale.api', 'api.authenticate']], function () {
    Route::group(['prefix' => 'transaction', 'namespace' => 'Payments'], function () {
        Route::get('/list', 'DetailPaymentController@list')->name('V3AdminTransactionList');
        Route::get('/config', 'DetailPaymentController@config')->name('V3AdminTransactionConfig');
        Route::post('/make', 'DetailPaymentController@make')->name('V3AdminTransactionMake');
    });
    Route::group(['prefix' => 'company', 'namespace' => 'Companies'], function () {
        Route::get('/list', 'DetailCompanyController@list')->name('V3AdminCompanyList');
        Route::get('/single/{id}', 'DetailCompanyController@single')->name('V3AdminCompanySingle');
    });

    Route::group(['prefix' => 'ed-transaction'], function () {
        Route::get('filter', 'EdTransactionController@filter')->name('V3AdminTransactionFilter');
        Route::get('download/report', 'EdTransactionController@downloadReport')->name('V3AdminTransactionDownloadReport');
    });


    Route::group(['prefix' => 'contract', 'namespace' => 'Contracts'], function () {
        Route::post('partly-cancel', 'ContractController@partlyCancel')->name('V3AdminContractPartlyCancel');
    });

    Route::group(['prefix' => 'faq-info', 'namespace' => 'Faq'], function () {
        Route::get('/list', 'FaqInfoController@faqList')->name('V3AdminАaqList');
        Route::get('/history', 'FaqInfoController@showHistory')->name('V3AdminShowHistory');
        Route::post('/insert', 'FaqInfoController@insert')->name('V3AdminFaqInsert');
        Route::delete('/delete/{faq_info}', 'FaqInfoController@delete')->name('V3AdminFaqDelete');
        Route::post('/update/{faq_info}', 'FaqInfoController@update')->name('V3AdminFaqUpdate');
    });

    Route::group(['prefix' => 'company/accounts', 'namespace' => 'Companies'], function () {
        Route::post('/add', 'CompanyAccountController@store')->name('V3AdminCompanyAccountAdd');
        Route::patch('/update/{id}', 'CompanyAccountController@update')->name('V3AdminCompanyAccountUpdate');
        Route::delete('/delete/{id}', 'CompanyAccountController@delete')->name('V3AdminCompanyAccountDelete');
    });

    Route::group(['prefix' => 'accounts', 'namespace' => 'MFO'], function () {
        Route::get('/', 'AccountController@index')->name('V3AdminAccountsIndex');
        Route::get('/balances', 'AccountController@balances')->name('V3AdminAccountsBalances');
        Route::post('/balances', 'AccountController@createBalanceHistoryRecord')->name('V3AdminAccountsCreateBalanceHistoryRecord');
        Route::post('/balances/calculate', 'AccountController@calculateAllBalances')->name('V3AdminAccountsCalculateAllBalances');
        Route::get('/balances/calculate/status/{processId}', 'AccountController@calculateBalancesProcessStatus')->name('V3AdminAccountsCalculateBalancesProcessStatus');
        Route::post('/balances/calculate/{id}', 'AccountController@calculateBalance')->name('V3AdminAccountsCalculateBalances');
        Route::put('/balances/{id}', 'AccountController@updateBalanceHistoryRecord')->name('V3AdminAccountsUpdateBalanceHistoryRecord');
        Route::delete('/balances/{id}', 'AccountController@deleteBalanceHistoryRecord')->name('V3AdminAccountsDeleteBalanceHistoryRecord');
    });
    Route::group(['prefix' => 'account-match', 'namespace' => 'MFO'], function () {
        Route::get('/list', 'MatchedAccountController@list')->name('V3AdminAccountMatchList');
        Route::post('/insert', 'MatchedAccountController@insert')->name('V3AdminAccountMatchInsert');
        Route::delete('/delete/{account_match}', 'MatchedAccountController@delete')->name('V3AdminAccountMatchDelete');
        Route::post('/update/{account_match}', 'MatchedAccountController@update')->name('V3AdminAccountMatchUpdate');
    });
});

Route::get('/delta/contracts/{contract}/actions', 'Admin\DeltaController@getActions')->middleware('delta.auth');

Route::group(['prefix' => 'v3/reports', 'namespace' => 'Core'], function () {
    Route::post('/from-mko', 'ReportsController@fromMko')->name('reports.from-mko');
    Route::post('/mko-reports', 'ReportsController@getMkoReportsList')->name('reports.mko-reports-list');
    Route::post('/mko-reports/sent/{mkoReport}', 'ReportsController@markMkoReportAsSent')->name('reports.mko-reports-sent');
    Route::post('/mko-reports/error/{mkoReport}', 'ReportsController@markMkoReportAsError')->name('reports.mko-reports-error');
});
