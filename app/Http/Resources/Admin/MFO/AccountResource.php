<?php

namespace App\Http\Resources\Admin\MFO;

use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
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
            'number' => $this->resource->number,
            'account_1c_numbers' => $this->when($this->resource->relationLoaded('accounts1C'), $this->resource->accounts1C->pluck('number')),
        ];
    }
}
