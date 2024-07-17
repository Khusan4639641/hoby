<?php

namespace App\Http\Requests\Scoring;

use App\Classes\Exceptions\MLRequestIDException;
use App\Classes\Exceptions\MLValidationException;
use App\Logging\ByUser\LoggerByUser;
use App\Models\ScoringResultMini;
use App\Services\Scoring\ScoringErrorService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MiniLimitRequest extends LimitRequest
{

    public function rules()
    {
        return [
            'buyer_id' => 'required|integer|exists:users,id',
            'data.scoring_request_id' => 'required|integer|exists:scoring_results,id',
            //'data.name' => 'required|string',
            //'data.surname' => 'required|string',
            //'data.patronymic' => 'required|string',
            //'data.gender' => 'required|integer',
            //'data.birth_date' => 'required|date',
//            'data.issue_doc_date' => 'required',
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
            return response()
                ->json(ScoringErrorService::catchingNoRequestIDException(
                    $logger,
                    new HttpException(422,"Произошла ошибка. Не найден эемент scoring_request_id"),
                    $this),
                    201);
        }
        $scoring = ScoringResultMini::find($response['data']['scoring_request_id']);
        if (!$scoring) {
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
