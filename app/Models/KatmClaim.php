<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KatmClaim extends Model
{

    protected $fillable = [
        'general_company_id',
        'user_id',
        'contract_id',
        'claim',
    ];

    private static function generateClaimID(): string
    {
        return mb_substr(md5(random_int(1, 1000000) . time()), 0, 20);
    }

    // @todo deprecated $generalCompanyID param
    public static function getClaimID(Contract $contract, int $generalCompanyID = 3): string
    {
        $katmClaim = $contract->katmClaim()->where([
            'general_company_id' => $generalCompanyID,
            'user_id' => $contract->user->id,
        ])->first();
        if (!$katmClaim) {
            $claimHash = KatmClaim::generateClaimID();
            $contract->katmClaim()->create([
                'general_company_id' => $generalCompanyID,
                'user_id' => $contract->user->id,
                'claim' => $claimHash,
            ]);
        } else {
            $claimHash = $katmClaim->claim;
        }
        return $claimHash;
    }

    public function generalCompany()
    {
        return $this->belongsTo(GeneralCompany::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

}
