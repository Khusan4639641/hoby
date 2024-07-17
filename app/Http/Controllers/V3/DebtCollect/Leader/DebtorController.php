<?php

namespace App\Http\Controllers\V3\DebtCollect\Leader;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Http\Requests\DebtCollect\UpdateDistrictRequest;

use App\Helpers\EncryptHelper;
use App\Helpers\FileHelper;

use App\Services\API\V3\BaseService;

use Illuminate\Support\Facades\Auth;

use App\Models\Buyer;
use App\Models\DebtCollect\DebtCollectContract;
use App\Models\DebtCollect\DebtCollectDebtorAddressHistory;
use App\Models\DebtCollect\Debtor;
use App\Models\Letter;
use App\Models\V3\District;

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

    public function attachDistrict(Buyer $debtor, Request $request)
    {
        $district = District::with(['region'])->findOrFail($request->district_id);
        $debtor->update(['local_region' => $district->cbu_id]);
        $debtor->update(['region' => $district->region->cbu_id]);

        return response()->json();
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

    public function analytic(Request $request)
    {
        return Debtor::search($request->search)
            ->whereHas('contracts', function($query) {
                $query->where('expired_days', '>=', 90)->whereIn('status', [1,3,4]);
            })
            ->with([
                'district' => function($query) {
                    $query->select('id', 'cbu_id', 'name', 'region_id');
                },
                'region' => function($query) {
                    $query->select('id', 'cbu_id', 'name');
                }
            ])
            ->paginate(null, ['id', 'name', 'surname', 'patronymic', 'phone', 'region', 'local_region']);
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
        $contract_data = [
            'id' => $contract->id,
            'created_at' => $contract->created_at,
            'debtor' => null,
            'debts' => [
                'autopay' => null,
                'collect_cost' => null,
                'total' => null,
            ],
            'debt_sum' => 'Нет данных', // need as fallback
            'order' => null,
            'products' => [],
            'company' => [ // filled array need as fallback
                'name' => 'Нет данных',
                'phone' => 'Нет данных',
            ],
            'files' => [ // filled array need as fallback
                'act' => null,
                'selfie_with_product' => null
            ],

        ];

        $debtor = $contract->debtor;
        if($debtor) {
            $debtor_data = [
                'id' => $debtor->id,
                'full_name' => $debtor->full_name,
                'phone' => $debtor->phone,
                'addresses' => [],
                'settings' => null
            ];

            $address_registration = $debtor->addressRegistration()->latest()->first();
            if($address_registration) {
                $debtor_data['addresses']['registration'] = $address_registration->address;
            }

            $address_residential = $debtor->addressResidential()->latest()->first();
            if($address_residential) {
                $debtor_data['addresses']['residential'] = $address_residential->address;
            }

            $buyer_settings = $debtor->settings()->latest()->first();
            if($buyer_settings) {
                $debtor_data['settings'] = [
                    'limit' => $buyer_settings->limit,
                    'mini_limit' => $buyer_settings->mini_limit,
                    'period' => $buyer_settings->period,
                ];
            }

            $contract_data['debtor'] = $debtor_data;
        }


        // debt_sum / Проверяем есть ли запись "Первое письмо домой" в таблице letters
        $letter_to_residency = $contract->letters()
            ->whereType(Letter::LETTER_TYPE_RESIDENCY) // 'letter-to-residency' = "Первое письмо домой"
            ->whereNotNull("letters.amounts")
            ->orderByDesc("letters.id")
            ->orderByDesc("letters.created_at")
            ->first()
        ;

        if ( !$letter_to_residency ) {
            return response()->json([
                'status' => "error",
                'message' => __('panel/letters.err_letter_to_residency_is_absent')
            ], 400);
        }

        $amounts = $letter_to_residency->amounts;
        if ( empty($amounts) ) {
            return response()->json([
                'status' => "error",
                'message' => __('panel/letters.err_letter_amounts_field_is_empty')
            ], 400);
        }

        $payments_sum_balance = $amounts["payments_sum_balance"] ?? 0;
        $percent = $amounts["percent"] ?? 0;
        $notary_fee = $contract->collcost->notary->fee ?? 0;
        $debt_sum = round($payments_sum_balance + $percent + $notary_fee);

        $collect_cost = $contract->collcost;

        if ( $collect_cost ) {
            $contract_data['debts']['collect_cost'] = [
                'percent' => $collect_cost->persent,
                'fix'     => $collect_cost->fix,
                'balance' => $collect_cost->balance,
            ];
        }
        $contract_data['debt_sum'] = $debt_sum;
        $contract_data['debts']['total'] = $debt_sum;


        $autopay_history = $contract->autopay_history;
        if(count($autopay_history)) {
            $percent = 0;
            $balance = 0;
            foreach ($autopay_history as $autopay) {
                $percent += $autopay->percent;
                $balance += $autopay->balance;
            }

            $contract_data['debts']['autopay'] = [
                'percent' => $percent,
                'balance' => $balance,
            ];
        }

        $order = $contract->order;
        if($contract->order) {
            $contract_data['order'] = [
                'id' => $order->id,
                'deposit' => $contract->deposit,
                'total' => $contract->total,
                'balance' => $contract->balance,
                'expired_days' => $contract->expired_days,
                'period' => $contract->period
            ];

            foreach($order->products as $product) {
                $contract_data['products'][] = [
                    'id' => $product->id,
                    'category' => $product->category->language->title,
                    'name' => $product->original_name ?? $product->name,
                    'price' => $product->price,
                    'amount' => $product->amount,
                ];
            }
        }

        $company = $contract->company;
        if($company) {
            $contract_data['company'] = [
                'id' => $company->id,
                'name' => $company->name,
                'phone' => $company->phone,
                'address' => $company->address,
                'legal_address' => $company->legal_address,
                'inn' => $company->inn,
                'payment_account' => $company->payment_account,
                'manager' => $company->manager->full_name,
            ];
        }

        $general_company = $contract->generalCompany;
        if($general_company) {
            $contract_data['company']['general_company_name_uzlat'] = $general_company->name_uzlat ?? null;
            $contract_data['company']['general_company_is_tpp']     = $general_company->is_tpp     ?? null;

            $contract_data['company']['general_company_director_uzlat'] = $general_company->director_uzlat ?? null;
            $contract_data['company']['general_company_stamp']          = FileHelper::url($general_company->stamp);
            $contract_data['company']['call_center']                    = callCenterNumber(4);
        }

        $contract_data['schedules'] = $contract->schedules()->get([
            'payment_date',
            'real_payment_date',
            'paid_at',
            'total',
            'balance',
        ]);

        $act = $contract->acts()->latest()->first();
        if($act) {
            $contract_data['files']['act'] = FileHelper::url($act->path);
        }

        $clientPhoto = $contract->clientPhoto()->latest()->first();
        if($clientPhoto) {
            $contract_data['files']['selfie_with_product'] = FileHelper::url($clientPhoto->path);
        }

        return $contract_data;
    }

    public function getSchedules(Request $request, DebtCollectContract $contract) {
        return $contract->schedules;
    }
}
