<?php

namespace App\Services\Scoring;

use App\Classes\Exceptions\MLException;
use App\Classes\Exceptions\MLValidationException;
use App\Logging\ByUser\LoggerByUser;
use App\Models\ScoringResult;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ScoringErrorService
{
    public const CODE_BASE_SCORING_IS_OUTDATED = 100400;
    public const CODE_ROYXAT_HAVE_DEBTS = 100401;
    public const CODE_DEBTS_MORE_550 = 100402;
    public const CODE_AGE_LESS_21 = 100403;
    public const CODE_OVERDUE_PAYMENT_5_MILLIONS = 100404;
    public const CODE_LOMBARD_CONTRACTS_MORE_2 = 100405;
    public const CODE_OVERDUE_DAYS_MORE_60 = 100407;
    public const CODE_CARD_INCOME_LESS_LIMIT = 100408;
    public const CODE_HIGH_DEFAULT_PROBABILITY = 100409;
    public const CODE_CARD_DATA_NOT_FOUND = 100410;
    public const CODE_GNK_INN_IN_BLACKLIST = 100411;
    public const CODE_INTERNAL_SERVER_ERROR = 100500;
    public const CODE_INTEGRATION_EXCEPTION = 100501;
    public const CODE_HIGH_TASK_STEP_RETRIES = 100502;

    /*static private $stateBehavior = [
        self::CODE_BASE_SCORING_IS_OUTDATED => User::KYC_STATUS_BLOCKED,
        self::CODE_ROYXAT_HAVE_DEBTS => User::KYC_STATUS_BLOCKED,
        self::CODE_DEBTS_MORE_550 => User::KYC_STATUS_BLOCKED,
        self::CODE_AGE_LESS_21 => User::KYC_STATUS_BLOCKED,
        self::CODE_OVERDUE_PAYMENT_5_MILLIONS => User::KYC_STATUS_BLOCKED,
        self::CODE_LOMBARD_CONTRACTS_MORE_2 => User::KYC_STATUS_BLOCKED,
        self::CODE_OVERDUE_DAYS_MORE_60 => User::KYC_STATUS_BLOCKED,
        self::CODE_CARD_INCOME_LESS_LIMIT => User::KYC_STATUS_EDIT,
        self::CODE_HIGH_DEFAULT_PROBABILITY => User::KYC_STATUS_VERIFY,
        self::CODE_GNK_INN_IN_BLACKLIST => User::KYC_STATUS_VERIFY,
        self::CODE_CARD_DATA_NOT_FOUND => User::KYC_STATUS_EDIT,
        self::CODE_INTERNAL_SERVER_ERROR => User::KYC_STATUS_UPDATE,
        self::CODE_INTEGRATION_EXCEPTION => User::KYC_STATUS_UPDATE,
        self::CODE_HIGH_TASK_STEP_RETRIES => User::KYC_STATUS_UPDATE,
    ];*/

    public static function buyerBehaviorState(int $code, $buyer): int
    {
        $status100400_100407 = User::KYC_STATUS_BLOCKED;

        $scoringResultMini = $buyer->scoringResultMini->last();

        $stateBehavior = [
            self::CODE_BASE_SCORING_IS_OUTDATED => $status100400_100407,
            self::CODE_ROYXAT_HAVE_DEBTS => $status100400_100407,
            self::CODE_DEBTS_MORE_550 => $status100400_100407,
            self::CODE_AGE_LESS_21 => $status100400_100407,
            self::CODE_OVERDUE_PAYMENT_5_MILLIONS => $status100400_100407,
            self::CODE_LOMBARD_CONTRACTS_MORE_2 => $status100400_100407,
            self::CODE_OVERDUE_DAYS_MORE_60 => $status100400_100407,

            self::CODE_CARD_INCOME_LESS_LIMIT => $buyer->settings->mini_limit > 0 ? User::KYC_STATUS_VERIFY : User::KYC_STATUS_UPDATE,
            self::CODE_HIGH_DEFAULT_PROBABILITY => ($buyer->settings->mini_limit > 0 && $scoringResultMini && $scoringResultMini->total_state == ScoringResult::STATE_USER_INFO_SUCCESS) ? User::KYC_STATUS_VERIFY : User::KYC_STATUS_UPDATE,
            //self::CODE_HIGH_DEFAULT_PROBABILITY => $buyer->settings->mini_limit > 0 ? User::KYC_STATUS_VERIFY : User::KYC_STATUS_UPDATE,
            self::CODE_GNK_INN_IN_BLACKLIST => $buyer->settings->mini_limit > 0 ? User::KYC_STATUS_VERIFY : User::KYC_STATUS_UPDATE,
            self::CODE_CARD_DATA_NOT_FOUND => $buyer->settings->mini_limit > 0 ? User::KYC_STATUS_VERIFY : User::KYC_STATUS_UPDATE,
            self::CODE_INTERNAL_SERVER_ERROR => User::KYC_STATUS_UPDATE,
            self::CODE_INTEGRATION_EXCEPTION => User::KYC_STATUS_UPDATE,
            self::CODE_HIGH_TASK_STEP_RETRIES => User::KYC_STATUS_UPDATE,
        ];

        return $stateBehavior[$code] ?? -1;
    }

    public static function catchingMLValidationException(
        ScoringResult         $scoring,
        LoggerByUser          $logger,
        MLValidationException $e,
        FormRequest           $request
    ): array
    {
        $logger->error($e->getMessage(), [
            'url' => $request->url(),
            'request' => $request->toArray(),
            'errors' => $e->errors(),
        ]);

        $error_message = '';

        if(isset($request->toArray()['error']['error_message'])){
            $error_message = $request->toArray()['error']['error_message'];
        }

        $scoring->errorMessage($error_message);

        /*$scoring->errorMessage($e->getMessage() . ' ' .
            json_encode([
                'url' => $request->url(),
                'request' => $request->toArray(),
                'errors' => $e->errors(),
            ], JSON_THROW_ON_ERROR));*/
        $scoring->finalFailed();
        throw $e;
    }

    public static function catchingMLBaseException(
        ScoringResult $scoring,
        LoggerByUser  $logger,
        MLException   $e
    ): array
    {
        $logger->error($e->getMessage(), [
            'url' => $e->urlText(),
            'code' => $e->getCode(),
            'request' => $e->requestArray(),
            'response' => $e->responseArray(),
        ]);

        $error_message = '';

        if(isset($e->requestArray()['error']['error_message'])){
            $error_message = $e->requestArray()['error']['error_message'];
        }

        $scoring->errorMessage($error_message);


        /*$scoring->errorMessage($e->getMessage() . ' ' .
            json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'url' => $e->urlText(),
                'code' => $e->getCode(),
                'request' => $e->requestArray(),
                'response' => $e->responseArray(),
            ], JSON_THROW_ON_ERROR));*/
        $scoring->finalFailed();
        throw $e;
    }

    public static function catchingException(
        ScoringResult $scoring,
        LoggerByUser  $logger,
        \Throwable    $e,
        FormRequest   $request
    ): array
    {
        $logger->error($e->getMessage(), [
            'url' => $request->url(),
            'request' => $request->toArray(),
            //'errors' => $e->getTrace(),
        ]);

        $error_message = '';

        if(isset($request->toArray()['error']['error_message'])){
            $error_message = $request->toArray()['error']['error_message'];
        }

        $scoring->errorMessage($error_message);

        /*$scoring->errorMessage($e->getMessage() . ' ' .
            json_encode([
                'url' => $request->url(),
                'request' => $request->toArray(),
                'errors' => $e->getTrace(),
            ], JSON_THROW_ON_ERROR));*/
        $scoring->finalFailed();
        throw $e;
    }

    public static function catchingWithNoTotalFailedChangeException(
        ScoringResult $scoring,
        LoggerByUser  $logger,
        \Throwable    $e,
        FormRequest   $request
    ): array
    {
        $logger->error($e->getMessage(), [
            'url' => $request->url(),
            'request' => $request->toArray(),
            //'errors' => $e->getTrace(),
        ]);

        $error_message = '';

        if(isset($request->toArray()['error']['error_message'])){
            $error_message = $request->toArray()['error']['error_message'];
        }

        $scoring->errorMessage($error_message);

        /*$scoring->errorMessage($e->getMessage() . ' ' .
            json_encode([
                'url' => $request->url(),
                'request' => $request->toArray(),
                'errors' => $e->getTrace(),
            ], JSON_THROW_ON_ERROR));*/
        $scoring->final_state = ScoringResult::STATE_USER_INFO_NOT_SUCCESS;
        $scoring->save();
        throw $e;
    }

    public static function catchingNoRequestIDException(
        LoggerByUser $logger,
        \Throwable   $e,
        FormRequest  $request
    ): array
    {
        $logger->error($e->getMessage(), [
            'url' => $request->url(),
            'request' => $request->toArray(),
            'errors' => $e->getTrace(),
        ]);
        throw $e;
    }

    public static function scoringResultUpdate($code, $scoring_request_id, $route){
        if ($route->is('*/extended')) {
            ScoringResult::where('id', $scoring_request_id)->where('type', 1)->update([
                'error_code' => $code,
            ]);
        }
    }

}
