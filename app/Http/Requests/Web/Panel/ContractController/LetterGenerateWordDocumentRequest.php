<?php

namespace App\Http\Requests\Web\Panel\ContractController;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

// Models
use App\Models\CourtRegion;
use Illuminate\Http\Response;

class LetterGenerateWordDocumentRequest extends FormRequest
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
            'selectedCourtRegionId' => [
                'required',
//                'integer',
                'digits:' . count_digits((string) CourtRegion::count()),
//                'exists:court_regions,id',
                'exists:App\Models\CourtRegion,id',
            ],
            'phoneNumber' => [
                'required',
                'string',
                'size:12', // "998977430282" 12 characters
            ],
            'position' => [
                'required',
                'string',
            ],
            'fio' => [
                'required',
                'string',
            ],
        ];
    }

    protected function failedAuthorization()
    {
//        $result['status'] = 'error';
//        $result['info'] = 'Access Denied';
//        throw new HttpResponseException(response()->json($result));

        $response["status"] = "error";
        $response["validation_errors"]["authorization"] = __("app.err_access_denied_role");

        throw new HttpResponseException(response()->json($response, Response::HTTP_FORBIDDEN));
    }

    protected function failedValidation(Validator $validator) : HttpResponseException
    {
        $validation_errors = $validator->errors()->messages();
        $errors = [];
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
        throw new HttpResponseException(response()->json($response, Response::HTTP_BAD_REQUEST));
    }
}
