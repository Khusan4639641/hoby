<?php
//header('Content-Type: text/html; charset=utf-8');
//header('Content-Type: application/xml; charset=utf-8');
//header('SOAPAction:');

echo 'ddd';
exit;
require_once 'classes.php';

//$client = new SoapClient('soap/ProviderWebService.wsdl',['soap_version' => SOAP_1_2]);
//$client->__setLocation('http://soap.loc/index.php');

$PerformTransactionArguments = new PerformTransactionArguments();

$PerformTransactionArguments->username = 'test_paynet';
$PerformTransactionArguments->password = 'asj37Ff2-38g3';
$PerformTransactionArguments->serviceId = '1';
$PerformTransactionArguments->transactionId = '111';
$PerformTransactionArguments->transactionTime = '2021.11.17 10:11:12';
$PerformTransactionArguments->amount = 33000;
$PerformTransactionArguments->parameters = [['paramKey'=>'client_id','paramValue'=>'998901000001'],['paramKey'=>'pay_amount','paramValue'=>'100']];


try{
	
	// initialize SOAP client and call web service function
	$client=new SoapClient('http://test.loc/ru/paynet.php?wsdl',['trace'=>1,'cache_wsdl'=>WSDL_CACHE_NONE]);
	//$resp  =$client->bookYear($book);
	
	//$client->__setLocation('http://test.loc/paynet.php');
	$client->__setLocation('http://test.loc/ru/paynet.php');
	
	$resp = $client->GetInformation($PerformTransactionArguments);

	//echo $resp; exit;

	//echo 'ok';
	print_r($resp);
	
	exit;
	
}catch(Exception $e){
	echo '<pre>';
	print_r($e);
	echo '</pre>';
}

/*
echo '[end-script]';

// model
class Book
{
	public $name;
	public $year;
}

// create instance and set a book name
$book      =new Book();
$book->name='test 3';

try{
// initialize SOAP client and call web service function
$client=new SoapClient('http://soap.loc/soap/server.php?wsdl',['trace'=>1,'cache_wsdl'=>WSDL_CACHE_NONE]);
$resp  =$client->bookYear($book);
}catch(Exception $e){
	echo '<pre>';
	print_r($e);
	echo '</pre>';
	
}

// dump response
var_dump($resp); */