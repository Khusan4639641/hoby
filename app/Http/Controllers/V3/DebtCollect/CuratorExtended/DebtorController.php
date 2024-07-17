<?php

namespace App\Http\Controllers\V3\DebtCollect\CuratorExtended;

use App\Http\Controllers\Controller;

use App\Http\Requests\DebtCollect\UpdateDistrictRequest;
use App\Http\Requests\DebtCollect\StoreDebtorActionRequest;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use App\Helpers\EncryptHelper;
use App\Helpers\FileHelper;

use App\Services\API\V3\BaseService;

use App\Models\DebtCollect\DebtCollectContract;
use App\Models\DebtCollect\DebtCollectContractAction;
use App\Models\DebtCollect\DebtCollectCuratorExtended;
use App\Models\DebtCollect\DebtCollectDebtorAddressHistory;
use App\Models\DebtCollect\Debtor;
use App\Models\V3\District;
use App\Models\V3\Region;


class DebtorController extends Controller
{
    public function all(Request $request)
    {
        return Debtor::search($request->search)
            ->whereHas('contracts', function($query) {
                $query->where('expired_days', '>=', 61)->whereIn('status', [1,3,4]);
            })
            ->with([
                'district' => function($query) {
                    $query->select('id', 'cbu_id', 'name', 'region_id');
                },
                'region' => function($query) {
                    $query->select('id', 'cbu_id', 'name');
                }
            ])
            ->orderBy('local_region', 'asc')
            ->paginate(null, ['id', 'name', 'surname', 'patronymic', 'phone', 'region', 'local_region']);
    }

    public function getDebtors(Request $request) {
        $curator = DebtCollectCuratorExtended::findOrFail(Auth::user()->id);
        $districts = $curator->districts()->pluck('districts.id');
        return Debtor::whereHas('district', function($query) use($districts) {
            $query->whereIn('districts.id', $districts);
        })->withOverdueContracts(1)->paginate(null, ['users.id', 'name', 'surname', 'patronymic', 'phone']);
    }

    public function getDebtor(Request $request, Debtor $debtor)
    {
        $debtorData = [
            'id' => $debtor->id,
            'full_name' => $debtor->full_name,
            'registration_address' => null,
            'phone' => $debtor->phone,
            'passport_number' => null,
            'district_id' => $debtor->district->id,
            'guarants' => null,
            'files' => [
                'passport_first_page' => null,
                'passport_with_address' => null,
                'passport_selfie' => null
            ],
            'contracts_count' => $debtor->contracts_count,
            'contracts' =>  [],
            'debt_collect_sum' => $debtor->debt_collect_sum
        ];
        if($debtor->addressRegistration) {
            $debtorData['registration_address'] = $debtor->addressRegistration->address;
        }

        $personals = $debtor->personalData;
        if($personals) {
            $debtorData['passport_number'] = EncryptHelper::decryptData($personals->passport_number);

            $passport_first_page = $personals->passport_first_page()->latest()->first();
            if($passport_first_page) {
                $debtorData['files']['passport_first_page'] = FileHelper::url($passport_first_page->path);
            }

            $passport_with_address = $personals->passport_with_address()->latest()->first();
            if($passport_with_address) {
                $debtorData['files']['passport_with_address'] = FileHelper::url($passport_with_address->path);
            }

            $passport_selfie = $personals->passport_selfie()->latest()->first();
            if($passport_selfie) {
                $debtorData['files']['passport_selfie'] = FileHelper::url($passport_selfie->path);
            }
        }

        foreach ($debtor->guarants as $guarant) {
            $debtorData['guarants'][] = [
                'name' => $guarant->name,
                'phone' => $guarant->phone
            ];
        }
        return $debtorData;
    }

    public function getContracts(Request $request, Debtor $debtor)
    {
        return $debtor->contracts()->orderByDesc('expired_days')->get();
    }

    public function getContract(Request $request, DebtCollectContract $contract)
    {
        $debtor = $contract->debtor;
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

        // Этот контроллер был скопирован с app/Http/Controllers/V3/DebtCollect/Curator/DebtorController.php
        // Так что я не знаю зачем этот код был продублирован, хотя он есть выше, на 119-ой строке.
        if($contract->debt_sum) {
            $contract_data['debt_sum'] = $contract->debt_sum;
        }

        return $contract_data;
    }

    public function getDebtorActions($debtor)
    {
        return response()->json($debtor->actions);
    }

    public function getContractActions($debtor)
    {
        return response()->json(DebtCollectContractAction::whereDebtorId($debtor->id)->get());
    }

    public function addDebtorAction(StoreDebtorActionRequest $request, Debtor $debtor)
    {
        DebtCollectCuratorExtended::findOrFail(Auth::id())->debtor_actions()->create($request->merge(['debtor_id' => $debtor->id])->all());

        return response()->json();
    }

    public function analytic(Request $request)
    {

        $curator = DebtCollectCuratorExtended::findOrFail(Auth::id());
        $curator_districts_id = $curator->districts()->pluck('districts.id');

        return Debtor::search($request->search)
            ->whereHas('contracts', function($query) {
                $query->where('expired_days', '>=', 90);
            })
            ->with([
                'district' => function($query) {
                    $query->select('id', 'cbu_id', 'name', 'region_id');
                },
                'region' => function($query) {
                    $query->select('id', 'cbu_id', 'name');
                }
            ])
            ->whereHas('district', function($query) use($curator_districts_id) {
                $query->whereIn('districts.id', $curator_districts_id);
            })
            ->paginate(null, ['id', 'name', 'surname', 'patronymic', 'phone', 'region', 'local_region'])
        ;
    }

    public function getSchedules(Request $request, DebtCollectContract $contract) {
        return $contract->schedules;
    }

    public function getUzbRegions()
    {
        $regions = Region::select("id", "name")
            ->pluck("name", "id") // pluck выдаёт почему-то в обратном порядке
            ->toArray()
        ;
        return response()->json($regions);
    }

    public function searchUzbRegions(Request $request)
    {
        $regions_query = Region::select("id", "name");

        if ($request->search) {
            $search_arg = "%{$request->search}%";
            $regions_query->where("name", "like", $search_arg);
        }

        $regions = $regions_query->pluck("name", "id")->toArray();

        if (!$regions) {
            return response()->json(__("app.txt_nothing_found"), 404);
        }
        return response()->json($regions);
    }

    public function getRegionDistricts($region_id)
    {
        $districts = District::select("cbu_id", "name")
            ->whereRegionId($region_id)
            ->pluck("name", "cbu_id") // pluck выдаёт почему-то в обратном порядке
            ->toArray()
        ;
        return response()->json($districts);
    }

    public function searchRegionDistricts(Request $request, $region_id)
    {
        $districts_query = District::select("cbu_id", "name")->whereRegionId($region_id);

        if ($request->search) {
            $search_arg = "%{$request->search}%";
            $districts_query->where("name", "like", $search_arg);
        }

        $districts = $districts_query->pluck("name", "cbu_id")->toArray();

        if (!$districts) {
            return response()->json(__("app.txt_nothing_found"), 404);
        }
        return response()->json($districts);
    }

    public function updateDistrict(Debtor $debtor, UpdateDistrictRequest $request)
    {
        $cbu_id  = $request->validated()["cbu_id"];
        $comment = $request->validated()["comment"];
        $file    = $request->validated()["file"];

        $old_district_cbu_id = $debtor->district->cbu_id;
        $new_district = District::with(["region"])->where("cbu_id", $cbu_id )->first();

        $debtor->collectors()->detach();
        $debtor->update([
            "local_region" => $new_district->cbu_id,  // Район внутри области
            "region" => $new_district->region->cbu_id // Область
        ]);

        $file_path = null;
        if ($file) {
            $file_data = [
                "model" => "debt-collect-debtor",
                "element_id" => $debtor->id,
                "type" => "address_change_proof_doc",
            ];
            $fullPath = FileHelper::saveLetterFile($file, $file_data);
            $file_path = FileHelper::url($fullPath);
        }

        $history = new DebtCollectDebtorAddressHistory();
        $history->debtor_id    = $debtor->id;
        $history->past_address = $old_district_cbu_id;
        $history->new_address  = $new_district->cbu_id;
        $history->changer_id   = Auth::id();
        $history->comment      = $comment;
        $history->file_path    = $file_path;
        $history->save();

        BaseService::handleResponse([__("collector.district_updated_successfully")]);
    }

}
