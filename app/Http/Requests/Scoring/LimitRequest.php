<?php

namespace App\Http\Requests\Scoring;

use App\Logging\ByUser\LoggerByUser;
use App\Models\Buyer;
use App\Models\ScoringResult;
use App\Models\User;
use App\Services\GradeScoringService;
use App\Services\Scoring\ScoringErrorService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;
use function GuzzleHttp\Psr7\_caseless_remove;

abstract class LimitRequest extends FormRequest
{

    abstract protected function extFailedValidation(Validator $validator, $response, LoggerByUser $logger);

    protected function prepareForValidation()
    {
        parent::prepareForValidation();
        $this->merge(['buyer_id' => $this->route('buyerID')]);
    }

    /**
     * @throws \Exception
     */
    protected function failOnErrorCode(array $response, ScoringResult $scoring, LoggerByUser $logger)
    {

        Log::channel('scoring_steps_logs')->info("Обработка ошибок от ML;\n User_id: {$this->route('buyerID')};\n Class: ".__CLASS__.";\n Method: ".__METHOD__.";\n Line: ".__LINE__."\n");
        if (isset($response['error'])) {
            if (isset($response['error']['code'])) {
                $buyer = Buyer::find($this->route('buyerID'));
                if($buyer->contracts->whereIn('status', [1,3,4])->count() == 0){
                    //if($response['error']['code'] != ScoringErrorService::CODE_BASE_SCORING_IS_OUTDATED){
                    //   GradeScoringService::initMiniScoring($this->route('buyerID'));
                    //}else{
                    $status = ScoringErrorService::buyerBehaviorState($response['error']['code'], $buyer);
                    //$status = ScoringErrorService::buyerBehaviorState($response['error']['code']);
                    if ($status === -1) {
                        $scoring->checkApproveFailed();
                        $code = $response['error']['code'];
                        return response()
                            ->json(ScoringErrorService::catchingException($scoring, $logger, new HttpException(201, "Код ошибки ($code) неизвестен, статус клиента не определён" ), $this), 201);
                    }
                    //$buyer = Buyer::find($this->route('buyerID'));
                    ScoringErrorService::scoringResultUpdate($response['error']['code'], $response['data']['scoring_request_id'], $this);
                    Log::channel('scoring_steps_logs')->info("Смена статуса пользователя на {$status};\n User_id: {$this->route('buyerID')};\n Class: ".__CLASS__.";\n Method: ".__METHOD__.";\n Line: ".__LINE__."\n");

                    if($response['error']['code'] != ScoringErrorService::CODE_CARD_INCOME_LESS_LIMIT && // здесь раньше стояло логическле ИЛИ
                        $response['error']['code'] != ScoringErrorService::CODE_CARD_DATA_NOT_FOUND &&
                        $response['error']['code'] != ScoringErrorService::CODE_BASE_SCORING_IS_OUTDATED)
                    {
                        $buyer->status = $status;
                    }

                    if($response['error']['code'] == ScoringErrorService::CODE_ROYXAT_HAVE_DEBTS ||
                        $response['error']['code'] == ScoringErrorService::CODE_DEBTS_MORE_550 ||
                        $response['error']['code'] == ScoringErrorService::CODE_AGE_LESS_21 ||
                        $response['error']['code'] == ScoringErrorService::CODE_OVERDUE_PAYMENT_5_MILLIONS ||
                        $response['error']['code'] == ScoringErrorService::CODE_LOMBARD_CONTRACTS_MORE_2 ||
                        $response['error']['code'] == ScoringErrorService::CODE_OVERDUE_DAYS_MORE_60 ||
                        $response['error']['code'] == ScoringErrorService::CODE_BASE_SCORING_IS_OUTDATED
                    )
                    {
                        $scoring->totalNotSuccess();
                        $buyer->status = User::KYC_STATUS_BLOCKED;
                        $buyer->save();
                        Log::channel('scoring_steps_logs')->info("Смена total_state на 2;\n User_id: {$this->route('buyerID')};\n Class: ".__CLASS__.";\n Method: ".__METHOD__.";\n Line: ".__LINE__."\n");
                        return response()
                            ->json(ScoringErrorService::catchingWithNoTotalFailedChangeException($scoring, $logger, new HttpException(201, $response['error']['error_message']), $this), 201);
                    }

                    if($response['error']['code'] == ScoringErrorService::CODE_GNK_INN_IN_BLACKLIST){
                        $buyer->black_list = Buyer::IN_BLACK_LIST;
                        $buyer->black_list_date = Carbon::now();
                    }
                    if($response['error']['code'] != ScoringErrorService::CODE_INTERNAL_SERVER_ERROR  // здесь раньше стояло логическле ИЛИ
                        && $response['error']['code'] != ScoringErrorService::CODE_INTEGRATION_EXCEPTION &&
                        $response['error']['code'] != ScoringErrorService::CODE_HIGH_TASK_STEP_RETRIES)
                    {
                        $buyer->save();
                    }
                    //}
                }
            } else {
                if($response['error']['code'] == ScoringErrorService::CODE_ROYXAT_HAVE_DEBTS ||
                    $response['error']['code'] == ScoringErrorService::CODE_DEBTS_MORE_550 ||
                    $response['error']['code'] == ScoringErrorService::CODE_AGE_LESS_21 ||
                    $response['error']['code'] == ScoringErrorService::CODE_OVERDUE_PAYMENT_5_MILLIONS ||
                    $response['error']['code'] == ScoringErrorService::CODE_LOMBARD_CONTRACTS_MORE_2 ||
                    $response['error']['code'] == ScoringErrorService::CODE_OVERDUE_DAYS_MORE_60 ||
                    $response['error']['code'] == ScoringErrorService::CODE_CARD_INCOME_LESS_LIMIT ||
                    $response['error']['code'] == ScoringErrorService::CODE_HIGH_DEFAULT_PROBABILITY ||
                    $response['error']['code'] == ScoringErrorService::CODE_CARD_DATA_NOT_FOUND ||
                    $response['error']['code'] == ScoringErrorService::CODE_GNK_INN_IN_BLACKLIST)
                {
                    $scoring->totalNotSuccess();
                    return response()
                        ->json(ScoringErrorService::catchingWithNoTotalFailedChangeException($scoring, $logger, new HttpException(422, "Код ошибки error -> code не найден"), $this), 201);
                }else{
                    $scoring->checkApproveFailed();

                    Log::channel('scoring_steps_logs')->info("Код ошибки error -> ML code не найден;\n User_id: {$this->route('buyerID')};\n Class: ".__CLASS__.";\n Method: ".__METHOD__.";\n Line: ".__LINE__."\n");
                    return response()
                        ->json(ScoringErrorService::catchingException($scoring, $logger, new HttpException(422, "Код ошибки error -> code не найден"), $this), 201);
                }
                /*Log::channel('scoring_steps_logs')->info("Код ошибки error -> ML code не найден;\n User_id: {$this->route('buyerID')};\n Class: ".__CLASS__.";\n Method: ".__METHOD__.";\n Line: ".__LINE__."\n");
                return response()
                    ->json(ScoringErrorService::catchingException($scoring, $logger, new HttpException(422, "Код ошибки error -> code не найден"), $this), 201);*/
            }
            Log::channel('scoring_steps_logs')->info("На стороне ML сервиса произошла ошибка;\n User_id: {$this->route('buyerID')};\n Class: ".__CLASS__.";\n Method: ".__METHOD__.";\n Line: ".__LINE__."\n");
            if($response['error']['code'] == ScoringErrorService::CODE_ROYXAT_HAVE_DEBTS || //401
                $response['error']['code'] == ScoringErrorService::CODE_DEBTS_MORE_550 || //402
                $response['error']['code'] == ScoringErrorService::CODE_AGE_LESS_21 || //403
                $response['error']['code'] == ScoringErrorService::CODE_OVERDUE_PAYMENT_5_MILLIONS || //404
                $response['error']['code'] == ScoringErrorService::CODE_LOMBARD_CONTRACTS_MORE_2 || // 405
                $response['error']['code'] == ScoringErrorService::CODE_OVERDUE_DAYS_MORE_60 || // 407
                $response['error']['code'] == ScoringErrorService::CODE_CARD_INCOME_LESS_LIMIT || // 408
                $response['error']['code'] == ScoringErrorService::CODE_HIGH_DEFAULT_PROBABILITY || // 409
                $response['error']['code'] == ScoringErrorService::CODE_CARD_DATA_NOT_FOUND || // 410
                $response['error']['code'] == ScoringErrorService::CODE_GNK_INN_IN_BLACKLIST // 411

            )
            {
                $scoring->totalNotSuccess();
                return response()
                    ->json(ScoringErrorService::catchingWithNoTotalFailedChangeException($scoring, $logger, new HttpException(422, "Код ошибки error -> code не найден"), $this), 201);
            }else{
                $scoring->checkApproveFailed();
            }

            if($response['error']['code'] == ScoringErrorService::CODE_ROYXAT_HAVE_DEBTS ||
                $response['error']['code'] == ScoringErrorService::CODE_DEBTS_MORE_550 ||
                $response['error']['code'] == ScoringErrorService::CODE_AGE_LESS_21 ||
                $response['error']['code'] == ScoringErrorService::CODE_OVERDUE_PAYMENT_5_MILLIONS ||
                $response['error']['code'] == ScoringErrorService::CODE_LOMBARD_CONTRACTS_MORE_2 ||
                $response['error']['code'] == ScoringErrorService::CODE_OVERDUE_DAYS_MORE_60 ||
                $response['error']['code'] == ScoringErrorService::CODE_CARD_INCOME_LESS_LIMIT ||
                $response['error']['code'] == ScoringErrorService::CODE_HIGH_DEFAULT_PROBABILITY ||
                $response['error']['code'] == ScoringErrorService::CODE_CARD_DATA_NOT_FOUND ||
                $response['error']['code'] == ScoringErrorService::CODE_GNK_INN_IN_BLACKLIST)
            {
                $scoring->totalNotSuccess();
                return response()
                    ->json(ScoringErrorService::catchingWithNoTotalFailedChangeException($scoring, $logger, new HttpException(422, "Код ошибки error -> code не найден"), $this), 201);
            }
            return response()
                ->json(ScoringErrorService::catchingException($scoring, $logger, new HttpException(201, $response['error']['error_message']), $this),201);


        }
    }

    protected function failedValidation(Validator $validator)
    {
        $response = $validator->getData();
        if ($this->is('*/mini')) {
            $logger = new LoggerByUser(Buyer::find($this->route('buyerID')), 'scoring', 'mini');
        } else if ($this->is('*/base') || $this->is('*/extended')) {
            $logger = new LoggerByUser(Buyer::find($this->route('buyerID')), 'scoring', 'full');
        }
        if (!$logger) {
            \Log::channel('scoring')->error('Logger не найден');
            throw new HttpException(400, 'Внутренняя ошибка');
        }
        $this->extFailedValidation($validator, $response, $logger);
    }

}
