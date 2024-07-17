<?php

namespace App\Http\Controllers\Web\Frontend;

use App\Soap\PaynetSoapServer;
use App\Models\Buyer;
use App\Soap\PerformTransactionArguments;
use Illuminate\Http\Request;
use \App\Http\Controllers\Core\CartController as Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SoapServer;


class PaynetController extends Controller {


    public function pay(Request $request)
    {

       /* header("Content-Type: text/xml; charset=utf-8");
        header('Cache-Control: no-store, no-cache');
        header('Expires: '.date('r'));

        ini_set("soap.wsdl_cache_enabled","0");*/
        Log::channel('paynet')->info('soap init');
        Log::channel('paynet')->info(request());
        try {

            // initialize SOAP Server
            $server = new SoapServer('file://' . Storage::disk('wsdl')->getDriver()->getAdapter()->applyPathPrefix('ProviderWebService.wsdl'), [
                'classmap' => [
                    'PerformTransactionArguments' => PerformTransactionArguments::class,
                ]
            ]);
            Log::channel('paynet')->info('soap in');

            // register available functions
            //$server->addFunction($this->PerformTransaction($request));
            $server->setClass(PaynetSoapServer::class);

            Log::channel('paynet')->info($server->getFunctions());

            // start handling requests
            $server->handle();


           // echo 'handle';
            Log::channel('paynet')->info('soap end');

        }catch (\SoapFault $e){
            Log::channel('paynet')->info('soap try error');
            Log::channel('paynet')->info($e);
           // dd($e);
        }

       // echo 'ok';

        //$server->setClass(PaynetSoapServer::class);
        // $server->handle();


        exit;

    }



}
