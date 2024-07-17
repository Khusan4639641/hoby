<?php

namespace App\Classes\Scoring;

use App\Helpers\EncryptHelper;
use App\Models\CardScoringRequestLog;
use Illuminate\Support\Facades\Log;

class ScoringRequestLog
{
    private $buyer_id, $card_number, $card_type, $request, $response, $request_type;

    public function __construct(int $buyer_id, string $card_number, string $card_type, array $request, array $response, string $request_type)
    {
        $this->buyer_id = $buyer_id;
        $this->card_number = $card_number;
        $this->card_type = $card_type;
        $this->request = $request;
        $this->response = $response;
        $this->request_type = $request_type;

        $this->setScoringRequestData();
    }

    private function setScoringRequestData()
    {
        $card_encrypt = EncryptHelper::encryptData($this->request['params']['card_number']);
        $this->request['params']['card_number'] = $card_encrypt;

        $cardScoringRequest = new CardScoringRequestLog();
        $cardScoringRequest->user_id = $this->buyer_id;
        $cardScoringRequest->card_hash = md5($this->card_number);
        $cardScoringRequest->card_type = $this->card_type;
        $cardScoringRequest->request_type = $this->request_type;
        $cardScoringRequest->request = json_encode($this->request);
        $cardScoringRequest->response = json_encode($this->response);

        if($cardScoringRequest->save()){
            Log::channel('cards')->info('Added to card_scoring_request_logs table: BUYER ID - '.$this->buyer_id.', CARD - '.$this->card_number);
        }else{
            Log::channel('cards')->info('Save error to card_scoring_request_logs table: BUYER ID - '.$this->buyer_id.', CARD - '.$this->card_number);
        }

    }
}