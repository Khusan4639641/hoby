<?php

namespace App\Http\Requests\Core\EmployeeBuyerController;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

use Illuminate\Http\Response;



class SearchBuyerRequest extends FormRequest
{
    /**
    * Determine if the user is authorized to make this request.
    *
    * @return bool
    */
    public function authorize()
    {
        // TODO: Определить, каким ролям разрешено делать поиск среди покупателей, снизу текущая версия.
        return auth()->user()->hasRole([
            "admin",
            "call-center",
            "cco",
            "employee",
            "editor",
            "kyc",
            "owner",
            "recover",
            "sales",
            "sales-manager",
            "debt-collect-curator",
            "debt-collect-leader",
        ]);
    }



    /**
    * Get the validation rules that apply to the request.
    *
    * @return array
    */
    public function rules(): array
    {
        return [
            "buyer_id" => [
                "nullable",
                "digits_between:1,8", // X(1) or XXXXXXXX(8), digits(0-9)
                "min:1",              // buyer_id > 0
                "exists:users,id"
            ],
            "status" => [
                "nullable",
                "integer",
                "digits_between:1,2", // X or XX digits(0-9)
                "min:0",              // status >= 0
            ],
            "phone"    => [
                "nullable",
                "digits_between:4,12",
            ],
            "name"     => [
                "nullable",
                "string",
                "max:32"
            ],
            "surname"  => [
                "nullable",
                "string",
                "max:64"
            ],
            "passport_number" => [
                "nullable",
                "string",
                "size:32",  // md5(AA1234567) from FrontEnd
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
            'buyer_id.exists' => __('validation.exists_real_error'),
        ];
    }

    protected function failedAuthorization() : HttpResponseException
    {
        $response["code"]   = Response::HTTP_FORBIDDEN;
        $response["status"] = "error";
        $response["validation_errors"]["authorization"]  = __("app.err_access_denied_role");

        throw new HttpResponseException(response()->json($response, $response['code']));
    }

    protected function failedValidation(Validator $validator) : HttpResponseException
    {
        $validation_errors = $validator->errors()->messages();
        $errors = [];
        $response['code']   = Response::HTTP_BAD_REQUEST;
        $response['status'] = "error";

        foreach ( $validation_errors as $error_attribute => $errors_msgs ) {
            foreach ( $errors_msgs as $errors_msg ) {
                $errors[] = [
                    "type" => "danger",
                    "text" => $errors_msg
                ];
            }
        }

        $response['error']  = $errors;
        throw new HttpResponseException(response()->json($response, $response['code']));
    }
}
