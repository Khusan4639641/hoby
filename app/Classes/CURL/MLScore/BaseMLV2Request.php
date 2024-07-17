<?php

namespace App\Classes\CURL\MLScore;

use App\Classes\Exceptions\MLException;
use App\Facades\GradeScoring;
use App\Models\ScoringResult;
use App\Models\ScoringResultMini;
use Illuminate\Support\Facades\Log;

abstract class BaseMLV2Request extends BaseMLWithTokenRequest
{

    public function init(): void
    {
        $this->baseUrl = config('test.ml.v2.url');
    }

    public function addUserParam($key, $value)
    {
        $user = [$key => $value];
        if (isset($this->requestBody['user'])) {
            $user = $this->requestBody['user'] + $user;
        }
        $this->addParamByKey('user', $user);
    }

    /**
     * @throws MLException
     */
    protected function updateStatus(): void
    {
        $this->state = self::FAILED;
        $data = $this->response()->json();
        $request = $this->requestBody;

        Log::channel('scoring_steps_logs')->info("Пришедшие данные от ML в callback", $data);

        if($this->responseBody->successful() && isset($data['data']['is_base_start']) && $data['data']['is_base_start']){
            $scoringResult = ScoringResultMini::find($data['data']['scoring_request_id']);
            $scoringResult->totalAwait();
            $body = [];
            if ($this->responseBody !== null) {
                $body = $this->responseBody->json();
            }
            throw new MLException(
                "Идет процесс base скоринга",
                $this->url(),
                $this->requestBody,
                $body ?: [],
                $this->responseBody->status()
            );
        }elseif ($this->responseBody->successful() && isset($data['data']['is_extended_start']) && $data['data']['is_extended_start']){
            $scoringResult = ScoringResult::find($data['data']['scoring_request_id']);
            $scoringResult->totalAwait();
            $body = [];
            if ($this->responseBody !== null) {
                $body = $this->responseBody->json();
            }
            throw new MLException(
                "Идет процесс extended скоринга",
                $this->url(),
                $this->requestBody,
                $body ?: [],
                $this->responseBody->status()
            );
        }

        /*if($this->responseBody->successful() && $data['data']['is_base_start']){
            $scoringResult = ScoringResultMini::find($data['data']['scoring_request_id']);
            $scoringResult->totalAwait();
            $body = [];
            if ($this->responseBody !== null) {
                $body = $this->responseBody->json();
            }
            throw new MLException(
                "Идет процесс base скоринга",
                $this->url(),
                $this->requestBody,
                $body ?: [],
                $this->responseBody->status()
            );
        }*/

        if($this->responseBody->status() == 422 && $data['error'] != null){
            $scoringResult = ScoringResultMini::find($data['data']['scoring_request_id']);
            $scoringResult->totalFailed();
            $body = [];
            if ($this->responseBody !== null) {
                $body = $this->responseBody->json();
            }
            throw new MLException(
                "Ошибка валидации на стороне ML",
                $this->url(),
                $this->requestBody,
                $body ?: [],
                $this->responseBody->status()
            );
        }

        /*if($this->responseBody->successful() && $data['data']['is_extended_start']){
            $scoringResult = ScoringResultMini::find($data['data']['scoring_request_id']);
            $scoringResult->totalAwait();
            $body = [];
            if ($this->responseBody !== null) {
                $body = $this->responseBody->json();
            }
            throw new MLException(
                "Идет процесс extended скоринга",
                $this->url(),
                $this->requestBody,
                $body ?: [],
                $this->responseBody->status()
            );
        }*/

        if($data['error'] != null){
            $scoringResult = ScoringResult::find($data['data']['scoring_request_id']);
            $scoringResult->totalFailed();
            Log::channel('scoring_steps_logs')->info("Смена статуса на total_state = 4", $data);
            if($data['error']['code'] == 100400){
                GradeScoring::initMiniScoring($scoringResult->user_id, false);
            }else{
                $body = [];
                if ($this->responseBody !== null) {
                    $body = $this->responseBody->json();
                }
                throw new MLException(
                    $data['error']['error_message'],
                    $this->url(),
                    $this->requestBody,
                    $body ?: [],
                    $this->responseBody->status()
                );
            }
        }

        if (!$this->responseBody->successful()) {
            $body = [];
            if ($this->responseBody !== null) {
                $body = $this->responseBody->json();
            }
            //Log::channel('scoring_steps_logs')->info("Ошибка: Ответ от сервиса не удовлетворителен; Url: ".$this->url()."; Тело запроса: ".$body."; Тело ответа: ".$this->responseBody."; Class: ".__CLASS__."; Method: ".__METHOD__."; Line: ".__LINE__);
            throw new MLException(
                "Ответ от сервиса не удовлетворителен",
                $this->url(),
                $this->requestBody,
                $body ?: [],
                $this->responseBody->status()
            );
        }
        $this->state = self::SUCCESS;
    }
}

