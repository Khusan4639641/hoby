<?php

namespace App\Services;

use App\Models\Buyer;
use App\Models\Contract;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class Monitoring
{

    static private function getReplenishments()
    {
        return '((payments.type = \'user\' AND payments.payment_system IN(' . self::getReplenishmentTypes() . ')) OR (payments.type = \'user_auto\' AND payments.payment_system IN(' . self::getOldReplenishmentTypes() . ')))';
    }

    static private function getDebit()
    {
        return '((payments.type = \'auto\' AND payments.payment_system IN(' . self::getFromOutDebitTypes() . ')) OR (payments.type = \'user_auto\' AND payments.payment_system IN(' . self::getFromAccountDebitTypes() . ')) OR (payments.type = \'user_auto\' AND payments.payment_system IN(' . self::getOldFromAccountDebitTypes() . ')))';
    }

    static private function getBonuses()
    {
        return '(payments.payment_system = \'Paycoin\' AND payments.status = 1)';
    }

    static private function getAutoDebit()
    {
        return '(payments.type = \'auto\' AND payments.payment_system IN(' . self::getAutoDebitTypes() . '))';
    }

    static private function getAutoDebitFromAccount()
    {
        return '(payments.type = \'auto\' AND payments.payment_system IN(' . self::getAutoDebitFromAccountTypes() . '))';
    }

    static private function getAutoDebitFromCards()
    {
        return '(payments.type = \'auto\' AND payments.payment_system IN(' . self::getAutoDebitFromCardsTypes() . '))';
    }

    static private function getAutoDebitFromPNFL()
    {
        return '(payments.type = \'auto\' AND payments.payment_system IN(' . self::getAutoDebitFromPNFLTypes() . '))';
    }

    static private function getManualDebit()
    {
        return '((payments.type = \'user_auto\' AND payments.payment_system IN(' . self::getFromAccountDebitTypes() . ')) OR (payments.type = \'user_auto\' AND payments.payment_system IN(' . self::getOldFromAccountDebitTypes() . ')))';
    }

    static private function getReplenishmentsByPaymentSystems()
    {
        return '(users.id AND payments.type = \'user\' AND payments.payment_system IN (' . self::getPaymentSystemsReplenishmentTypes() . '))';
    }

    static private function getAutoDebitFromAccountTypes()
    {
        return implode(',', [
            '\'ACCOUNT\'',
        ]);
    }

    static private function getAutoDebitFromPNFLTypes()
    {
        return implode(',', [
            '\'PNFL\'',
        ]);
    }

    static private function getAutoDebitFromCardsTypes()
    {
        return implode(',', [
            '\'UZCARD\'',
            '\'HUMO\'',
        ]);
    }

    static private function getAutoDebitTypes()
    {
        return self::getFromOutDebitTypes();
    }

    static private function getFromOutDebitTypes()
    {
//        type: auto
        return implode(',', [
            '\'UZCARD\'',
            '\'HUMO\'',
            '\'ACCOUNT\'',
            '\'PNFL\'',
        ]);
    }

    static private function getFromAccountDebitTypes()
    {
//        type: user_auto
        return implode(',', [
            '\'ACCOUNT\'',
        ]);
    }

    static private function getOldFromAccountDebitTypes()
    {
//        type: user_auto
        return implode(',', [
            '\'UZCARD\'',
            '\'HUMO\'',
        ]);
    }

    static private function getReplenishmentTypes()
    {
//        type: user
        return implode(',', [
                '\'UZCARD\'',
                '\'HUMO\'',
                '\'Autopay\'',
            ]) . ', ' . self::getPaymentSystemsReplenishmentTypes();
    }

    static private function getPaymentSystemsReplenishmentTypes()
    {
//        type: user
        return implode(',', [
            '\'MYUZCARD\'',
            '\'OCLICK\'',
            '\'PAYME\'',
            '\'PAYNET\'',
            '\'UPAY\'',
            '\'BANK\'',
            '\'APELSIN\'',
        ]);
    }

    static private function getOldReplenishmentTypes()
    {
//        type: user_auto
        return implode(',', [
            '\'UZCARD\'',
            '\'HUMO\'',
        ]);
    }

    public function getAccountsCollection()
    {
        return DB::query()->fromSub(function ($query) {
            $query->from('payments')
                ->selectRaw("payments.user_id AS id")
                ->selectRaw('SUM(IF (' . self::getReplenishments() . ', payments.amount, 0)) -
                SUM(IF (' . self::getDebit() . ', payments.amount, 0)) +
                SUM(IF (' . self::getAutoDebitFromPNFL() . ', payments.amount, 0)) +
                SUM(IF (' . self::getAutoDebitFromCards() . ', payments.amount, 0)) -
                (SELECT SUM(contracts.deposit) FROM contracts WHERE contracts.status IN(1) AND contracts.user_id = payments.user_id) AS different')
                ->selectRaw('(SELECT COUNT(*) FROM contracts WHERE contracts.user_id = payments.user_id AND contracts.status IN (1, 3, 4, 9)) AS count')
                ->whereRaw('status = 1')
                ->groupBy('user_id');
        }, 't')
            ->selectRaw("t.id, t.different, t.count")
            ->selectRaw("CONCAT(users.name, ' ', users.surname, ' ', users.patronymic) AS user")
            ->selectRaw("buyer_settings.personal_account AS account")
            ->leftJoin('users', 'users.id', '=', 't.id')
            ->leftJoin('buyer_settings', 'buyer_settings.user_id', '=', 't.id')
            ->where('different', '<', '-1');
    }

    public function getBonusAccountsCollection()
    {
        return DB::query()->fromSub(function ($query) {
            $query->from('payments')
                ->selectRaw("payments.user_id AS id")
                ->selectRaw('SUM(IF (payments.type IN(\'fill\', \'refund\'), payments.amount, 0)) AS bonus')
                ->selectRaw('SUM(IF (payments.type = \'upay\', payments.amount, 0)) AS debit')
                ->whereRaw('status = 1')
                ->whereRaw('payments.payment_system = \'Paycoin\'')
                ->groupBy('user_id');
        }, 't')
            ->selectRaw("t.id, t.bonus, t.debit")
            ->selectRaw("CONCAT(users.name, ' ', users.surname, ' ', users.patronymic) AS user")
            ->selectRaw("t.bonus + t.debit AS different")
            ->selectRaw("buyer_settings.zcoin AS account")
            ->leftJoin('users', 'users.id', '=', 't.id')
            ->leftJoin('buyer_settings', 'buyer_settings.user_id', '=', 't.id')
            ->whereRaw('(buyer_settings.zcoin - (t.bonus + t.debit)) < -1 OR (buyer_settings.zcoin - (t.bonus + t.debit)) > 1');
    }

    public function getContractsCollection()
    {
        return DB::query()->fromSub(function ($query) {
            $query->from('contracts')
                ->selectRaw("contracts.id, contracts.total - contracts.balance AS contracts_sum, contracts.user_id")
                ->selectRaw("(SELECT CONCAT(users.name, ' ', users.surname, ' ', users.patronymic) FROM users WHERE users.id = contracts.user_id) AS user_name")
                ->selectRaw("(SELECT SUM(contract_payments_schedule.total) - SUM(contract_payments_schedule.balance) FROM contract_payments_schedule WHERE contract_payments_schedule.contract_id = contracts.id) AS schedules_sum")
                ->selectRaw("(
                SELECT SUM(IF(" . self::getDebit() . ", payments.amount, 0))
                FROM payments
                WHERE contracts.id = payments.contract_id
                AND payments.status = 1
                ) AS payments_sum")
                ->whereRaw('contracts.status = 1')
                ->groupByRaw('contracts.id, contracts.total, contracts.balance, contracts.user_id, contracts.deposit');
        }, 't')
            ->whereRaw('t.payments_sum != t.contracts_sum OR t.payments_sum != t.schedules_sum OR t.contracts_sum != t.schedules_sum');
    }

    public function getUser($id)
    {
        return Buyer::query()
            ->select()
            ->selectRaw('(SELECT COUNT(contracts.id) FROM contracts WHERE contracts.user_id = users.id) AS contracts_count')
            ->selectRaw('(SELECT COUNT(contracts.id) FROM contracts WHERE contracts.user_id = users.id AND contracts.status IN (1, 3, 4, 9)) AS active_contracts_count')
            ->selectRaw('(SELECT SUM(contracts.deposit) FROM contracts WHERE contracts.status IN(1) AND contracts.user_id = users.id) AS deposit_sum')
            ->selectRaw('(SELECT SUM(buyer_settings.personal_account) FROM buyer_settings WHERE buyer_settings.user_id = users.id) AS personal_account')
            ->selectRaw('(SELECT SUM(buyer_settings.zcoin) FROM buyer_settings WHERE buyer_settings.user_id = users.id) AS bonuses')
            ->selectRaw('(SELECT SUM(buyer_settings.`limit`) FROM buyer_settings WHERE buyer_settings.user_id = users.id) AS `limit`')
            ->selectRaw('(SELECT SUM(buyer_settings.balance) FROM buyer_settings WHERE buyer_settings.user_id = users.id) AS limit_balance')
            ->selectRaw('(SELECT SUM(IF (' . self::getDebit() . ', payments.amount, 0)) FROM payments WHERE payments.user_id = users.id AND payments.status = 1) AS payments')
            ->selectRaw('(SELECT SUM(payments.amount) FROM payments WHERE payments.user_id = users.id AND payments.payment_system = \'Paycoin\' AND payments.status = 1) AS bonuses_amount')
            ->selectRaw('(SELECT SUM(payments.amount) FROM payments WHERE payments.user_id = users.id AND ' . self::getReplenishments() . ' AND payments.status = 1) AS replenishments')
            ->selectRaw('(SELECT SUM(payments.amount) FROM payments WHERE payments.user_id = users.id AND ' . self::getReplenishmentsByPaymentSystems() . ' AND payments.status = 1) AS payments_from_payment_system')
            ->selectRaw('(SELECT SUM(payments.amount) FROM payments WHERE payments.user_id = users.id AND payments.type = \'user\' AND (payments.payment_system = \'HUMO\' OR payments.payment_system = \'UZCARD\') AND payments.status = 1) AS payments_from_card')
            ->selectRaw('(SELECT SUM(payments.amount) FROM payments WHERE payments.user_id = users.id AND payments.type = \'user\' AND payments.payment_system = \'Autopay\' AND payments.status = 1) AS payments_from_autopay')
            ->selectRaw('(SELECT SUM(payments.amount) FROM payments WHERE payments.user_id = users.id AND ' . self::getManualDebit() . ' AND payments.status = 1) AS manual_debit')
            ->selectRaw('(SELECT SUM(payments.amount) FROM payments WHERE payments.user_id = users.id AND ' . self::getAutoDebit() . ' AND payments.status = 1) AS auto_debit')
            ->selectRaw('(SELECT SUM(payments.amount) FROM payments WHERE payments.user_id = users.id AND ' . self::getAutoDebitFromCards() . ' AND payments.status = 1) AS auto_debit_from_cards')
            ->selectRaw('(SELECT SUM(payments.amount) FROM payments WHERE payments.user_id = users.id AND ' . self::getAutoDebitFromAccount() . ' AND payments.status = 1) AS auto_debit_from_account')
            ->selectRaw('(SELECT SUM(payments.amount) FROM payments WHERE payments.user_id = users.id AND ' . self::getAutoDebitFromPNFL() . ' AND payments.status = 1) AS auto_debit_from_pnfl')
            ->selectRaw('(SELECT SUM(IF (' . self::getReplenishments() . ', payments.amount, 0)) - SUM(IF (' . self::getDebit() . ', payments.amount, 0)) + SUM(IF (' . self::getAutoDebitFromCards() . ', payments.amount, 0)) + SUM(IF (' . self::getAutoDebitFromPNFL() . ', payments.amount, 0)) FROM payments WHERE payments.user_id = users.id AND payments.status = 1) -
            (SELECT SUM(buyer_settings.personal_account) FROM buyer_settings WHERE buyer_settings.user_id = users.id) - (SELECT SUM(contracts.deposit) FROM contracts WHERE contracts.status IN(1) AND contracts.user_id = users.id) AS different')
            ->find($id);
    }

    public function paymentsBonusesByUser($id)
    {
        return Payment::whereRaw(self::getBonuses())
            ->where('user_id', $id)
            ->orderBy('created_at')
            ->get();
    }

    public function paymentsReplenishmentsByUser($id)
    {
        return Payment::where('status', 1)
            ->whereRaw(self::getReplenishments())
            ->where('user_id', $id)
            ->get();
    }

    public function paymentsDebitByUser($id)
    {
        return Payment::where('status', 1)
            ->whereRaw(self::getDebit())
            ->where('user_id', $id)
            ->get();
    }

    public function contractsByUser($id)
    {
        return Contract::where('user_id', $id)
            ->select()
            ->selectRaw('(SELECT SUM(IF (' . self::getDebit() . ', payments.amount, 0)) FROM payments WHERE payments.contract_id = contracts.id AND payments.status = 1) AS payments')
            ->with('schedule')
            ->get();
    }

    public function getExpiredDays(int $contractStatus = null)
    {
        $contract = Contract::query()
            ->selectRaw('SUM(expired_days) AS expired_days');
        if ($contractStatus != null) {
            $contract->where('status', $contractStatus);
        }
        $contract = $contract->first();
        return $contract->expired_days;
    }

    public function getContractsCount(int $contractStatus = null)
    {
        $contract = Contract::query()
            ->selectRaw('COUNT(id) AS count');
        if ($contractStatus != null) {
            $contract->where('status', $contractStatus);
        }
        $contract = $contract->first();
        return $contract->count;
    }

}
