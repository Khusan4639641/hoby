<?php

namespace App\Http\Controllers\V3\DebtCollect\Collector;

use App\Aggregators\DebtCollectActionsAggregators\ActionsAggregator;
use App\Exports\DebtorsByDistrictExport;
use App\Helpers\EncryptHelper;
use App\Helpers\FileHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\DebtCollect\ExportDebtorByDistrictRequest;
use App\Http\Requests\DebtCollect\StoreDebtorActionRequest;
use App\Models\DebtCollect\AddedActionHistory;
use App\Models\DebtCollect\DebtCollector;
use App\Models\DebtCollect\Debtor;
use App\Models\User;
use App\Services\DebtCollect\ActionsProcessing;
use Carbon\Carbon;
use App\Models\V3\District;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DebtorController extends Controller
{
    public function getDebtors(Request $request)
    {
        $collector = DebtCollector::findOrFail(Auth::user()->id);
        $debtors_query = $collector->debtors()
            ->withOverdueContracts(1)
            ->with(['addressRegistration'])
            ->leftJoin('buyer_addresses', 'users.id', '=', 'buyer_addresses.user_id')
            ->where('buyer_addresses.type', '=', 'registration')
            ->orderBy('buyer_addresses.address', 'asc');

        if($request->has('district_id')) {
            $district_id = $request->district_id;
            $debtors_query->whereHas('district', function($query) use($district_id) {
                $query->where('id', $district_id);
            });
        }

        return $debtors_query->paginate();
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
        foreach ($debtor->contracts()->orderByDesc('expired_days')->get() as $contract) {
            $contract_data = [
                'id' => $contract->id,
                'debt_sum' => $contract->append('debt_sum')->debt_sum,
                'expired_days' => $contract->expired_days,
                'status' => $contract->status
            ];

            $debtorData['contracts'][] = $contract_data;
        }
        return response()->json($debtorData);
    }

    public function getContracts(Request $request, Debtor $debtor)
    {
        return $debtor->contracts()->orderByDesc('expired_days')->get();
    }

    public function addDebtorAction(StoreDebtorActionRequest $request, Debtor $debtor)
    {
        $collector = DebtCollector::findOrFail(\Auth::id());
        $action = $collector->debtor_actions()->create($request->merge(['debtor_id' => $debtor->id])->all());

        if($request->get('type') === 'photo') {
            if(!$request->get('content_type')) {
                return response([
                    // TODO: error.key
                    'error' => 'Incorrect content',
                    'error_key' => 'content'
                ], 500);
            }

            $file = FileHelper::uploadNew([
                'model' => 'debt-collect-debtor-action',
                'element_id' => $action->id,
                'user_id' => $collector->id,
                'type' => 'debt-collect-debtor-action-photo',
                'files' => [$request->file('content_file')]
            ], true);

            if(!$file) {
                return response([
                    // TODO: error.key
                    'error' => 'Can\'t create file',
                ], 500);
            }

            $content = [
                'type' => $request->get('content_type'),
                'file' => $file->path
            ];

            $action->content = json_encode($content);
            $action->save();
        }

        ActionsProcessing::sync($collector->id, $debtor->id);

        return response()->json();
    }

    public function promisedPaymentsByDebtor(Request $request, Debtor $debtor)
    {
        return response()->json(array_values(collect(ActionsAggregator::getByDebtors($debtor->id))->sortByDesc('created_at')->toArray()));
    }

    public function exportDebtorByDistrict(ExportDebtorByDistrictRequest $request)
    {
        return (new DebtorsByDistrictExport($request->district_id, $request->collector_id))
            ->download(str_replace(' ', '_',mb_strtolower(District::find($request->district_id)->name)).date('_Y_m_d_h_i_s').'.xlsx');
    }
}
