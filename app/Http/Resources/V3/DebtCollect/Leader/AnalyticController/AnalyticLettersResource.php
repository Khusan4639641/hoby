<?php

namespace App\Http\Resources\V3\DebtCollect\Leader\AnalyticController;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class AnalyticLettersResource extends JsonResource
{
    /**
     * Indicates if the resource's collection keys should be preserved.
     *
     * @var bool
     */
    public $preserveKeys = true;

    /**
     * The "data" wrapper that should be applied.
     *
     * @var string
     */
    public static $wrap = 'data';

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */

    public function toArray($request) : array
    {
        return [
            'sender_id'     => $this->sender_id,                      //  1;
            'sender'        => $this->sender->fio,                    //  2;
            'debtor_id'     => $this->buyer_id,                       //  3;
            'debtor'        => $this->debtor->fio,                    //  4;
            'contract_id'   => $this->contract_id,                    //  5;
            'region'        => $this->getRelation('region')->name,    //  6;
            'area'          => $this->getRelation('area')->name,      //  7;
            'created_at'    => $this->created_at->toDateTimeString(), //  8;
        ];
    }

}
