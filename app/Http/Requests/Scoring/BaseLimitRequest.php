<?php

namespace App\Http\Requests\Scoring;

use App\Classes\Exceptions\MLRequestIDException;
use App\Classes\Exceptions\MLValidationException;
use App\Logging\ByUser\LoggerByUser;
use App\Models\ScoringResult;
use App\Models\ScoringResultMini;
use App\Services\Scoring\ScoringErrorService;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BaseLimitRequest extends LimitRequest
{

    public function rules()
    {
        return [
            'buyer_id' => 'required|integer|exists:users,id',
            'data.scoring_request_id' => 'required|integer|exists:scoring_results,id',
            'data.claim_id' => 'required|string',
            'data.approved' => 'required|boolean',
        ];
    }

    /**
     * @throws MLRequestIDException
     * @throws \Exception
     */
    protected function extFailedValidation(Validator $validator, $response, LoggerByUser $logger)
    {
        if (!isset($response['data'])) {
            Log::channel('scoring_steps_logs')->info("Не найден элемент data;\n Class: ".__CLASS__.";\n Method: ".__METHOD__.";\n Line: ".__LINE__."\n");
            return response()
                ->json(ScoringErrorService::catchingNoRequestIDException(
                    $logger,
                    new HttpException(422,"Произошла ошибка. Не найден эемент scoring_request_id"),
                    $this),
                    201);
        }
        $scoring = ScoringResult::find($response['data']['scoring_request_id']);
        if (!$scoring) {
            Log::channel('scoring_steps_logs')->info("Произошла ошибка. Не найден элемент scoring_request_id в таблице scoring_results;\n Class: ".__CLASS__.";\n Method: ".__METHOD__.";\n Line: ".__LINE__."\n");
            return response()
                ->json(ScoringErrorService::catchingNoRequestIDException(
                    $logger,
                    new HttpException(422,"Произошла ошибка. Не найден эемент scoring_request_id"),
                    $this),
                    201);
        }

        $this->failOnErrorCode($response, $scoring, $logger);
        $scoring->checkApproveFailed();
        return response()
            ->json(ScoringErrorService::catchingMLValidationException(
                $scoring,
                $logger,
                new MLValidationException("Произошла ошибка. Запрос не прошёл валидацию", $validator),
                $this),
                400);
    }

}
