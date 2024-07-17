<?php

namespace App\Extensions;
use Illuminate\Support\Facades\Log;

/**
 * Class ExSoapClient
 * @package App\Extensions
 * Расширение стандартного SoapClient для работы с humo шлюзами у которых нет документации WSDL
 */
class ExSoapClient extends \SoapClient {

    function __doRequest($request, $location, $action, $version, $one_way = NULL) {
        Log::channel('cards')->warning(print_r($request,1));
        if(strpos($request, 'urn:PaymentServer') > 0 && preg_match('/ebppif1/', $request)) {
            $head = '<env:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:ebppif1="urn:PaymentServer">';
            $request = preg_replace('/\<.*?urn\:PaymentServer.*?\>/', $head, $request);
            $request = str_replace('env:', 'SOAP-ENV:', $request);
        }elseif(strpos($request, 'xsd') === false)
            $request = preg_replace('/xmlns\:[a-z0-1]+\=\"urn\:/is', 'xmlns:urn="urn:', $request);
        Log::channel('cards')->warning(print_r($request,1));
        // parent call
        return parent::__doRequest($request, $location, $action, $version, $one_way);
    }

}
