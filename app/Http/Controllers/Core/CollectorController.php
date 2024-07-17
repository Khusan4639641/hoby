<?php

namespace App\Http\Controllers\Core;

use App\Services\API\Core\PaymentHistoryService;
use Illuminate\Http\Request;
use App\Http\Requests\Collector\GetRequest;
use App\Http\Requests\Collector\Contracts\GetRequest as GetContractsRequest;
use App\Http\Requests\Collector\KatmRegion\AttachRequest;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

use App\Models\Collector;
use App\Models\Contract;
use App\Models\KatmRegion;

use App\Helpers\EncryptHelper;
use App\Helpers\FileHelper;

use Illuminate\Database\QueryException;

class CollectorController extends CoreController
{
    function all(GetRequest $request) {
        $region = $request->region;
        $local_region = $request->local_region;
        $search = $request->search;

        $collectorsQuery = Collector::with('katm_regions');

        if($region || $local_region) {
            $collectorsQuery = $collectorsQuery->whereHas('katm_regions', function(Builder $query) use($region, $local_region) {
                if($region) $query->where('region', $region);
                if($local_region) $query->where('local_region', $local_region);
            });
        }

        if($search) {
            $collectorsQuery = $collectorsQuery->whereHas('user', function(Builder $query) use($search) {
                $query->where('name', 'LIKE', "%$search%")
                      ->orWhere('surname', 'LIKE', "%$search%")
                      ->orWhere('patronymic', 'LIKE', "%$search%")
                      ->orWhere('phone', 'LIKE', "%$search%");
            });
        }

        return $collectorsQuery->join('users', 'collectors.user_id', '=', 'users.id')
                               ->orderBy('users.name')
                               ->orderBy('users.surname')
                               ->orderBy('users.patronymic')
                               ->without('user')
                               ->paginate(null, [
                                    'collectors.id', 'collectors.user_id', 'collectors.balance',
                                    'users.id as user_id', 'users.name', 'users.surname', 'users.patronymic', 'users.phone'
                                ]);
    }

    function attachKatmRegion(AttachRequest $request, Collector $collector) {
        $katm_region_id = $request->katm_region_id;

        try {
            $collector->load('katm_regions');
            $collector->katm_regions()->attach($katm_region_id);
        } catch(QueryException $e) {
            $errorCode = $e->errorInfo[1];
            if($errorCode === 1062) {
                return response([
                    // TODO: error.key
                    'error' => 'Dublicate Entry'
                ], 409);
            }
            return response([
                'error' => $e
            ], 500);
        }

        $katm_region = KatmRegion::find($katm_region_id);
        $contracts_ids = Contract::where('expired_days', '>=', 90)->whereHas('buyer', function(Builder $query) use($katm_region) {
            $query->where('local_region', $katm_region->local_region);
        })->pluck('id');

        $collector->contracts()->attach($contracts_ids);

        return response('OK');
    }

    function detachKatmRegion(AttachRequest $request, Collector $collector) {
        $katm_region_id = $request->katm_region_id;

        $collector->load('katm_regions');
        $collector->katm_regions()->detach($katm_region_id);

        $katm_region = KatmRegion::find($katm_region_id);
        $contracts_ids = Contract::where('expired_days', '>=', 90)->whereHas('buyer', function(Builder $query) use($katm_region) {
            $query->where('local_region', $katm_region->local_region);
        })->pluck('id');

        $collector->contracts()->detach($contracts_ids);

        return response('OK');
    }

    private function collector() {
        return Collector::where('user_id', Auth::user()->id)->firstOr(function() {
            return response([
                // TODO: error.key
                'error' => 'User is not Collector'
            ], 403);
        });
    }

    function collectorLocalRegions(Request $request) {
        return $this->collector()->katm_regions;
    }

    function collectorContracts(GetContractsRequest $request) {
        $local_region_id = $request->local_region_id;

        $contractsQuery = $this->collector()->contracts()->with('buyer');
        if($local_region_id) {
            $contractsQuery = $contractsQuery->whereHas('buyer', function(Builder $query) use($local_region_id) {
                $query->where('local_region', $local_region_id);
            });
        }

        return $contractsQuery->latest()->paginate();
    }

    function collectorContract(Request $request, $contract_id) {
        $contract = $this->collector()->contracts()->with(['buyer'])->find($contract_id);
        if($contract === null) {
            return response([
                'error' => 'Collector dont has access to Contract'
            ], 403);
        }

        $buyer = $contract->buyer;

        if($buyer === null) {
            return response([
                'error' => 'Bad contract data'
            ], 500);
        }
        $contract_data = [
            'id' => $contract->id,
            'collector_contract_id' => $contract->pivot->id,
            'fio' => $buyer->fio,
            'phone' => $buyer->phone,
            'region' => $buyer->region,
            'local_region' => $buyer->local_region,
            'passport_number' => 'Нет данных',
            'registration_address' => 'Нет данных',
            'recovery_sum' => 'Нет данных',
            'guarants' => [],
            'products' => [],
            'company' => [
                'name' => 'Нет данных',
                'phone' => 'Нет данных'
            ],
            'files' => [
                'act' => null,
                'passport_with_address' => null,
                'passport_selfie' => null,
                'selfie_with_product' => null
            ]

        ];

        $personals = $buyer->personals;
        if($personals) {
            $contract_data['passport_number'] = EncryptHelper::decryptData($personals->passport_number);
            if($personals->passport_first_page) {
                $contract_data['files']['passport_with_address'] = FileHelper::url($personals->passport_first_page->path);
            }
            if($personals->passport_selfie) {
                $contract_data['files']['passport_selfie'] = FileHelper::url($personals->passport_selfie->path);
            }
        }

        if($buyer->addressRegistration) {
            $contract_data['registration_address']= $buyer->addressRegistration->address;
        }

        $contract->append('recovery_sum');
        if($contract->recovery_sum) {
            $contract_data['recovery_sum'] = number_format($contract->recovery_sum, 2, '.', ' ') . ' сум';
        }

        if(count($buyer->guarants) > 0) {
            foreach($buyer->guarants as $guarant) {
                $contract_data['guarants'][] = [
                    'name' => $guarant->name,
                    'phone' => $guarant->phone
                ];
            }
        }

        if($contract->order) {
            foreach($contract->order->products as $product) {
                $contract_data['products'][] = [
                    'name' => $product->original_name ?? $product->name,
                    'price' => number_format($product->price, 2, '.', ' ')
                ];
            }
        }

        if($contract->company) {
            $contract_data['company']['name'] = $contract->company->name;
            $contract_data['company']['phone'] = $contract->company->phone;
        }

        if($contract->act) {
            $contract_data['files']['act'] = FileHelper::url($contract->act->path);
        }

        if($contract->clientPhoto) {
            $contract_data['files']['selfie_with_product'] = FileHelper::url($contract->clientPhoto->path);
        }


        return $contract_data;
    }

    public function showHistoryPayments(Request $request){
       return (new PaymentHistoryService())->getInfo($request);
    }
}
