<?php

namespace App\Services\KATM;

use App\Classes\CURL\Katm\Accounting\KatmAccountingBalance;
use App\Classes\CURL\Katm\Accounting\KatmAccountingLoanAgreement;
use App\Classes\CURL\Katm\Accounting\KatmAccountingPayments;
use App\Classes\CURL\Katm\Accounting\KatmAccountingRefuse;
use App\Classes\CURL\Katm\Accounting\KatmAccountingRepaymentSchedule;
use App\Classes\CURL\Katm\Accounting\KatmAccountingStatus;
use App\Classes\CURL\Katm\Accounting\KatmLoanRegistration;
use App\Classes\CURL\Katm\MFO\KatmMfoRequestReport;
use App\Facades\KATM\CollectDataToKatm;
use App\Helpers\EncryptHelper;
use App\Models\AccountingEntry;
use App\Models\BuyerAddress;
use App\Models\Contract;
use App\Models\KatmClaim;
use App\Models\KatmReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MakeReportToKatmService
{

    private $logChannel;

    public function __construct()
    {
        $this->logChannel = Log::channel('katm_report');
    }

    private function infoLog(string $info, array $data = []): void
    {
        if (config('app.env') === 'prod'
            || config('app.env') === 'production') {
            return;
        }
        $this->logChannel->info($info, $data);
    }

    private function findReport(
        Contract $contract,
        string   $reportNumber,
        string   $reportType
    ): ?KatmReport
    {
        return $contract->katmReport()
            ->where([
                'report_number' => $reportNumber,
                'report_type' => $reportType,
            ])->first();
    }

    private function createReport(
        Contract $contract,
        string   $reportNumber,
        string   $reportType,
        string   $body,
        string   $date
    ): KatmReport
    {
        $hash = md5($date . Str::length($body));
        $order = $contract->katmReport()->count() + 1;
        return $contract->katmReport()->create([
            'report_number' => $reportNumber,
            'report_type' => $reportType,
            'body' => $body,
            'hash' => $hash,
            'order' => $order,
        ]);
    }

    private function cutAddress(string $address, int $maxSymbols): string
    {
        $addressArr = explode(',', $address);
        while (strlen(implode(",", $addressArr)) > $maxSymbols) {
            array_shift($addressArr);
        }
        return trim(implode(",", $addressArr));
    }

    public function generatePaymentType($date): string
    {
        return KatmReport::TYPE_PAYMENT . '_' . Carbon::parse($date)->format('Ymd');
    }

    /**
     * @throws \Exception
     */
    public function report001(Contract $contract, $reportType = KatmReport::TYPE_PRE_REGISTRATION): KatmReport
    {
        $katmReport = $this->findReport(
            $contract,
            KatmReport::NUMBER_LOAN_REG,
            $reportType
        );
        if ($katmReport) {
            return $katmReport;
        }

        $buyer = $contract->buyer;
        $personals = $buyer->personals;
        $buyerAddress = $buyer->addresses
            ->where('type', BuyerAddress::TYPE_REGISTRATION)
            ->first();

        $passport = EncryptHelper::decryptData($personals->passport_number);
        $passportSeries = \Str::substr($passport, 0, 2);
        $passportNumber = \Str::substr($passport, 2, 7);

        $claimID = KatmClaim::getClaimID($contract);

        // @todo Удалить лишний функционал (Выборка проводок)
        if (!$contract->accountingEntries()
            ->where('destination_code', AccountingEntry::CODE_1007)
            ->exists()) {
//            $this->infoLog("[ID контракта: $contract->id]: Отчёт 001. Не найдены проводки для олучения даты");
            throw new \Exception("[ID контракта: $contract->id]: Отчёт 001. Не найдены проводки для олучения даты");
        }

        // @todo Удалить лишний функционал (Выборка проводок)
        $accEntries = $contract->accountingEntries()
            ->where('destination_code', AccountingEntry::CODE_1007)
            ->take(1)
            ->get();

        // 001
        $this->infoLog("[ID контракта: $contract->id]: Отчёт 001");

        $requestToLoanRegistration = new KatmLoanRegistration(
            $claimID,
            $contract->id,
            $accEntries->first()->created_at,
            $accEntries->first()->created_at,
            $buyer->inn,
            $personals->passport_type,
            $passportSeries,
            $passportNumber,
            EncryptHelper::decryptData($personals->passport_date_issue),
            $buyer->gender,
            CollectDataToKatm::getSettings()->client_type,
            $buyer->birth_date,
            $buyer->nibbd,
            $buyer->name,
            $buyer->surname,
            $buyer->patronymic,
            Str::padLeft($buyer->region, 2, "0"),
            Str::padLeft($buyer->local_region, 3, "0"),
            $this->cutAddress($buyerAddress->address, 100),
            $buyer->getRawOriginal('phone'),
            EncryptHelper::decryptData($personals->pinfl)
        );

        return $this->createReport(
            $contract,
            KatmReport::NUMBER_LOAN_REG,
            $reportType,
            $requestToLoanRegistration->getRequestText(),
            Carbon::parse($contract->confirmed_at)->format('Y-m-d')
        );
    }

    public function reportStart(Contract $contract, $reportType = KatmReport::TYPE_PRE_REGISTRATION): KatmReport
    {
        $katmReport = $this->findReport(
            $contract,
            KatmReport::NUMBER_START,
            $reportType
        );
        if ($katmReport) {
            return $katmReport;
        }

        // START
        $claimID = $contract->katmClaim->claim;
        $this->infoLog("[ID контракта: $contract->id]: Отчёт START");
        $katmRequestReport = new KatmMfoRequestReport($claimID);

        return $this->createReport(
            $contract,
            KatmReport::NUMBER_START,
            $reportType,
            $katmRequestReport->getRequestText(),
            Carbon::parse($contract->confirmed_at)->format('Y-m-d')
        );
    }

    public function report003(Contract $contract, $reportType = KatmReport::TYPE_CANCEL): void
    {
        $katmReport = $this->findReport(
            $contract,
            KatmReport::NUMBER_REFUSE,
            $reportType
        );
        if ($katmReport) {
            return;
        }

        $claimID = $contract->katmClaim->claim;
        $number = Str::substr('0000000000' . $contract->id, 0, 10);
        $reason = CollectDataToKatm::getSettings()->reason_early_termination;
        $reasonDescription = CollectDataToKatm::getSettings()->disclaimer_note;

        // 003
        $this->infoLog("[ID контракта: $contract->id]: Отчёт 003");
        $requestToRefuse = new KatmAccountingRefuse(
            $claimID,
            $contract->canceled_at,
            $number,
            $reason,
            $reasonDescription
        );
        $this->createReport(
            $contract,
            KatmReport::NUMBER_REFUSE,
            $reportType,
            $requestToRefuse->getRequestText(),
            Carbon::parse($contract->canceled_at)->format('Y-m-d')
        );

    }

    public function report004(Contract $contract, $date, $reportType = KatmReport::TYPE_REGISTRATION): void
    {
        $katmReport = $this->findReport(
            $contract,
            KatmReport::NUMBER_LOAN_AGREEMENT,
            $reportType
        );
        if ($katmReport) {
            return;
        }

        $lastSchedule = $contract->schedule->last();
        $buyer = $contract->buyer;
        $claimID = $contract->katmClaim->claim;
        $firstDate = Carbon::parse($contract->getRawOriginal('confirmed_at'));
        // 004
        $this->infoLog("[ID контракта: $contract->id]: Отчёт 004");
        $requestToLoanAgreement = new KatmAccountingLoanAgreement(
            $claimID,
            $contract->id,
            $buyer->inn,
            $buyer->nibbd,
            CollectDataToKatm::getSettings()->loan_type_code,
            CollectDataToKatm::getSettings()->credit_object_code,
            $firstDate,
            $lastSchedule->payment_date,
            $contract->total,
            CollectDataToKatm::getSettings()->currency_code_uzs
        );
        $this->createReport(
            $contract,
            KatmReport::NUMBER_LOAN_AGREEMENT,
            $reportType,
            $requestToLoanAgreement->getRequestText(),
            Carbon::parse($date)->format('Y-m-d')
        );
    }

    public function report005(Contract $contract, $date, $reportType = KatmReport::TYPE_REGISTRATION): void
    {
        $katmReport = $this->findReport(
            $contract,
            KatmReport::NUMBER_SCHEDULES,
            $reportType
        );
        if ($katmReport) {
            return;
        }

        $claimID = $contract->katmClaim->claim;
        $buyer = $contract->buyer;
        $schedules = $contract->schedule;
        // 005
        $this->infoLog("[ID контракта: $contract->id]: Отчёт 005");
        $requestToRegSchedule = new KatmAccountingRepaymentSchedule(
            $claimID,
            $contract->id,
            $buyer->nibbd
        );
        foreach ($schedules as $key => $schedule) {
            $requestToRegSchedule->addSchedule(
                Carbon::parse($schedule->getRawOriginal('payment_date')),
                0,
                CollectDataToKatm::getSettings()->currency_code_uzs,
                $schedule->total
            );
        }

        $this->createReport(
            $contract,
            KatmReport::NUMBER_SCHEDULES,
            $reportType,
            $requestToRegSchedule->getRequestText(),
            Carbon::parse($date)->format('Y-m-d')
        );
    }

    public function report015(Contract $contract, $date, bool $allAccounts = false, $reportType = KatmReport::TYPE_PAYMENT): void
    {
        if ($reportType === KatmReport::TYPE_PAYMENT) {
            $reportType = $this->generatePaymentType($date);
        }

        $katmReport = $this->findReport(
            $contract,
            KatmReport::NUMBER_BALANCES,
            $reportType
        );
        if ($katmReport) {
            return;
        }

        // 015
        $this->infoLog("[ID контракта: $contract->id]: Отчёт 015");
        $requestToSetBalance = new KatmAccountingBalance(
            $contract->id
        );

        $this->infoLog("[ID контракта: $contract->id]: Сбор балансов счетов");
        $entries = CollectDataToKatm::collectAccountsBalances($contract, $date, $allAccounts);

        foreach ($entries as $entry) {
            $requestToSetBalance->addRepayment(
                $entry['account'],
                $entry['date'],
                $entry['startBalance'],
                $entry['debit'],
                $entry['credit'],
                $entry['endBalance'],
            );
        }

        $this->createReport(
            $contract,
            KatmReport::NUMBER_BALANCES,
            $reportType,
            $requestToSetBalance->getRequestText(),
            Carbon::parse($date)->format('Y-m-d')
        );
    }

    public function report016(Contract $contract, $date, $reportType = KatmReport::TYPE_PAYMENT): void
    {
        if ($reportType === KatmReport::TYPE_PAYMENT) {
            $reportType = $this->generatePaymentType($date);
        }

        $katmReport = $this->findReport(
            $contract,
            KatmReport::NUMBER_PAYMENTS,
            $reportType
        );
        if ($katmReport) {
            return;
        }

        // 016
        $this->infoLog("[ID контракта: $contract->id]: Отчёт 016");
        $requestToSendPayments = new KatmAccountingPayments(
            $contract->id,
            CollectDataToKatm::getSettings()->contract_type_code
        );

        $this->infoLog("[ID контракта: $contract->id]: Сбор оплат");
        $payments = CollectDataToKatm::collectPayments($contract, $date);

        foreach ($payments as $payment) {
            $requestToSendPayments->addPayment(
                $payment['accountA'],
                $payment['accountB'],
                $payment['branchA'],
                $payment['branchB'],
                $payment['coaA'],
                $payment['coaB'],
                $payment['currency'],
                $payment['destination'],
                $payment['docDate'],
                $payment['docNum'],
                $payment['docType'],
                $payment['nameA'],
                $payment['nameB'],
                $payment['payType'],
                $payment['paymentId'],
                $payment['purpose'],
                $payment['summa']
            );
        }

        $this->createReport(
            $contract,
            KatmReport::NUMBER_PAYMENTS,
            $reportType,
            $requestToSendPayments->getRequestText(),
            Carbon::parse($date)->format('Y-m-d')
        );
    }

    public function report018(Contract $contract, $date, $reportType = KatmReport::TYPE_COMPLETE): void
    {
        $katmReport = $this->findReport(
            $contract,
            KatmReport::NUMBER_ACCOUNTS_STATUSES,
            $reportType
        );
        if ($katmReport) {
            return;
        }

        // 018
        $this->infoLog("[ID контракта: $contract->id]: Отчёт 018");
        $requestToChangeStatus = new KatmAccountingStatus(
            $contract->id,
            CollectDataToKatm::getSettings()->contract_type_code
        );

        $this->infoLog("[ID контракта: $contract->id]: Сбор  счетов");
        $accounts = CollectDataToKatm::collectAccounts($contract);

        foreach ($accounts as $account) {
            $requestToChangeStatus->addAccountStatus(
                $account['date'],
                $account['account'],
                $account['coa'],
                $account['dateOpen'],
                $account['dateClose']
            );
        }

        $this->createReport(
            $contract,
            KatmReport::NUMBER_ACCOUNTS_STATUSES,
            $reportType,
            $requestToChangeStatus->getRequestText(),
            Carbon::parse($date)->format('Y-m-d')
        );
    }

}
