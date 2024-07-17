<?php

namespace App\Http\Requests\Core\GeneralCompanyController;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class GeneralCompanyStampAndFacsimileRequest extends FormRequest
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
  public function rules(): array
  {
      return [
          'type' => 'required|in:sign,stamp',  // Тип: какую картинку загружаем, Подпись или печать
          'file' => [
            'required',
            'image',
            'mimes:bmp,jpe,jpg,jpeg,png,svg,webp',
            'max:35840',  // 35 Мб 'max:7168' 7 Мб
          ],
          'over_write' => 'nullable|boolean',  // Перезаписать файл
      ];
  }

  protected function failedValidation(Validator $validator): void
  {
      $errors = [];
      foreach($validator->errors()->messages() as $error) {
          $errors[] = $error[0];
      }

      $json = [
          'status'  => 'error',
          'errors'  => $errors
      ];

      throw new HttpResponseException(response()->json($json));
  }
}
