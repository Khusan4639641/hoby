<?php

namespace App\Services\Payment;

use App\Models\DetailPayment;
use App\Services\API\V3\BaseService;
use App\Services\resusBank\Config\resusBankConfigContract;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DetailPaymentService extends BaseService
{

    public ApelsinPaymentService $apelsin_payment_service;

    public resusBankConfigContract $resusBankConfigContract;

    public function __construct()
    {
        $this->apelsin_payment_service = new ApelsinPaymentService;
        $this->resusBankConfigContract = app()->make(resusBankConfigContract::class);
    }

    public function makeDetailTransaction(int $company_id, int $amount, string $account, string $mfo, string $name, string $detail = ''): array
    {

        $sender_account = $this->resusBankConfigContract->getAccountNumber();
        $sender_name = $this->resusBankConfigContract->getSenderName();
        $sender_mfo = $this->resusBankConfigContract->getAccountMFO();

        $method = "receipt.pay.requisite";

        $rand_pre_request = 'sendmoney' . time();

        $record = new DetailPayment;
        $record->company_id = $company_id;
        $record->receipt_id = $rand_pre_request;
        $record->ext_id = null;
        $record->amount = $amount;
        $record->sender_account = $sender_account;
        $record->sender_name = $sender_name;
        $record->sender_mfo = $sender_mfo;
        $record->payment_detail = $detail;
        $record->payment_type = 0;
        $record->payment_state = 0;
        $record->receiver_account = $account;
        $record->receiver_name = $name;
        $record->receiver_mfo = $mfo;
        $record->payment_at = null;
        $record->status = DetailPayment::CREATED_STATUS;
        $record->save();

        Log::channel('apelsin_detail_payments')->info('REQUEST_TRANSACTION ' . json_encode($record->toArray()));


        $request_arr = [
            'jsonrpc' => '2.0',
            'id' => $record->receipt_id,
            'method' => $method,
            'params' => (object)[
                'amount' => $record->amount * 100,
                'ext_id' => $record->receipt_id,
                'sender' => (object)[
                    'number' => $sender_account,
                    'mfo' => $sender_mfo,
                ],
                'payment_detail' => [
                    'number' => $record->receiver_account,
                    'mfo' => $record->receiver_mfo,
                    'name' => $record->receiver_name,
                    'details' => $record->payment_detail,
                    'sender_name' => $record->sender_name,
                ],
            ]
        ];

        $httpRequest = $this->apelsin_payment_service->send($request_arr);
        $httpRequest->body();
        $response = $httpRequest->json();
        Log::channel('apelsin_detail_payments')->info('RESPONSE_TRANSACTION ' . json_encode($response));

        if ($response['error'] && empty($response['result'])) {
            $error_code = $response['error']['code'];
            $error_message = $response['error']['message'];
            switch ($error_code) {
                case -31609:
                    $error_message = 'Операция запрещена (В таких случаях чек не создаются) Данный тип операции для вас не открыто';
                    break;
                case -31613:
                    $error_message = 'Идентификатор с таким значением уже существует';
                    break;
                case -31611:
                    $error_message = 'Счет отправителя не найден или не прикреплен к логину';
                    break;
            }

            $record->status = DetailPayment::ERROR_STATUS;
            $record->payment_detail = $error_message;
            $record->payment_at = Carbon::now();
            $record->update();

            return ['status' => 'error', 'message' => $error_message];
        }

        $response_receipt = $response['result']['receipt'];
        $response_details = $response_receipt['details'];

        $record->ext_id = $response_receipt['id'];
        $record->payment_state = $response_receipt['state'];
        $record->payment_type = $response_receipt['type'];
        $record->payment_detail = $response_details['details'];
        $record->payment_memorial = $response_details['memorial'];
        $record->payment_at = empty($response_receipt['pay_date']) ? null : Carbon::parse($response_receipt['pay_date'] / 1000)->addHour(5);

        if (!empty($response_receipt['error'])) {
            $record->status = DetailPayment::ERROR_STATUS;
            $record->save();
            return ['status' => 'error', 'message' => $response_receipt['error']];
        }

        $record->status = DetailPayment::SUCCESS_STATUS;
        $record->save();
        return ['status' => 'success', 'message' => 'success'];


    }


    public function getConfig(): array
    {

        $request_arr = [
            'jsonrpc' => '2.0',
            'id' => 'checkbalance',
            'method' => 'account.balance',
            'params' => (object)[
                'number' => $this->resusBankConfigContract->getAccountNumber(),
                'mfo' => $this->resusBankConfigContract->getAccountMFO(),
            ]
        ];

        $result = [
            "number" => $this->resusBankConfigContract->getAccountNumber(),
            "mfo" => $this->resusBankConfigContract->getAccountMFO(),
            "name" => $this->resusBankConfigContract->getSenderName(),
            "balance" => 0,
        ];


        $httpRequest = $this->apelsin_payment_service->send($request_arr);
        $httpRequest->body();
        $httpResponse = $httpRequest->json();

        if (!empty($httpResponse['result'])) {
            $result['balance'] = $httpResponse['result']['account']['balance'];
        }

        return $result;

    }


}
