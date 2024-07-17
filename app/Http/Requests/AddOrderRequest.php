<?php

namespace App\Http\Requests;

use App\Rules\CheckAmountOfPhonesVendorSellingToOneUser;
use App\Rules\CheckIfVendorHasThisCategory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddOrderRequest extends FormRequest
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
            // new CheckIfVendorHasThisCategory проверяет на соответствие категории внесенных товаров с данными в БД
            // закоментирован, т.к. у старых вендоров нету данных о категориях в БД

            // тут мы проверяем количество телефонов в договоре (должно быть не больше 2)
            'products' => [new CheckAmountOfPhonesVendorSellingToOneUser()]
        ];
    }
    protected function failedValidation(Validator $validator)
    {
        $errors = [];
        foreach($validator->errors()->messages() as $error)
            array_push($errors, $error[0]);

        $json = [
            'status'  => 'error',
            'errors'  => $errors
        ];
        throw new HttpResponseException(response()->json($json));
    }
}
