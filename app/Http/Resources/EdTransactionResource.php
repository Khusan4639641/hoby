<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\EdTransaction */
class EdTransactionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'doc_id' => $this->doc_id,
            'doc_time' => Carbon::createFromTimestampMs($this->doc_time)->toDateTimeString(),
            'amount' => $this->amount,
            'purpose_of_payment' => $this->purpose_of_payment,
            'cash_symbol' => $this->cash_symbol,
            'corr_name' => $this->corr_name,
            'corr_account' => $this->corr_account,
            'corr_mfo' => $this->corr_mfo,
            'corr_inn' => $this->corr_inn,
            'corr_bank' => $this->corr_bank,
            'type' => $this->type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
