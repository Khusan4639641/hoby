<?php

namespace App\Http\Requests\Core\PartnerController;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class BlockRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
         return Auth::user()->can('modify', Company::find($this->partner_id));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'partner_id' => ['required', 'exists:companies,id'],
            'block_reason' => ['min:0', 'max:191'],
        ];
    }

    protected function failedAuthorization()
    {
        $result['status'] = 'error';
        $result['response']['code'] = 403;
        $result['response']['message'][] = array(
            'type' => 'danger',
            'text' =>  __( 'app.err_access_denied' ),
        );
        throw new HttpResponseException(response()->json($result));
    }
}
