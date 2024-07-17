<?php

namespace App\Http\Requests;

use App\Models\GeneralCompany;
use Illuminate\Foundation\Http\FormRequest;

class FromMkoReportRequest extends FormRequest
{
    public function rules(): array
    {
        return ['mko_id'          => ['required',
                                      'integer',
                                      function ($attribute, $value, $fail) {
                                          $company = GeneralCompany::where('is_mfo', 1)
                                              ->whereNotNull('nko')->find($this->mko_id);
                                          if (!$company) {
                                              $fail(__('company.company_not_found'));
                                          }
                                          $this->offsetSet('company', $company);
                                      }],
                'from'            => 'required|date_format:Y-m-d',
                'to'              => 'required|date_format:Y-m-d',
                'dispatch_number' => 'required|string',
                'count_from'      => 'filled|integer|min:0|max:7',
                'count_to'        => 'filled|integer|min:1|max:8',];
    }
}
