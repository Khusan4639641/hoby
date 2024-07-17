<?php

namespace App\Http\Requests\DebtCollect\Leader\AnalyticController;

use App\Models\V3\UserV3;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

use App\Services\API\V3\BaseService;
use Illuminate\Support\Facades\Auth;

class AnalyticLettersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return UserV3::find( Auth::id() )->roles->permissions()->whereName("detail-leader-reports-letters")->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'date_from' => 'nullable|string|size:10|date_format:Y-m-d',
            'date_to'   => 'nullable|string|size:10|date_format:Y-m-d',
            'senders'   => 'nullable|array',
            'senders.*' => 'required_with:senders|numeric|exists:users,id',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        BaseService::handleError($validator->errors()->getMessages());
    }
}
