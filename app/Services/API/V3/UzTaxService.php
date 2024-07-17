<?php

namespace App\Services\API\V3;

use App\Enums\ServiceFeeEnum;
use App\Helpers\NdsStopgagHelper;
use App\Models\Contract;
use App\Models\OrderProduct;
use App\Models\UzTax;
use App\Traits\UzTaxTrait;
use Illuminate\Support\Carbon;

final class UzTaxService
{
    public function createPartialCancellationCheques(Contract $contract): bool
    {
        // if status was cancelled with an error, then we shouldn't generate cancellation cheque
        if ($contract->status === Contract::STATUS_CANCELED && $contract->uzTaxError) {
            $contract->uzTaxError->delete();

            return false;
        }

        // contract must have tax with status "accept"
        if (!$contract->uzTaxUrl) {
            return false;
        }

        $cancellationTax = new UzTax();
        $cancellationTax->save();


        $cancellationChequeJson = $this->generateChequeWithCancelledProducts($contract, $cancellationTax->id, UzTax::RECEIPT_TYPE_SELL);
        $this->handleCertificateResponse(
            UzTaxTrait::createCertificate($cancellationChequeJson),
            $cancellationChequeJson,
            $cancellationTax,
            0,
            $contract->id,
            UzTax::RECEIPT_TYPE_SELL,
        );


        if (intval($contract->deposit) !== 0) {
            $prepaidTax = new UzTax();
            $prepaidTax->save();

            $prepaidChequeJson = $this->generateChequeWithCancelledProducts($contract, $prepaidTax->id, UzTax::RECEIPT_TYPE_PREPAID);
            $this->handleCertificateResponse(
                UzTaxTrait::createCertificate($prepaidChequeJson),
                $prepaidChequeJson,
                $prepaidTax,
                $contract->refundDepositToAccountPayment->id ?? 0,
                $contract->id,
                UzTax::RECEIPT_TYPE_PREPAID
            );
        }

        return true;
    }

    private function generateChequeWithCancelledProducts(Contract $contract, int $receiptId, int $receiptType): string
    {
        $cancelledOrderProducts = $contract->orderProducts()
            ->where('status', '=', OrderProduct::STATUS_CANCELED)
            ->get();

        // get nds percentage
        if ($receiptType === UzTax::RECEIPT_TYPE_SELL) {
            if ($contract->generalCompany->is_mfo === 1) {
                $vat = $contract->company->settings->nds === 0 ? 0 : NdsStopgagHelper::getActualNds($contract->getRawOriginal('created_at'));
            } else {
                $vat = NdsStopgagHelper::getActualNds($contract->getRawOriginal('created_at'));
            }
        } else {
            $vat = 0;
        }

        $cheque = [];
        $items = [];

        $totalAmount = 0; // sum of all product amounts (we will use it for "service fee")
        $totalServicePrice = 0; // total price of "service reward"
        $totalReceivedCard = 0;
        $totalVat = 0;

        // iterate through just cancelled products and calculate cheque data
        foreach ($cancelledOrderProducts as $product) {
            $amount = intval($product->amount);
            // if gen.company is mfo, then take discount and calculate price for the service, otherwise there is no price for the service.
            $servicePrice = $contract->generalCompany->isMFO() && $contract->period !== 3 ? ($product->price - $product->price_discount) * $amount * 100 : 0;

            if ($receiptType === UzTax::RECEIPT_TYPE_SELL) {
                // if gen.company is mfo, then take discount price, otherwise take original price
                $price = abs(($contract->generalCompany->isMFO() ? $product->price_discount : $product->price) * ($amount * 100));
                $totalReceivedCard += $price + $servicePrice;
            } else {
                // prepaid
                $price = abs($contract->deposit * 100 / $cancelledOrderProducts->count());
            }

            // if company is resus, then we only calculate "service fee" without products
            if (!$contract->company->isresus()) {
                $item = [
                    'Name' => $product->name,
                    'SPIC' => $product->psic_code,
                    'Units' => intval($product->unit_id),
                    'Price' => intval($price),
                    'Amount' => intval($amount * 1000),
                ];
                if ($contract->generalCompany->isMFO()) {
                    if ($contract->company->isIndividualEntrepreneur()) {
                        $item['Commission']['PINFL'] = $contract->company->inn;
                    } else {
                        $item['Commission']['TIN'] = $contract->company->inn;
                    }
                } else {
                    $item['Commission']['TIN'] = $contract->generalCompany->inn;
                }
                $item['VAT'] = intval($contract->company->settings->nds === 0 ? $price * $vat : $price / (1 + $vat) * $vat);
                $item['VATPercent'] = intval($vat * 100);
                $items[] = $item;
                $totalVat += $item['VAT'];
            }

            // add each product data for calculating amount and price of "service fee"
            $totalAmount += $amount;
            $totalServicePrice += $servicePrice;

        }

        if ($contract->generalCompany->isMFO() && $contract->period !== 3 && $receiptType === UzTax::RECEIPT_TYPE_SELL) {
            $serviceFee = [
                'Name' => ServiceFeeEnum::NAME,
                'SPIC' => ServiceFeeEnum::SPIC,
                'PackageCode' => ServiceFeeEnum::PACKAGE_CODE,
                'Units' => ServiceFeeEnum::UNITS,
                'Price' => intval($totalServicePrice),
                'Amount' => $totalAmount * 1000,
                'CommissionInfo' => [
                    'TIN' => ServiceFeeEnum::COMMISSION_INFO_TIN
                ],
                'VAT' => 0,
                'VATPercent' => 0
            ];

            $items[] = $serviceFee;
        }

        $cheque['items'] = $items;

        if ($receiptType === UzTax::RECEIPT_TYPE_PREPAID) {
            $totalReceivedCard = intval($contract->deposit * 100);
        }

        // cheque info
        $cheque['ReceiptId'] = $receiptId;
        $cheque['ReceivedCash'] = 0;
        if ($contract->generalCompany->isMFO() && $receiptType === UzTax::RECEIPT_TYPE_SELL) {
            $cheque['ReceivedCard'] = intval($contract->company->isresus() ? $totalServicePrice : $totalReceivedCard);
            $cheque['TotalVat'] = intval($contract->company->isresus() ? 0 : abs($totalVat));
        } else {
            $cheque['ReceivedCard'] = intval(abs($totalReceivedCard));
            $cheque['TotalVat'] = abs($totalVat);
        }
        $cheque['Time'] = Carbon::parse($contract->updated_at)->format('Y-m-d H:i:s');
        $cheque['IsRefund'] = 1;
        $cheque['ReceiptType'] = $receiptType;
        $cheque['ExtraInfo'] = [
            'PhoneNumber' => preg_replace('/[^0-9]/', '', $contract->buyer->phone)
        ];

        // refund info
        $cheque['RefundInfo'] = $this->getRefundInfo($contract->id, $receiptType);

        return json_encode($cheque);
    }

    private function getRefundInfo(int $contractId, int $receiptType): array
    {
        $refundCheque = UzTax::where('contract_id', '=', $contractId)
            ->where('status', '=', UzTax::ACCEPT)
            ->where('type', '=', $receiptType)
            ->first();

        if (!$refundCheque) {
            return [];
        }

        return [
            'TerminalID' => $refundCheque->terminal_id,
            'ReceiptId' => strval($refundCheque->id),
            'DateTime' => Carbon::parse(json_decode($refundCheque->json_data, true)['Time'])->format('YmdHis'),
            'FiscalSign' => $refundCheque->fiscal_sign
        ];
    }

    private function handleCertificateResponse(
              $response,
              $chequeJson,
        UzTax $tax,
        int   $paymentId,
        int   $contractId,
        int   $receiptType
    ): void {
        if (isset($response->Code) && $response->Code === 0) {
            $tax->contract_id = $contractId;
            $tax->payment_id = $paymentId;
            $tax->status = UzTax::CANCEL;
            $tax->type = $receiptType;
            $tax->json_data = $chequeJson;
            $tax->fiscal_sign = $response->FiscalSign ?? 0000;
            $tax->terminal_id = $response->TerminalID ?? '';
            $tax->qr_code_url = $response->QRCodeURL ?? '';
            $tax->save();
        } else {
            UzTaxTrait::catchError(
                $paymentId,
                $contractId,
                $tax->id,
                $response->code ?? UzTax::OFD_SERVER_ERROR,
                $chequeJson
            );
        }
    }
}
