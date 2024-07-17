<?php

namespace App\Http\Controllers\Web\Panel;

use App\Facades\Monitoring;
use App\Helpers\ScoringCacheHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Core\CoreController;
use App\Models\Buyer;
use App\Models\CardLog;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class MonitoringController extends CoreController
{

    public function index()
    {
        return view('panel.monitoring.index');
    }

    public function deposits()
    {
        $deposits = Payment::query()
            ->selectRaw("(SELECT CONCAT(users.name, ' ', users.surname, ' ', users.patronymic) FROM users WHERE users.id = contracts.user_id) AS user_name")
            ->selectRaw('contracts.user_id, contracts.id AS contract_id, payments.amount AS payments_deposit, contracts.deposit AS contract_deposit')
            ->leftJoin('contracts', 'contracts.id', '=', 'payments.contract_id')
            ->where('payments.payment_system', '=', 'DEPOSIT')
            ->whereRaw('CAST(payments.amount AS VARCHAR(100)) != CAST(contracts.deposit AS VARCHAR(100))');
        $count = $deposits->count();
        $deposits = $deposits->paginate(10);
        return view('panel.monitoring.deposits', compact('deposits', 'count'));
    }

    public function accounts()
    {
        $users = Monitoring::getAccountsCollection();
        $count = $users->count();
        $users = $users->paginate(10);
        return view('panel.monitoring.accounts', compact('users', 'count'));
    }

    public function contracts()
    {
        $contracts = Monitoring::getContractsCollection();
        $count = $contracts->count();
        $contracts = $contracts->paginate(10);
        return view('panel.monitoring.contracts', compact('contracts', 'count'));
    }

    public function bonuses()
    {
        $users = Monitoring::getBonusAccountsCollection();
        $count = $users->count();
        $users = $users->paginate(10);
        return view('panel.monitoring.bonuses', compact('users', 'count'));
    }

    public function user($id)
    {
        $user = Monitoring::getUser($id);
        $payments = Payment::where('user_id', $id)->orderBy('created_at')->get();
        $contracts = Monitoring::contractsByUser($id);
        $paymentsBonuses = Monitoring::paymentsBonusesByUser($id);
        $paymentsReplenishments = Monitoring::paymentsReplenishmentsByUser($id);
        $paymentsDebit = Monitoring::paymentsDebitByUser($id);
        $paymentsByContracts = [];

        $notExistsDeposits = [];
        foreach ($contracts as $contract) {
            if ($contract->deposit > 0) {
                $notExistsDeposits[$contract->id] = $contract->deposit;
            }
        }
        foreach ($payments as $payment) {
            if ($payment->contract_id) {
                $paymentsByContracts[$payment->contract_id][] = $payment;
                foreach ($contracts as $contract) {
                    if ($contract->id == $payment->contract_id
                        && strtoupper($payment->payment_system) == 'DEPOSIT') {
                        unset($notExistsDeposits[$contract->id]);
                    }
                }
            }
        }
        $timelinePayments = $this->makeTimelinePayments($payments, $contracts);

        $timelineOnlyPayments = [];
        foreach ($timelinePayments as $payment) {
            if ($payment['payment']->payment_system != 'Paycoin') {
                $timelineOnlyPayments[] = $payment;
            }
        }
        $timelineOnlyBonuses = [];
        foreach ($timelinePayments as $payment) {
            if ($payment['payment']->payment_system == 'Paycoin') {
                $timelineOnlyBonuses[] = $payment;
            }
        }

        $totalContractDebit = $user->contracts->sum('total') - $user->contracts->sum('balance');
        $totalSchedulesDebit = $contracts->map(function ($item) {
            return $item->schedule->sum('total') - $item->schedule->sum('balance');
        })->sum();
        $totalPaymentsDebit = $user->payments;

        $isTotalHaveDifferent = ((string)($totalContractDebit * 100) != (string)($totalSchedulesDebit * 100))
            || ((string)($totalContractDebit * 100) != (string)($totalPaymentsDebit * 100))
            || ((string)($totalSchedulesDebit * 100) != (string)($totalPaymentsDebit * 100));

        return view('panel.monitoring.user', compact(
            'user',
            'payments',
            'contracts',
            'paymentsBonuses',
            'paymentsReplenishments',
            'paymentsDebit',
            'paymentsByContracts',
            'totalContractDebit',
            'totalSchedulesDebit',
            'totalPaymentsDebit',
            'isTotalHaveDifferent',
            'notExistsDeposits',
            'timelinePayments',
            'timelineOnlyPayments',
            'timelineOnlyBonuses',
        ));
    }

    public function userCards($id)
    {
        $user = Buyer::find($id);
        $keys = ScoringCacheHelper::keys($id);
        $redisCache = [];
        foreach ($keys as $key) {
            $redisCache[$key] = ScoringCacheHelper::exists($id, $key);
        }
        return view('panel.monitoring.cards', compact(
            'user',
            'redisCache',
        ));
    }

    public function cacheClear($id)
    {
        ScoringCacheHelper::remove($id);
        return redirect(localeRoute('panel.monitoring.cards', $id));
    }

    private function makeTimelinePayments($payments, $contracts)
    {
        $timelinePayments = [];
        $account = 0;
        $bonus = 0;
        $timelineContracts = [];
        foreach ($contracts as $contract) {
            $timelineContracts[$contract->id] = $contract->total;
        }
        foreach ($payments as $payment) {
            if ($payment->status == 1) {
                if ($payment->type == 'user'
                    && (
                        $payment->payment_system == 'UZCARD'
                        || $payment->payment_system == 'HUMO'
                        || $payment->payment_system == 'MYUZCARD'
                        || $payment->payment_system == 'OCLICK'
                        || $payment->payment_system == 'PAYME'
                        || $payment->payment_system == 'PAYNET'
                        || $payment->payment_system == 'UPAY'
                        || $payment->payment_system == 'BANK'
                        || $payment->payment_system == 'APELSIN'
                        || $payment->payment_system == 'Autopay'
                    )) {
                    $account += $payment->amount;
                } else if ($payment->type == 'auto'
                    && (
                        $payment->payment_system == 'UZCARD'
                        || $payment->payment_system == 'HUMO'
                        || $payment->payment_system == 'ACCOUNT'
                        || $payment->payment_system == 'PNFL')) {
                    if (isset($timelineContracts[$payment->contract_id])) {
                        $timelineContracts[$payment->contract_id] -= $payment->amount;
                    }
                    if ($payment->payment_system == 'ACCOUNT') {
                        $account -= $payment->amount;
                    }
                } else if ($payment->type == 'user_auto' && ($payment->payment_system == 'ACCOUNT' || $payment->payment_system == 'UZCARD' || $payment->payment_system == 'HUMO')) {
                    if ($payment->payment_system == 'ACCOUNT') {
                        $account -= $payment->amount;
                    }
                    if (isset($timelineContracts[$payment->contract_id])) {
                        $timelineContracts[$payment->contract_id] -= $payment->amount;
                    }
                } else if ($payment->payment_system == 'DEPOSIT') {
                    $account -= $payment->amount;
                } else if ($payment->payment_system == 'Paycoin') {
                    if ($payment->type == 'fill') {
                        $bonus += $payment->amount;
                    }
                    if ($payment->type == 'upay') {
                        $bonus += $payment->amount;
                    }
                    if ($payment->type == 'refund') {
                        $bonus += $payment->amount;
                    }
                }
                if ($payment->status == 1) {
                    $timelinePayments[] = [
                        'payment' => $payment,
                        'account' => $account,
                        'bonus' => $bonus,
                        'contracts' => $timelineContracts,
                    ];
                }
            }
        }
        return $timelinePayments;
    }

    static public function toJson($data)
    {
        $content = '';
        if ($data) {
            if (is_string($data)) {
                $jsonData = json_decode($data, true);
                if ($jsonData) {
                    $content = self::drawJsonItem($jsonData);
                }
            }
        }
        return $content;
    }

    static private function drawJsonItem($data)
    {
        $content = '';
        if (is_array($data)) {
            $content .= '<ul>';
            foreach ($data as $key => $item) {
                if (is_array($item)) {
                    $content .= '<li><label class="font-weight-bold d-inline">' . $key . ':</label> ' . self::drawJsonItem($item) . '</li>';
                } else {
                    $content .= '<li><label class="font-weight-bold d-inline">' . $key . ':</label> ' . $item . '</li>';
                }
            }
            $content .= '</ul>';
        } else {
            $content .= $data;
        }
        return $content;
    }

}
