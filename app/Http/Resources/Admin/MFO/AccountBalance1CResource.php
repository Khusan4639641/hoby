<?php

namespace App\Http\Resources\Admin\MFO;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountBalance1CResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->resource->id,
            'operation_date' => $this->resource->operation_date,
            'earliest_balance' => intval($this->resource->balance * 100),
            'current_balance' => intval($this->resource->latestBalance->balance * 100),
            'mfo_account' => new AccountResource($this->whenLoaded('mfoAccount')),
        ];

    }
}
