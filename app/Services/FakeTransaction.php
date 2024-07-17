<?php
namespace App\Services;

use App\Http\Requests\FakeTransactionExportRequest;
use App\Models\FakeTransactionDetails;
use App\Models\FakeTransactions;
use App\Models\GeneralCompany;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Http\Request;

class FakeTransaction
{
    protected  $Company;

    protected $Types = [
        'identifing'    => "Удаленная идентификация и оценка платежеспособности потенциальных  Клиентов на основе данных ГНК, КБ «КИАЦ», Платежных систем и НИББД",
        "datafinding"   => "Автоматизированное распознавание данных в идентифицирующих документах потенциальных Клиентов",
        "registrating"  => "Регистрация и последующее сопровождение договора рассрочки в системе биллинга"
    ];
    /**
     * @var Request
     */
    public  $result = [];

    public function __construct()
    {
        foreach (GeneralCompany::all() as $company) {
            $this->Company[$company->id] = $company->name_ru;
        }
    }

    public function dataForExcel(FakeTransactionExportRequest $request) {
        $useCompanies = [];
        $useDates = [];
        for ($i=0;$i<count($request->data);$i+=2) {
            $excelDate = date("F",strtotime($request->data[$i]))."-".date("Y",strtotime($request->data[$i]));
            $useDates[]=$excelDate;
            $transactions = FakeTransactions::where([
                ['date', '>=', $request->data[$i]],
                ['date', '<=', $request->data[$i+1]]
            ])->get();
            foreach ($transactions as $transaction) {
                $useCompanies[$transaction->general_company_id] = $this->Company[$transaction->general_company_id];

                if(!isset($this->result[$excelDate]
                    [$this->Company[$transaction->general_company_id]]
                    [$this->Types[$transaction->type]]))

                    $this->result[$excelDate][
                    $this->Company[$transaction->general_company_id]
                    ][
                    $this->Types[$transaction->type]
                    ] = 0;

                $this->result[$excelDate][
                $this->Company[$transaction->general_company_id]
                ][
                $this->Types[$transaction->type]
                ] += $transaction->amount;

            }
        }
        return ['val'=>$this->result,'companies'=>$useCompanies,'dates'=>$useDates,'types'=>$this->Types];
    }

    public function getDetails(FakeTransactionExportRequest $request) {
        $date = $request->data;
        usort($date, function ($a, $b) {return strtotime($a) - strtotime($b);});
        $details = FakeTransactionDetails::where([
            ['date','>=',$date[0]],
            ['date','<=',$date[count($date)-1]]
        ])->select(
            'id',
            'type',
            'date',
            'general_company_id',
            'user_id'
        )->get()->toArray();
        $f = fopen('php://memory', md5(microtime()));
        fseek($f, 0);
        fputs($f, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($f, ['ID','ТИП','Дата','Компания','ИД пользователя'], ";");
        foreach ($details as $transaction) {
            fputcsv($f, [
                $transaction['id'],
                $this->Types[$transaction['type']],
                " ".$transaction['date']." ",
                $this->Company[$transaction['general_company_id']],
                $transaction['user_id'],
            ], ";");
        }
        fseek($f, 0);
        header('Content-Disposition: attachment; filename="'."details.csv".'";');
        fpassthru($f);
        exit;
    }
}
