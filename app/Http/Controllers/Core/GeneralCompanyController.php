<?php

namespace App\Http\Controllers\Core;

use App\Helpers\FileHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Core\GeneralCompanyController\GeneralCompanyStampAndFacsimileRequest;
use App\Models\GeneralCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GeneralCompanyController extends Controller
{
    /**
     * @param GeneralCompany $general_company
     * @return GeneralCompany|JsonResponse
     */
    public function get(GeneralCompany $general_company)
    {
        if (!(Auth::user()->hasRole(["admin", "recover"]))) {
            return response()->json([
                'info' => 'err_access_denied',
                'message' => __('app.err_access_denied')
            ], 403);
        }

        return $general_company;
    }

    public function uploadPhoto(GeneralCompanyStampAndFacsimileRequest $request, GeneralCompany $general_company)
    {
        $user = Auth::user();

        if (!($user->hasRole(['admin']))) {
            return response()->json([
                'info' => 'err_access_denied',
                'message' => __('app.err_access_denied')
            ], 403);
        }

        $file = $request->validated()["file"];
        $type = $request->validated()["type"];  // "sign" or "stamp"
        $over_write = $request->validated()["over_write"];  // 0 or 1 / false or true

        if (!is_null($general_company->{$type}) && !((boolean)$over_write)) {
            Log::info("General Company ID: $general_company->id, User ID: $user->id. Already has \"$type\" image file.");
            return response()->json([
                'info' => "already_have_$type",  // "already_have_sign" or "already_have_stamp"
                'message' => __("general_company.already_have_$type")
            ], 400);    // Bad Request
        }

        try {
            $path = "general-companies/{$general_company->id}/";
            $fileName = md5($file->getClientOriginalName() . time()) . "." . $file->extension();

            $uploaded = Storage::disk('sftp')->putFileAs($path, $file, $fileName);

            if (!$uploaded) {
                Log::info("General Company ID: $general_company->id, User ID: $user->id. \"$type\" image file upload error (inside Try{}).");

                return response()->json([
                    "info" => 'file_upload_error',
                    'message' => __('general_company.file_upload_error')
                ], 500);    // Internal Server Error
            }
        } catch (\Exception $e) {
            Log::info("General Company ID: $general_company->id, User ID: $user->id. \"$type\" image file upload error (inside Catch{}).");
            Log::info($e);

            return response()->json([
                'info' => 'file_upload_error',
                'message' => __('general_company.file_upload_error')
            ], 500);    // Internal Server Error
        }

        $general_company->{$type} = $path . $fileName;

        if (!$general_company->save()) {
            Log::info("General Company ID: $general_company->id, User ID: $user->id. General Company Model save error.");

            return response()->json([
                'info' => 'general_company_model_save_error',
                'message' => __('general_company.general_company_model_save_error')
            ], 500);    // Internal Server Error
        }

        return response()->json([
            'info' => 'file_upload_success',
            'url' => $path . $fileName,
            'message' => __('general_company.file_upload_success')
        ]);
    }
}
