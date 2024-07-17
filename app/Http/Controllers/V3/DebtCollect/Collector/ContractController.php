<?php

namespace App\Http\Controllers\V3\DebtCollect\Collector;

use App\Aggregators\DebtCollectActionsAggregators\ActionsAggregator;
use App\Helpers\EncryptHelper;
use App\Helpers\FileHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\DebtCollect\StoreContractActionRequest;
use App\Models\Contract;
use App\Models\DebtCollect\DebtCollectContract;
use App\Models\DebtCollect\DebtCollector;
use App\Models\DebtCollect\Debtor;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function getContract(DebtCollectContract $contract)
    {
        $debtor = $contract->buyer;

        $contract_data = [
            'id' => $contract->id,
            'debtor' => [
                'id' => $debtor->id,
                'full_name' => $debtor->full_name,
                'phone' => $debtor->phone,
            ],
            'debt_sum' => 'Нет данных',
            'products' => [],
            'company' => [
                'name' => 'Нет данных',
                'phone' => 'Нет данных'
            ],
            'files' => [
                'act' => null,
                'selfie_with_product' => null
            ]

        ];

        $contract->append('debt_sum');
        if($contract->debt_sum) {
            $contract_data['debt_sum'] = $contract->debt_sum;
        }

        if($contract->order) {
            foreach($contract->order->products as $product) {
                $contract_data['products'][] = [
                    'name' => $product->original_name ?? $product->name,
                    'price' => $product->price
                ];
            }
        }

        if($contract->company) {
            $contract_data['company']['name'] = $contract->company->name;
            $contract_data['company']['phone'] = $contract->company->phone;
        }

        $act = $contract->acts()->latest()->first();
        if($act) {
            $contract_data['files']['act'] = FileHelper::url($act->path);
        }

        $clientPhoto = $contract->clientPhoto()->latest()->first();
        if($clientPhoto) {
            $contract_data['files']['selfie_with_product'] = FileHelper::url($clientPhoto->path);
        }

        if($contract->debt_sum) {
            $contract_data['debt_sum'] = $contract->debt_sum;
        }

        return response()->json($contract_data);
    }

    public function addContractAction(StoreContractActionRequest $request, Contract $contract)
    {
        DebtCollector::findOrFail(\Auth::id())->contract_actions()->create($request->merge(['contract_id' => $contract->id])->all());

        return response()->json();
    }

    public function getSchedules(Request $request, DebtCollectContract $contract) {
        return $contract->schedules;
    }
}
