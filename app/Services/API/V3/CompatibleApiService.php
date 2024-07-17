<?php

namespace App\Services\API\V3;

use App\Helpers\SellerBonusesHelper;
use App\Models\Buyer;
use App\Models\Company;
use App\Models\Contract;
use App\Models\GeneralCompany;
use App\Models\OrderProduct;
use App\Models\Payment;
use App\Rules\CountPhonesAmount;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Log;
use Validator;
use Illuminate\Support\Facades\Validator as RealValidator;

class CompatibleApiService extends BaseService
{

    public static function validatedSendContractSmsCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|integer',
            'phone' => 'required|numeric|digits:12|regex:/(998)[0-9]{9}/',
        ]);
        if ($validator->fails()) {
            return self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function validatedCheckContractSmsCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|integer',
            'phone' => 'required|numeric|digits:12|regex:/(998)[0-9]{9}/',
            'code' => 'required|numeric|digits:6',
        ]);
        if ($validator->fails()) {
            return self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function SendContractSmsCode(Request $request)
    {
        self::validatedSendContractSmsCode($request);
        $buyer = Buyer::where("phone", $request->phone)->first();
        if (!$buyer) {
            return self::handleError([__('auth.error_user_not_found')]);
        }
        $statuses = [Contract::STATUS_AWAIT_SMS,Contract::STATUS_AWAIT_VENDOR];
        $contract = Contract::where(['id' => $request->contract_id, 'user_id' => $buyer->id])->whereIn('status',$statuses)->first();
        if (!$contract) {
            Log::channel('contracts')->info("Контракт " . $request->contract_id . " не найден, смс код клиенту не отправлен  " . '$buyer->id ' . $buyer->id . '$request->phone ' . $request->phone);
            return self::handleError([__('api.contract_not_found')]);
        }
        if(GeneralCompany::MFO_COMPANY_ID === $contract->general_company_id) {
            return self::handleError([__('api.contract_not_be_activated')]);
        }
        $created_at = strtotime(Carbon::parse($contract->created_at)->addHours(5));
        $today = strtotime(Carbon::now()->addHours(5));
        $dif = ($today - $created_at);
        if ($dif > 3600) {    // 1 час
            return self::handleError([__('api.contract_out_of_date')]);
        }
        if (!$request->flag) {
            $msg = 'resusNasiya / :code - Shartnomani tasdiqlash kodi. Xaridingiz uchun rahmat! Tel: ' . callCenterNumber(2);
        } else {
            /*Пока не ясно как мобильщики будут парсить url строку,
             из-за этого нижняя строка закомментирована и ей на замену установленна динамическая ссылка*/
//            $link = 'https://test.uz/uz/contract/' . $contract->id;
            $link = 'https://test.page.link/test/';
            $msg = "resusNasiya / Shartnomani tasdiqlash uchun quyidagi havola orqali o'ting " . $link;
        }
        $result = LoginService::sendSmsCode($request->phone, true, $msg);
        if ($result['code'] === 1) {
            Log::channel('contracts')->info("Отправка смс кода клиенту о создании контракта");
            Log::channel('contracts')->info("Sms code: " . $request->phone . ': ' . $msg);
        } else {
            Log::channel('contracts')->info("НЕ отправлен смс код клиенту о создании контракта");
        }
        return self::handleResponse(['contract_id' => $contract->id]);
    }

    public static function CheckContractSmsCode(Request $request)
    {
        self::validatedCheckContractSmsCode($request);
        $validator = RealValidator::make($request->all(), [
            'contract_id' => new CountPhonesAmount()
        ]);
        if ($validator->fails()) {
            return self::handleError($validator->errors()->getMessages());
        }

        $buyer = Buyer::where("phone", $request->phone)->first();
        if (!$buyer) {
            return self::handleError([__('auth.error_user_not_found')]);
        }
        $statuses = [Contract::STATUS_AWAIT_SMS,Contract::STATUS_AWAIT_VENDOR];
        $contract = Contract::where(['id' => $request->contract_id, 'user_id' => $buyer->id])->whereIn('status',$statuses)->first();
        if (!$contract) {
            return self::handleError([__('api.contract_not_found')]);
        }
        if(GeneralCompany::MFO_COMPANY_ID === $contract->general_company_id) {
            return self::handleError([__('api.contract_not_be_activated')]);
        }
        $company = Company::find($contract->company_id);
        // Здесь проверка на кол-во договоров по категории 'телефоны' у клиента (должно быть не более 2х)
        // Проверяем относится ли текущий договор, который мы хотим активировать к первой категории (телефоны)
        if ($amountOfPhonesInContract = OrderProduct::where(['order_id' => $contract->order->id, 'category_id' => 1])->count()) {
            // Берем order_id активных контрактов
            $buyerActiveContractsOrderId = Contract::where('user_id', $buyer->id)->whereIn('status', [1, 3, 4])->pluck('order_id');
            // Проверяем, какое кол-во активных договоров с категорией 1 (телефоны) имеет клиент
            $activeUserOrdersWithFirstCategory = OrderProduct::whereIn('order_id', $buyerActiveContractsOrderId)->where('category_id', 1)->count();
            // Если кол-во активных договоров превысит лимит (2), при условии что мы его активируем, то выдаем ошибку и не пропускаем к активации
            if ($amountOfPhonesInContract + $activeUserOrdersWithFirstCategory > 2) {
                return self::handleError([__('api.you_have_exceeded_the_maximum_allowable_number_of_contracts_in_the_phones_category')]);
            }
        }
        // проверка - не выдет ли клиент за лимит если подтвердит договор
        $available_balance = $buyer->settings->balance + $buyer->settings->personal_account;
        $available_balance -= $contract->order->credit - $contract->deposit;
        if ($available_balance < 0) {
            return self::handleError([__('api.limit_error')]);
        }
        $encSms = LoginService::checkSmsCode($request);
        if ($encSms['code'] == 1) {
            // SellerBonusesHelper::activateBonusByContract($contract->id);
            $contract->confirmation_code = $request->code;
            $contract->status = 1;
            $contract->save();
            $contract->order->status = 9;
            $contract->order->save();

            // если это трехмесячная акция
            if ($company->promotion == 1) {
                if ($contract->period == 3) {

                    $prepayment = $company->settings->promotion_percent / 100 * $contract->total;
                    $month_discount = $company->settings['discount_' . $contract->period] / 100;

                    // снимаем первый платеж с ЛС
                    if ($buyer->settings->personal_account >= $prepayment) {
                        $buyer->settings->personal_account -= $prepayment;

                        // возвращаем лимит за первый месяц
                        $buyer->settings->balance += $contract->schedule[0]->price;

                        // отнимаем первый платеж из баланса
                        $contract->balance -= $prepayment;
                        $contract->save();

                        if ($buyer->settings->save()) {

                            // сразу закрываем первый месяц
                            $contract->schedule[0]->status = 1;
                            $contract->schedule[0]->balance = 0;
                            $contract->schedule[0]->paid_at = time();
                            $contract->schedule[0]->save();

                            // записать первый платеж как транзакцию в payments
                            $pay = new Payment;
                            $pay->schedule_id = $contract->schedule[0]->id;
                            $pay->type = 'auto';
                            $pay->order_id = $contract->order_id;
                            $pay->contract_id = $contract->id;
                            $pay->amount = $prepayment;
                            $pay->user_id = $buyer->id;
                            $pay->payment_system = 'ACCOUNT';
                            $pay->status = 1;
                            $pay->save();
                        }
                    }
                }
            }

            $buyer->settings->balance -= ($contract->order->credit - $contract->deposit); // снять после подтверждения смс кода
            $buyer->settings->personal_account -= $contract->deposit; // снять после подтверждения смс кода
            $buyer->settings->save();
            // IF DEPOSIT
            if ($contract->deposit > 0) {
                // записать как транзакцию в payments
                $payment = new Payment();
                $payment->schedule_id = $contract->schedule[0]->id;
                $payment->type = 'auto';
                $payment->order_id = $contract->order->id;
                $payment->contract_id = $contract->id;
                $payment->amount = $contract->deposit;
                $payment->user_id = $buyer->id;
                $payment->payment_system = 'DEPOSIT';
                $payment->status = 1;
                $payment->save();
            }

            ContractVerifyService::instantVerification($contract);

            $data['contract_id'] = $contract->id;
            $data['message'] = __('api.check_sms_contract_success');
            return self::handleResponse($data);
        }
        return self::handleError($encSms['error'],'error',400, $encSms['data']['errorCode']);
    }
}
