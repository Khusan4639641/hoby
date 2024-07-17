<?php

namespace App\Http\Requests\Core\LetterController;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class LetterFillingDataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::user()->can('detail', Contract::find($this->contract_id));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'contract_id' => [ 'required', 'exists:contracts,id' ],
            'notary_id'   => [ 'nullable', 'exists:notary_settings,id' ]
        ];
    }

    public function messages()
    {
        return [
            'contract_id.required' => 'contract_id is required',
            'contract_id.exists' => 'Contract by id: ' . $this->contract_id . ' not found',
        ];
    }

    protected function failedAuthorization()
    {
        $result['status'] = 'error';
        $result['info'] = 'Access Denied';
        throw new HttpResponseException(response()->json($result));
    }
}
