<?php


namespace App\Services\API\Core;


use App\Http\Requests\Core\PartnerController\BlockRequest;
use App\Models\BlockingHistory;
use App\Models\BlockingReasons;
use App\Models\Buyer;
use App\Models\Company;
use App\Models\EdWallet;
use App\Models\MFOPayment;
use App\Models\User;
use DDZobov\PivotSoftDeletes\Model;
use Illuminate\Http\Request;

class MFOPaymentsHistoryService
{
    /**
     * @param int $user_id
     * @return array
     */

    public static function show(Request $request) {
        $bank = new EdWallet();
        $payment = MFOPayment::with('wallets');
        $result = [];

        if (is_array($request->date)) {
            $dateFrom = date('Y-m-d 00:00:00', strtotime($request->date[0]));
            $dateTo = date('Y-m-d 23:59:59', strtotime($request->date[1]));

            $bank->whereBetween('doc_date', [$dateFrom, $dateTo]);
            $payment->whereBetween('created_at', [$dateFrom, $dateTo])->where('type', 'payment');
        } else {
            $dateTo = date('Y-m-d 23:59:59', strtotime($request->date));
            $bank->where('doc_date', '<=', $dateTo);
            $payment->where('created_at', '<=', $dateTo)->where('type', 'payment');
        }

        if ($request->type == 'bank') {
            $result = $bank->paginate(10, ['*'], 'page', $request->page);
        } elseif ($request->type == 'payment') {
            $result = $payment->paginate(10, ['*'], 'page', $request->page);
        } elseif ($request->type == 'all') {
            $result['bank'] = $bank->sum('turnover_debit');
            $result['payment'] = $payment->sum('amount');
            $result['total'] = $bank->sum('turnover_debit') - $payment->sum('amount');
        }

        return $result;
    }
}
