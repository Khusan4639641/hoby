<?php

namespace App\Services\API\V3\Account;

use App\Http\Requests\MatchedAccount\UpdateMfoMatchRequest;
use App\Models\MatchedAccount;
use \App\Services\API\V3\BaseService;
use DDZobov\PivotSoftDeletes\Model;

class MatchedAccountService extends BaseService
{
    public function list(int $limit = 10, int $page = 1) {
        return MatchedAccount::paginate($limit, ['*'], 'page', $page);
    }

    public function new(string $mfo_mask, string $one_c_mask, int $parent_id, string $number, string $mfo_account_name)
    {
        $accountMatching = MatchedAccount::create([
            'mfo_mask'         => $mfo_mask,
            '1c_mask'          => $one_c_mask,
            'parent_id'        => $parent_id,
            'number'           => $number,
            'mfo_account_name' => $mfo_account_name,
        ]);

        return $accountMatching;
    }


    public function update(UpdateMfoMatchRequest $request, MatchedAccount $accountMatch)
    {
        $accountMatch->update($request->validated());
        return $accountMatch;
    }

}
