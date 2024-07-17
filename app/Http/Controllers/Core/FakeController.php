<?php

namespace App\Http\Controllers\Core;

use App\Exports\ExportTransactions;
use App\Http\Controllers\Controller;
use App\Http\Requests\FakeTransactionAddRequest;
use App\Http\Requests\FakeTransactionExportRequest;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\FakeTransactionDetails;
use App\Models\FakeTransactions;
use App\Models\GeneralCompany;
use App\Models\User;
use App\Models\Faq as Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Services\FakeTransaction;

class FakeController
{
    /**
     * @var Excel
     */
    protected $Types = [
        'identifing'    => "Удаленная идентификация и оценка платежеспособности потенциальных Клиентов на основе данных ГНК, КБ «КИАЦ», Платежных систем и НИББД",
        "datafinding"   => "Автоматизированное распознавание данных в идентифицирующих документах потенциальных Клиентов",
        "registrating"  => "Регистрация и последующее сопровождение договора рассрочки в системе биллинга"
    ];
    protected $Company = [];

    private $fakeTransactionService;
    /**
     * @var FakeTransactionExportRequest
     */
    private $fakeRequest;

    /**
     * @var Request
     */

    public function __construct() {
        foreach (GeneralCompany::all() as $company){
            $this->Company[$company->id] = $company->name_ru;
        }
        $this->fakeTransactionService = new FakeTransaction();
    }

    public function add(FakeTransactionAddRequest $fakeRequest) {
        $this->fakeRequest = $fakeRequest;
        $saveTransaction = [];
        $activeUsers = User::select('id')->whereBetween(
            'created_at',[$this->fakeRequest->firstDate , $this->fakeRequest->lastDate]
        )->where('status',4)->get()->toArray();
        $index = 0;
        if(count($activeUsers) === 0)
            $activeUsers = User::select('id')->get()->toArray();
        foreach ($this->fakeRequest->data as $transaction){
            $fakeTransactionId = FakeTransactions::create([
                'type'               => $transaction['type'],
                'date'               => $transaction["dates"],
                'general_company_id' => $transaction['companyId'],
                'amount'             => $transaction['amount'],
            ])->id;
            for ($i=0;$i<$transaction['amount'];$i++) {
                $saveTransaction[] = [
                    'type'               => $transaction['type'],
                    'date'               => $transaction['dates'],
                    'general_company_id' => $transaction['companyId'],
                    'fake_transaction_id'=> $fakeTransactionId,
                    'user_id'            =>$activeUsers[$index]['id'] ?? $activeUsers[rand(0,count($activeUsers)-1)]['id']
                ];
                $index++;
            }
        }
        $chunk_data = array_chunk($saveTransaction, 1000);
        if (isset($chunk_data) && !empty($chunk_data)) {
            foreach ($chunk_data as $chunk_data_val) {
                FakeTransactionDetails::insert($chunk_data_val);
            }
        }
        return true;
    }

    public function export(FakeTransactionExportRequest $fakeRequest){
        $this->fakeRequest = $fakeRequest;
        if($this->fakeRequest->details=='no')
            return Excel::download(new ExportTransactions($this->fakeRequest), 'ExportTransactions.xlsx');
        return $this->fakeTransactionService->getDetails($this->fakeRequest);
    }
}
