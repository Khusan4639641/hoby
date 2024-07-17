<?php

namespace App\Http\Resources\Admin\MFO;

use App\Models\User;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class AccountBalance1CCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'balances' => AccountBalance1CResource::collection($this->collection),
            'pagination' => [
                'total' => $this->resource->total(),
                'per_page' => $this->resource->perPage(),
                'current_page' => $this->resource->currentPage(),
                'last_page' => $this->resource->lastPage(),
                'first_page_url' => $this->resource->url(1),
                'last_page_url' => $this->resource->url($this->resource->lastPage()),
                'prev_page_url' => $this->resource->previousPageUrl(),
                'next_page_url' => $this->resource->nextPageUrl(),
                'path' => $this->resource->path(),
                'from' => $this->resource->firstItem(),
                'to' => $this->resource->lastItem(),
            ]
        ];
    }
}
