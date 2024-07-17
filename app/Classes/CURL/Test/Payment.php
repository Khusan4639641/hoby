<?php

namespace App\Classes\CURL\test;

use App\Helpers\CardHelper;
use App\Models\CardLog;
use App\Models\PaymentLog;
use App\Models\Payment as PaymentModel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class Payment extends Basetest
{
    public function __construct(string $cardNumber, int $cardId, int $userId, int $type, $sum)
    {
        parent::__construct();
        $this->cardNumber = $cardNumber;
        $this->cardId     = $cardId;
        $this->userId     = $userId;
        $this->type       = $type;
        $this->sum        = $sum;
        $this->method     = 'payment';
    }


    public function accountReplenishment(string $cardNumber, string $cardValidDate, int $type, $sum)
    {
        $id = 'test_' . uniqid(rand(), 1);
        $this->url = $this->url . 'v1/rpc/payments';
        $this->makeRequest($this->method);
        $this->addParamToRequest('id', $id);
        $this->addParamsByKey(self::getEposes($type));
        $this->addParamsByKey
        ([
            'pan'      => $cardNumber,
            'expire'   => $cardValidDate, // последовательность: год месяц
            'amount'   => $sum * 100,
        ]);

        Log::channel('payment')->info(print_r($this, 1));

        $this->execute();
        $result = $this->response();

        Log::channel('payment')->info(print_r($result, 1));

        self::recordResult($result);

        return $result;
    }

    public static function getEposes(int $type) : array
    {
        switch ($type)
        {
            case 1: // UZCARD
                $eposes = ['merchant' => Config::get('test.test_api_uzcard_merchant'), 'terminal' => Config::get('test.test_api_uzcard_terminal')];
                break;
            case 2: // HUMO
                $eposes = ['merchant' => Config::get('test.test_api_humo_merchant'), 'terminal' => Config::get('test.test_api_humo_terminal')];
                break;
        }
        return $eposes;
    }

    public function recordResult($result) : void
    {
        if(isset($result))
        {
            if($result['status'] === true && $result['result']['payment']['id'] && isset($result['result']['payment']['uuid']))
            {
                $payment = new PaymentModel();
                $payment->type           = 'user';
                $payment->card_id        = $this->cardId;
                $payment->amount         = $this->sum;
                $payment->user_id        = $this->userId;
                $payment->payment_system = CardHelper::checkTypeCard($this->cardNumber)['name'];
                $payment->transaction_id = $result['result']['payment']['id'];
                $payment->uuid           = $result['result']['payment']['uuid'];
                $payment->status         = 1;
                $payment->save();

                $paymentLog = new PaymentLog();
                $paymentLog->request    = json_encode($this->requestBody);
                $paymentLog->response   = json_encode($result);
                $paymentLog->status     = 1;
                $paymentLog->payment_id = $payment->id;
                $paymentLog->save();
            }
            else if($result['status'] === false)
            {
                $cardLog = new CardLog();
                $cardLog->user_id  = $this->userId;
                $cardLog->card_id  = $this->cardId;
                $cardLog->method   = $this->method;
                $cardLog->request  = json_encode($this->requestBody);
                $cardLog->response = json_encode($result);
                $cardLog->code     = $result['error']['code'] ?? null;
                $cardLog->save();
            }
            else
            {
                $cardLog = new CardLog();
                $cardLog->user_id  = $this->userId;
                $cardLog->card_id  = $this->cardId;
                $cardLog->method   = $this->method;
                $cardLog->request  = json_encode($this->requestBody);
                $cardLog->response = json_encode($result);
                $cardLog->code     = (int)$result['status'] ?? 500;
                $cardLog->save();
            }
        }
        else
        {
            $cardLog = new CardLog();
            $cardLog->user_id  = $this->userId;
            $cardLog->card_id  = $this->cardId;
            $cardLog->method   = $this->method;
            $cardLog->request  = json_encode($this->requestBody);
            $cardLog->response = 'server error 500';
            $cardLog->code     = 500;
            $cardLog->save();
        }
    }
}
