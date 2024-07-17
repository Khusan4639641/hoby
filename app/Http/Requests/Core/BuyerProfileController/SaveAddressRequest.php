<?php

namespace App\Http\Requests\Core\BuyerProfileController;

use App\Models\Buyer;
use App\Services\API\V3\BaseService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SaveAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::user()->can('modify', Buyer::find($this->buyer_id));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'buyer_id' => ['required', 'exists:users,id'],
            'postal_region' => ['integer'],
            'postal_area' => ['integer'],
            'address' => ['string'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        BaseService::handleError([$validator->errors()->first()]);
    }

    protected function failedAuthorization()
    {
        BaseService::handleError(['Access Denied']);
    }
}
