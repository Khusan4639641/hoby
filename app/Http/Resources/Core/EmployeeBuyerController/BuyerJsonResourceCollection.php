<?php

namespace App\Http\Resources\Core\EmployeeBuyerController;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BuyerJsonResourceCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = BuyerJsonResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request) : array
    {
        return [
            'data' => $this->collection
        ];
    }
}
