<?php

namespace App\Helpers;

use App\Models\Contract;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\Saller;
use App\Models\SellerBonus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Exception;

class SellerBonusesHelper
{

    const ACCEPTABLE_DAYS_TO_PAY = 30;

    /**
     * Вычилсение коэффициента бонуса
     *
     * @param int $sellerID
     * @param float $productCost
     *
     * @return float
     */
    static public function calculateCoefficient(int $sellerID, float $productCost): float
    {
        try {
            $seller = Saller::find($sellerID);
            if ($seller) {
                $company = $seller->companyEmployer;
                if ($company) {
                    return $company->seller_coefficient * Config::get('test.seller_coefficient');
                } else {
                    throw new Exception("Не удалось определить компанию продавца!");
                }
            } else {
                throw new Exception("Не удалось найти продавца!");
            }
        } catch (\Exception $e) {
            $data = [
                'userID: ' . $sellerID,
                'amount: ' . $productCost,
            ];
            Log::channel('errors')->error('Ошибка при вычислении коэффициента бонуса | ' . implode(', ', $data) . ' | ' . $e->getMessage());
        }
        return 0;
    }

    /**
     * Вычисление суммы бонуса
     *
     * @param int $sellerID
     * @param float $productCost
     *
     * @return float
     */
    static public function calculateBonus(int $sellerID, float $productCost): float
    {
        $coefficient = self::calculateCoefficient($sellerID, $productCost);
        // ((Чист. цена) / 1.15) * ((Коэффициент) / 100)
        $bonus = (($productCost / NdsStopgagHelper::getActualNdsPlusOne()) * ($coefficient / 100));
        $bonus = round($bonus, 2);
        return $bonus;
    }

    /**
     * Пополнение счёта бонусов пользователя и добавление транзакции по бонусам
     *
     * @param string $type
     * @param string $paymentSystem
     * @param int $userID
     * @param float $amount
     *
     * @param int|null $transactionID
     * @param bool $status
     * @return Model
     *
     * @throws Exception
     */
    static private function newPaymentTransaction(string $type, string $paymentSystem, int $userID, float $amount, int $transactionID = null, bool $status = true): Model
    {
        $seller = Saller::find($userID);
        if ($seller) {
            $payment = new Payment();
            $payment->type = $type;
            $payment->payment_system = $paymentSystem;
            $payment->user_id = $userID;
            $payment->transaction_id = $transactionID;
            $payment->amount = $amount;
            $payment->status = (int)$status;
            if ($payment->save()) {
                if ($status) {
                    $isBonusAdded = $seller->addToBonusAccount($amount);
                    if (!$isBonusAdded) {
                        throw new Exception("Не удалось обработать бонусный счёт!");
                    }
                }
            } else {
                throw new Exception("Не удалось создать платёжную транзакцию!");
            }
        } else {
            throw new Exception("Не удалось найти бонусный счёт!");
        }
        return $payment;
    }

    /**
     * Баланс счёта бонусов
     *
     * @param int $sellerID
     *
     * @return float
     */
    static public function getBonusAccountBalance(int $sellerID)
    {
        $seller = Saller::find($sellerID);
        if ($seller) {
            return $seller->bonusAccount;
        } else {
            $data = [
                'userID: ' . $sellerID,
            ];
            Log::channel('errors')->error('Не удалось проверить бонусный счёт продавца | ' . implode(', ', $data) . ' | Продавец не найден!');
        }
        return 0;
    }

    /**
     * Проверка на разрешение оплаты бонусами. По истечении 30 дней с последней продажи, оплата бонусами блокируется
     *
     * @param int $sellerID
     *
     * @return bool
     */
    static public function isAcceptableToPay(int $sellerID): bool
    {
        return SellerBonus::where('seller_id', $sellerID)
                ->whereRaw('updated_at >= (\'' . date('Y-m-d H:i:s') . '\' - INTERVAL ' . self::ACCEPTABLE_DAYS_TO_PAY . ' DAY )')
                ->count() > 0;
    }

    /**
     * Проверка достаточности средств на счёте бонусов
     *
     * @param int $sellerID
     * @param float $amount
     *
     * @return bool
     */
    static public function isEnoughFunds(int $sellerID, float $amount): bool
    {
        return self::getBonusAccountBalance($sellerID) >= $amount;
    }

    /**
     * Регитсрация нового бонуса
     *
     * @param int $sellerID
     * @param int $contractID
     * @param float $bonusAmount
     *
     * @return bool
     */
    static public function registerBonus(int $sellerID, int $contractID, float $bonusAmount)
    {
        try {
            if (self::fillAccount($sellerID, $contractID, $bonusAmount, false)) {
                return true;
            } else {
                throw new Exception("Не удалось добавить бонус!");
            }
        } catch (\Exception $e) {
            $data = [
                'userID: ' . $sellerID,
                'contractID: ' . $contractID,
                'amount: ' . $bonusAmount,
            ];
            Log::channel('errors')->error('Ошибка при фиксировании бонусов продавцу | ' . implode(', ', $data) . ' | ' . $e->getMessage());
        }
        return false;
    }

    /**
     * Пополнение счёта бонусов
     *
     * @param int $sellerID
     * @param int $contractID
     * @param float $bonusAmount
     * @param bool $status
     *
     * @return bool
     */
    static private function fillAccount(int $sellerID, int $contractID, float $bonusAmount, bool $status = true)
    {
        try {
            $payment = self::newPaymentTransaction(Payment::PAYMENT_TYPE_FILL_ACCOUNT, Payment::PAYMENT_SYSTEM_PAYCOIN, $sellerID, $bonusAmount, null, $status);
            $contract = Contract::find($contractID);
            $sellerBonus = new SellerBonus();
            $sellerBonus->seller_id = $sellerID;
            $sellerBonus->contract_id = $contractID;
            $sellerBonus->amount = $bonusAmount;
            $sellerBonus->payment_id = $payment->id;
            $sellerBonus->coefficient = self::calculateCoefficient($sellerID, $contract->order->partner_total);
            $sellerBonus->status = $status;
            $sellerBonus->type = SellerBonus::BONUS_TYPE_FILL;
            if ($sellerBonus->save()) {
                return true;
            } else {
                throw new Exception("Не удалось начислить бонус!");
            }
        } catch (\Exception $e) {
            $data = [
                'userID: ' . $sellerID,
                'contractID: ' . $contractID,
                'amount: ' . $bonusAmount,
            ];
            Log::channel('errors')->error('Ошибка при начислении бонусов продавцу | ' . implode(', ', $data) . ' | ' . $e->getMessage());
        }
        return false;
    }

    /**
     * Активация бонуса
     *
     * @param int $contractID
     *
     * @return bool
     */
    static public function activateBonusByContract(int $contractID)
    {
        try {
            $bonus = SellerBonus::where('contract_id', $contractID)->first();
            if ($bonus) {
                if ($bonus->status != SellerBonus::BONUS_STATUS_ACTIVE) {
                    $bonus->status = SellerBonus::BONUS_STATUS_ACTIVE;
                    if ($bonus->save()) {
                        $payment = $bonus->payment;
                        $payment->status = Payment::PAYMENT_STATUS_ACTIVE;
                        if ($payment->save()) {
                            if ($bonus->seller->addToBonusAccount($payment->amount)) {
                                return true;
                            } else {
                                throw new Exception("Не удалось зачислить бонус на счёт!");
                            }
                        } else {
                            throw new Exception("Не удалось активировать транзакцию бонуса!");
                        }
                    } else {
                        throw new Exception("Не удалось активировать бонус!");
                    }
                } else {
                    throw new Exception("Бонус активен!");
                }
            } else {
                throw new Exception("Активируемый бонус не найден!");
            }
        } catch (\Exception $e) {
            $data = [
                'contractID: ' . $contractID,
            ];
            Log::channel('errors')->error('Ошибка при активации бонуса | ' . implode(', ', $data) . ' | ' . $e->getMessage());
        }
        return false;
    }

    /**
     * Активация бонусов
     *
     * @param int $contractID
     *
     * @return bool
     */
    static public function activateBonusesByContract(int $contractID)
    {
      $bonuses = SellerBonus::where('contract_id', $contractID)->get();

      $success = false;

      if (count($bonuses)) {
        foreach ($bonuses as $bonus) {
          try {
            if ($bonus->status != SellerBonus::BONUS_STATUS_ACTIVE) {
              $bonus->status = SellerBonus::BONUS_STATUS_ACTIVE;
              if ($bonus->save()) {
                $payment = $bonus->payment;
                $payment->status = Payment::PAYMENT_STATUS_ACTIVE;
                if ($payment->save()) {
                  if ($bonus->seller->addToBonusAccount($payment->amount)) {
                    $success = true;
                  } else {
                    throw new Exception("Не удалось зачислить бонус на счёт!");
                  }
                } else {
                  throw new Exception("Не удалось активировать транзакцию бонуса!");
                }
              } else {
                throw new Exception("Не удалось активировать бонус!");
              }
            } else {
              throw new Exception("Бонус активен!");
            }
          } catch (\Exception $e) {
            $data = [
              'contractID: ' . $contractID,
              'bonusID: ' . $bonus->id,
            ];
            Log::channel('errors')->error('Ошибка при активации бонуса | ' . implode(', ', $data) . ' | ' . $e->getMessage());
          }
        }
      }

      return $success;
    }

    /**
     * Возврат бонуса
     *
     * @param int $contractID
     *
     * @return bool
     */
    static public function refundByContract(int $contractID)
    {
      $bonuses = SellerBonus::where('contract_id', $contractID)->get();

      $success = false;

      if (count($bonuses)) {
        foreach ($bonuses as $bonus) {

          try {

            $sellerID = $bonus->seller_id;
            $amount = $bonus->amount;

            if ($bonus->activeFillPayment()->exists()) {
              $refundPayment = self::newPaymentTransaction(Payment::PAYMENT_TYPE_REFUND, Payment::PAYMENT_SYSTEM_PAYCOIN, $sellerID, (-$amount));
              if ($refundPayment) {
                $refundBonus = new SellerBonus();
                $refundBonus->seller_id = $sellerID;
                $refundBonus->contract_id = $contractID;
                $refundBonus->amount = (-$amount);
                $refundBonus->payment_id = $refundPayment->id;
                $refundBonus->coefficient = $bonus->coefficient;
                $refundBonus->status = SellerBonus::BONUS_STATUS_ACTIVE;
                $refundBonus->type = SellerBonus::BONUS_TYPE_REFUND;
                if ($refundBonus->save()) {
                  $success = true;
                } else {
                  throw new Exception("Не удалось создать возвращаемый бонус!");
                }
              } else {
                throw new Exception("Не удалось создать транзакцию возврата бонуса!");
              }
            } else {
              throw new Exception("Начисленная транзакция бонуса не найдена!");
            }

          } catch (\Exception $e) {
            $data = [
              'contractID: ' . $contractID,
              'bonusID: ' . $bonus->id,
            ];
            Log::channel('errors')->error('Ошибка при возврате бонусов | ' . implode(', ', $data) . ' | ' . $e->getMessage());
          }
        }
      }

      return $success;
    }

    /**
     * Оплата бонусами
     *
     * @param int $sellerID
     * @param int $transactionID
     * @param float $amount
     * @param string $requestLog
     * @param string $responseLog
     *
     * @return bool
     */
    static public function pay(int $sellerID, int $transactionID, float $amount, string $requestLog = '', string $responseLog = '')
    {
        try {
            if (self::getBonusAccountBalance($sellerID) >= $amount) {
                $payment = self::newPaymentTransaction(Payment::PAYMENT_TYPE_PAY, Payment::PAYMENT_SYSTEM_PAYCOIN, $sellerID, (-$amount), $transactionID);
                $paymentLog = new PaymentLog();
                $paymentLog->payment_id = $payment->id;
                $paymentLog->request = $requestLog;
                $paymentLog->response = $responseLog;
                $paymentLog->status = 1;
                if ($paymentLog->save()) {
                    return true;
                } else {
                    throw new Exception("Не удалось зафиксировать списываемую сумму в payment_logs!");
                }
            } else {
                throw new Exception("Недостаточно средств на бонусном счёту!");
            }
        } catch (\Exception $e) {
            $data = [
                'userID: ' . $sellerID,
                'transactionID: ' . $transactionID,
                'amount: ' . $amount,
            ];
            Log::channel('errors')->error('Ошибка при оплате с бонусного счёта | ' . implode(', ', $data) . ' | ' . $e->getMessage());
        }
        return false;
    }

}
