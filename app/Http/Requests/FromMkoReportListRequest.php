<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FromMkoReportListRequest extends FormRequest
{
    public function rules(): array
    {
        return ['from'   => 'required_with:to|date_format:Y-m-d',
                'to'     => 'required_with:from|date_format:Y-m-d',
                'count'  => 'filled|integer',
                'offset' => 'filled|integer',
        ];
    }
}
