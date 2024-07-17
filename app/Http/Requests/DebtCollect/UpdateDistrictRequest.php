<?php

namespace App\Http\Requests\DebtCollect;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Support\Facades\Auth;
use App\Models\V3\UserV3;
use App\Services\API\V3\BaseService;

class UpdateDistrictRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return UserV3::find( Auth::id() )->roles->permissions()->whereName("modify-debtors-district")->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'cbu_id'  => 'required|numeric|digits_between:1,3|exists:districts,cbu_id',
            'comment' => 'required|string|max:1024',
            'file' => [
                'nullable',
                'file',
                'max:15360',  // in kilobytes, = 15 Mb
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'cbu_id.exists' => __('collector.district_does_not_exist'),
        ];
    }

    protected function failedValidation(Validator $validator): void {
        BaseService::handleError($validator->errors()->getMessages());
    }
}
