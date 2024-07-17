<?php
header("Content-Type: text/xml; charset=utf-8");
header('Cache-Control: no-store, no-cache');
header('Expires: ' . date('r') );
ini_set("soap.wsdl_cache_enabled","0");

include 'classes.php';
Log::info('Connect from paynet: '. $_SERVER['REMOTE_ADDR']);


if( !in_array($_SERVER['REMOTE_ADDR'],['213.230.106.112','213.230.106.115','213.230.65.80','37.110.210.13','84.54.80.200'])){
    if(class_exists('Log')) {
        Log::info('ERROR. Incorrect ip address, not valid for PAYNET');
        Log::info($_SERVER);
    }
    return [
        'timeStamp' => date('Y-m-d\TH:i:s+05:00'),
        'errorMsg' =>'Error',
        'status' => 601,
    ];
}


$server=new SoapServer('ProviderWebService.wsdl',[
    'classmap'=>[
        'PerformTransactionArguments'=>'PerformTransactionArguments',
        'CheckTransactionArguments'=>'CheckTransactionArguments',
        'CancelTransactionArguments'=>'CancelTransactionArguments',
        'GetInformationArguments'=>'GetInformationArguments',
        'GetStatementArguments'=>'GetStatementArguments',
    ]
]);

$server->setClass(PaynetService::class);
$server->handle();

exit;

