<?php


namespace App\Http\Controllers\Web\Panel;

use App\Helpers\CurlHelper;
use App\Http\Controllers\Core\EmployeeBuyerController as Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SoliqController extends Controller {

    private $login = 'solutions';
    private $password = '7SYhA[~t{VWNZF@B';

    public function index(Request $request){

        $inn = '';
        $period = 1;

        if( $request->has('inn') && $request->has('reports') && $request->has('period') ){
            $inn = $request->inn;
            $period = $request->period;
            $reports = explode(',',trim($request->reports,','));

            Log::channel('curl')->info('SOLIQ:');
            Log::channel('curl')->info($request);
            Log::info('SOLIQ:');
            Log::info($request);

            $result = [];
            foreach($reports as $k=>$report){
                $action = 'report'.ucfirst($report);
                $res = $this->$action($request);
                Log::info('SOLIQ: res ' . $action);
                Log::info($res);


                if($res['status'] == 'success') $result[$report] = $res['data'];
            }

            //dd('test');

            Log::info('SOLIQ: 2');

            $file_catalog = json_encode($result,JSON_UNESCAPED_UNICODE);
            $filename = 'soliq.csv';
            $file_catalog = iconv('utf-8','windows-1251//TRANSLIT',$file_catalog);

            file_put_contents($filename,$file_catalog);
            Log::info('SOLIQ: 3');
            if(file_exists($filename)) {
                header( 'Content-type: '. mime_content_type($filename));
                header( 'Content-Disposition: attachment; filename=' . $filename );
                readfile($filename);
                exit;
            }

        }

        return view('panel.buyer.soliq', compact('period','inn') );

    }

    public function reportCompanies(Request $request){

        $data = ['company_tin'=>$request->inn,'lang'=>'uz'];

        $options['header'] = ['Content-Type:application/x-www-form-urlencoded'];
        $options['method'] = 'POST';
        $options['url'] = 'https://ws.soliqservis.uz/gnk/data/yurnp1';
        $options['basic'] = true;
        $options['login'] = $this->login;
        $options['password'] = $this->password;
        $options['data'] = $data;

        return CurlHelper::send($options);

    }
    public function reportDebts(Request $request){

        $data = ['tin'=>$request->inn,'lang'=>'uz'];

        $options['header'] = ['Content-Type:application/x-www-form-urlencoded'];
        $options['method'] = 'POST';
        $options['url'] = 'https://ws.soliqservis.uz/gnk/data/yurdebt';
        $options['basic'] = true;
        $options['login'] = $this->login;
        $options['password'] = $this->password;
        $options['data'] = $data;

        return CurlHelper::send($options);

    }
    public function reportWorkers(Request $request){

        $data = ['tin'=>$request->inn,'lang'=>'uz', 'year'=>date('Y')-1];

        $options['header'] = ['Content-Type:application/x-www-form-urlencoded'];
        $options['method'] = 'POST';
        $options['url'] = 'https://ws.soliqservis.uz/gnk/data/yur-employee-count';
        $options['basic'] = true;
        $options['login'] = $this->login;
        $options['password'] = $this->password;
        $options['data'] = $data;

        return CurlHelper::send($options);

    }
    public function reportBalance(Request $request){
        $data = ['tin'=>$request->inn,'lang'=>'uz','year'=>date('Y')-1,'period'=>$request->period];

        $options['header'] = ['Content-Type:application/x-www-form-urlencoded'];
        $options['method'] = 'POST';
        $options['url'] = 'https://ws.soliqservis.uz/gnk/data/buxbalans';
        $options['basic'] = true;
        $options['login'] = $this->login;
        $options['password'] = $this->password;
        $options['data'] = $data;

        return CurlHelper::send($options);

    }
    public function reportFinance(Request $request){
        $data = ['tin'=>$request->inn,'lang'=>'uz','year'=>date('Y')-1,'period'=>$request->period];

        $options['header'] = ['Content-Type:application/x-www-form-urlencoded'];
        $options['method'] = 'POST';
        $options['url'] = 'https://ws.soliqservis.uz/gnk/data/finreport';
        $options['basic'] = true;
        $options['login'] = $this->login;
        $options['password'] = $this->password;
        $options['data'] = $data;

        return CurlHelper::send($options);

    }
    public function reportNds(Request $request){
        $data = ['tin'=>$request->inn,'lang'=>'uz','year'=>date('Y')-1,'period'=>$request->period];

        $options['header'] = ['Content-Type:application/x-www-form-urlencoded'];
        $options['method'] = 'POST';
        $options['url'] = 'https://ws.soliqservis.uz/gnk/data/nds';
        $options['basic'] = true;
        $options['login'] = $this->login;
        $options['password'] = $this->password;
        $options['data'] = $data;

        return CurlHelper::send($options);

    }
    public function reportEnp(Request $request){
        $data = ['tin'=>$request->inn,'lang'=>'uz','year'=>date('Y')-1,'period'=>$request->period];

        $options['header'] = ['Content-Type:application/x-www-form-urlencoded'];
        $options['method'] = 'POST';
        $options['url'] = 'https://ws.soliqservis.uz/gnk/data/enp';
        $options['basic'] = true;
        $options['login'] = $this->login;
        $options['password'] = $this->password;
        $options['data'] = $data;

        return CurlHelper::send($options);

    }


}
