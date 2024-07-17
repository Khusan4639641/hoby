<?php

namespace App\Http\Controllers\Core\V3;

use App\Facades\OldCrypt;
use App\Http\Controllers\Controller;
use App\Http\Response\BaseResponse;
use App\Models\BuyerAddress;
use App\Models\KatmRegion;
use App\Models\V3\BuyerPersonalsV3;
use App\Models\V3\KatmV3History;
use App\Models\V3\KatmV3Response;
use App\Models\V3\UserV3;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class KatmV3Controller extends Controller
{

    public static function status(): JsonResponse
    {
        Log::channel('katmV3')->info('katm send curl for methods: credit/report/status');

        $KatmV3History = KatmV3History::where('is_complete', 0)->whereNotNull('token')->limit(20)->get()->toArray();
        Log::channel('katmV3')->info('get all records for db and not complete');


        Log::channel('katmV3')->info('send requests to api KATM');
        foreach ($KatmV3History as $request) {
            $pReportFormat = 1;
            if ($request['report_code'] == 177) {
                $pReportFormat = 0;
            }
            $requestData['data'] = [
                'pHead' => config('test.katm_phead'),
                'pToken' => $request['token'],
                'pClaimId' => $request['claim_id'],
                'pReportFormat' => $pReportFormat, //Формат отчёта (0-XML, 1-JSON)
            ];

            $responses = self::CurlKatm(config('test.katm_apiurl3'), $requestData);
            if ($responses['data']['result'] === '05000') {
                Log::channel('katmV3')->info("get success response for record_id = {$request['id']}");
                if (isset($responses['data']['reportBase64']) && $pReportFormat === 1) {
                    $response = base64_decode($responses['data']['reportBase64']);
                    $responses['data']['reportBase64'] = json_decode($response, true);
                } else {
                    $responses['data']['reportBase64'] = base64_decode($responses['data']['reportBase64']);
                }
                Log::channel('katmV3')->info("update response for record_id = {$request['id']}");
                $hystory = KatmV3History::find($request['id']);
                $hystory->response = $responses;
                $hystory->is_complete = 1;
                $hystory->save();
            } else {
                Log::channel('katmV3')->info("get error response for record_id = {$request['id']}");
            }
        }
        Log::channel('katmV3')->info('end send requests to api KATM');
        return BaseResponse::success('', 201);

    }

    protected static function CurlKatm(string $url, array $RequestParams)
    {
        $RequestParams = array_merge($RequestParams, ['security' => [
            'pLogin' => OldCrypt::decryptString(config('test.katm_login')),
            'pPassword' => OldCrypt::decryptString(config('test.katm_password')),
        ]]);

        $RequestParams['data'] = array_merge($RequestParams['data'], [
            'pCode' => config('test.katm_pcode'),
            'pYear' => 0,
            'pQuarter' => 0,
        ]);

        Log::channel('katmV3')->info('katm send curl for methods: ' . $url);
        Log::channel('katmV3')->info('curl send params' . json_encode($RequestParams));

        $curl = curl_init($url); // /report

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($RequestParams));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $curl_result = curl_exec($curl);

        curl_close($curl);

        $curlJson = json_decode($curl_result, true);


        if (env('APP_ENV') === 'local') {
            // мок для проверки ответов от сервиса
            $curlJson = json_decode('{"data":{"result":"05000","resultMessage":"The report awaits confirmation by the operator","reportBase64":"Ilx1MDQyZFx1MDQ0Mlx1MDQzZSBcdTA0MzdcdTA0MzBcdTA0M2FcdTA0M2VcdTA0MzRcdTA0MzhcdTA0NDBcdTA0M2VcdTA0MzJcdTA0MzBcdTA0M2RcdTA0M2RcdTA0MzBcdTA0NGYgXHUwNDQxXHUwNDQyXHUwNDQwXHUwNDNlXHUwNDNhXHUwNDMwIg==","token":12312},"errorMessage":null,"code":200}', true);
        }

        return $curlJson;
    }

    public function register(UserV3 $user): JsonResponse
    {
        Log::channel('katmV3')->info('Register KatmV3');
        if ($user->region === null) {
            Log::channel('katmV3')->info('error 400 region not found');
            return BaseResponse::error('region not found', 400);
        }

        if (!$personalDatas = BuyerPersonalsV3::where('user_id', $user->id)->first()) {
            Log::channel('katmV3')->info('error 400 personal datas not found');
            return BaseResponse::error('personal datas not found', 400);
        }

        if (!$address = BuyerAddress::where('user_id', $user->id)->where('type', 'registration')->first()) {
            Log::channel('katmV3')->info('error 400 address not found');
            return BaseResponse::error('address not found', 400);
        }

        $claimId = mb_substr($user->id . md5('pm-' . time()), 0, 20);

        $t = microtime(true);
        $micro = sprintf("%03d", ($t - floor($t)) * 1000);
        $utc = gmdate('Y-m-d\TH:i:s.', $t) . $micro . 'Z';

        $passport = $personalDatas->passport_number;
        $requestData = [];
        $requestData['data'] = [
            'pClaimId' => $claimId,
            'pClaimDate' => $utc,
            'pAgreementId' => mb_substr($claimId, 0 , 10),
            'pAgreementDate' => $utc,
            'pPinfl' => $personalDatas->pinfl ?: null,
            'pDocSeries' => mb_substr($passport, 0, 2) ?: null,
            'pDocNumber' => mb_substr($passport, 2, 9) ?: null,
            'pDocType' => ($personalDatas->passport_type || $personalDatas->passport_type === 0) ? $personalDatas->passport_type : null,
            'pRegion' => $user->region ?: null,
            'pLocalRegion' => $user->local_region ?: null,
            'pAddress' => $address->address ?: null,
            'pPhone' => $user->phone ?: null,
        ];

        $errorColumn = [];
        foreach ($requestData['data'] as $column => $data) {
            if (!$data) {
                array_push($errorColumn, $column);
            }
        }

        if ($errorColumn) {
            Log::channel('katmV3')->info("error 400 data not found for column" . json_encode($errorColumn));
            return BaseResponse::error("data not found for column", 400, $errorColumn);
        }


        Log::channel('katmV3')->info("success, insert request data for db" . json_encode($requestData));
        KatmV3Response::create([
            'user_id' => $user->id,
            'claim_id' => $claimId,
            'params' => $requestData,
        ]);

        $responses = self::CurlKatm(config('test.katm_apiurl'), $requestData);
        if (!$responses) {
            Log::channel('katmV3')->info('error unknown response ');
            return BaseResponse::error('error unknown response', 400);
        }
        if ($responses['errorMessage']) {
            return BaseResponse::error($responses['errorMessage'], $responses['code']);
        }

        return BaseResponse::success("claimId: {$claimId}, resultMessage:  {$responses['data']['resultMessage']} ", 200);
    }

    public function report(UserV3 $user, Request $request): JsonResponse
    {
        Log::channel('katmV3')->info('katm send curl for methods: credit/report');
        if (!$request->has('report_id')) {
            Log::channel('katmV3')->info('error report_id is required fields');
            return BaseResponse::error('report_id is required fields', 400);
        }
        $report_id = $request->get('report_id');

        if (!$claimId = KatmV3Response::where('user_id', $user->id)->orderBy('id', 'desc')->first()) {
            Log::channel('katmV3')->info("claimId for user_id {$user->id} not found");
            return BaseResponse::error('claimId for this user not found', 400);
        }
        $pReportFormat = 1;
        if ((integer)$report_id == 177) {
            $pReportFormat = 0;
        }

        $requestData['data'] = [
            'pHead' => config('test.katm_phead'),
            'pClaimId' => $claimId->claim_id,
            'pLegal' => 1, // физическое лицо
            'pReportId' => (integer)$report_id,
            'pReportFormat' => $pReportFormat, // Формат отчёта (0-XML, 1-JSON)
        ];

        $responses = self::CurlKatm(config('test.katm_apiurl2'), $requestData);
        if (!$responses) {
            Log::channel('katmV3')->info('error unknown response ');
            return BaseResponse::error('error unknown response', 400);
        }

        if ($responses['errorMessage']) {
            return BaseResponse::error($responses['errorMessage'], $responses['code']);
        }

        if ($responses['data']['result'] === '05001') {
            return BaseResponse::error($responses['data']['resultMessage'], $responses['code']);
        }

        if (isset($responses['data']['reportBase64'])) {
            $response = base64_decode($responses['data']['reportBase64']);
            $responses['data']['reportBase64'] = json_decode($response, true);
        }
        $is_complete = false;

        Log::channel('katmV3')->info("insert datas for table KatmV3History");
        $history = KatmV3History::create([
            'user_id' => $user->id,
            'claim_id' => $claimId->claim_id,
            'params' => $requestData,
            'response' => $responses,
            'report_code' => $report_id,
            'is_complete' => $is_complete,
            'token' => $responses['data']['token'],
        ]);

        return BaseResponse::success("claimId: { $claimId->claim_id } reportId: {$report_id}, record_id: {$history->id}", 200);
    }
}

