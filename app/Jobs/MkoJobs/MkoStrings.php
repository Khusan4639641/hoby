<?php
/** @noinspection NotOptimalIfConditionsInspection */

namespace App\Jobs\MkoJobs;

use App\Enums\MKOInfoCodesEnum;
use App\Helpers\EncryptHelper;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\{Carbon, Collection};
use Illuminate\Support\Facades\{DB, Log};

class MkoStrings
{
    private string    $percent       = '0.01';
    private string    $groupInfoCode = '13';
    private string    $mkoId;
    private string    $lineStart;
    private array     $request;
    private array     $dataArray     = [];
    private MkoFrom1C $from1C;
    public array      $sumByMask     = [];


    public function __construct(array $request)
    {
        $this->request   = $request;
        $this->mkoId     = $request['mko_id'];
        $this->lineStart = Carbon::parse($this->request['to'])->format("Ymd") . '#' . $request['company']['nko'] . '#';
        $this->from1C    = new MkoFrom1C(Carbon::parse($this->request['from']), Carbon::parse($this->request['to']));
    }


    /**
     * Получаем информацию для составления отчета, согласно его номеру
     *
     * @param string $code
     *
     * @return array|QueryBuilder|Collection
     * @throws HttpClientException
     */
    public function getDataByReportCode(string $code)
    {
        switch ($code) {
            case MKOInfoCodesEnum::IC001:
                return $this->get001();
                break;
            case MKOInfoCodesEnum::IC002:
                return $this->get002();
                break;
            case MKOInfoCodesEnum::IC003:
                return $this->get003();
                break;
            case MKOInfoCodesEnum::IC004:
                return $this->get004();
                break;
            case MKOInfoCodesEnum::IC005:
                return $this->get005();
                break;
            case MKOInfoCodesEnum::IC006:
                return $this->get006();
                break;
            case MKOInfoCodesEnum::IC007:
                return $this->get007();
                break;
            case MKOInfoCodesEnum::IC008:
                return $this->get008();
                break;
            default:
                throw new \RuntimeException('Unexpected value');
        }
    }

    /**
     * Заполняем файл отчета полученной информацией, согласно его номеру
     *
     * @param string $code
     * @param $data
     *
     * @return string
     */
    public function fillReportDataByCode(string $code, $data): string
    {
        switch ($code) {
            case MKOInfoCodesEnum::IC001:
                return $this->fill001($data);
                break;
            case MKOInfoCodesEnum::IC002:
                return $this->fill002($data);
                break;
            case MKOInfoCodesEnum::IC003:
                return $this->fill003($data);
                break;
            case MKOInfoCodesEnum::IC004:
                return $this->fill004($data);
                break;
            case MKOInfoCodesEnum::IC005:
                return $this->fill005($data);
                break;
            case MKOInfoCodesEnum::IC006:
                return $this->fill006($data);
                break;
            case MKOInfoCodesEnum::IC007:
                return $this->fill007($data);
                break;
            case MKOInfoCodesEnum::IC008:
                return $this->fill008($data);
                break;
            default:
                throw new \RuntimeException('Unexpected value');
        }
    }

    private function get001(): QueryBuilder
    {
        Log::channel('mko_to_bank_errors')->info('MKO-REPORT-GET 001 STARTED');
        $from   = Carbon::parse($this->request['from'])->subDay()->endOfDay()->format('Y-m-d H:i:s');
        $to     = Carbon::parse($this->request['to'])->addDay()->startOfDay()->format('Y-m-d H:i:s');
        $toLast = Carbon::parse($this->request['from'])->format('Y-m-d H:i:s');

        $this->from1C->collect001();
        $expression = "ac.id, ac.contract_id, ac.number, 3 as mark,
               COALESCE(cast(100 * (select sum(aec.amount)
                         from accounting_entries_cbu as aec
                         where (operation_date > '$from' AND operation_date < '$to')
                         and ac.number = aec.debit_account and aec.status = 1
                         and aec.destination_code != '0000') as INT), 0)  as debit_account_sum,
               COALESCE(cast(100 * (select sum(amount)
                         from accounting_entries_cbu as aec
                         where (operation_date > '$from' AND operation_date < '$to')
                         and ac.number = aec.credit_account and aec.status = 1
                         and aec.destination_code != '0000') as INT), 0) as credit_account_sum,
               0 as balance_history_from,
               0 as balance_history_to,
               COALESCE(cast(100 * (select sum(aec.amount)
                         from accounting_entries_cbu as aec
                         where (operation_date > '2023-03-01' AND operation_date < '$toLast')
                         and ac.number = aec.debit_account and aec.status = 1
                         and aec.destination_code != '0000') as INT), 0)  as last_debit_account_sum,
               COALESCE(cast(100 * (select sum(amount)
                         from accounting_entries_cbu as aec
                         where (operation_date > '2023-03-01' AND operation_date < '$toLast')
                         and ac.number = aec.credit_account and aec.status = 1
                         and aec.destination_code != '0000') as INT), 0) as last_credit_account_sum,
               ap.contract_bind,
               0 as is_from_1c_api,
               ac.mask";

        return DB::table('accounts_cbu as ac')->selectRaw($expression)
            ->whereRaw("ac.contract_id IN (SELECT DISTINCT(aec.contract_id)
                FROM accounting_entries_cbu as aec
                where aec.operation_date > '$from' and aec.operation_date < '$to'
                  and aec.status = 1)
                or ac.contract_id in (SELECT DISTINCT c3.id
                            from contracts as c3
                            where (c3.status in (1,3,4) and c3.confirmed_at < '$to')
                            or (c3.status in (5, 9) and EXISTS(SELECT *
                                                              FROM account_balance_histories_cbu as abhc
                                                              WHERE abhc.operation_date > '$from' and abhc.operation_date < '$to'
                                                                AND abhc.balance != 0
                                                                AND abhc.account_id in
                                                                    (select ac2.id from accounts_cbu as ac2 where ac2.mask in ('12401', '12405') and ac2.contract_id = ac.contract_id)))
                            and c3.general_company_id = $this->mkoId)")
            ->join('account_parameters as ap', 'ap.mask', '=', 'ac.mask')
            ->union(DB::table(MkoFrom1C::$accountsTempTable)->selectRaw('*, SUBSTR(`number`, 1, 5) as mask'))
            ->orderBy('number');
    }

    private function fill001(QueryBuilder $data): string
    {
        Log::channel('mko_to_bank_errors')->info('MKO-REPORT-FILL 001 STARTED');
        $this->dataArray    = [];
        $this->dataArray[0] = '0#' . $this->lineStart . $this->groupInfoCode . '#' . MKOInfoCodesEnum::IC001 . '#';
        $count              = 0;
        $k                  = 100000;
        $total              = $data->count();
        $data->chunk($k, function ($items) use (&$count, &$total, &$k) {
            foreach ($items as $account) {
                $lineEnd = $this->lineStart . 'RPLC#';
                $lineEnd .= trim($account->number . '#');
                $lineEnd .= trim($account->mark) . '#';
                $lineEnd .= trim($account->contract_id) . '#';
                if (!isset($this->sumByMask[$account->mask])) {
                    $this->sumByMask[$account->mask]['in_sum']  = 0;
                    $this->sumByMask[$account->mask]['out_sum'] = 0;
                }
                if ($account->is_from_1c_api) {
                    $fromBh                                     = $account->balance_history_from;
                    $toBh                                       = $account->balance_history_to;
                    $this->sumByMask[$account->mask]['in_sum']  += abs($fromBh);
                    $lineEnd                                    .= trim($fromBh) . '#';
                    $lineEnd                                    .= trim($account->debit_account_sum) . '#';
                    $lineEnd                                    .= trim($account->credit_account_sum) . '#';
                    $this->sumByMask[$account->mask]['out_sum'] += abs($toBh);
                    $lineEnd                                    .= trim($toBh) . '#';
                } else {
                    $fromBh                                     = $account->last_credit_account_sum - $account->last_debit_account_sum;
                    $this->sumByMask[$account->mask]['in_sum']  += abs($fromBh);
                    $lineEnd                                    .= trim($fromBh) . '#';
                    $lineEnd                                    .= trim($account->debit_account_sum) . '#';
                    $lineEnd                                    .= trim($account->credit_account_sum) . '#';
                    $toBh                                       = $fromBh - $account->debit_account_sum + $account->credit_account_sum;
                    $this->sumByMask[$account->mask]['out_sum'] += abs($toBh);
                    $lineEnd                                    .= trim($toBh) . '#';
                }
                if ($fromBh === 0 && $toBh === 0 && $account->debit_account_sum === 0 && $account->credit_account_sum === 0) {
                    continue;
                }

                $countLine         = str_replace('RPLC', '', $lineEnd);
                $symbolsCount      = $this->countAsciiSymbols($countLine);
                $this->dataArray[] = str_replace('RPLC', $symbolsCount, $lineEnd) . "\r";
            }
            $count += $k;
            if ($count > $total) {
                $count = $total;
            }
            Log::channel('mko_to_bank_errors')->info($count . ' accounts of ' . $total . ' processed');
        });
        $cnt = count($this->dataArray);
        Log::channel('mko_to_bank_errors')->info('Non empty accounts count: ' . $cnt);
        $arrayCount                       = $cnt;
        $this->dataArray[0]               .= $arrayCount . "#\r";
        $this->dataArray[$arrayCount - 1] .= "\n";

        Log::channel('mko_to_bank_errors')->info('MKO-REPORT-FILL 001 ENDED');

        return implode(PHP_EOL, $this->dataArray);
    }

    /**
     * @throws HttpClientException
     */
    private function get002(): array
    {
        Log::channel('mko_to_bank_errors')->info('MKO-REPORT-GET 002 STARTED');
        $from = Carbon::parse($this->request['from'])->subDay()->endOfDay()->format('Y-m-d H:i:s');
        $to   = Carbon::parse($this->request['to'])->addDay()->startOfDay()->format('Y-m-d H:i:s');
        $this->from1C->collect002();
        $dbName = MkoFrom1C::$clientsTempTable;

        /** @noinspection SqlInsertValues */
        return DB::select("SELECT DISTINCT (IF(u.id = 363454, 11, u.id)) as id,
                u.nibbd,
                u.surname,
                u.name,
                u.patronymic,
                u.gender,
                u.birth_date,
                u.region,
                u.local_region,
                bp.passport_type,
                bp.passport_number,
                bp.passport_date_issue,
                bp.passport_issued_by,
                bp.inn,
                2 as client_type,
                0 as record_type
                FROM users as u
                         inner join `buyer_personals` as `bp` on bp.id = (select id from buyer_personals as bp2 where bp2.user_id = u.id limit 1)
                where u.id in (select DISTINCT(c.user_id)
                                from contracts as c
                                where (c.id IN (SELECT DISTINCT(aec.contract_id)
                                               FROM accounting_entries_cbu as aec
                                               where aec.operation_date > '$from' and aec.operation_date < '$to'
                                                 and aec.status = 1)
                                    or c.id in (SELECT DISTINCT c2.id
                                               from contracts as c2
                                               where (c2.status in (1,3,4)  and c2.confirmed_at < '$to')
                                                 or (c2.status in (5,9)
                                                    and EXISTS(SELECT * FROM account_balance_histories_cbu as abhc
                                                           WHERE abhc.operation_date > '$from' and abhc.operation_date < '$to'
                                                           AND abhc.balance != 0
                                                           AND abhc.account_id in
                                                               (select ac2.id from accounts_cbu as ac2 where ac2.mask in ('12401', '12405') and ac2.contract_id = c2.id)))
                                               and c2.general_company_id = '$this->mkoId')))
                and u.nibbd is not null
                union SELECT * FROM $dbName
                order by 1");
    }

    private function fill002($data): string
    {
        Log::channel('mko_to_bank_errors')->info('MKO-REPORT-FILL 002 STARTED');
        $this->dataArray    = [];
        $this->dataArray[0] = '0#' . $this->lineStart . $this->groupInfoCode . '#' . MKOInfoCodesEnum::IC002 . '#';
        $count              = 0;
        $total              = count($data);
        foreach ($data as $key => $user) {
            $lineEnd = $this->lineStart . 'RPLC#';
            $lineEnd .= ((int)trim($user->id)) . '#';
            $lineEnd .= trim($user->client_type) . '#';
            $lineEnd .= trim($user->nibbd) . '#';
            $lineEnd .= '#';
            $lineEnd .= $this->cleanString($user->surname) . '#';
            $lineEnd .= $this->cleanString($user->name) . '#';
            $lineEnd .= $this->cleanString($user->patronymic) . '#';
            if ($user->gender !== null) {
                $lineEnd .= trim($user->gender) . '#';
            } elseif ($user->record_type === 0) {
                Log::channel('mko_to_bank_errors')->error('User has no gender value', ['user_id' => $user->id]);
                $lineEnd .= 'NO_GENDER#';
            } else {
                $lineEnd .= '#';
            }
            if ($bDate = Carbon::make($user->birth_date)) {
                $lineEnd .= trim($bDate->format('Ymd')) . '#';
            } else {
                $lineEnd .= '#';
            }
            if ($user->passport_type !== null && $user->record_type === 0) {
                $lineEnd .= trim($user->passport_type) . '#';
            } elseif ($user->record_type === 0) {
                Log::channel('mko_to_bank_errors')
                    ->error('User has no passport type value', ['user_id' => $user->id]);
                $lineEnd .= 'NO_PASSPORT_TYPE#';
            } else {
                $lineEnd .= '#';
            }
            $passportData      = (EncryptHelper::decryptData($user->passport_number));
            $lineEnd           .= trim(substr($passportData, 0, 2)) . '#';
            $lineEnd           .= trim(substr($passportData, -7)) . '#';
            $passportDateIssue = EncryptHelper::decryptData($user->passport_date_issue);
            if ($passportDateIssue === '' || $passportDateIssue === 'None') {
                $myId = \App\Models\MyIDJob::where('user_id', $user->id)->where('result_code', 1)->first();
                if ($myId) {
                    $passportDateIssue = Carbon::createFromFormat('d.m.Y', $myId->profile['doc_data']['issued_date'])
                        ->format('Ymd');
                } else {
                    Log::channel('mko_to_bank_errors')->info("Passport Date Issue From MyID, NO DATA for user: ",
                                                             ['passportDateIssue' => $passportDateIssue,
                                                              'userId'            => $user->id]);
                }
            } elseif (Carbon::hasFormat($passportDateIssue, 'd.m.Y')) {
                $passportDateIssue = Carbon::createFromFormat('d.m.Y', $passportDateIssue)->format('Ymd');
            } elseif (Carbon::hasFormat($passportDateIssue, 'Y.m.d')) {
                $passportDateIssue = Carbon::createFromFormat('Y.m.d', $passportDateIssue)->format('Ymd');
            } else {
                try {
                    Carbon::make($passportDateIssue);
                } catch (InvalidFormatException $e) {
                    Log::channel('mko_to_bank_errors')->info("Passport Date Issue: ",
                                                             ['passportDateIssue' => $passportDateIssue,
                                                              'userId'            => $user->id]);
                }
                $passportDateIssue = '';
            }
            if ($passportDateIssue !== '') {
                $lineEnd .= trim($passportDateIssue) . '#';
            } else {
                if ($user->record_type === 0) {
                    $lineEnd .= 'NO_PASSPORT_ISSUE_DATE#';
                } else {
                    $lineEnd .= '#';
                }
            }
            $lineEnd .= $this->cleanString(EncryptHelper::decryptData($user->passport_issued_by)) . '#';
            $lineEnd .= '#';
            if (!$user->region && $user->record_type === 0) {
                $lineEnd .= 'NO_REGION#';
                Log::channel('mko_to_bank_errors')->error('User has no region value', ['user_id' => $user->id]);
            } else {
                if (strlen($user->region) < 2 && $user->record_type === 0) {
                    $user->region = 0 . $user->region;
                }
                $lineEnd .= trim($user->region) . '#';
            }
            if (!$user->local_region && $user->record_type === 0) {
                $lineEnd .= 'NO_LOCAL_REGION#';
                Log::channel('mko_to_bank_errors')->error('User has no local_region value', ['user_id' => $user->id]);
            } else {
                if ($user->local_region < 10 && $user->record_type === 0) {
                    $user->local_region = "00" . $user->local_region;
                }
                if (strlen($user->local_region) < 3 && $user->record_type === 0) {
                    $user->local_region = "0" . $user->local_region;
                }
                $lineEnd .= trim($user->local_region) . '#';
            }
            $lineEnd .= trim($user->nibbd) . '#';
            $lineEnd .= (strlen($user->inn) > 10 ? trim(EncryptHelper::decryptData($user->inn)) : $user->inn) . '#';

            $countLine         = str_replace('RPLC', '', $lineEnd);
            $symbolsCount      = $this->countAsciiSymbols($countLine);
            $this->dataArray[] = str_replace('RPLC', $symbolsCount, $lineEnd) . "\r";
            if ($key % 1000 === 0) {
                $count += 1000;
                if ($count > $total) {
                    $count = $total;
                }
                Log::channel('mko_to_bank_errors')->info($count . ' clients of ' . $total . ' processed');
            }
        }
        $arrayCount                       = count($this->dataArray);
        $this->dataArray[0]               .= $arrayCount . "#\r";
        $this->dataArray[$arrayCount - 1] .= "\n";

        Log::channel('mko_to_bank_errors')->info('MKO-REPORT-FILL 002 ENDED');

        return implode(PHP_EOL, $this->dataArray);
    }

    private function get003(): Collection
    {
        Log::channel('mko_to_bank_errors')->info('MKO-REPORT-GET 003 STARTED');
        $from = Carbon::parse($this->request['from'])->subDay()->endOfDay()->format('Y-m-d H:i:s');
        $to   = Carbon::parse($this->request['to'])->addDay()->startOfDay()->format('Y-m-d H:i:s');

        return DB::table('contracts as c')->selectRaw("distinct(c.id), c.*,
            m.loan_type_code, m.credit_object_code, m.currency_code, m.bank_code, m.contract_type_code, m.subject_type_code,
            m.borrower_type_code,m.reason_early_termination,m.disclaimer_note,m.issuance_form,m.payment_purpose,
            m.type_loan_collateral, pt.period_id, pt.urgency_type, pt.urgency_interval,
            IFNULL((select ov.id
            from overdue_loan_quality_class as ov
            where c.expired_days between ov.expiry_days_from and COALESCE(ov.expiry_days_to, 10000)), 1) as ov_id,
           (select DATE_FORMAT(MAX(aec.operation_date), '%Y%m%d')
            from accounting_entries_cbu as aec
            where aec.debit_account LIKE '12405%'
            and aec.contract_id = c.id
            and aec.operation_date > '$from' and aec.operation_date < '$to') as last_bh,
           (select DATE_FORMAT(cpsch.payment_date, '%Y%m%d')
            from contract_payments_schedule as cpsch
            where cpsch.contract_id = c.id
            order by payment_date DESC limit 1)  as last_payment_date,
            (select u.nibbd from users as u where c.user_id = u.id) as nibbd")
            ->whereRaw("c.id IN (SELECT DISTINCT(aec.contract_id)
                FROM accounting_entries_cbu as aec
                where aec.operation_date > '$from' and aec.operation_date < '$to'
                  and aec.status = 1 and aec.destination_code != '0000')
               OR (c.status in (1,3,4) AND confirmed_at < '$to')
               OR (c.status in (5, 9)
                and EXISTS(SELECT *
               FROM account_balance_histories_cbu as abhc
               WHERE abhc.operation_date > '$from' and abhc.operation_date < '$to'
                 AND abhc.balance != 0
                 and abhc.account_id in (select ac2.id from accounts_cbu as ac2 where ac2.mask in ('12401', '12405') and ac2.contract_id = c.id)))")
            ->where('c.general_company_id', $this->mkoId)
            ->join('accounting_entries_cbu as aec', 'aec.contract_id', '=', 'c.id')
            ->join('mfo_settings as m', 'm.general_company_id', '=', 'c.general_company_id')
            ->join('payment_terms as pt', 'pt.period_id', '=', 'c.price_plan_id')
            ->orderBy('c.id')
            ->get();
    }

    private function fill003(Collection $data): string
    {
        Log::channel('mko_to_bank_errors')->info('MKO-REPORT-FILL 003 STARTED');
        $this->dataArray    = [];
        $this->dataArray[0] = '0#' . $this->lineStart . $this->groupInfoCode . '#' . MKOInfoCodesEnum::IC003 . '#';
        $count              = 0;
        $total              = count($data);
        foreach ($data as $key => $contract) {
            $lineEnd = $this->lineStart . 'RPLC#';
            $lineEnd .= trim($contract->id) . '#';
            $lineEnd .= '#';
            $lineEnd .= trim($contract->nibbd) . "#";
            $lineEnd .= trim($contract->borrower_type_code) . "#";
            $lineEnd .= trim($contract->loan_type_code) . "#";
            $lineEnd .= trim($contract->credit_object_code) . "#";
            $lineEnd .= trim($contract->urgency_type) . "#";
            $lineEnd .= trim($contract->urgency_interval) . "#";
            $lineEnd .= trim($contract->ov_id) . "#";
            $lineEnd .= trim($contract->issuance_form) . '#';
            $lineEnd .= trim($this->percent) . '#';
            $lineEnd .= trim(Carbon::parse($contract->confirmed_at)->format('Ymd')) . "#";
            $lineEnd .= trim($contract->id) . '#';
            $lineEnd .= trim((int)($contract->total * 100)) . '#';
            $lineEnd .= '#';
            $lineEnd .= '#';
            $lineEnd .= trim(Carbon::parse($contract->confirmed_at)->format('Ymd')) . "#";
            $lineEnd .= '#';
            if ($contract->last_bh) {
                $lineEnd .= trim($contract->last_bh) . '#';
            } else {
                $lineEnd .= '#';
            }
            $lineEnd .= '#';
            $lineEnd .= trim($contract->last_payment_date) . '#';

            $date       = Carbon::parse($this->request['to'])->addDay()->startOfDay();
            $cancelDate = Carbon::parse($contract->canceled_at);
            $closedDate = Carbon::parse($contract->closed_at);

            if ($contract->status === 5 && $cancelDate->lt($date)) {
                $lineEnd .= $cancelDate->format('Ymd') . '#';
            } elseif ($contract->status === 9 && $closedDate->lt($date)) {
                $lineEnd .= $closedDate->format('Ymd') . '#';
            } else {
                $lineEnd .= '#';
            }

            $countLine         = str_replace('RPLC', '', $lineEnd);
            $symbolsCount      = $this->countAsciiSymbols($countLine);
            $this->dataArray[] = str_replace('RPLC', $symbolsCount, $lineEnd) . "\r";
            if ($key % 1000 === 0) {
                $count += 1000;
                if ($count > $total) {
                    $count = $total;
                }
                Log::channel('mko_to_bank_errors')->info($count . ' contracts of ' . $total . ' processed');
            }
        }
        $arrayCount                       = count($this->dataArray);
        $this->dataArray[0]               .= $arrayCount . "#\r";
        $this->dataArray[$arrayCount - 1] .= "\n";
        Log::channel('mko_to_bank_errors')->info('MKO-REPORT-FILL 003 ENDED');

        return implode(PHP_EOL, $this->dataArray);
    }

    private function get004(): Collection
    {
        return new Collection([]);
    }

    private function fill004(Collection $data): string
    {
        if (!$this->checkForDataExists($data)) {
            return $this->generateOnlyNullLine(MKOInfoCodesEnum::IC004);
        }

        return '';
        //else {
        // make report (no need now);
        //}
    }

    /**
     * @throws HttpClientException
     */
    private function get005(): Collection
    {
        Log::channel('mko_to_bank_errors')->info('MKO-REPORT-GET 005 STARTED');

        return $this->from1C->collect005();
    }

    private function fill005(collection $data): string
    {
        if (!$this->checkForDataExists($data)) {
            return $this->generateOnlyNullLine(MKOInfoCodesEnum::IC005);
        }
        Log::channel('mko_to_bank_errors')->info('MKO-REPORT-FILL 005 STARTED');
        $this->dataArray    = [];
        $this->dataArray[0] = '0#' . $this->lineStart . $this->groupInfoCode . '#' . MKOInfoCodesEnum::IC005 . '#';
        foreach ($data as $deposit) {
            $lineEnd = $this->lineStart . 'RPLC#';
            $lineEnd .= ((int)trim($deposit['НомерУчетнойКарточки'])) . '#';
            $lineEnd .= trim($deposit['ДатаРазмещения']) . '#';
            $lineEnd .= trim($deposit['ДатаИзъятия']) . '#';
            $lineEnd .= trim($deposit['КодОтделенияБанка']) . '#';
            $lineEnd .= trim($deposit['БанковскийЛицевойСчет']) . '#';
            $lineEnd .= trim($deposit['СуммаДепозита']) . '#';
            $lineEnd .= trim($deposit['ГодоваяПроцентнаяСтавка']) . '#';
            $lineEnd .= trim($deposit['ДатаЗакрытия']) . '#';

            $countLine         = str_replace('RPLC', '', $lineEnd);
            $symbolsCount      = $this->countAsciiSymbols($countLine);
            $this->dataArray[] = str_replace('RPLC', $symbolsCount, $lineEnd) . "\r";
        }
        $arrayCount                       = count($this->dataArray);
        $this->dataArray[0]               .= $arrayCount . "#\r";
        $this->dataArray[$arrayCount - 1] .= "\n";

        Log::channel('mko_to_bank_errors')->info('MKO-REPORT-FILL 005 ENDED');

        return implode(PHP_EOL, $this->dataArray);
    }

    /**
     * @throws HttpClientException
     */
    private function get006(): Collection
    {
        Log::channel('mko_to_bank_errors')->info('MKO-REPORT-GET 006 STARTED');

        return $this->from1C->collect006();
    }

    private function fill006(Collection $data): string
    {
        if (!$this->checkForDataExists($data)) {
            return $this->generateOnlyNullLine(MKOInfoCodesEnum::IC006);
        }
        $from = Carbon::parse($this->request['from'])->startOfDay();
        $to   = Carbon::parse($this->request['to'])->endOfDay();

        Log::channel('mko_to_bank_errors')->info('MKO-REPORT-FILL 006 STARTED');
        $this->dataArray    = [];
        $this->dataArray[0] = '0#' . $this->lineStart . $this->groupInfoCode . '#' . MKOInfoCodesEnum::IC006 . '#';
        foreach ($data as $credit) {
            $lineEnd    = $this->lineStart . 'RPLC#';
            $lineEnd    .= ((int)trim($credit['НомерУчетнойКарточки'])) . '#';
            $lineEnd    .= trim($credit['ДатаПолучения']) . '#';
            $lineEnd    .= trim($credit['ДатаПогашения']) . '#';
            $lineEnd    .= trim($credit['ИННКредитора']) . '#';
            $lineEnd    .= trim($credit['НаименованиеКредитора']) . '#';
            $lineEnd    .= trim($credit['НаименованиеКредитногоПродукта']) . '#';
            $lineEnd    .= trim($credit['СуммаКредита']) . '#';
            $lineEnd    .= trim($credit['ОстатокКредита']) . '#';
            $lineEnd    .= trim($credit['ГодоваяПроцентнаяСтавка']) . '#';
            $dateClosed = Carbon::createFromFormat('Ymd', $credit['ДатаЗакрытия']);
            if ($dateClosed->between($from, $to)) {
                $lineEnd .= trim($credit['ДатаЗакрытия']) . '#';
            } else {
                $lineEnd .= '#';
            }

            $countLine         = str_replace('RPLC', '', $lineEnd);
            $symbolsCount      = $this->countAsciiSymbols($countLine);
            $this->dataArray[] = str_replace('RPLC', $symbolsCount, $lineEnd) . "\r";
        }
        $arrayCount                       = count($this->dataArray);
        $this->dataArray[0]               .= $arrayCount . "#\r";
        $this->dataArray[$arrayCount - 1] .= "\n";

        Log::channel('mko_to_bank_errors')->info('MKO-REPORT-FILL 006 ENDED');

        return implode(PHP_EOL, $this->dataArray);
    }

    /**
     * @throws HttpClientException
     */
    private function get007(): Collection
    {
        Log::channel('mko_to_bank_errors')->info('MKO-REPORT-GET 007 STARTED');

        return $this->from1C->collect007();
    }

    private function fill007(Collection $data): string
    {
        if (!$this->checkForDataExists($data)) {
            return $this->generateOnlyNullLine(MKOInfoCodesEnum::IC007);
        }
        Log::channel('mko_to_bank_errors')->info('MKO-REPORT-FILL 007 STARTED');
        $this->dataArray    = [];
        $this->dataArray[0] = '0#' . $this->lineStart . $this->groupInfoCode . '#' . MKOInfoCodesEnum::IC007 . '#';
        $lineEnd            = $this->lineStart . 'RPLC#';
        $lineEnd            .= trim($data['ФИОРуководителя']) . '#';
        $lineEnd            .= trim($data['ФИОГлавногоБухгалтера']) . '#';
        $lineEnd            .= trim($data['КоличествоРаботников']) . '#';
        $lineEnd            .= trim($data['КоличествоКредитныхРаботников']) . '#';
        $lineEnd            .= trim($data['КоличествоПунктовОбслуживанияИИКЦ']) . '#';
        $lineEnd            .= trim($data['КоличествоФилиалов']) . '#';
        $lineEnd            .= trim($data['КонтактныеТелефоны']) . '#';
        $lineEnd            .= trim($data['КодОтделенияБанкаОсновногоЛицевогоСчета']) . '#';
        $lineEnd            .= trim($data['ОсновнойЛицевойСчет']) . '#';
        $lineEnd            .= trim($data['КодОтделенияБанкаВторичногоЛицевогоСчета']) . '#';
        $lineEnd            .= trim($data['ВторичныйЛицевойСчет']) . '#';

        $countLine         = str_replace('RPLC', '', $lineEnd);
        $symbolsCount      = $this->countAsciiSymbols($countLine);
        $this->dataArray[] = str_replace('RPLC', $symbolsCount, $lineEnd) . "\r";

        $arrayCount                       = count($this->dataArray);
        $this->dataArray[0]               .= $arrayCount . "#\r";
        $this->dataArray[$arrayCount - 1] .= "\n";
        Log::channel('mko_to_bank_errors')->info('MKO-REPORT-FILL 007 ENDED');

        return implode(PHP_EOL, $this->dataArray);
    }

    /**
     * @throws HttpClientException
     */
    private function get008(): Collection
    {
        Log::channel('mko_to_bank_errors')->info('MKO-REPORT-GET 008 STARTED');

        return $this->from1C->collect008();
    }

    private function fill008(Collection $data): string
    {
        if (!$this->checkForDataExists($data)) {
            return $this->generateOnlyNullLine(MKOInfoCodesEnum::IC008);
        }
        Log::channel('mko_to_bank_errors')->info('MKO-REPORT-FILL 008 STARTED');
        $this->dataArray    = [];
        $this->dataArray[0] = '0#' . $this->lineStart . $this->groupInfoCode . '#' . MKOInfoCodesEnum::IC008 . '#';
        foreach ($data as $user) {
            $lineEnd = $this->lineStart . 'RPLC#';
            $lineEnd .= trim($user['КодКлиента']) . '#';
            $lineEnd .= trim($user['СуммаВкладаВУставнойФонд']) . '#';
            $lineEnd .= trim($user['ДатаВступления']) . '#';
            $lineEnd .= trim($user['ДатаВыбытия']) . '#';

            $countLine         = str_replace('RPLC', '', $lineEnd);
            $symbolsCount      = $this->countAsciiSymbols($countLine);
            $this->dataArray[] = str_replace('RPLC', $symbolsCount, $lineEnd) . "\r";
        }
        $arrayCount                       = count($this->dataArray);
        $this->dataArray[0]               .= $arrayCount . "#\r";
        $this->dataArray[$arrayCount - 1] .= "\n";

        Log::channel('mko_to_bank_errors')->info('MKO-REPORT-FILL 008 ENDED');

        return implode(PHP_EOL, $this->dataArray);
    }

    /**
     * Подсчитать сумму символов в строке,
     * представленных в виде ASCII кодов
     *
     * @param string|null $string $string
     *
     * @return int
     */
    private function countAsciiSymbols(?string $string): int
    {
        $countSymbolsInLine = 0;
        $string             = str_replace(['Í', 'Ў', 'Ғ', 'Қ', "'", "`", "ʼ", "´", "’", "#"],
                                          ['I', 'У', 'Г', 'К', "‘", "‘", "‘", "‘", "‘", ''], $string);
        foreach (mb_str_split($string) as $symbol) {
            $number             = ord(mb_convert_encoding($symbol, 'WINDOWS-1251'));
            $countSymbolsInLine += $number;
        }

        return $countSymbolsInLine;
    }

    /**
     * Подсчитать сумму символов в строке,
     * представленных в виде ASCII кодов и заменить все кавычки на нужную
     *
     * @param string|null $string $string
     *
     * @return string
     */
    private function cleanString(?string $string): string
    {
        return trim(str_replace(['Í', 'Ў', 'Ғ', 'Қ', "'", "`", "ʼ", "´", "’"],
                                ['I', 'У', 'Г', 'К', "‘", "‘", "‘", "‘", "‘",], $string));
    }

    /**
     * Проверяем, пришли ли данные
     *
     * @param Collection $data
     *
     * @return bool
     */
    private function checkForDataExists(Collection $data): bool
    {
        if ($data->isEmpty()) {
            return false;
        }

        return true;
    }

    /**
     * Сгенерировать только нулевую строку для отчетов без данных
     *
     * @param string $reportNumber
     *
     * @return string
     */
    private function generateOnlyNullLine(string $reportNumber): string
    {
        return '0#' . $this->lineStart . $this->groupInfoCode . '#' . $reportNumber . '#' . 1 . "#\r\n";
    }
}
