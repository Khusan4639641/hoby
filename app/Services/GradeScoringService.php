<?php

namespace App\Services;

use App\Classes\CURL\MLScore\MLScoreBaseLimit;
use App\Classes\CURL\MLScore\MLScoreExtendedLimit;
use App\Classes\Exceptions\MLException;
use App\Helpers\EncryptHelper;
use App\Logging\ByUser\LoggerByUser;
use App\Models\Buyer;
use App\Models\BuyerPersonal;
use App\Models\BuyerSetting;
use App\Models\ScoringResult;
use App\Models\ScoringResultMini;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Psr\Log\LoggerInterface;

class GradeScoringService
{

    private LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = Log::channel('scoring');
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function mustSendSmsToUser(int $userID, bool $isMust)
    {
        Redis::hDel(":scoring:" . $userID, "SendSms");
        if ($isMust) {
            $ttl = Carbon::now()->diffInMinutes(Carbon::now()->addMinutes(20), false) * 60;
            Redis::hSet(":scoring:" . $userID, "SendSms", true);
            Redis::expire(":scoring:" . $userID, $ttl);
        }
    }

    public function isMustSendSmsToUser(int $userID): bool
    {
        return (bool)Redis::hGet(":scoring:" . $userID, "SendSms");
    }

    public function findDuplicatedBuyersByPinfl(string $pinfl, int $withoutBuyerID = 0): bool
    {
        $foundedBuyersCount = BuyerPersonal::where('pinfl_hash', md5($pinfl))
            ->whereHas('buyer', function (Builder $query) {
                $query->whereIn('status', [User::KYC_STATUS_VERIFY, User::KYC_STATUS_BLOCKED]);
            })
            ->where('user_id', '!=', $withoutBuyerID)
            ->count();
        return $foundedBuyersCount > 0;
    }

    private function makeSettings(Buyer $buyer)
    {
        $buyerSetting = $buyer->settings;
        if (!$buyerSetting) {

            $this->logger->info('Создание и сохранение настроек покупателя');

            $buyerSetting = new BuyerSetting();
            $buyerSetting->period = 12;
            $buyerSetting->limit = 0;
            $buyerSetting->balance = 0;
            $buyerSetting->katm_region_id = $buyer->region;
            $buyerSetting->katm_local_region_id = $buyer->local_region;
            $buyer->settings()->save($buyerSetting);
        } else {

            $this->logger->info('Сохранение настроек покупателя');

            $buyerSetting->period = 12;
            $buyerSetting->limit = 0;
            $buyerSetting->balance = 0;
            $buyerSetting->katm_region_id = $buyer->region;
            $buyerSetting->katm_local_region_id = $buyer->local_region;
            $buyerSetting->save();
        }
    }

    public function saveUserDataByScoringV2(
        ScoringResult $scoring,
        string        $claimID
    )
    {
        $this->logger->info('Сохранение заявки в KATM');

        $scoring->katm_claim = $claimID;
        $scoring->save();
    }

    public function saveUserDataByScoring(
        ScoringResult $scoring,
        string        $name,
        string        $surname,
        string        $patronymic,
        int           $gender,
        string        $birthDate,
        string        $inn,
        string        $issueDocDate,
        string        $claimID
    )
    {
        $buyer = $scoring->buyer;

        $this->logger->info('Сохранение данных покупателя');

        $buyer->name = $name;
        $buyer->surname = $surname;
        $buyer->patronymic = $patronymic;
        $buyer->gender = $gender;
        $buyer->birth_date = $birthDate;
        $buyer->save();

        $this->logger->info('Сохранение персональных данных покупателя');

        $personalData = $buyer->personalData;
        $personalData->inn = EncryptHelper::encryptData($inn);
        $personalData->passport_date_issue = $issueDocDate ? EncryptHelper::encryptData($issueDocDate) : '';
        $personalData->birthday = EncryptHelper::encryptData($birthDate);
        $personalData->save();

        $this->makeSettings($buyer);

        $this->logger->info('Сохранение заявки в KATM');

        $scoring->katm_claim = $claimID;
        $scoring->save();

    }

    public function saveLimitByScoring(
        ScoringResult $scoring,
        float         $limit
    )
    {
        $this->makeSettings($scoring->buyer);

        $this->logger->info('Сохранение лимита');

        $scoring->final_limit = $limit;
        $scoring->save();
    }

    public function initMiniScoring(int $userID, $isMini = true)
    {
        $user = User::find($userID);
        $this->setLogger(new LoggerByUser($user, 'scoring', 'mini'));

        $buyer = Buyer::find($userID);
        $scoringResultMini = $buyer->scoringResultMini->last();
        if (!$scoringResultMini) {
            $scoringResultMini = new ScoringResultMini();
            $scoringResultMini->initiator_id = Auth::id();
            $scoringResultMini->is_katm_auto = true;
            $scoringResultMini->buyer()->associate($buyer);
            $scoringResultMini->save();
        } else {
            $scoringResultMini->resetFails();
        }
        $scoringResultMini->finalAwait();
        $this->requestToMLByBaseLimit($scoringResultMini, $userID, true, $isMini);
    }

    public function miniScoringStateReport(int $userID)
    {
        $buyer = Buyer::find($userID);

        $scoring = $buyer->scoringResultMini->last();

        if (!$scoring) {
            return [
                'debts_by_royxat_state' => 0,
                'debts_by_royxat_state_text' => __('Не найден'),
                'debts_by_mib_state' => 0,
                'debts_by_mib_state_text' => __('Не найден'),
                'debts_by_katm_state' => 0,
                'debts_by_katm_state_text' => __('Не найден'),
                'overdue_by_infoscore_state' => 0,
                'overdue_by_infoscore_state_text' => __('Не найден'),
                'total_state' => 0,
            ];
        }

        return [
            'debts_by_royxat_state' => $scoring->debts_by_royxat_state,
            'debts_by_royxat_state_text' => $this->makeScoringMessage($scoring->debts_by_royxat_state),
            'debts_by_mib_state' => $scoring->debts_by_mib_state,
            'debts_by_mib_state_text' => $this->makeScoringMessage($scoring->debts_by_mib_state),
            'debts_by_katm_state' => $scoring->debts_by_katm_state,
            'debts_by_katm_state_text' => $this->makeScoringMessage($scoring->debts_by_katm_state),
            'overdue_by_infoscore_state' => $scoring->overdue_by_infoscore_state,
            'overdue_by_infoscore_state_text' => $this->makeScoringMessage($scoring->overdue_by_infoscore_state),
            'total_state' => $scoring->total_state,
        ];
    }

    /**
     * @throws \Exception
     */
    public function requestToMLByBaseLimit(ScoringResult $scoring, int $userID, bool $isAuto = true, $isMini = false): void
    {
        Log::channel('scoring_steps_logs')->info('Запрос на ML в Base');
        try {
            $buyer = Buyer::find($userID);

            if (!$buyer) {
                Log::channel('scoring_steps_logs')->info("Message: Покупатель с User_id = $userID не найден;\n Class: " . __CLASS__ . ";\n Method: " . __METHOD__ . ";\n Line: " . __LINE__ . "\n");
            }

            $personalData = $buyer->personalData;

            if (!$personalData) {
                Log::channel('scoring_steps_logs')->info("Message: Не найдены персональные данные покупателя;\n User_id: $userID;\n Class: " . __CLASS__ . ";\n Method: " . __METHOD__ . ";\n Line: " . __LINE__ . "\n");
            }

            $passport = EncryptHelper::decryptData($personalData->passport_number);
            $passportSeries = mb_substr($passport, 0, 2);
            $passportNumber = mb_substr($passport, 2, mb_strlen($passport));
            $pinfl = EncryptHelper::decryptData($personalData->pinfl);
            $passportType = $personalData->passport_type;
            if ($buyer->addressRegistration && $buyer->addressRegistration->address) {
                $setAddress = $buyer->addressRegistration->address;
            } else {
                $setAddress = '';
            }
            $address = $setAddress;
            $phone = $buyer->getRawOriginal('phone');
            $region = (int)$buyer->region ?? 26;
            $localRegion = $buyer->local_region ?? 203;
            $gender = $buyer->gender ?? 1;
            /*
            $birthDate = Carbon::createFromFormat("Y-m-d", $buyer->birth_date)->format("d.m.Y");
            */
            //$birthDate = $buyer->birth_date;
            $birthDate = $personalData->birthday_open;

            $inn = EncryptHelper::decryptData($personalData->inn);
            $name = $buyer->name ?: "";
            $surname = $buyer->surname ?: "";
            $patronymic = $buyer->patronymic ?: "";
            $mrz = $personalData->mrz;
            //$issueDocDate = EncryptHelper::decryptData($personalData->passport_date_issue);
            $issueDocDate = $personalData->passport_date_issue_open;
            //$expiredDocDate = EncryptHelper::decryptData($personalData->passport_expire_date);
            $expiredDocDate = $personalData->passport_expire_date_open;

            try {
                if ($issueDocDate) {
                    if (!Carbon::hasFormat($issueDocDate, "d.m.Y")) {
                        $issueDocDate = Carbon::parse($issueDocDate)->format("d.m.Y");
                    }
                } else {
                    $issueDocDate = '';
                }
                //$issueDocDate = $issueDocDate ? Carbon::createFromFormat("d.m.Y", $issueDocDate)->format("Y-m-d") : "";
            } catch (\Throwable $ee) {
                throw new \Exception("Не приемлимый формат даты buyer_personals->passport_date_issue = " . $issueDocDate . ". Ожидаемый формат - d.m.Y");
            }


            try {
                if ($expiredDocDate) {
                    if (!Carbon::hasFormat($expiredDocDate, "d.m.Y")) {
                        $expiredDocDate = Carbon::parse($expiredDocDate)->format("d.m.Y");
                    }
                } else {
                    $expiredDocDate = '';
                }
                //$expiredDocDate = $expiredDocDate ? Carbon::createFromFormat("d.m.Y", $expiredDocDate)->format("Y-m-d") : "";
            } catch (\Throwable $ee) {
                throw new \Exception("Не приемлимый формат даты buyer_personals->passport_expire_date = " . $expiredDocDate . ". Ожидаемый формат - d.m.Y");
            }

            try {
                if ($birthDate) {
                    if (!Carbon::hasFormat($birthDate, "d.m.Y")) {
                        $birthDate = Carbon::parse($birthDate)->format("d.m.Y");
                    }
                } else {
                    $birthDate = '';
                }
                //$expiredDocDate = $expiredDocDate ? Carbon::createFromFormat("d.m.Y", $expiredDocDate)->format("Y-m-d") : "";
            } catch (\Throwable $ee) {
                throw new \Exception("Не приемлимый формат даты buyer_personals->birth_date = " . $birthDate . ". Ожидаемый формат - d.m.Y");
            }

            $route = route('ml.limit.base', $buyer->id);
            if ($isMini) {
                $route = route('ml.limit.mini', $buyer->id);
            }

            $request = new MLScoreBaseLimit(
                $route,
                $scoring->id,
                $userID,
                $passportSeries,
                $passportNumber,
                $pinfl,
                $passportType,
                $phone,
                $address,
                $region,
                $localRegion,
                $gender,
                $birthDate,
                $inn ?: '',
                //$isAuto,
                $name,
                $surname,
                $patronymic,
                $mrz ?? '',
                $issueDocDate,
                $expiredDocDate
            );

            Log::channel('scoring_steps_logs')->info("Message: Тело запроса на ML в Base: (" . $request->requestText() . ");\n User_id: $userID;\n Class: " . __CLASS__ . ";\n Method: " . __METHOD__ . ";\n Line: " . __LINE__ . "\n");

            $request->execute();
            if (!$request->isSuccessful()) {
                Log::channel('scoring_steps_logs')->info("Не удалось обратиться к сервису ML;\n User_id: $userID;\n  Class: " . __CLASS__ . ";\n Method: " . __METHOD__ . ";\n Line: " . __LINE__ . "\n");
                throw new \Exception("Не удалось обратиться к сервису ML");
            }
        } catch (MLException $e) {
            $data = [
                'message' => $e->getMessage(),
                'url' => $e->urlText(),
                'code' => $e->getCode(),
                'request' => $e->requestArray(),
                'response' => $e->responseArray(),
            ];
            Log::channel('scoring_steps_logs')->info("Ошибка: " . $e->getMessage() . ";\n User_id: $userID;\n Class: " . __CLASS__ . ";\n Method: " . __METHOD__ . ";\n Line: " . __LINE__ . "\n");
            $this->logger->error($e->getMessage(), $data);
        } catch (\Throwable $e) {
            Log::channel('scoring_steps_logs')->info("Ошибка: " . $e->getMessage() . ";\n User_id: $userID;\n Class: " . __CLASS__ . ";\n Method: " . __METHOD__ . ";\n Line: " . __LINE__ . "\n");
            $this->logger->error($e->getMessage());
        }

    }

    /**
     * @throws \Exception
     */
    public function requestToMLByExtendedLimit(ScoringResult $scoring): void
    {
        Log::channel('scoring_steps_logs')->info('Запрос в ML на extended');
        try {
            $buyer = $scoring->buyer;
            if ($buyer->cardsActive->count() === 0) {
                Log::channel('scoring_steps_logs')->info("Message: Активных карт покупателя не найдено;\n Class: " . __CLASS__ . ";\n Method: " . __METHOD__ . ";\n Line: " . __LINE__ . "\n");
                throw new \Exception("Активных карт покупателя не найдено");
            }
            $card = $buyer->cardsActive->first();
            $pinfl = EncryptHelper::decryptData($buyer->personalData->pinfl);
            $cardToken = $card->token_payment;

            $scoring->finalAwait();

            $request = new MLScoreExtendedLimit(
                route('ml.limit.extended', $buyer->id),
                $scoring->id,
                $buyer->id,
                $pinfl,
                $cardToken
            );

            Log::channel('scoring_steps_logs')->info("Тело запроса на ML в Extended: {$request->requestText()};\n Class: " . __CLASS__ . ";\n Method: " . __METHOD__ . ";\n Line: " . __LINE__ . "\n");

            $request->execute();
            if (!$request->isSuccessful()) {
                Log::channel('scoring_steps_logs')->info("Не удалось обратиться к сервису ML;\n Class: " . __CLASS__ . ";\n Method: " . __METHOD__ . ";\n Line: " . __LINE__ . "\n");
                throw new \Exception("Не удалось обратиться к сервису ML");
            }
        } catch (MLException $e) {
            $data = [
                'message' => $e->getMessage(),
                'url' => $e->urlText(),
                'code' => $e->getCode(),
                'request' => $e->requestArray(),
                'response' => $e->responseArray(),
            ];
            Log::channel('scoring_steps_logs')->info("Ошибка: " . $e->getMessage() . "; User_id: $buyer->id;  Class: " . __CLASS__ . "; Method: " . __METHOD__ . "; Line: " . __LINE__);
            $this->logger->error($e->getMessage(), $data);
        } catch (\Throwable $e) {
            Log::channel('scoring_steps_logs')->info("Ошибка: " . $e->getMessage() . "; User_id: $buyer->id;  Class: " . __CLASS__ . "; Method: " . __METHOD__ . "; Line: " . __LINE__);
            $this->logger->error($e->getMessage());
        }

    }

    public function initScoring(int $userID, bool $isAuto = true)
    {
        Log::channel('scoring_steps_logs')->info("-----------Инициализация скоринга для User_id = $userID;\n Class: " . __CLASS__ . ";\n Method: " . __METHOD__ . ";\n Line: " . __LINE__ . "\n");

        $user = User::find($userID);
        $this->setLogger(new LoggerByUser($user, 'scoring', 'full'));

        $buyer = Buyer::find($userID);

        if (!$buyer) {
            Log::channel('scoring_steps_logs')->info("Message: Покупатель с $userID не найден;\n Class: " . __CLASS__ . ";\n Method: " . __METHOD__ . ";\n Line: " . __LINE__ . "\n");
        }

        $scoringResult = $buyer->scoringResult->last();

        if (!$scoringResult) {
            $scoringResult = new ScoringResult();
            $scoringResult->initiator_id = Auth::id();
            $scoringResult->is_katm_auto = $isAuto;
            $scoringResult->buyer()->associate($buyer);
            $scoringResult->save();
        } else {
            $scoringResult->resetFails();
        }

        Log::channel('scoring_steps_logs')->info("Message: Проставка статуса в scoring_results (check_approve_state и total_state) равным 3 (В процессе скоринга);\n User_id: $userID;\n Class: " . __CLASS__ . ";\n Method: " . __METHOD__ . ";\n Line: " . __LINE__ . "\n");
        $scoringResult->checkApproveAwait();

        Log::channel('scoring_steps_logs')->info("Message: Вызов метода requestToMLByExtendedLimit. Мини лимит пользователя: " . $buyer->settings->mini_limit . ";\n User_id: $userID;\n Class: " . __CLASS__ . ";\n Method: " . __METHOD__ . ";\n Line: " . __LINE__ . "\n");
        $this->requestToMLByExtendedLimit($scoringResult);

        /*$scoringResultMini = $buyer->scoringResultMini->last();

        if (!$scoringResultMini) {
            Log::channel('scoring_steps_logs')->info("Message: Создание записи в таблицу scoring_results;\n User_id: $userID;\n Class: ".__CLASS__.";\n Method: ".__METHOD__.";\n Line: ".__LINE__."\n");
            $scoringResultMini = new ScoringResultMini();
            $scoringResultMini->initiator_id = Auth::id();
            $scoringResultMini->is_katm_auto = $isAuto;
            $scoringResultMini->buyer()->associate($buyer);
            $scoringResultMini->save();

            Log::channel('scoring_steps_logs')->info("Message: Проставка статуса в scoring_results (check_approve_state и total_state) равным 3 (В процессе скоринга);\n User_id: $userID;\n Class: ".__CLASS__.";\n Method: ".__METHOD__.";\n Line: ".__LINE__."\n");
            $scoringResultMini->checkApproveAwait();

            Log::channel('scoring_steps_logs')->info("Message: Вызов метода requestToMLByBaseLimit. Мини лимит пользователя: ".$buyer->settings->mini_limit.";\n User_id: $userID;\n Class: ".__CLASS__.";\n Method: ".__METHOD__.";\n Line: ".__LINE__."\n");
            $this->requestToMLByBaseLimit($scoringResultMini, $userID, $isAuto);
        } else {
            if($scoringResultMini->total_state == ScoringResult::STATE_USER_INFO_SUCCESS){
                $scoringResult = $buyer->scoringResult->last();

                if(!$scoringResult){
                    $scoringResult = new ScoringResult();
                    $scoringResult->initiator_id = Auth::id();
                    $scoringResult->is_katm_auto = $isAuto;
                    $scoringResult->buyer()->associate($buyer);
                    $scoringResult->save();
                }else {
                    $scoringResult->resetFails();
                }

                Log::channel('scoring_steps_logs')->info("Message: Проставка статуса в scoring_results (check_approve_state и total_state) равным 3 (В процессе скоринга);\n User_id: $userID;\n Class: ".__CLASS__.";\n Method: ".__METHOD__.";\n Line: ".__LINE__."\n");
                $scoringResult->checkApproveAwait();

                Log::channel('scoring_steps_logs')->info("Message: Вызов метода requestToMLByExtendedLimit. Мини лимит пользователя: ".$buyer->settings->mini_limit.";\n User_id: $userID;\n Class: ".__CLASS__.";\n Method: ".__METHOD__.";\n Line: ".__LINE__."\n");
                $this->requestToMLByExtendedLimit($scoringResult);

            }elseif($scoringResultMini->total_state == ScoringResult::STATE_FAILED_RESPONSE){
                Log::channel('scoring_steps_logs')->info("Message: Проставка статуса в scoring_results (check_approve_state и total_state) равным 3 (В процессе скоринга);\n User_id: $userID;\n Class: ".__CLASS__.";\n Method: ".__METHOD__.";\n Line: ".__LINE__."\n");
                $scoringResultMini->checkApproveAwait();

                Log::channel('scoring_steps_logs')->info("Message: Найдена запись в таблице scoring_results. Обнуление параметров check_approve_state, final_state, total_state, tries_count;\n User_id: $userID;\n Class: ".__CLASS__.";\n Method: ".__METHOD__.";\n Line: ".__LINE__."\n");
                $scoringResultMini->resetFails();

                Log::channel('scoring_steps_logs')->info("Message: Вызов метода requestToMLByBaseLimit. Мини лимит пользователя: ".$buyer->settings->mini_limit.";\n User_id: $userID;\n Class: ".__CLASS__.";\n Method: ".__METHOD__.";\n Line: ".__LINE__."\n");
                $this->requestToMLByBaseLimit($scoringResultMini, $userID, $isAuto);
            }
        }*/

        /*if($buyer->settings && $buyer->settings->mini_limit > 0){
            Log::channel('scoring_steps_logs')->info("Message: Вызов метода requestToMLByExtendedLimit. Мини лимит пользователя: ".$buyer->settings->mini_limit.";\n User_id: $userID;\n Class: ".__CLASS__.";\n Method: ".__METHOD__.";\n Line: ".__LINE__."\n");
            $this->requestToMLByExtendedLimit($scoringResult);
        }else{
            Log::channel('scoring_steps_logs')->info("Message: Вызов метода requestToMLByBaseLimit. Мини лимит пользователя: ".$buyer->settings->mini_limit.";\n User_id: $userID;\n Class: ".__CLASS__.";\n Method: ".__METHOD__.";\n Line: ".__LINE__."\n");
            $this->requestToMLByBaseLimit($scoringResult, $userID, $isAuto);
        }*/
    }

    public function scoringStateReportMessages(int $userID)
    {
        $buyer = Buyer::find($userID);
        $scoring = $buyer->scoringResult->last();
        $scoring_mini = $buyer->scoringResultMini->last();
        if (!$scoring && !$scoring_mini) {
            return [
                'check_approve_state' => 0,
                'check_approve_state_text' => __('Не найден'),
                'final_state' => 0,
                'final_state_text' => __('Не найден'),
                'total_state' => 0,
            ];
        }
        $actualScoring = $scoring ?? $scoring_mini;
        $scoring_answer = [
            'total_state' => $actualScoring->total_state,
        ];
        if ($scoring) {
            $scoring_answer += [
                'final_state' => $scoring->total_state,
                'final_state_text' => $this->makeScoringMessage($scoring->total_state),
            ];
        }
        if ($scoring_mini) {
            $scoring_answer += [
                'check_approve_state' => $scoring_mini->total_state,
                'check_approve_state_text' => $this->makeScoringMessage($scoring_mini->total_state),
            ];
        }
        return $scoring_answer;
    }

    public function scoringNewStateReport(?ScoringResult $scoring, ?ScoringResultMini $scoring_mini)
    {
        $actualScoring = $scoring ?? $scoring_mini;
        $resData = [
            'id' => $actualScoring->user_id,
            'name' => $actualScoring->buyer->fio,
            'final_limit' => $actualScoring->final_limit,
        ];
        $total = ScoringResult::STATE_USER_INFO_SUCCESS;
        if ($scoring_mini) {
            $total = $total < $scoring_mini->total_state ? $scoring_mini->total_state : $total;
            $resData['rows']['check_approve'] = [
                'key' => 'check_approve_state',
                'state' => $scoring_mini->total_state,
                'state_text' => $this->makeScoringMessage($scoring_mini->total_state),
                'state_error_message' => $scoring_mini->error_message,
            ];
        }
        if ($scoring) {
            $total = $total < $scoring->total_state ? $scoring->total_state : $total;
            $resData['rows']['final'] = [
                'key' => 'final_state',
                'state' => $scoring->total_state,
                'state_text' => $this->makeScoringMessage($scoring->total_state),
                'state_error_message' => $scoring->error_message,
            ];
        }

        $resData += [
            'total_state' => $total,
        ];
        return $resData;
    }

    public function scoringStateReport(ScoringResult $scoring)
    {
        return [
            'debts_by_royxat_state' => $scoring->debts_by_royxat_state,
            'debts_by_royxat_state_text' => $this->makeScoringMessage($scoring->debts_by_royxat_state),
            'scoring_state' => $scoring->scoring_state,
            'scoring_state_text' => $this->makeScoringMessage($scoring->scoring_state),
            'write_off_check_state' => $scoring->write_off_check_state,
            'write_off_check_state_text' => $this->makeScoringMessage($scoring->write_off_check_state),
            'overdue_by_infoscore_state' => $scoring->overdue_by_infoscore_state,
            'overdue_by_infoscore_state_text' => $this->makeScoringMessage($scoring->overdue_by_infoscore_state),
            'debts_by_mib_state' => $scoring->debts_by_mib_state,
            'debts_by_mib_state_text' => $this->makeScoringMessage($scoring->debts_by_mib_state),
            'debts_by_katm_state' => $scoring->debts_by_katm_state,
            'debts_by_katm_state_text' => $this->makeScoringMessage($scoring->debts_by_katm_state),
            'check_approve_state' => $scoring->check_approve_state,
            'check_approve_state_text' => $this->makeScoringMessage($scoring->check_approve_state),
            'scoring_by_tax_state' => $scoring->scoring_by_tax_state,
            'scoring_by_tax_state_text' => $this->makeScoringMessage($scoring->scoring_by_tax_state),
            'final_state' => $scoring->final_state,
            'final_state_text' => $this->makeScoringMessage($scoring->final_state),
            'total_state' => $scoring->total_state,
        ];
    }

    private function makeScoringMessage($state)
    {
        $text = __('На очереди');
        if ($state == ScoringResult::STATE_USER_INFO_SUCCESS) {
            $text = __('Успешное завершение скоринга');
        } else if ($state == ScoringResult::STATE_USER_INFO_NOT_SUCCESS) {
            $text = __('Не прошел скоринг');
        } else if ($state == ScoringResult::STATE_AWAIT_RESPONSE) {
            $text = __('В ожидании');
        } else if ($state == ScoringResult::STATE_FAILED_RESPONSE) {
            $text = __('Техническая ошибка, требуется повторный скоринг');
        }
        return $text;
    }

}
