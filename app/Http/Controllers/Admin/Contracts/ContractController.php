<?php

namespace App\Http\Controllers\Admin\Contracts;

use App\Http\Controllers\Controller;
use App\Models\AvailablePeriod;
use App\Models\Contract;
use App\Models\ContractPaymentsSchedule;
use App\Models\OrderProduct;
use App\Models\Partner;
use App\Services\API\V3\BaseService;
use App\Services\API\V3\BuyerService;
use App\Services\API\V3\UzTaxService;
use App\Services\MFO\AccountingEntryService;
use App\Services\MFO\MFOOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ContractController extends Controller
{
    public function partlyCancel(Request $request, UzTaxService $uzTaxService): array
    {
        $validation = Validator::make($request->all(),[
            'contract_id' => 'required|exists:contracts,id',
            'external_id' => 'required|string',
            'products' => 'required|array',
            'products.*.id' => 'required|exists:order_products,id',
            'products.*.amount' => 'required|numeric|gte:1',
        ]);
        if($validation->fails()){
            return ['status' => 'error','message' => $validation->errors()->getMessages()];
        }
        $input = $validation->validated();
        //Extra check
        $contract = Contract::find($input['contract_id']);
        if (!isset($contract)) {
            BaseService::handleError([__('api.contract_not_found')]);
        }
        $has_external_id = OrderProduct::query()
                                    ->where('external_id','=',$input['external_id'])
                                    ->where('order_id','=',$contract->order_id)
                                    ->exists();
        if($has_external_id){
            BaseService::handleError(['Внешний идентификатор должен быть уникальным']);
        }
        $partner = Partner::find($contract->partner_id);
        $company = $partner->company;
        if (!$company) {
            BaseService::handleError([__('company.company_not_found')]);
        }
        $available_period = AvailablePeriod::find($contract->price_plan_id);
        if (!isset($available_period)) {
            BaseService::handleError(['Период не найден']);
        }
        if (!in_array($contract->status,[1,2])) {
            BaseService::handleError(['Неверный статус договора (статус должен быть 1 или 2)']);
        }
        $error_messages = [];
        $products_array = [];
        $products = [];
        $amount_of_products_to_cancel = 0;
        $amount_of_total_products = 0;
        foreach ($input['products'] as $key => $item){
            $order_product = OrderProduct::where('order_id','=',$contract->order_id)
                ->where('id','=',$item['id'])
                ->where('status','=',OrderProduct::STATUS_ACTIVE)
                ->first();
            if(!$order_product){
                $error_messages[] = 'ID продукта не найден';
                continue;
            }
            if($order_product->amount < $item['amount']){
                $error_messages[] = 'Ошибка в количестве товара';
                continue;
            }
            $amount_of_products_to_cancel += $item['amount'];
            $amount_of_total_products += $order_product->amount;
            $products_array[$key]['amount'] = $item['amount'];
            $products_array[$key]['price'] = $order_product->price_discount;
            $products_array[$key]['product_id'] = $order_product->product_id;
            $products_array[$key]['product_type'] = $order_product->product_type;
            $products[] = $order_product;
        }
        if($amount_of_total_products < $amount_of_products_to_cancel){
            $error_messages[] = 'Ошибка в количестве товара';
        }
        if(count($error_messages) > 0){
            BaseService::handleError($error_messages);
        }

        $mfo_order_service = new MFOOrderService();
        $calculation = $mfo_order_service->calculateByPeriod($company, $available_period, $products_array,true,$contract->buyer);
        if($contract->total - $calculation['total'] == 0){
            BaseService::handleError(['Необходимо полностью отменить договор']);
        }
        //Entries
        $service = new AccountingEntryService();
        $response = $service->partialCancellation($contract->id,$contract->total - $calculation['total'],false);
        if(isset($response['status']) && $response['status'] == 'error'){
            BaseService::handleError($response['message']);
        }
        //CBU
        $service->partialCancellation($contract->id,$contract->total - $calculation['total'],true);

        //Logic
        foreach ($products as $key => $product) {
            $product->amount -= $input['products'][$key]['amount'];
            $product->save();
            //create duplicate with status 2 (canceled)
            $canceled_product = $product->toArray();
            $canceled_product['amount'] = $input['products'][$key]['amount'];
            $canceled_product['created_at'] = now();
            $canceled_product['updated_at'] = now();
            $canceled_product['status'] = OrderProduct::STATUS_CANCELED;
            $canceled_product['external_id'] = $input['external_id'];
            OrderProduct::query()->create($canceled_product);
        }

        //Update contract
        $contract->total -= $calculation['total'];
        $contract->balance -= $calculation['total'];
        $contract->save();

        $order = $contract->order;
        $limit = $calculation['partner'];

        //Update order
        $order->total -= $calculation['total'];
        $order->partner_total -= $calculation['partner'];
        $order->credit -= $calculation['partner'];
        $order->save();

        //Return limit
        if($available_period->is_mini_loan) {
            $contract->buyer->settings->mini_balance += $limit;
        }else {
            $contract->buyer->settings->balance += $limit;
        }
        $contract->buyer->settings->save();

        //Update schedules
        $schedules = ContractPaymentsSchedule::where('contract_id','=',$contract->id)->get();
        if(count($schedules) > 0){
            foreach ($schedules as $key => $schedule) {
                $schedule->total -= $calculation['contract']['payments'][$key]['total'];
                $schedule->price -= $calculation['contract']['payments'][$key]['origin'];
                $schedule->balance -= $calculation['contract']['payments'][$key]['total'];
                $schedule->save();
            }
        }
        $uzTaxService->createPartialCancellationCheques($contract);
        BaseService::handleResponse();
    }
}
