<?php

namespace App\Traits;

use App\Enums\UzTaxesEnum;
use App\Helpers\NdsStopgagHelper;
use App\Models\CatalogCategory;
use App\Models\Contract;
use App\Models\UzTax;
use App\Models\UzTaxError;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

trait UzTaxTrait
{
    public static function createJsonData($receipt_id, $contract_id, $is_refund = 0, $receipt_type = 0, $payment = null, $cancelable_uz_tax_id = null)
    {
        $contract = Contract::where('id', $contract_id)->with(['buyer:id,phone', 'orderProducts:id,order_id,name,label,amount,price,price_discount,psic_code,unit_id', 'generalCompany:id,inn,is_mfo', 'contractCompanySetting:id,nds'])->first();
        $productInfo = self::formProductInfo($receipt_id, $contract, $is_refund, $receipt_type, $payment);
        if ($is_refund === UzTax::IS_REFUND_RETURN_PRODUCT && $refundInfo = self::formRefundInfo($contract_id, $receipt_type, $payment->id ?? 0, $cancelable_uz_tax_id)) {
            $productInfo += $refundInfo;
        }
        return json_encode($productInfo);
    }

    public static function createCertificate($json_data)
    {
        $files = Storage::disk('uz_tax_storage')->allFiles();
        $json_file = Storage::disk('uz_tax_public')->allFiles();

        Log::channel('uz_tax')->info("UZ_TAX_ID: " . json_decode($json_data)->ReceiptId);
        Log::channel('uz_tax')->info($files);

        if (empty($json_file)) {

            Storage::disk('uz_tax_public')->put('receipt.json', $json_data);

        } else {

            Storage::disk('uz_tax_public')->delete('receipt.json');
            Storage::disk('uz_tax_public')->delete('receipt.p7b');
            Storage::disk('uz_tax_public')->put('receipt.json', $json_data);

        }

        $key = "key";
        $crt = "crt";
        $file_name = str_replace('\\', '/', Storage::disk('uz_tax_public')->getDriver()->getAdapter()->getPathPrefix());
        $receipt = $file_name . 'receipt.json';
        $file_name .= 'receipt';
        $soliq_url = env("UZ_TAX_URL_TEST");

        foreach ($files as $file) {

            $data = explode('.', $file);

            $ext = $data[count($data) - 1];

            if ($crt == $ext) {

                $crt = str_replace('\\', '/', storage_path('uz_tax/')) . $file;

            } elseif ($key == $ext) {

                $key = str_replace('\\', '/', storage_path('uz_tax/')) . $file;

            }
        }

        //create  certificate
        $create_p7b = "openssl cms -sign -nodetach -binary -in $receipt -text -outform der -out $file_name.p7b -nocerts -signer $crt -inkey $key";
        exec($create_p7b, $output, $retval);
        Log::channel('uz_tax')->info($create_p7b);
        Log::channel('uz_tax')->info($output);

        // send to soliq_uz
        $command = "curl --request POST '$soliq_url' --header 'Content-Type: application/x-pkcs7-certificates' --data-binary '@$file_name.p7b'";
        exec($command, $output, $retval);
        Log::channel('uz_tax')->info($command);
        Log::channel('uz_tax')->info($output);
        $text = '';

        foreach ($output as $item) {

            $text .= $item;

        }

        if ($text) {

            return json_decode($text);

        } else {

            return "error";

        }
    }

    public static function catchError($payment_id, $contract_id, $receipt_id, $error_code, $json_data)
    {
        $uzTaxError = new UzTaxError();
        $uzTaxError->payment_id = $payment_id;
        $uzTaxError->contract_id = $contract_id;
        $uzTaxError->receipt_id = $receipt_id;
        $uzTaxError->error_code = $error_code;
        $uzTaxError->json_data = $json_data;
        return $uzTaxError->save();
    }

    public static function refundReturnProduct($contract_id = 0, $cancelable_uz_tax_id = null)
    {
        $contract = Contract::where('id', $contract_id)->with(['uzTaxError', 'uzTaxUrl', 'refundDepositToAccountPayment'])->first();

        if ($contract->status == Contract::STATUS_CANCELED && !empty($contract->uzTaxError)) {
            UzTaxError::where('contract_id', $contract->id)->delete();

            return false;
        }

        if (empty($contract->uzTaxUrl)) {

            return false;

        }

        $uz_tax = new UzTax();
        $uz_tax->save();
        $receipt_id = $uz_tax->id;

        $json_data = self::createJsonData($receipt_id, $contract_id, UzTax::IS_REFUND_RETURN_PRODUCT, UzTax::RECEIPT_TYPE_SELL, null, $cancelable_uz_tax_id);
        $response_array = self::createCertificate(($json_data));

        if (isset($response_array->Code) && $response_array->Code === 0) {

            $uz_tax->contract_id = $contract_id;
            $uz_tax->status = UzTax::CANCEL;
            $uz_tax->type = UzTax::RECEIPT_TYPE_SELL;
            $uz_tax->json_data = $json_data;
            $uz_tax->fiscal_sign = $response_array->FiscalSign ?? 0000;
            $uz_tax->terminal_id = $response_array->TerminalID ?? "";
            $uz_tax->qr_code_url = $response_array->QRCodeURL ?? "";
            $uz_tax->save();

        } else {

            $error_code = $response_array->Code ?? UzTax::OFD_SERVER_ERROR;

            self::catchError(0, $contract_id, $uz_tax->id, $error_code, $json_data);

        }

        if ((int)$contract->deposit !== 0) {

            $uz_tax = new UzTax();
            $uz_tax->save();
            $receipt_id = $uz_tax->id;
            $json_data = self::createJsonData($receipt_id, $contract_id, UzTax::IS_REFUND_RETURN_PRODUCT, UzTax::RECEIPT_TYPE_PREPAID);
            $response_array = self::createCertificate(($json_data));

            if (isset($response_array->Code) && $response_array->Code === 0) {

                $uz_tax->payment_id = $contract->refundDepositToAccountPayment->id ?? 0;
                $uz_tax->contract_id = $contract_id;
                $uz_tax->status = UzTax::CANCEL;
                $uz_tax->type = UzTax::RECEIPT_TYPE_PREPAID;
                $uz_tax->json_data = $json_data;
                $uz_tax->fiscal_sign = 0000;
                $uz_tax->terminal_id = $response_array->TerminalID ?? "";
                $uz_tax->qr_code_url = $response_array->QRCodeURL ?? "";
                $uz_tax->save();

            } else {

                $error_code = $response_array->Code ?? UzTax::OFD_SERVER_ERROR;

                self::catchError($contract->refundDepositToAccountPayment->id ?? 0, $contract_id, $uz_tax->id, $error_code, $json_data);
            }
        }

        return true;

    }

    public static function getTokenFromOfd(): string
    {

        $body = [
            "password" => config('test.uz_tax_ofd_uz_password'),
            "username" => config('test.uz_tax_ofd_uz_username')
        ];

        $ofd_url = config('test.uz_tax_ofd_uz_login_url');

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $ofd_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $ofd_res = json_decode(curl_exec($curl), true);

        curl_close($curl);
        return $ofd_res['access_token'];
    }

    public static function getCommissionListFromOfd(): array
    {
        $token = self::getTokenFromOfd();
        $url = config('test.uz_tax_ofd_uz_commission_list_url') . '/' . config('test.uz_tax_ofd_company_stir');
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer $token"
            ),
        ));
        $data = [];
        $list = json_decode(curl_exec($curl));
        curl_close($curl);
        Log::channel('uz_tax')->info(['tin_list_in_getCommissionListFromOfd' => $list]);
        if (isset($list->success, $list->data) && $list->success === true && is_array($list->data)) {
            $now = now();
            foreach ($list->data as $item) {
                if ($now >= $item->contractBeginDate && $now <= $item->contractEndDate) {
                    if ($item->tin) {
                        $data['TIN'][] = $item->tin;
                    }
                    if ($item->pinfl) {
                        $data['PINFL'][] = $item->pinfl;
                    }
                }
            }
        }

        return $data;
    }

    private static function formProductInfo(int $receipt_id, Contract $contract, int $is_refund, int $receipt_type, $payment = null): array
    {
        $data = [];
        $totalAmount = 0;
        $totalServicePrice = 0;
        $price = 0;
        $receivedCard = 0;
        $vat = 0;
        $totalReceivedCard = 0;
        $totalVat = 0;
        $time = null;
        $qqs = 0;
        if ($receipt_type === UzTax::RECEIPT_TYPE_SELL) {
            if ($contract->generalCompany->isMFO()) {
                $qqs = $contract->company->settings->nds === 0 ? 0 : NdsStopgagHelper::getActualNds($contract->getRawOriginal('created_at'));
            } else if ($contract->generalCompany->is_mfo === 0) {
                $qqs = NdsStopgagHelper::getActualNds($contract->getRawOriginal('created_at'));
            }
        }

        foreach ($contract->orderProducts as $product) {
            $amount = (int)($product->amount);
            $totalAmount += $amount;
            $servicePrice = $contract->generalCompany->isMFO() && $contract->period !== 3 ? ($product->price - $product->price_discount) * $amount * 100 : 0;
            $totalServicePrice += $servicePrice;
            if ($contract->company->isresus() && is_null($payment)) {
                continue;
            }
            switch ($receipt_type) {
                case UzTax::RECEIPT_TYPE_SELL:
                    if ($contract->generalCompany->isMFO()) {
                        $price = $product->price_discount * $amount * 100;
                    } else if (!$contract->generalCompany->isMFO()) {
                        $price = $product->price * $amount * 100;
                    }
                    $price = abs($price);
                    $receivedCard += $price + $servicePrice;
                    break;
                case UzTax::RECEIPT_TYPE_PREPAID:
                    $price = abs($contract->deposit * 100 / count($contract->orderProducts));
                    break;
                case UzTax::RECEIPT_TYPE_CREDIT:
                    $price = abs($payment->amount * 100 / count($contract->orderProducts));
                    break;
            }

            $item['Name'] = $product->name;
            $item['Label'] = $product->label;
            $item['SPIC'] = $product->psic_code;
            if ($product->psic_code === UzTaxesEnum::GRANTING_LICENSE_PSIC) {
                $item['Units'] = UzTaxesEnum::SERVICE_UNITS;
                $item['PackageCode '] = UzTaxesEnum::SERVICE_UNIT_SUM;
            } else {
                $item['Units'] = (int)$product->unit_id;
            }
            $item['Price'] = (int)$price;
            $item['Amount'] = (int)($amount * 1000);
            if ($contract->generalCompany->isMFO()) {
                if ($contract->company->isIndividualEntrepreneur()) {
                    $item['CommissionInfo']['PINFL'] = $contract->company->inn;
                } else {
                    $item['CommissionInfo']['TIN'] = $contract->company->inn;
                }
            } else {
                $item['CommissionInfo']['TIN'] = $contract->generalCompany->inn;
            }
            $item['VAT'] = (int)($contract->company->settings->nds === 0 ? $price * $qqs : $price / (1 + $qqs) * $qqs); // чистая сумма НДС,
            $item['VATPercent'] = (int)($qqs * 100);

            $vat += $item['VAT'];
            $data["Items"][] = $item;
        }

        if (is_null($payment) && $receipt_type === UzTax::RECEIPT_TYPE_SELL) {
            $totalReceivedCard = $receivedCard;
            $totalVat = $vat;
            $time = Carbon::parse($contract->updated_at)->format('Y-m-d H:i:s');
        } elseif ($payment && $receipt_type === UzTax::RECEIPT_TYPE_CREDIT) {
            $totalReceivedCard = (int)($payment->amount * 100);
            $totalVat = (int)($payment->amount * $qqs * 100);
            $time = Carbon::parse($payment->created_at)->format('Y-m-d H:i:s');
        } elseif ($receipt_type === UzTax::RECEIPT_TYPE_PREPAID) {
            $totalReceivedCard = (int)($contract->deposit * 100);
            $time = Carbon::parse($contract->updated_at)->format('Y-m-d H:i:s');
        }

        if (
            $contract->generalCompany->isMFO() &&
            $contract->period !== 3 &&
            $receipt_type === UzTax::RECEIPT_TYPE_SELL &&
            $totalServicePrice > 0
        ) {
            $serviceInfo = [
                'Name' => UzTaxesEnum::SERVICE_NAME,
                'SPIC' => UzTaxesEnum::SERVICE_SPIC,
                'PackageCode' => UzTaxesEnum::SERVICE_PACKAGE_CODE,
                'Units' => UzTaxesEnum::SERVICE_UNITS,
                'Price' => (int)$totalServicePrice,
                'Amount' => $totalAmount * 1000,
                'CommissionInfo' => [
                    'TIN' => UzTaxesEnum::SERVICE_TIN
                ],
                'VAT' => UzTaxesEnum::SERVICE_VAT,
                'VATPercent' => UzTaxesEnum::SERVICE_VAT_PERCENT,
            ];
            $data['Items'][] = $serviceInfo;

            $chequeData = [
                'ReceiptId' => $receipt_id,
                'ReceivedCash' => 0,
                'ReceivedCard' => (int)($contract->company->isresus() ? (int)$totalServicePrice : $totalReceivedCard),
                'Time' => $time,
                'TotalVAT' => (int)($contract->company->isresus() ? 0 : abs($totalVat)),
                'IsRefund' => $is_refund,
                'ReceiptType' => $receipt_type,
                'ExtraInfo' => [
                    'PhoneNumber' => preg_replace('/[^0-9]/', '', $contract->buyer->phone)
                ]
            ];
            $data += $chequeData;
        } else {
            $chequeData = [
                'ReceiptId' => $receipt_id,
                'ReceivedCash' => 0,
                'ReceivedCard' => (int)abs($totalReceivedCard),
                'Time' => $time,
                'TotalVAT' => (int)abs($totalVat),
                'IsRefund' => $is_refund,
                'ReceiptType' => $receipt_type,
                'ExtraInfo' => [
                    'PhoneNumber' => preg_replace('/[^0-9]/', '', $contract->buyer->phone)
                ]
            ];
            $data += $chequeData;
        }

        return $data;
    }

    private static function formRefundInfo(int $contract_id, int $receipt_type, int $payment_id = null, int $cancelable_uz_tax_id = null): array
    {
        if (isset($cancelable_uz_tax_id)) {
            $refundPayment = UzTax::where('id', $cancelable_uz_tax_id)->first();
        } else {
            $refundPayment = UzTax::where([
                ['contract_id', $contract_id],
                ['payment_id', $payment_id],
                ['status', UzTax::ACCEPT],
                ['type', $receipt_type],
            ])->first();
        }
        if (!empty($refundPayment)) {
            $decoded_json_data = json_decode($refundPayment->json_data, true);

            return [
                "RefundInfo" => [
                    "TerminalID" => $refundPayment->terminal_id,
                    "ReceiptId" => "$refundPayment->id",
                    "DateTime" => Carbon::parse($decoded_json_data['Time'])->format('YmdHis'),
                    "FiscalSign" => $refundPayment->fiscal_sign,
                ]
            ];
        }
        return [];
    }

    private static function getPsicCodeStatus($psic_code): int
    {
        if (empty($psic_code)) return CatalogCategory::PSIC_CODE_STATUS_INCORRECT;

        $response = Http::timeout(15)->get(Config::get('test.tasnif_base_url') . "/api/cls-api/mxik/search/by-params?mxikCode=$psic_code");

        if ($response->ok()) {
            $response = $response->object();
            if (isset($response->success, $response->reason) && $response->success === true && $response->reason === 'ok') {
                foreach ($response->data->content as $productOrServiceData) {
                    if ($productOrServiceData->mxikCode === $psic_code) {
                        return CatalogCategory::PSIC_CODE_STATUS_ACTIVE;
                    }
                }
                return CatalogCategory::PSIC_CODE_STATUS_INCORRECT;
            } else {
                return CatalogCategory::PSIC_CODE_STATUS_NOT_ACTIVE;
            }
        }
        return CatalogCategory::PSIC_CODE_STATUS_UNCHECKED;
    }

    private static function getRenewedPsicCodeIfExists($psic_code): string
    {
        if (empty($psic_code)) return false;

        $response = Http::timeout(15)->get(Config::get('test.tasnif_base_url') . "/api/cls-api/integration-mxik/get/history/$psic_code");

        if ($response->ok()) {
            $response = $response->object();
            if (isset($response->success) && $response->success === true) {
                if (isset($response->data->mxikCode) && $response->data->mxikCode) {
                    return $response->data->mxikCode;
                }
            }
        }
        return false;
    }
}
