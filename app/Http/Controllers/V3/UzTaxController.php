<?php

namespace App\Http\Controllers\V3;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CatalogCategory;
use App\Models\Contract;
use App\Models\OrderProduct;
use App\Models\Payment;
use App\Models\UzTax;
use App\Models\UzTaxError;
use App\Services\API\V3\ContractVerifyService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\UzTaxTrait;

class UzTaxController extends Controller
{
    use UzTaxTrait;

    public function __invoke()
    {
        $errors = UzTaxError::whereNotIn('error_code', [UzTax::OFD_NOT_MATCH_TIN_ERROR, UzTax::OFD_INCORRECT_PSIC_CODE])->with('payment')->get();
        $listOfTINs = self::getCommissionListFromOfd();
        if (!empty($errors)) {
            foreach ($errors as $error) {
                if (!ContractVerifyService::isTINValid($error->contract, $listOfTINs)) {
                    $error->update(['error_code' => UzTax::OFD_NOT_MATCH_TIN_ERROR]);
                    continue;
                }
                $jsonData = json_decode($error->json_data);
                if ($jsonData->ReceiptType == UzTax::RECEIPT_TYPE_CREDIT) {
                    $continue = false;
                    foreach ($error->contract->orderProducts as $orderProduct) {
                        try {
                            $psicCodeStatus = self::getPsicCodeStatus($orderProduct->psic_code);
                            if ($psicCodeStatus === CatalogCategory::PSIC_CODE_STATUS_UNCHECKED) {
                                $continue = true;
                                break;
                            } elseif ($psicCodeStatus === CatalogCategory::PSIC_CODE_STATUS_INCORRECT) {
                                UzTaxError::where('id', $error->id)->update(['error_code' => UzTax::OFD_INCORRECT_PSIC_CODE]);
                                $continue = true;
                                break;
                            }
                        } catch (\Exception $exception) {
                            $continue = true;
                            break;
                        }
                        if ($psicCodeStatus !== CatalogCategory::PSIC_CODE_STATUS_ACTIVE) {
                            try {
                                $renewedProductPsicCode = self::getRenewedPsicCodeIfExists($orderProduct->psic_code);
                            } catch (\Exception $exception) {
                                $continue = true;
                                break;
                            }
                            if ($renewedProductPsicCode) {
                                OrderProduct::where('id', $orderProduct->id)->update(['psic_code' => $renewedProductPsicCode]);
                            } else {
                                UzTaxError::where('id', $error->id)->update(['error_code' => UzTax::OFD_INCORRECT_PSIC_CODE]);
                                $continue = true;
                                break;
                            }
                        }
                    }
                    if ($continue) {
                        continue;
                    }
                    $uzTaxErrorPayment = $error->payment ?? (Payment::find($error->payment_id) ?? null);
                    if (!isset($uzTaxErrorPayment)) {
                        Log::channel('uz_tax_errors')->info('PAYMENT NOT FOUND BY ID: ' . $error->payment_id);
                        continue;
                    }
                }

                Log::channel('uz_tax_errors_json_updates')->info("JSON DATA BEFORE UPDATE \n uz_tax_error_id: $error->id \n receipt_id $error->receipt_id");
                Log::channel('uz_tax_errors_json_updates')->info((array)$jsonData);
                // Update JSON DATA
                $updatedJsonData = json_decode(self::createJsonData($jsonData->ReceiptId, $error->contract_id, (int)$jsonData->IsRefund, $jsonData->ReceiptType, $uzTaxErrorPayment ?? ($error->payment ?? null), $jsonData->IsRefund === 1 && isset($jsonData->RefundInfo->ReceiptId) ? $jsonData->RefundInfo->ReceiptId : null));
                $updatedJsonData->Time = now()->toDateTimeString();
                Log::channel('uz_tax_errors_json_updates')->info("UPDATED JSON DATA \n uz_tax_error_id $error->id  \n receipt_id $error->receipt_id");
                Log::channel('uz_tax_errors_json_updates')->info((array)$updatedJsonData);
                $updatedJsonData = json_encode($updatedJsonData);
                $response_array = self::createCertificate($updatedJsonData);

                if (isset($response_array->Code) && $response_array->Code === 0) {

                    $json_decode = json_decode($updatedJsonData, true);

                    $uz_tax = UzTax::where('id', $json_decode['ReceiptId'])->first();

                    if (!empty($uz_tax)) {
                        if ($json_decode['ReceiptType'] === UzTax::RECEIPT_TYPE_SELL) {
                            UzTax::where('id', $json_decode['ReceiptId'])->update([
                                'payment_id' => 0,
                                'contract_id' => $error->contract_id,
                                'status' => $json_decode['IsRefund'] === 1 ? UzTax::CANCEL : UzTax::ACCEPT,
                                'type' => UzTax::RECEIPT_TYPE_SELL,
                                'json_data' => $updatedJsonData,
                                'fiscal_sign' => $response_array->FiscalSign,
                                'terminal_id' => $response_array->TerminalID,
                                'payment_system' => null,
                                'qr_code_url' => $response_array->QRCodeURL,
                            ]);
                        } elseif ($json_decode['ReceiptType'] === UzTax::RECEIPT_TYPE_CREDIT) {
                            $payment = Payment::where('id', $error->payment_id)->first();
                            UzTax::where('id', $json_decode['ReceiptId'])->update([
                                'payment_id' => $error->payment_id,
                                'contract_id' => $error->contract_id,
                                'status' => UzTax::ACCEPT,
                                'type' => UzTax::RECEIPT_TYPE_CREDIT,
                                'json_data' => $updatedJsonData,
                                'fiscal_sign' => 0000,
                                'terminal_id' => $response_array->TerminalID,
                                'payment_system' => $payment->payment_system,
                                'qr_code_url' => null,
                            ]);
                        } elseif ($json_decode['ReceiptType'] === UzTax::RECEIPT_TYPE_PREPAID) {
                            UzTax::where('id', $json_decode['ReceiptId'])->update([
                                'payment_id' => 0,
                                'contract_id' => $error->contract_id,
                                'status' => $json_decode['IsRefund'] === 1 ? UzTax::CANCEL : UzTax::ACCEPT,
                                'type' => UzTax::RECEIPT_TYPE_PREPAID,
                                'json_data' => $updatedJsonData,
                                'fiscal_sign' => 0000,
                                'terminal_id' => $response_array->TerminalID,
                                'payment_system' => null,
                                'qr_code_url' => $response_array->QRCodeURL ?? null,
                            ]);
                        }
                    }
                    UzTaxError::where('id', $error->id)->delete();
                }
            }
        }

        $contracts = Contract::where('verified', Contract::NOT_VERIFIED)
            ->where('period', '!=', 3)
            ->whereIn('status', [Contract::STATUS_ACTIVE, Contract::STATUS_OVERDUE_60_DAYS, Contract::STATUS_OVERDUE_30_DAYS])
            ->whereIn('company_id', Company::resus_COMPANY_ID)
            ->get();

        foreach ($contracts as $contract) {
            ContractVerifyService::instantVerification($contract);
        }

        $payments = DB::select("select * from payments where `created_at` > ?
                         and `payment_system` <> 'DEPOSIT'
                         and `status` = 1
                         and (`amount` > 100 or `amount` < -100)
                         and `type` in ('auto', 'user_auto', 'refund')
                         and contract_id not in (select id from contracts where general_company_id in (select id from general_companies where is_mfo = 1))", array($this->getLastPaymentRecordDate()));

        Log::channel('uz_tax')->info(['tin' => $listOfTINs]);

        foreach ($payments as $payment) {
            self::send($payment, $listOfTINs);
        }
    }

    private function getLastPaymentRecordDate(): string
    {
        $date = null;

        $last_id = UzTax::where('fiscal_sign', 0)
            ->whereIn('status', [UzTax::ACCEPT, UzTax::CANCEL])
            ->whereIn('type', [UzTax::RECEIPT_TYPE_CREDIT, UzTax::RECEIPT_TYPE_PREPAID])
            ->orderBy('id', 'desc')->first();

        if (is_null($last_id)) {

            $date = Carbon::parse(config("test.uz_tax_schedule_start_date"))->format('Y-m-d H:i:s');

        } else {

            $date = Carbon::parse($last_id->created_at)->format('Y-m-d H:i:s');
            Log::channel('uz_tax')->info(['last_id' => $last_id->id]);

        }

        Log::channel('uz_tax')->info(['date' => $date]);

        return $date;
    }

    private static function send($payment, $listOfTINs = [])
    {
        $is_refund = null;
        $status = null;

        switch ($payment->type) {

            case 'auto':
            case 'user_auto':

                $is_refund = UzTax::IS_REFUND_SELL_PRODUCT;
                $status = UzTax::ACCEPT;
                break;

            case 'refund':

                $is_refund = UzTax::IS_REFUND_RETURN_PRODUCT;
                $status = UzTax::CANCEL;
                break;

        }

        if ($payment->contract_id && $payment->status === 1 && $contract = Contract::where('id', $payment->contract_id)->with(['generalCompany', 'orderProducts.category', 'company'])->first()) {

            foreach ($contract->orderProducts as $orderProduct) {
                try {
                    $psicCodeStatus = self::getPsicCodeStatus($orderProduct->psic_code);
                    if ($psicCodeStatus === CatalogCategory::PSIC_CODE_STATUS_UNCHECKED || $psicCodeStatus === CatalogCategory::PSIC_CODE_STATUS_INCORRECT) {
                        $uz_tax = new UzTax();
                        $uz_tax->save();
                        $json_data = self::createJsonData($uz_tax->id, $payment->contract_id, $is_refund, UzTax::RECEIPT_TYPE_CREDIT, $payment);
                        $errorCode = $psicCodeStatus === CatalogCategory::PSIC_CODE_STATUS_UNCHECKED ? UzTax::OFD_SERVER_ERROR : UzTax::OFD_INCORRECT_PSIC_CODE;
                        self::catchError($payment->id, $payment->contract_id, $uz_tax->id, $errorCode, $json_data);
                        return false;
                    }
                } catch (\Exception $exception) {
                    $uz_tax = new UzTax();
                    $uz_tax->save();
                    $json_data = self::createJsonData($uz_tax->id, $payment->contract_id, $is_refund, UzTax::RECEIPT_TYPE_CREDIT, $payment);
                    self::catchError($payment->id, $payment->contract_id, $uz_tax->id, UzTax::OFD_SERVER_ERROR, $json_data);
                    return false;
                }
                if ($psicCodeStatus !== CatalogCategory::PSIC_CODE_STATUS_ACTIVE) {
                    try {
                        $renewedProductPsicCode = self::getRenewedPsicCodeIfExists($orderProduct->psic_code);
                    } catch (\Exception $exception) {
                        $uz_tax = new UzTax();
                        $uz_tax->save();
                        $json_data = self::createJsonData($uz_tax->id, $payment->contract_id, $is_refund, UzTax::RECEIPT_TYPE_CREDIT, $payment);
                        self::catchError($payment->id, $payment->contract_id, $uz_tax->id, UzTax::OFD_SERVER_ERROR, $json_data);
                        return false;
                    }
                    if ($renewedProductPsicCode) {
                        OrderProduct::where('id', $orderProduct->id)->update(['psic_code' => $renewedProductPsicCode]);
                    } else {
                        $uz_tax = new UzTax();
                        $uz_tax->save();
                        $json_data = self::createJsonData($uz_tax->id, $payment->contract_id, $is_refund, UzTax::RECEIPT_TYPE_CREDIT, $payment);
                        self::catchError($payment->id, $payment->contract_id, $uz_tax->id, UzTax::OFD_INCORRECT_PSIC_CODE, $json_data);
                        return false;
                    }
                }
            }

            if (!ContractVerifyService::isTINValid($contract, $listOfTINs)) {
                $uz_tax = new UzTax();
                $uz_tax->save();
                $json_data = self::createJsonData($uz_tax->id, $payment->contract_id, $is_refund, UzTax::RECEIPT_TYPE_CREDIT, $payment);
                self::catchError($payment->id, $payment->contract_id, $uz_tax->id, UzTax::OFD_NOT_MATCH_TIN_ERROR, $json_data);
                return false;
            }

            $uz_tax = new UzTax();
            $uz_tax->save();

            $json_data = self::createJsonData($uz_tax->id, $payment->contract_id, $is_refund, UzTax::RECEIPT_TYPE_CREDIT, $payment);
            $response_array = self::createCertificate(($json_data));


            if (isset($response_array->Code) && $response_array->Code === 0) {

                UzTax::where('id', $uz_tax->id)->update([
                    "payment_id" => $payment->id,
                    "contract_id" => $payment->contract_id,
                    "status" => $status,
                    "type" => UzTax::RECEIPT_TYPE_CREDIT,
                    "json_data" => $json_data,
                    "fiscal_sign" => 0000,
                    "terminal_id" => $response_array->TerminalID,
                    "payment_system" => $payment->payment_system,
                    "qr_code_url" => $response_array->QRCodeURL
                ]);

                return true;

            } else {

                $error_code = $response_array->Code ?? UzTax::OFD_SERVER_ERROR;

                self::catchError($payment->id, $payment->contract_id, $uz_tax->id, $error_code, $json_data);

                return false;

            }
        }
    }

    public static function QrCodeFromOfd($request, Contract $contract)
    {
        $uz_tax = new UzTax();
        $uz_tax->save();

        $json_data = self::createJsonData($uz_tax->id, $request->contract_id, UzTax::IS_REFUND_SELL_PRODUCT, UzTax::RECEIPT_TYPE_SELL);
        $response_array = self::createCertificate(($json_data));

        if (isset($response_array->Code) && $response_array->Code === 0) {

            UzTax::where('id', $uz_tax->id)->update([

                "contract_id" => $request->contract_id,
                "status" => UzTax::ACCEPT,
                "type" => UzTax::RECEIPT_TYPE_SELL,
                "json_data" => $json_data,
                "fiscal_sign" => $response_array->FiscalSign ?? null,
                "terminal_id" => $response_array->TerminalID ?? null,
                "qr_code_url" => $response_array->QRCodeURL ?? null,

            ]);

        } else {

            $error_code = $response_array->Code ?? UzTax::OFD_SERVER_ERROR;

            self::catchError(0, $request->contract_id, $uz_tax->id, $error_code, $json_data);

        }

        if ((int)$contract->deposit !== 0 && $contract->generalCompany->is_mfo === 0) {
            $uz_tax = new UzTax();
            $uz_tax->save();

            $json_data = self::createJsonData($uz_tax->id, $request->contract_id, UzTax::IS_REFUND_SELL_PRODUCT, UzTax::RECEIPT_TYPE_PREPAID);
            $response_array = self::createCertificate(($json_data));

            if (isset($response_array->Code) && $response_array->Code === 0) {

                UzTax::where('id', $uz_tax->id)->update([
                    "payment_id" => $contract->autoDepositPayment->id ?? 0,
                    "contract_id" => $request->contract_id,
                    "status" => UzTax::ACCEPT,
                    "type" => UzTax::RECEIPT_TYPE_PREPAID,
                    "json_data" => $json_data,
                    "fiscal_sign" => 0000,
                    "terminal_id" => $response_array->TerminalID ?? null,
                    "qr_code_url" => $response_array->QRCodeURL ?? null,
                ]);

            } else {

                $error_code = $response_array->Code ?? UzTax::OFD_SERVER_ERROR;

                self::catchError($contract->autoDepositPayment->id ?? 0, $request->contract_id, $uz_tax->id, $error_code, $json_data);

            }
        }
    }
}
