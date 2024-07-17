<?php

namespace App\Http\Requests;

use App\Rules\CheckCardByService;
use App\Rules\CheckInBannedList;
use App\Rules\CheckInBlackList;
use App\Rules\RegionRelevant;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BuyerPersonalDataRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'buyer_id' => ['required', new CheckCardByService()],
            'passport' => ['required', new CheckInBannedList()],
            'pinfl' => ['required', new CheckInBlackList()],
            'region_id' => ['required'],
            'local_region_id' => ['required', new RegionRelevant($this->request->all())],
            'first_name' => ['required'],
            'last_name' => ['required'],
            'patronymic' => ['nullable'],
            'mrz' => ['nullable'],
            /* 30.05.2023 */
            'gender' => ['required', 'integer', 'in:1,2'],
            'passport_type' => ['required', 'integer', 'in:0,6'],
            'birth_date' => ['required', 'date_format:d.m.Y'],
            'passport_date_issue' => ['required', 'date_format:d.m.Y'],
            'passport_expire_date' => ['required', 'date_format:d.m.Y'],
        ];
    }

    public function messages()
    {
        return [
            'passport.required' => __('Поле "Серия и номер паспорта" обязательно для заполнения.'),
            'region_id.required' => __('Поле "Регионы" обязательно для заполнения.'),
            'local_region_id.required' => __('Поле "Районы" обязательно для заполнения.'),
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $errors = [];
        foreach ($validator->errors()->messages() as $error) {
            $errors[] = $error[0];
        }

        $json = [
            'status' => 'error',
            'errors' => $errors
        ];
        throw new HttpResponseException(response()->json($json));
    }

}
