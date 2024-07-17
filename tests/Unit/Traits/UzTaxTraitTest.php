<?php

namespace Tests\Unit\Traits;

use App\Helpers\NdsStopgagHelper;
use App\Models\Company;
use App\Models\Contract;
use App\Models\OrderProduct;
use App\Models\UzTax;
use App\Traits\UzTaxTrait;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UzTaxTraitTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */

//{#3000
//+"Items": array:4 [
//0 => {#2958
//+"Name": "телефон redmi note 8 (6+128)"
//+"SPIC": "08544002014000000"
//+"Units": 1
//+"Price": 2000000
//+"Amount": 2000
//+"CommissionInfo": {#2980
//+"TIN": "307642723"
//}
//+"VAT": 214285
//+"VATPercent": 12
//    }
//    1 => {#2947
//    +"Name": "Телефон Redmi 10S 64 gray"
//    +"SPIC": "08544002014000000"
//    +"Units": 1
//    +"Price": 3000000
//    +"Amount": 3000
//    +"CommissionInfo": {#2965
//        +"TIN": "307642723"
//      }
//      +"VAT": 321428
//    +"VATPercent": 12
//    }
//    2 => {#3004
//    +"Name": "Телевизоры и видеотехника"
//    +"SPIC": "08544002014000000"
//    +"Units": 1
//    +"Price": 1000000
//    +"Amount": 1000
//    +"CommissionInfo": {#2959
//        +"TIN": "307642723"
//      }
//      +"VAT": 107142
//    +"VATPercent": 12
//    }
//    3 => {#3010
//    +"Name": "Вознаграждение за право использования ПО"
//    +"SPIC": "0305008002000000"
//    +"Units": 1
//    +"Price": 1100000
//    +"Amount": 6000
//    +"CommissionInfo": {#2969
//        +"TIN": "308349548"
//      }
//      +"VAT": 0
//    +"VATPercent": 0
//    }
//  ]
//  +"ReceiptId": 683735
//+"ReceivedCash": 0
//+"ReceivedCard": 7100000
//+"Time": "2023-03-03 16:51:01"
//+"TotalVAT": 642855
//+"IsRefund": 0
//+"ReceiptType": 0
//+"ExtraInfo": {#2995
//    +"PhoneNumber": "998123456789"
//  }
//}

    public function createJsonData(bool $cancel): string
    {
        $uztax = new UzTax();
        $uztax->contract_id = Contract::where('verified', Contract::NOT_VERIFIED)->first()->id;
        $uztax->save();

        return UzTaxTrait::createJsonData($uztax->id, $uztax->contract_id, $cancel ? UzTax::IS_REFUND_RETURN_PRODUCT : UzTax::IS_REFUND_SELL_PRODUCT, UzTax::RECEIPT_TYPE_SELL);
    }

    public function test_if_all_json_data_elements_set()
    {
        $cancel = false;
        $jsonData = json_decode($this->createJsonData($cancel));
        $allElementsSet = isset($jsonData->Items)
            && isset($jsonData->ReceiptId)
            && isset($jsonData->ReceivedCash)
            && isset($jsonData->ReceivedCard)
            && isset($jsonData->Time)
            && isset($jsonData->TotalVAT)
            && isset($jsonData->IsRefund)
            && isset($jsonData->ReceiptType)
            && isset($jsonData->ExtraInfo)
            && isset($jsonData->ExtraInfo->PhoneNumber)
            && (isset($jsonData->RefundInfo) && isset($jsonData->RefundInfo->TerminalID) && isset($jsonData->RefundInfo->ReceiptId) && isset($jsonData->RefundInfo->DateTime) && isset($jsonData->RefundInfo->FiscalSign) || !$cancel);
        $this->assertTrue($allElementsSet);
    }

    public function test_if_all_cancel_json_data_elements_set()
    {
        $cancel = true;
        $jsonData = json_decode($this->createJsonData($cancel));
        $allElementsSet = isset($jsonData->Items)
            && isset($jsonData->ReceiptId)
            && isset($jsonData->ReceivedCash)
            && isset($jsonData->ReceivedCard)
            && isset($jsonData->Time)
            && isset($jsonData->TotalVAT)
            && isset($jsonData->IsRefund)
            && isset($jsonData->ReceiptType)
            && isset($jsonData->ExtraInfo)
            && isset($jsonData->ExtraInfo->PhoneNumber)
            && isset($jsonData->RefundInfo)
            && isset($jsonData->RefundInfo->TerminalID)
            && isset($jsonData->RefundInfo->ReceiptId)
            && isset($jsonData->RefundInfo->DateTime)
            && isset($jsonData->RefundInfo->FiscalSign);
        $this->assertTrue($allElementsSet);
    }

    public function test_if_all_json_data_element_types_are_correct()
    {
//        $array = array(
//            'Items' =>
//                array(
//                    0 =>
//                        (object)array(
//                            'Name' => 'Portativ karnak/Meirende/MR-218A',
//                            'SPIC' => '08518003003027002',
//                            'Units' => 1,
//                            'Price' => 1800000000,
//                            'Amount' => 1000,
//                            'CommissionInfo' =>
//                                (object)array(
//                                    'TIN' => '307769761',
//                                ),
//                            'VAT' => 192857142,
//                            'VATPercent' => 12,
//                        ),
//                ),
//            'ReceiptId' => 918584,
//            'ReceivedCash' => 0,
//            'ReceivedCard' => 1800000000,
//            'Time' => '2023-03-02 11:46:54',
//            'TotalVAT' => 192857142,
//            'IsRefund' => 1,
//            'ReceiptType' => 0,
//            'ExtraInfo' =>
//                (object)array(
//                    'PhoneNumber' => '998946853312',
//                ),
//            'RefundInfo' =>
//                (object)array(
//                    'TerminalID' => 'EP000000000117',
//                    'ReceiptId' => '873848',
//                    'DateTime' => '20230214140033',
//                    'FiscalSign' => '232040484406',
//                ),
//        );
        $jsonData = json_decode($this->createJsonData(false));
        $itemTypesCorrect = true;
        foreach ($jsonData->Items as $item) {
            if (
                gettype($item->Name) !== 'string'
                || gettype($item->SPIC) !== 'string'
                || gettype($item->Units) !== 'integer'
                || gettype($item->Price) !== 'integer'
                || gettype($item->Amount) !== 'integer'
                || gettype($item->CommissionInfo) !== 'object'
                || gettype($item->CommissionInfo->TIN) !== 'string'
                || gettype($item->VAT) !== 'integer'
                || gettype($item->VATPercent) !== 'integer'
            ) {
                $itemTypesCorrect = false;
            }
        }

        $this->assertTrue($itemTypesCorrect);
        $restJsonElementTypesCorrect = gettype($jsonData->ReceiptId === 'integer')
            && gettype($jsonData->ReceivedCash === 'integer')
            && gettype($jsonData->ReceivedCard === 'integer')
            && gettype($jsonData->Time === 'string')
            && gettype($jsonData->TotalVAT === 'integer')
            && gettype($jsonData->IsRefund === 'integer')
            && gettype($jsonData->ReceiptType === 'integer')
            && gettype($jsonData->ExtraInfo === 'object')
            && gettype($jsonData->ExtraInfo->PhoneNumber === 'string');
        $this->assertTrue($restJsonElementTypesCorrect);

        $jsonData = json_decode($this->createJsonData(false));
        $itemTypesCorrect = true;
        foreach ($jsonData->Items as $item) {
            if (
                gettype($item->Name) !== 'string'
                || gettype($item->SPIC) !== 'string'
                || gettype($item->Units) !== 'integer'
                || gettype($item->Price) !== 'integer'
                || gettype($item->Amount) !== 'integer'
                || gettype($item->CommissionInfo) !== 'object'
                || gettype($item->CommissionInfo->TIN) !== 'string'
                || gettype($item->VAT) !== 'integer'
                || gettype($item->VATPercent) !== 'integer'
            ) {
                $itemTypesCorrect = false;
            }
        }

        $this->assertTrue($itemTypesCorrect);
        $restJsonElementTypesCorrect = gettype($jsonData->ReceiptId === 'integer')
            && gettype($jsonData->ReceivedCash === 'integer')
            && gettype($jsonData->ReceivedCard === 'integer')
            && gettype($jsonData->Time === 'string')
            && gettype($jsonData->TotalVAT === 'integer')
            && gettype($jsonData->IsRefund === 'integer')
            && gettype($jsonData->ReceiptType === 'integer')
            && gettype($jsonData->ExtraInfo === 'object')
            && gettype($jsonData->ExtraInfo->PhoneNumber === 'string');
        $this->assertTrue($restJsonElementTypesCorrect);
    }

//        $prepaidJsonData = json_decode(UzTaxTrait::createJsonData($uztax->id, $uztax->contract_id, UzTax::IS_REFUND_SELL_PRODUCT, UzTax::RECEIPT_TYPE_PREPAID, null, null));
//        $creditJsonData = json_decode(UzTaxTrait::createJsonData($uztax->id, $uztax->contract_id, UzTax::IS_REFUND_SELL_PRODUCT, UzTax::RECEIPT_TYPE_CREDIT, null, null));
//        $returnSellJsonData = json_decode(UzTaxTrait::createJsonData($uztax->id, $uztax->contract_id, UzTax::IS_REFUND_SELL_PRODUCT, UzTax::RECEIPT_TYPE_SELL, null, null));
//        $returnCreditJsonData = json_decode(UzTaxTrait::createJsonData($uztax->id, $uztax->contract_id, UzTax::IS_REFUND_SELL_PRODUCT, UzTax::RECEIPT_TYPE_SELL, null, null));
    public function test_if_sell_json_data_is_correct()
    {
        $contract = Contract::where('verified', Contract::NOT_VERIFIED)->with('orderProducts')->first();
        $products = $contract->orderProducts;
        $uztax = new UzTax();
        $uztax->contract_id = $contract->id;
        $uztax->save();
        $sellJsonData = json_decode(UzTaxTrait::createJsonData($uztax->id, $uztax->contract_id, UzTax::IS_REFUND_SELL_PRODUCT, UzTax::RECEIPT_TYPE_SELL, null, null));
        $this->assertTrue(isset($sellJsonData));
        $this->assertTrue(isset($sellJsonData->Items));
        $this->assertTrue(count($sellJsonData->Items) > 0);
        foreach ($sellJsonData->Items as $item) {
            $this->assertTrue(isset($item->Name));
            $this->assertTrue(isset($item->SPIC));
            $this->assertTrue(isset($item->Units));
            $this->assertTrue(isset($item->Price));
            $this->assertTrue(isset($item->Amount));
            $this->assertTrue(isset($item->CommissionInfo));
            $this->assertTrue(isset($item->CommissionInfo->TIN));
            $this->assertTrue(isset($item->VAT));
            $this->assertTrue(isset($item->VATPercent));
        }
        $this->assertTrue(isset($sellJsonData->ReceiptId));
        $this->assertTrue(isset($sellJsonData->ReceivedCash));
        $this->assertTrue(isset($sellJsonData->ReceivedCard));
        $this->assertTrue(isset($sellJsonData->Time));
        $this->assertTrue(isset($sellJsonData->TotalVAT));
        $this->assertTrue(isset($sellJsonData->IsRefund));
        $this->assertTrue(isset($sellJsonData->ReceiptType));
        $this->assertTrue(isset($sellJsonData->ExtraInfo));

        $this->assertTrue(count($sellJsonData->Items) === $uztax->contract->orderProducts->count() + ($uztax->contract->generalCompany->is_mfo === 1 ? 1 : 0));

        $nds = NdsStopgagHelper::getActualNds($contract->getRawOriginal('created_at'));
        $this->assertEquals(count($sellJsonData->Items), $products->count());
        for ($i = 0; $i < count($sellJsonData->Items); $i++) {
            $productPrice = $products[$i]->price * $products[$i]->amount;
            $this->assertEquals($sellJsonData->Items[$i]->Name, $products[$i]->name);
            $this->assertEquals($sellJsonData->Items[$i]->SPIC, $products[$i]->psic_code);
            $this->assertEquals($sellJsonData->Items[$i]->Units, $products[$i]->unit_id);
            $this->assertEquals($sellJsonData->Items[$i]->Price, $products[$i]->price * $products[$i]->amount * 100);
            $this->assertEquals($sellJsonData->Items[$i]->CommissionInfo->TIN, $uztax->contract->generalCompany->is_mfo === 1 ? $contract->company->inn : $contract->generalCompany->inn);
            $this->assertEquals($sellJsonData->Items[$i]->VAT, (int)(($contract->contractCompanySetting->nds === 1 ? $productPrice / (1 + $nds) * $nds : $productPrice * $nds) * 100));
            $this->assertEquals($sellJsonData->Items[$i]->VATPercent, $nds * 100);
        }
        $totalPrice = OrderProduct::where('order_id', $contract->order_id)->sum(DB::raw('price * amount')) * 100;
        $this->assertEquals($sellJsonData->ReceiptId, $uztax->id);
        $this->assertEquals($sellJsonData->ReceivedCash, 0);
        $this->assertEquals($sellJsonData->ReceivedCard, OrderProduct::where('order_id', $contract->order_id)->sum(DB::raw('price * amount')) * 100);
        $this->assertEquals($sellJsonData->Time, Carbon::parse($contract->updated_at)->format('Y-m-d H:i:s'));
        $this->assertEquals($sellJsonData->TotalVAT, (int)($contract->contractCompanySetting->nds === 1 ? $totalPrice / (1 + $nds) * $nds : $totalPrice * $nds));
        $this->assertEquals($sellJsonData->IsRefund, UzTax::IS_REFUND_SELL_PRODUCT);
        $this->assertEquals($sellJsonData->ReceiptType, UzTax::RECEIPT_TYPE_SELL);
        $this->assertEquals($sellJsonData->ExtraInfo->PhoneNumber, preg_replace('/[^0-9]/', '', $contract->buyer->phone));
    }

    public function test_if_sell_json_data_is_correct_when_contract_belongs_to_resus_and_mfo()
    {
        $contract = Contract::where(['verified' => Contract::NOT_VERIFIED, 'status' => Contract::STATUS_ACTIVE])
            ->whereIn('company_id', Company::resus_COMPANY_ID)
            ->where('period', '!=', 3)
            ->whereHas('generalCompany', function ($query) {
            $query->where('is_mfo', 1);
        })->with('orderProducts')->first();
        $products = $contract->orderProducts;
        $uztax = new UzTax();
        $uztax->contract_id = $contract->id;
        $uztax->save();
        $sellJsonData = json_decode(UzTaxTrait::createJsonData($uztax->id, $uztax->contract_id, UzTax::IS_REFUND_SELL_PRODUCT, UzTax::RECEIPT_TYPE_SELL, null, null));
        $this->assertTrue(isset($sellJsonData));
        $this->assertTrue(isset($sellJsonData->Items));
        $this->assertTrue(count($sellJsonData->Items) > 0);
        foreach ($sellJsonData->Items as $item) {
            $this->assertTrue(isset($item->Name));
            $this->assertTrue(isset($item->SPIC));
            $this->assertTrue(isset($item->Units));
            $this->assertTrue(isset($item->Price));
            $this->assertTrue(isset($item->Amount));
            $this->assertTrue(isset($item->CommissionInfo));
            $this->assertTrue(isset($item->CommissionInfo->TIN));
            $this->assertTrue(isset($item->VAT));
            $this->assertTrue(isset($item->VATPercent));
        }
        $this->assertTrue(isset($sellJsonData->ReceiptId));
        $this->assertTrue(isset($sellJsonData->ReceivedCash));
        $this->assertTrue(isset($sellJsonData->ReceivedCard));
        $this->assertTrue(isset($sellJsonData->Time));
        $this->assertTrue(isset($sellJsonData->TotalVAT));
        $this->assertTrue(isset($sellJsonData->IsRefund));
        $this->assertTrue(isset($sellJsonData->ReceiptType));
        $this->assertTrue(isset($sellJsonData->ExtraInfo));

        $this->assertTrue(count($sellJsonData->Items) === 1);

        $nds = NdsStopgagHelper::getActualNds($contract->getRawOriginal('created_at'));
        $this->assertEquals(count($sellJsonData->Items), $products->count());
        for ($i = 0; $i < count($sellJsonData->Items); $i++) {
            $totalServicePrice = $products[$i]->price * $products[$i]->amount;
            $this->assertEquals($sellJsonData->Items[$i]->Name, 'Вознаграждение за право использования ПО');
            $this->assertEquals($sellJsonData->Items[$i]->SPIC, '0305008002000000');
            $this->assertEquals($sellJsonData->Items[$i]->Units, 1);
            $this->assertEquals($sellJsonData->Items[$i]->Price, $products[$i]->price * $products[$i]->amount * 100);
            $this->assertEquals($sellJsonData->Items[$i]->CommissionInfo->TIN, '308349548');
            $this->assertEquals($sellJsonData->Items[$i]->VAT, 0);
            $this->assertEquals($sellJsonData->Items[$i]->VATPercent, 0);
        }
        $totalPrice = OrderProduct::where('order_id', $contract->order_id)->sum(DB::raw('price * amount')) * 100;
        $this->assertEquals($sellJsonData->ReceiptId, $uztax->id);
        $this->assertEquals($sellJsonData->ReceivedCash, 0);
        $this->assertEquals($sellJsonData->ReceivedCard, OrderProduct::where('order_id', $contract->order_id)->sum(DB::raw('price * amount')) * 100);
        $this->assertEquals($sellJsonData->Time, Carbon::parse($contract->updated_at)->format('Y-m-d H:i:s'));
        $this->assertEquals($sellJsonData->TotalVAT, (int)($contract->contractCompanySetting->nds === 1 ? $totalPrice / (1 + $nds) * $nds : $totalPrice * $nds));
        $this->assertEquals($sellJsonData->IsRefund, UzTax::IS_REFUND_SELL_PRODUCT);
        $this->assertEquals($sellJsonData->ReceiptType, UzTax::RECEIPT_TYPE_SELL);
        $this->assertEquals($sellJsonData->ExtraInfo->PhoneNumber, preg_replace('/[^0-9]/', '', $contract->buyer->phone));
    }

    public function test_if_return_sell_json_data_is_correct()
    {
        $contract = Contract::where('verified', Contract::NOT_VERIFIED)->with('orderProducts')->first();
        $products = $contract->orderProducts;
        $uztax = new UzTax();
        $uztax->contract_id = $contract->id;
        $uztax->save();
        $sellJsonData = json_decode(UzTaxTrait::createJsonData($uztax->id, $uztax->contract_id, UzTax::IS_REFUND_RETURN_PRODUCT, UzTax::RECEIPT_TYPE_SELL, null, null));
        $this->assertTrue(isset($sellJsonData));
        $this->assertTrue(isset($sellJsonData->Items));
        $this->assertTrue(count($sellJsonData->Items) > 0);
        foreach ($sellJsonData->Items as $item) {
            $this->assertTrue(isset($item->Name));
            $this->assertTrue(isset($item->SPIC));
            $this->assertTrue(isset($item->Units));
            $this->assertTrue(isset($item->Price));
            $this->assertTrue(isset($item->Amount));
            $this->assertTrue(isset($item->CommissionInfo));
            $this->assertTrue(isset($item->CommissionInfo->TIN));
            $this->assertTrue(isset($item->VAT));
            $this->assertTrue(isset($item->VATPercent));
        }
        $this->assertTrue(isset($sellJsonData->ReceiptId));
        $this->assertTrue(isset($sellJsonData->ReceivedCash));
        $this->assertTrue(isset($sellJsonData->ReceivedCard));
        $this->assertTrue(isset($sellJsonData->Time));
        $this->assertTrue(isset($sellJsonData->TotalVAT));
        $this->assertTrue(isset($sellJsonData->IsRefund));
        $this->assertTrue(isset($sellJsonData->ReceiptType));
        $this->assertTrue(isset($sellJsonData->ExtraInfo));

        $this->assertTrue(count($sellJsonData->Items) === $uztax->contract->orderProducts->count() + ($uztax->contract->generalCompany->is_mfo === 1 ? 1 : 0));

        $nds = NdsStopgagHelper::getActualNds($contract->getRawOriginal('created_at'));
        $this->assertEquals(count($sellJsonData->Items), $products->count());
        for ($i = 0; $i < count($sellJsonData->Items); $i++) {
            $productPrice = $products[$i]->price * $products[$i]->amount;
            $this->assertEquals($sellJsonData->Items[$i]->Name, $products[$i]->name);
            $this->assertEquals($sellJsonData->Items[$i]->SPIC, $products[$i]->psic_code);
            $this->assertEquals($sellJsonData->Items[$i]->Units, $products[$i]->unit_id);
            $this->assertEquals($sellJsonData->Items[$i]->Price, $products[$i]->price * $products[$i]->amount * 100);
            $this->assertEquals($sellJsonData->Items[$i]->CommissionInfo->TIN, $uztax->contract->generalCompany->is_mfo === 1 ? $contract->company->inn : $contract->generalCompany->inn);
            $this->assertEquals($sellJsonData->Items[$i]->VAT, (int)(($contract->contractCompanySetting->nds === 1 ? $productPrice / (1 + $nds) * $nds : $productPrice * $nds) * 100));
            $this->assertEquals($sellJsonData->Items[$i]->VATPercent, $nds * 100);
        }
        $totalPrice = OrderProduct::where('order_id', $contract->order_id)->sum(DB::raw('price * amount')) * 100;
        $this->assertEquals($sellJsonData->ReceiptId, $uztax->id);
        $this->assertEquals($sellJsonData->ReceivedCash, 0);
        $this->assertEquals($sellJsonData->ReceivedCard, OrderProduct::where('order_id', $contract->order_id)->sum(DB::raw('price * amount')) * 100);
        $this->assertEquals($sellJsonData->Time, Carbon::parse($contract->updated_at)->format('Y-m-d H:i:s'));
        $this->assertEquals($sellJsonData->TotalVAT, (int)($contract->contractCompanySetting->nds === 1 ? $totalPrice / (1 + $nds) * $nds : $totalPrice * $nds));
        $this->assertEquals($sellJsonData->IsRefund, UzTax::IS_REFUND_RETURN_PRODUCT);
        $this->assertEquals($sellJsonData->ReceiptType, UzTax::RECEIPT_TYPE_SELL);
        $this->assertEquals($sellJsonData->ExtraInfo->PhoneNumber, preg_replace('/[^0-9]/', '', $contract->buyer->phone));
        $this->assertEquals($sellJsonData->RefundInfo->TerminalID, $contract->uzTaxUrl->terminal_id);
        $this->assertEquals($sellJsonData->RefundInfo->ReceiptId, $contract->uzTaxUrl->id);
        $this->assertEquals($sellJsonData->RefundInfo->DateTime, Carbon::parse(json_decode($contract->uzTaxUrl->json_data)->Time)->format('YmdHis'));
        $this->assertEquals($sellJsonData->RefundInfo->FiscalSign, $contract->uzTaxUrl->fiscal_sign);
    }


//    array:9 [
//        "Items" => array:1 [
//            0 => array:8 [
//                "Name" => "Велотренажёр",
//                "SPIC" => "08517001001018015",
//                "Units" => 0,
//                "Price" => 21053017,
//                "Amount" => 1000,
//                "CommissionInfo" => array:1 [
//                "TIN" => "4432432432432",
//                ]
//                "VAT" => 0,
//                "VATPercent" => 0,
//            ]
//        ]
//        "ReceiptId" => 684095,
//        "ReceivedCash" => 0,
//        "ReceivedCard" => 21053017,
//        "Time" => "2021-07-12 08:42:07",
//        "TotalVAT" => 0,
//        "IsRefund" => 0,
//        "ReceiptType" => 2,
//        "ExtraInfo" => array:1 [
//            "PhoneNumber" => "1176823",
//        ]
//    ]

    public function test_if_credit_json_data_is_correct()
    {
        $payment = DB::select("select * from payments where `payment_system` <> 'DEPOSIT'
                         and `status` = 1
                         and (`amount` > 100 or `amount` < -100)
                         and `type` in ('auto', 'user_auto', 'refund')
                         and contract_id not in (select id from contracts where general_company_id in (select id from general_companies where is_mfo = 1)) limit 1");
        $payment = reset($payment);

        $uztax = UzTax::where('payment_id', $payment->id)->first();
        $contract = Contract::with('orderProducts')->find($uztax->contract_id);

        $products = $contract->orderProducts;

        $creditJsonData = json_decode(UzTaxTrait::createJsonData($uztax->id, $uztax->contract_id, UzTax::IS_REFUND_SELL_PRODUCT, UzTax::RECEIPT_TYPE_CREDIT, $payment));
        $this->assertTrue(isset($creditJsonData));
        $this->assertTrue(isset($creditJsonData->Items));
        $this->assertTrue(count($creditJsonData->Items) > 0);
        foreach ($creditJsonData->Items as $item) {
            $this->assertTrue(isset($item->Name));
            $this->assertTrue(isset($item->SPIC));
            $this->assertTrue(isset($item->Units));
            $this->assertTrue(isset($item->Price));
            $this->assertTrue(isset($item->Amount));
            $this->assertTrue(isset($item->CommissionInfo));
            $this->assertTrue(isset($item->CommissionInfo->TIN));
            $this->assertTrue(isset($item->VAT));
            $this->assertTrue(isset($item->VATPercent));
        }
        $this->assertTrue(isset($creditJsonData->ReceiptId));
        $this->assertTrue(isset($creditJsonData->ReceivedCash));
        $this->assertTrue(isset($creditJsonData->ReceivedCard));
        $this->assertTrue(isset($creditJsonData->Time));
        $this->assertTrue(isset($creditJsonData->TotalVAT));
        $this->assertTrue(isset($creditJsonData->IsRefund));
        $this->assertTrue(isset($creditJsonData->ReceiptType));
        $this->assertTrue(isset($creditJsonData->ExtraInfo));

        $this->assertTrue(count($creditJsonData->Items) === $products->count());

        $this->assertEquals(count($creditJsonData->Items), $products->count());
        for ($i = 0; $i < count($creditJsonData->Items); $i++) {
            $this->assertEquals($creditJsonData->Items[$i]->Name, $products[$i]->name);
            $this->assertEquals($creditJsonData->Items[$i]->SPIC, $products[$i]->psic_code);
            $this->assertEquals($creditJsonData->Items[$i]->Units, $products[$i]->unit_id);
            $this->assertEquals($creditJsonData->Items[$i]->Price, $payment->amount * 100 / $products->count());
            $this->assertEquals($creditJsonData->Items[$i]->CommissionInfo->TIN, $uztax->contract->generalCompany->is_mfo === 1 ? $contract->company->inn : $contract->generalCompany->inn);
            $this->assertEquals($creditJsonData->Items[$i]->VAT, 0);
            $this->assertEquals($creditJsonData->Items[$i]->VATPercent, 0);
        }
        $this->assertEquals($creditJsonData->ReceiptId, $uztax->id);
        $this->assertEquals($creditJsonData->ReceivedCash, 0);
        $this->assertEquals($creditJsonData->ReceivedCard, $payment->amount * 100);
        $this->assertEquals($creditJsonData->Time, Carbon::parse($payment->created_at)->format('Y-m-d H:i:s'));
        $this->assertEquals($creditJsonData->TotalVAT, 0);
        $this->assertEquals($creditJsonData->IsRefund, UzTax::IS_REFUND_SELL_PRODUCT);
        $this->assertEquals($creditJsonData->ReceiptType, UzTax::RECEIPT_TYPE_CREDIT);
        $this->assertEquals($creditJsonData->ExtraInfo->PhoneNumber, preg_replace('/[^0-9]/', '', $contract->buyer->phone));
    }

    public function test_if_return_credit_json_data_is_correct()
    {
        $payment = DB::select("select * from payments where `payment_system` <> 'DEPOSIT'
                         and `status` = 1
                         and (`amount` > 100 or `amount` < -100)
                         and `type` in ('auto', 'user_auto', 'refund')
                         and contract_id not in (select id from contracts where general_company_id in (select id from general_companies where is_mfo = 1)) limit 1");

        $payment = reset($payment);


        $uztax = UzTax::where('payment_id', $payment->id)->first();
        $contract = Contract::with('orderProducts')->find($uztax->contract_id);

        $products = $contract->orderProducts;

        $returnCreditJsonData = json_decode(UzTaxTrait::createJsonData($uztax->id, $uztax->contract_id, UzTax::IS_REFUND_RETURN_PRODUCT, UzTax::RECEIPT_TYPE_CREDIT, $payment));
//        dd("RETURN DATA", $returnCreditJsonData);
        $this->assertTrue(isset($returnCreditJsonData));
        $this->assertTrue(isset($returnCreditJsonData->Items));
        $this->assertTrue(count($returnCreditJsonData->Items) > 0);
        foreach ($returnCreditJsonData->Items as $item) {
            $this->assertTrue(isset($item->Name));
            $this->assertTrue(isset($item->SPIC));
            $this->assertTrue(isset($item->Units));
            $this->assertTrue(isset($item->Price));
            $this->assertTrue(isset($item->Amount));
            $this->assertTrue(isset($item->CommissionInfo));
            $this->assertTrue(isset($item->CommissionInfo->TIN));
            $this->assertTrue(isset($item->VAT));
            $this->assertTrue(isset($item->VATPercent));
        }
        $this->assertTrue(isset($returnCreditJsonData->ReceiptId));
        $this->assertTrue(isset($returnCreditJsonData->ReceivedCash));
        $this->assertTrue(isset($returnCreditJsonData->ReceivedCard));
        $this->assertTrue(isset($returnCreditJsonData->Time));
        $this->assertTrue(isset($returnCreditJsonData->TotalVAT));
        $this->assertTrue(isset($returnCreditJsonData->IsRefund));
        $this->assertTrue(isset($returnCreditJsonData->ReceiptType));
        $this->assertTrue(isset($returnCreditJsonData->ExtraInfo));

        $this->assertTrue(count($returnCreditJsonData->Items) === $products->count());
        $this->assertEquals(count($returnCreditJsonData->Items), $products->count());
        for ($i = 0; $i < count($returnCreditJsonData->Items); $i++) {
            $this->assertEquals($returnCreditJsonData->Items[$i]->Name, $products[$i]->name);
            $this->assertEquals($returnCreditJsonData->Items[$i]->SPIC, $products[$i]->psic_code);
            $this->assertEquals($returnCreditJsonData->Items[$i]->Units, $products[$i]->unit_id);
            $this->assertEquals($returnCreditJsonData->Items[$i]->Price, $payment->amount * 100 / $products->count());
            $this->assertEquals($returnCreditJsonData->Items[$i]->CommissionInfo->TIN, $uztax->contract->generalCompany->is_mfo === 1 ? $contract->company->inn : $contract->generalCompany->inn);
            $this->assertEquals($returnCreditJsonData->Items[$i]->VAT, 0);
            $this->assertEquals($returnCreditJsonData->Items[$i]->VATPercent, 0);
        }
        $this->assertEquals($returnCreditJsonData->ReceiptId, $uztax->id);
        $this->assertEquals($returnCreditJsonData->ReceivedCash, 0);
        $this->assertEquals($returnCreditJsonData->ReceivedCard, $payment->amount * 100);
        $this->assertEquals($returnCreditJsonData->Time, Carbon::parse($payment->created_at)->format('Y-m-d H:i:s'));
        $this->assertEquals($returnCreditJsonData->TotalVAT, 0);
        $this->assertEquals($returnCreditJsonData->IsRefund, UzTax::IS_REFUND_RETURN_PRODUCT);
        $this->assertEquals($returnCreditJsonData->ReceiptType, UzTax::RECEIPT_TYPE_CREDIT);
        $this->assertEquals($returnCreditJsonData->ExtraInfo->PhoneNumber, preg_replace('/[^0-9]/', '', $contract->buyer->phone));
        $this->assertEquals($returnCreditJsonData->RefundInfo->TerminalID, $contract->uzTaxCredit->terminal_id);
        $this->assertEquals($returnCreditJsonData->RefundInfo->ReceiptId, $contract->uzTaxCredit->id);
        $this->assertEquals($returnCreditJsonData->RefundInfo->DateTime, Carbon::parse(json_decode($contract->uzTaxUrl->json_data)->Time)->format('YmdHis'));
        $this->assertEquals($returnCreditJsonData->RefundInfo->FiscalSign, $contract->uzTaxCredit->fiscal_sign);
    }
}
