<?php

namespace App\Http\Controllers\Core;

use Illuminate\Http\Request;
use App\Http\Requests\Collector\Transaction\CreateRequest;

use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\Controller;
use App\Helpers\FileHelper;
use Illuminate\Support\Facades\Auth;

use App\Models\Collector;
use App\Models\CollectorTransaction;
use App\Models\Contract;

use Illuminate\Database\QueryException;

class CollectorTransactionController extends CoreController
{
    function get(Request $request, Collector $collector, Contract $contract) {
        $contractPivot = $collector->contracts()->find($contract->id)->pivot->id;

        return CollectorTransaction::where('collector_contract_id', $contractPivot)->latest()->paginate();
    }

    function add(CreateRequest $request) {
        try {
            // TODO: Check user access to create (Admin or Collector with that contract)
            $validatedData = $request->all();

            $collectorTransaction = CollectorTransaction::create($validatedData);

            if($validatedData['type'] === 'photo') {
                $user = Auth::user();

                $file = FileHelper::uploadNew([
                    'model' => 'collector-transaction',
                    'element_id' => $collectorTransaction->id,
                    'user_id' => $user->id,
                    'type' => 'collector-transaction-photo',
                    'files' => [$validatedData['content']]
                ], true);

                if(!$file) {
                    return response([
                        // TODO: error.key
                        'error' => 'Can\'t create file',
                        'error_code' => $errorCode
                    ], 500);
                }

                $collectorTransaction->content = $file->path;
                $collectorTransaction->save();
            }
            
        } catch(QueryException $e) {
            $errorCode = $e->errorInfo[1];

            return response([
                // TODO: error.key
                'error' => 'Can\'t create transaction',
                'error_code' => $errorCode
            ], 500);
        }

        return response('OK');
    }
}
