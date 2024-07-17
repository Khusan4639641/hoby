<?php

namespace App\Http\Controllers\Partners;

use App\Exports\DelaysForEachPartnerExport;
use App\Exports\FilialsCancelExport;
use App\Exports\FilialsExport;
use App\Exports\ReceiptExport;
use App\Exports\VendorsCancelExport;
use App\Exports\VendorsExport;
use App\Http\Controllers\V3\CoreController;
use App\Http\Response\BaseResponse;
use App\Models\Company;
use App\Models\User;
use App\Services\API\V3\BaseService;
use App\Services\ExcelExportDateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Excel;

class ReportsController extends CoreController
{
    private Excel $excel;

    public function __construct(Excel $excel)
    {
        $this->excel = $excel;
    }

    /**
     * @OA\GET(
     *      path="/billing/reports/vendors/export",
     *      tags={"Reports"},
     *      summary="Endpoint для выгрузки отчетов",
     *      description="Return json",
     *      security={{"api_token_security":{}}},
     *      @OA\Parameter(
     *          name="type",
     *          description="Type of date (custom,last_day,last_week,last_month,last_half_year)",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="report",
     *          description="Type of report (vendors,vendorsCancel,delays,filials,filialsCancel,receipts)",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="company_id",
     *          description="Company ID",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="company_parent_id",
     *          description="Company parent ID",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
     *          )
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         "message": "Unauthenticated"
     *                     }
     *                 )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         "message": "Forbidden"
     *                     }
     *                 )
     *              ),
     *          )
     *     )
     */
    public function vendorReportToExcel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required_if:type,custom|array',
            'type' => ['required', Rule::in(['custom', 'last_day', 'last_week', 'last_7_days', 'last_month', 'last_half_year'])],
            'report' => ['required', Rule::in(['vendors', 'vendorsCancel', 'delays', 'filials', 'filialsCancel', 'receipts'])],
            'company_id' => 'nullable|integer',
            'company_parent_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'date.*' => [
                'required_if:type,custom',
                'date_format:Y-m-d'
            ],
        ]);
        if ($validator->fails()) {
            BaseService::handleError($validator->errors()->getMessages());
        }
        $inputs = $validator->validated();
        $user = Auth::user();
        $company = Company::query()->find($user->company_id);


        if ($inputs['report'] == 'delays') {
            $company_id = $user->company_id;
            $company_parent_id = $company ? $company->parent_id : 0;
            $user_id = $user->id;
            return $this->excel->download(new DelaysForEachPartnerExport($company_id, $company_parent_id, $user_id), 'delays.xlsx');
        }

        if ($inputs['report'] == 'receipts') {
            $company_id = $user->company_id ?? 0;
            return $this->excel->download(new ReceiptExport(
                $company_id,
                ExcelExportDateService::getDates($inputs['type'], $inputs['date'] ?? []),
            ), 'receipts.xlsx');
        }

        if ($inputs['report'] == 'filials') {
            return $this->excel->download(new FilialsExport, 'filials.xlsx');
        }

        if ($inputs['report'] == 'filialsCancel') {
            return $this->excel->download(new FilialsCancelExport($request), 'FilialsCancel.xlsx');
        }

        if ($inputs['report'] == 'vendors') {
            return $this->excel->download(new VendorsExport, 'vendors.xlsx');
        }

        if ($inputs['report'] == 'vendorsCancel') {
            $result = VendorsCancelExport::report($request);
            $f = fopen('php://memory', md5(microtime()));
            fseek($f, 0);
            fputs($f, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($f, $result['header'], ";");
            foreach ($result['values'] as $res) {
                $res[3] = " " . $res[3] . " ";
                $res[4] = " " . $res[4] . " ";
                fputcsv($f, $res, ";");
            }
            fseek($f, 0);
            header('Content-type: text/csv');
            header('Content-Disposition: attachment; filename=VendorsCancelExport.csv;');
            header('Access-Control-Allow-Origin: *');
            fpassthru($f);
            exit();
        }
    }
}
