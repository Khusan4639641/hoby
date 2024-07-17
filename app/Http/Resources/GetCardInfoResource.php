<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GetCardInfoResource extends JsonResource
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
            'card_number' => $this['result']['card_number'],
            'balance'     => $this['result']['balance'],
            'expire'      => $this['result']['expire'],
            'owner'       => $this['result']['owner'],
            'phone'       => $this['result']['phone'],
        ];
    }
}
