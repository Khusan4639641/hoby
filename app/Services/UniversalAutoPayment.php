<?php

namespace App\Services;

use App\Classes\Autopay\AutopayAccount;
use App\Classes\Autopay\UZCARD\AutopayTransactionsHistoryRequest;
use App\Classes\Universal\Autopayment\Debtors;
use App\Helpers\SmsHelper;
use App\Helpers\TelegramInformer;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class UniversalAutoPayment
{

    const AUTOPAY_LAST_REFRESH = 'AUTOPAY_LAST_REFRESH';
    const AUTOPAY_ACTIVATE_DAY = 'AUTOPAY_ACTIVATE_DAY';
    const DAYS_COUNT = 2;

    private $run = true;

    private function convertAmountToReal($amount)
    {
        return $amount / 100;
    }

    /**
     * @throws \Exception
     */
    private function sepItemTransactionID(array $item)
    {
        if (isset($item['id'])) {
            return $item['id'];
        }
        if (isset($item['ext'])) {
            return $item['ext'];
        }
        throw new \Exception('Элемент id и элемент ext не найдены');
    }

    /**
     * @throws \Exception
     */
    private function sepIsCancelledTransaction(array $item)
    {
        if (!isset($item['tranType'])) {
            throw new \Exception('Элемент tranType не найден');
        }
        return $item['tranType'] == 'REVERSAL';
    }

    /**
     * @throws \Exception
     */
    private function sepItemAmount(array $item)
    {
        if (!isset($item['amount'])) {
            throw new \Exception('Элемент amount не найден');
        }
        return $item['amount'];
    }

    /**
     * @throws \Exception
     */
    private function sepItemExtContractID(array $item)
    {
        if (!isset($item['contractId'])) {
            throw new \Exception('Элемент contractId не найден');
        }
        return $item['contractId'];
    }

    private function sendSmsMessage(string $phone, float $debit)
    {
        $callCenterPhone = callCenterNumber(2);
        $textUz = __("Sizning kartangizdan qarzdorlik bo'yicha :debit so'm yechib olindi. Tel: :callCenterPhone", compact('debit', 'callCenterPhone'));
//        $textRu = __("S vashey karti spisan dolg v razmere :debit sum. Tel: :callCenterPhone", compact('debit', 'callCenterPhone'));
//        SmsHelper::sendSms($phone, $textUz . " / " . $textRu);
        SmsHelper::sendSms($phone, $textUz);
    }

    public function lastRefresh()
    {
        return Setting::getParam(UniversalAutoPayment::AUTOPAY_LAST_REFRESH);
    }

    public function autopayActivateDay()
    {
        return Setting::getParam(UniversalAutoPayment::AUTOPAY_ACTIVATE_DAY);
    }

    private function getTransactionsData(int $iteration): array
    {
        $days = ($iteration * self::DAYS_COUNT);
        $date = date('Y-m-d', strtotime('-' . $days . ' day'));
        $dateTwoDayAgo = date('d-m-Y', strtotime('-' . ($days + self::DAYS_COUNT) . ' day'));
        try {
            Log::channel('universal')->info('Период: с ' . $date . ' по ' . $dateTwoDayAgo);
            $paymentsHistory = new AutopayTransactionsHistoryRequest($dateTwoDayAgo, $date);
            $response = $paymentsHistory->execute()->response();
            $responseData = $response['result'];
        } catch (\Exception $e) {
            Log::channel('universal')->info('Запрос к сервису отработал не удовлетворительно',
                [
                    'message' => $e->getMessage(),
                    'response_status' => $paymentsHistory->status(),
                    'response' => $paymentsHistory->response()
                ]);
            $telegramBot = new TelegramInformer(
                env('MONITORING_REPORT_TELEGRAM_BOT_TOKEN'),
                env('MONITORING_REPORT_CHAT_ID')
            );
            $telegramBot->line('<b>Запрос к сервису автопей</b>');
            $telegramBot->line('Не удалось получить данные за период: с ' . $date . ' по ' . $dateTwoDayAgo);
            $telegramBot->send();
            return [];
        }
        return $responseData;
    }

    public function getPayments()
    {

        try {
            Log::channel('universal')->info('Старт зачисления транзакций');

            $payments = $this->getTransactionsData(0);

            $i = 0;
            while (count($payments) > 0) {
                if (!$this->run) {
                    break;
                }

                $payments = $this->getTransactionsData($i);

                foreach ($payments as $item) {

                    try {
                        $transactionID = $this->sepItemTransactionID($item);
                        $extContractID = $this->sepItemExtContractID($item);
                        $amount = $this->convertAmountToReal($this->sepItemAmount($item));

                        if (AutopayAccount::isExistTransaction($transactionID)) {
                            Log::channel('universal')->info('Транзакция существует в БД. Цикл остановлен', compact('transactionID'));
                            $this->run = false;
                        }
                        if (!$this->run) {
                            break;
                        }

                        if ($this->sepIsCancelledTransaction($item)) {
                            continue;
                        }

                    } catch (\Exception $e) {
                        Log::channel('universal')->info('Не удалось получить элемент',
                            ['message' => $e->getMessage(), 'response' => $item]);
                        $telegramBot = new TelegramInformer(
                            env('MONITORING_REPORT_TELEGRAM_BOT_TOKEN'),
                            env('MONITORING_REPORT_CHAT_ID')
                        );
                        try {
                            $telegramBot->line('<b>Запрос к сервису автопей</b>');
                            $telegramBot->line('Не удалось обработать Autopay транзакцию');
                            $telegramBot->line(json_encode($item, JSON_THROW_ON_ERROR));
                            $telegramBot->send();
                        } catch (\Throwable $e) {
                            Log::channel('universal')->info('Не возможно декодировать JSON');
                            $telegramBot->line('Не возможно декодировать JSON');
                            $telegramBot->line($e->getMessage());
                            $telegramBot->send();
                        }
                        continue;
                    }

                    Log::channel('universal')->info('Обработка транзакции', compact('transactionID'));

                    $uDebtor = AutopayAccount::findByExtContractID($extContractID);
                    if ($uDebtor) {
                        $buyer = $uDebtor->user;
                        if ($buyer) {
                            $this->sendSmsMessage($buyer->phone, $amount);

                            $autopayAccount = new AutopayAccount($buyer, $uDebtor);
                            $autopayAccount->addPaymentTransaction($transactionID, '', $amount);
                        } else {
                            Log::channel('universal')->error('Покупатель не найден в БД. Данные транзакции', $item);
                        }
                    } else {
                        Log::channel('universal')->error('Должник не найден в БД. Данные транзакции', $item);
                    }
                }
                $i++;


            }

            Setting::setParam(UniversalAutoPayment::AUTOPAY_LAST_REFRESH, now());

            Log::channel('universal')->info('Финиш зачисления транзакций');

        } catch (\Exception $e) {
            Log::channel('universal')->info('Произошла ошибка', ['message' => $e->getMessage()]);
        }

    }

    /*
     * @todo deprecated
     */
    public function registerUniversalDebtors()
    {
        $pageSize = 1000;

        $debtorsResponse = new Debtors(1, 0);
        $debtors = $debtorsResponse->execute()->response();
        $i = 0;
        while (count($debtors['result']) > 0) {
            $debtorsResponse = new Debtors($pageSize, $i);
            $debtors = $debtorsResponse->execute()->response();
            $debtorsResult = $debtors['result'];
            foreach ($debtorsResult as $debtor) {
                $buyer = AutopayAccount::findBuyerByPassport($debtor['passport_id'], $debtor['client_id']);
                if ($buyer) {
                    $uDebtor = AutopayAccount::findByBuyerID($buyer->id);
                    if (!$uDebtor) {
                        $universalDebtor = AutopayAccount::makeDebtor($buyer->id, $this->convertAmountToReal($debtor['current_debit']));
                        $userID = $buyer->id;
                        $debtorID = $universalDebtor->id;
                        Log::channel('universal')->info('Должник зарегистрирован в БД', compact('userID', 'debtorID'));
                    } else {
                        $userID = $uDebtor->user_id;
                        $debtorID = $uDebtor->id;
                        Log::channel('universal')->info('Такой должник зарегистрирован в БД', compact('userID', 'debtorID'));
                    }
                } else {
                    Log::channel('universal')->error('Покупатель не найден в БД. Данные покупателя', $debtor);
                }
            }
            $i++;
        }
    }

}
