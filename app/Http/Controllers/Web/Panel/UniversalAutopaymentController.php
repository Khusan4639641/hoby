<?php

namespace App\Http\Controllers\Web\Panel;

use App\Classes\Universal\Autopayment\Debtors;
use App\Facades\OldCrypt;
use App\Facades\UniversalAutoPayment;
use App\Helpers\EncryptHelper;
use App\Http\Controllers\Controller;
use App\Models\BuyerPersonal;
use App\Models\Universal\UniversalAutoPaymentsTransaction;
use App\Classes\Universal\Autopayment\DailyPayments;
use App\Models\Universal\UniversalDebtor;
use Illuminate\Support\Facades\Crypt;

class UniversalAutopaymentController extends Controller
{

    public function index()
    {

//        $daily = new DailyPayments(date('Y-m-d', strtotime("-1 days")));
//        $daily = $daily->execute()->response();
//        dd($daily['result']['data'][0]);


//        UniversalAutoPayment::getPayments();
//        UniversalAutoPayment::registerDebtors();


//        UniversalAutoPayment::createDebtors();
//        dd('success');

        $debtors = UniversalDebtor::orderBy('id', 'DESC')->limit(10)->get();
        $autopayments = UniversalAutoPaymentsTransaction::orderBy('id', 'DESC')->limit(10)->get();
        return view('panel.autopayment.index', compact('debtors', 'autopayments'));
    }

    public function transactions()
    {
        $autopayments = UniversalAutoPaymentsTransaction::paginate();
        return view('panel.autopayment.transactions', compact('autopayments'));
    }

    public function debtors()
    {
        $debtors = UniversalDebtor::paginate();
        return view('panel.autopayment.debtors', compact('debtors'));
    }

    public function debtorTransactions($id)
    {
        $debtor = UniversalDebtor::where('user_id', $id)->first();
        $autopayments = $debtor->autoPaymentsTransactions;
        return view('panel.autopayment.debtor-transactions', compact('debtor', 'autopayments'));
    }

}
