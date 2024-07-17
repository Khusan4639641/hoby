<?php

namespace App\Services\API\V3;

use App\Classes\CURL\Katm\KatmRequestClientAddress;
use App\Helpers\BuyerBlockingChecker;
use App\Helpers\EncryptHelper;
use App\Helpers\FileHelper;
use App\Helpers\MRZHelper;
use App\Jobs\RestartMiniScoringIfBroken;
use App\Models\Buyer;
use App\Models\BuyerAddress;
use App\Models\BuyerPersonal;
use App\Models\BuyerPersonalHistory;
use App\Models\Card;
use App\Models\Company;
use App\Models\KycHistory;
use App\Models\MyID;
use App\Models\MyIDJob;
use App\Models\Partner;
use App\Models\PassportIssuerRegion;
use App\Models\User;
use App\Services\GradeScoringService;
use App\Services\MFO\AccountService;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Validation\Rule;
use Str;
use Validator;

class MyIDService extends BaseService
{
    private static string $url;
    private static string $username;
    private static string $password;
    private static string $client_id;
    private static string $token;

    public function __construct()
    {
        self::$url = config('test.myid_url');
        self::$username = config('test.myid_username');
        self::$password = config('test.myid_password');
        self::$client_id = config('test.myid_client_id');
        self::$token = self::token();
    }

    private static function makeAccessTokenRequest()
    {
        try {
            $data = [
                'grant_type' => 'password',
                'username' => self::$username,
                'password' => self::$password,
                'client_id' => self::$client_id,
            ];
            $response = Http::asForm()->post(self::$url.'api/v1/oauth2/access-token',$data);
            self::logData([
                'API: '.self::$url.'api/v1/oauth2/access-token',
                'REQUEST: '.json_encode($data),
                'RESPONSE: '.$response->body(),
                'STATUS: '.$response->status()
            ]);
            if($response->failed()){
                self::saveResponse('get token failed'.$response->body(),$response->status());
            }
            return $response;
        }
        catch(\Exception $exception){
            self::logData('get token failed'.' Message: '.$exception->getMessage());
            self::saveResponse('get token failed: '.$exception->getMessage(),500);
            return false;
        }
    }

    private static function makeRefreshTokenRequest($last_refresh_token)
    {
        try {
            $query = [
                'refresh_token' => $last_refresh_token,
                'client_id' => self::$client_id
            ];
            $response = Http::withBody(json_encode($query),'application/json')->post(self::$url.'api/v1/oauth2/refresh-token');
            self::logData([
                'API: '.self::$url.'api/v1/oauth2/refresh-token',
                'REQUEST: '.json_encode($query),
                'RESPONSE: '.$response->body(),
                'STATUS: '.$response->status()
            ]);
            if($response->failed()){
                self::saveResponse('refresh token failed: '.$response->body(),$response->status());
            }
            return $response;
        }
        catch (\Exception $exception){
            self::logData('refresh token failed'.' Message: '.$exception->getMessage());
            self::saveResponse('refresh token failed: '.$exception->getMessage(),500);
            return false;
        }
    }

    private static function makeJobRequest($body)
    {
        try {
            $response = Http::withToken(self::$token)
                ->withBody(json_encode($body),'application/json')
                ->post(self::$url.'api/v1/authentication/simple-inplace-authentication-request-task');
            $log_data_without_image = $body;
            unset($log_data_without_image['photo_from_camera']);
            self::logData([
                'API: '.self::$url.'api/v1/authentication/simple-inplace-authentication-request-task',
                'REQUEST: '.json_encode($log_data_without_image),
                'RESPONSE: '.$response->body(),
                'STATUS: '.$response->status(),
                'BEARER: '.self::$token
            ]);
            if($response->failed()){
                self::saveResponse('makeJobRequest failed: '.$response->body(),$response->status(),$body);
            }
            return $response;
        }
        catch (\Exception $exception){
            self::logData('makeJobRequest failed'.' Message: '.$exception->getMessage());
            self::saveResponse('makeJobRequest failed: '.$exception->getMessage(),500);
            return false;
        }
    }

    private static function makeJobStatusRequest(string $job_id)
    {
        try {
            $response = Http::withToken(self::$token)->post(self::$url.'api/v1/authentication/simple-inplace-authentication-request-status?job_id='.$job_id);
            self::logData([
                'API: '.self::$url.'api/v1/authentication/simple-inplace-authentication-request-status',
                'REQUEST: '.$job_id,
                'RESPONSE: '.$response->body(),
                'STATUS: '.$response->status(),
                'BEARER: '.self::$token
            ]);
            if($response->failed()){
                self::saveResponse('makeJobStatusRequest failed: '.$response->body(),$response->status());
            }
            return $response;
        }
        catch (\Exception $exception){
            self::logData('makeJobStatusRequest failed'.' Message: '.$exception->getMessage());
            self::saveResponse('makeJobStatusRequest failed: '.$exception->getMessage(),500);
            return false;
        }
    }

    private static function makeAddressRequest($body)
    {
        try {
            $response = Http::withToken(self::$token)
                ->withBody(json_encode($body),'application/json')
                ->post(self::$url.'api/v1/users/get-address');
            self::logData([
                'API: '.self::$url.'api/v1/users/get-address',
                'REQUEST: '.json_encode($body),
                'RESPONSE: '.$response->body(),
                'STATUS: '.$response->status(),
                'BEARER: '.self::$token
            ]);
            if($response->serverError()){
                self::saveResponse('makeAddressRequest failed: '.$response->body(),$response->status());
            }
            return $response;
        }
        catch (\Exception $exception){
            self::logData('makeAddressRequest failed'.' Message: '.$exception->getMessage());
            self::saveResponse('makeAddressRequest failed: '.$exception->getMessage(),500);
            return false;
        }
    }

    public static function validateJob(Request $request) : array
    {
        $inputs = $request->all();
        $user = Auth::user();
        $is_vendor = BuyerService::is_vendor($user->role_id);
        self::logData([
            'API: '.'validateJobStart',
            'REQUEST: '.json_encode($request->all())
        ]);
        $validator = Validator::make($request->all(), [
            'pass_data' => 'required|string|min:9|max:9',
            'birth_date' => 'required|date_format:Y-m-d',
            'passport_selfie' => 'required|file|mimes:jpg,png,jpeg|max:1536',
            'agreed_on_terms' => ['required',Rule::in(true,1)],
            'partner_id' => 'nullable|integer',
            'phone' => $is_vendor ? 'required|numeric|digits:12|regex:/(998)[0-9]{9}/' : 'nullable'
        ]);
        if ($validator->fails()) {
            self::handleError($validator->errors()->getMessages());
        }
        $inputs = $validator->validated();
        $inputs['agreed_on_terms'] = (bool)$inputs['agreed_on_terms'];
        return $inputs;
    }

    public static function token() : string
    {
        //Check token expire date. If expired get new one
        $token_exp_date = Redis::get('myid_expires_in');
        if(!empty($token_exp_date) && $token_exp_date > now()){
            self::logData([
                'API: '.'token',
                'TOKEN IN REDIS NOT EXPIRED YET: '.$token_exp_date
            ]);
            return Redis::get('myid_access_token');
        }
        $token = self::getToken();
        if(!$token){
            self::handleError([__('api.internal_error')]);
        }
        return $token;
    }

    private static function getToken() : string
    {
        $refresh_token = self::refreshToken();
        if ($refresh_token){
            return $refresh_token;
        }
        $response = self::makeAccessTokenRequest();
        if($response instanceof Response && $response->ok()){
            //store in Redis
            $data = $response->json();
            $data['expires_in'] = Carbon::now()->addSeconds($data['expires_in'] - 1600)->format('Y-m-d H:j:s');
            self::storeToken($data['access_token'],$data['expires_in'],$data['refresh_token']);
            return $data['access_token'];
        }
        return false;
    }

    private static function refreshToken() : string
    {
        $last_refresh_token = Redis::get('myid_refresh_token');
        if($last_refresh_token){
            $response = self::makeRefreshTokenRequest($last_refresh_token);
            if($response instanceof Response && $response->ok()){
                //store in DB
                $data = $response->json();
                $data['expires_in'] = Carbon::now()->addSeconds($data['expires_in'] - 1600)->format('Y-m-d H:j:s');
                self::storeToken($data['access_token'],$data['expires_in'],$data['refresh_token']);
                return $data['access_token'];
            }
        }
        return false;
    }

    private static function storeToken(string $access_token,string $expires_in,string $refresh_token)
    {
        try{
            Redis::set('myid_access_token',$access_token);
            Redis::set('myid_expires_in',$expires_in);
            Redis::set('myid_refresh_token',$refresh_token);
        }
        catch (\Exception $exception){
            self::logData([
                'REDIS storeToken failed',
                'DATA: '.'access_token='.$access_token.' expires_in='.$expires_in.' refresh_token='.$refresh_token,
                'Message: '.$exception->getMessage()
            ]);
            self::handleError([__('api.internal_error')]);
        }
    }

    public static function job(Request $request)
    {
        $inputs = self::validateJob($request);
        //check user age (must be between 22 - 65)
        $now = Carbon::now();
        if(Carbon::parse($inputs['birth_date'])->addYears(22)->subDays(30) > $now){
            self::handleError([__('api.myid_result_user_has_no_enough_age')]);
        }
        if(Carbon::parse($inputs['birth_date'])->addYears(65)->subDays(30) < $now){
            self::handleError([__('api.myid_result_user_has_no_enough_age')]);
        }
        $user = Auth::user();
        //check user limit for use this API
        $is_vendor = BuyerService::is_vendor($user->role_id);
        if ($is_vendor) {
            $buyer = Buyer::where('phone', $request->get('phone'))->first();
        }else{
            $buyer = Buyer::find($user->id);
        }
        if (!$buyer) {
            return self::handleError([__('auth.error_user_not_found')]);
        }
        //check for passport expiration if we have info about the user
        if(self::isPassportExpired($buyer->id,$inputs['pass_data'],$inputs['birth_date'])){
            self::handleError([__('api.myid_result_passport_expire')]);
        }
        $passport_selfie = $request->file('passport_selfie');
        $ext = $passport_selfie->getClientOriginalExtension();
        $image = base64_encode(file_get_contents($passport_selfie));
        $image = "data:image/$ext;base64,$image";
        $inputs['client_id'] = self::$client_id;
        $inputs['external_id'] = Str::uuid();
        $request->merge(['external_id' => $inputs['external_id']]);
        $inputs['photo_from_camera']['front'] = $image;
        $partner_id = $is_vendor ? $user->id : null;
        $company_id = $inputs['company_id'] ?? null;
        unset($inputs['passport_selfie']);
        unset($inputs['partner_id']);
        unset($inputs['company_id']);
        unset($inputs['phone']);
        //create or update buyer personals
        $buyerPersonals = $buyer->personals ?? new BuyerPersonal();
        $buyerPersonals->user_id = $buyer->id;
        $buyerPersonals->passport_type = $buyerPersonals->passport_type ?? 6;//6:BIO,0:ID Идентификатор типа документа (по справочник ЦБ)
        $buyerPersonals->save();
        $response = self::makeJobRequest($inputs);
        if($response instanceof Response && $response->ok()){
            $data = $response->json();

            //Create job
            $inputs['user_id'] = $buyer->id;
            unset($inputs['photo_from_camera']);
            $params = [
                'files' => $request->file(),
                'element_id' => $buyerPersonals->id,
                'model' => 'buyer-personal',
                'user_id' => $buyer->id,
            ];
            $file = FileHelper::uploadNew($params, true);
            $inputs['job_id'] = $data['job_id'];
            $job = MyIDJob::create($inputs);
            $job->photo_from_camera = $file ? $file->id : null;
            $job->save();

            //Get job status
            $job_status = self::getJobStatus($job->job_id);
            $the_address = null;
            if($job_status){
                $job_status = json_decode($job_status);
                //Check for duplicate PINFL
                if($job_status->profile->common_data->pinfl){
                    if(self::pinflExist($buyer->id,$job_status->profile->common_data->pinfl)){
                        $buyer->status = 8;
                        $buyer->save();
                        return self::handleError([__('api.myid_result_pinfl_duplicate')]);
                    }
                }

                //Change buyerPersonal passport type
                $buyerPersonals->passport_type = $job_status->profile->doc_data->doc_type_id_cbu ?? $buyerPersonals->passport_type;
                $buyerPersonals->birthday = $job_status->profile->common_data->birth_date ? EncryptHelper::encryptData($job_status->profile->common_data->birth_date) : $buyerPersonals->birthday;
                $buyerPersonals->city_birth = $job_status->profile->common_data->birth_place ?? $buyerPersonals->city_birth;
                $buyerPersonals->passport_number = $job_status->profile->doc_data->pass_data ? EncryptHelper::encryptData($job_status->profile->doc_data->pass_data) : $buyerPersonals->passport_number;
                $buyerPersonals->passport_number_hash = $job_status->profile->doc_data->pass_data ? md5($job_status->profile->doc_data->pass_data) : $buyerPersonals->passport_number_hash;
                $buyerPersonals->pinfl = $job_status->profile->common_data->pinfl ? EncryptHelper::encryptData($job_status->profile->common_data->pinfl) : $buyerPersonals->pinfl;
                $buyerPersonals->pinfl_hash = $job_status->profile->common_data->pinfl ? md5($job_status->profile->common_data->pinfl) : $buyerPersonals->pinfl;
                $buyerPersonals->inn = isset($job_status->profile->common_data->inn) ? EncryptHelper::encryptData($job_status->profile->common_data->inn) : $buyerPersonals->inn;
                $buyerPersonals->passport_issued_by = $job_status->profile->doc_data->issued_by ? EncryptHelper::encryptData($job_status->profile->doc_data->issued_by) : $buyerPersonals->passport_issued_by;
                $buyerPersonals->passport_date_issue = $job_status->profile->doc_data->issued_date ? EncryptHelper::encryptData($job_status->profile->doc_data->issued_date) : $buyerPersonals->passport_date_issue;
                $buyerPersonals->passport_expire_date = $job_status->profile->doc_data->expiry_date ? EncryptHelper::encryptData($job_status->profile->doc_data->expiry_date) : $buyerPersonals->passport_expire_date;

                try {
                    $buyerPersonals->birthday_open = Carbon::createFromFormat('d.m.Y', $job_status->profile->common_data->birth_date)->format('Y-m-d');
                } catch (InvalidFormatException $exception){
                    Log::channel('myid')->warning($exception->getMessage(), ['date' => $job_status->profile->common_data->birth_date]);
                }
                try {
                    $buyerPersonals->passport_date_issue_open = Carbon::createFromFormat('d.m.Y', $job_status->profile->doc_data->issued_date)->format('Y-m-d');
                } catch (InvalidFormatException $exception){
                    Log::channel('myid')->warning($exception->getMessage(), ['date' => $job_status->profile->doc_data->issued_date]);
                }
                try {
                    $buyerPersonals->passport_expire_date_open = Carbon::createFromFormat('d.m.Y', $job_status->profile->doc_data->expiry_date)->format('Y-m-d');
                } catch (InvalidFormatException $exception){
                    Log::channel('myid')->warning($exception->getMessage(), ['date' => $job_status->profile->doc_data->expiry_date]);
                }


                //Generate MRZ
                $seria_number = isset($job_status->profile->doc_data->pass_data) ? $job_status->profile->doc_data->pass_data : null;
                $birth_date = isset($job_status->profile->common_data->birth_date) ? $job_status->profile->common_data->birth_date : null;
                $expiry_date = isset($job_status->profile->doc_data->expiry_date) ? $job_status->profile->doc_data->expiry_date : null;
                $gender = isset($job_status->profile->common_data->gender) ? $job_status->profile->common_data->gender : null;
                $pinfl = isset($job_status->profile->common_data->pinfl) ? $job_status->profile->common_data->pinfl : null;
                $mrz = MRZHelper::getMrz($seria_number,$birth_date,$expiry_date,$gender,$pinfl);
                if($mrz){
                    $buyerPersonals->mrz = $mrz;
                }

                $buyerPersonals->save();

                //Update buyer info
                if(isset($job_status->profile->address->permanent_registration->region_id_cbu)){
                    $buyer->local_region = $job_status->profile->address->permanent_registration->district_id_cbu ?? $buyer->local_region;
                    $buyer->region = $job_status->profile->address->permanent_registration->region_id_cbu;
                }
                //IF region or local-region does not exist. Find regions by issued by id
                if(!isset($job_status->profile->address->permanent_registration->region_id_cbu) || !isset($job_status->profile->address->permanent_registration->district_id_cbu)){
                    if(isset($job_status->profile->doc_data->issued_by_id)){
                        Log::channel('myid')->info('Region or local region region not present. issued_by_id='.$job_status->profile->doc_data->issued_by_id);
                        $issuer = PassportIssuerRegion::where('issuer_id',$job_status->profile->doc_data->issued_by_id)->first();
                        if($issuer){
                            $buyer->local_region = $issuer->local_region;
                            $buyer->region = $issuer->region;
                        }
                    }
                }
                $buyer->surname = $job_status->profile->common_data->first_name ?? $buyer->surname;
                $buyer->name = $job_status->profile->common_data->last_name ?? $buyer->name;
                $buyer->patronymic = $job_status->profile->common_data->middle_name ?? $buyer->patronymic;
                $buyer->gender = $job_status->profile->common_data->gender ?? $buyer->gender;
                $buyer->birth_date = $job_status->profile->common_data->birth_date ? Carbon::createFromFormat('d.m.Y',$job_status->profile->common_data->birth_date)->format('Y-m-d') : $buyer->birth_date;
                $buyer->save();

                //create buyer address
                if($buyer->addressRegistration){
                    $buyerAddress = $buyer->addressRegistration;
                }else{
                    $buyerAddress  = new BuyerAddress();
                }
                $the_address = $job_status->profile->address->permanent_registration->address ?? null;
                $buyerAddress->user_id = $buyer->id;
                $buyerAddress->address_myid = $the_address;
                $buyerAddress->address = $the_address;
                $buyerAddress->type = BuyerAddress::TYPE_REGISTRATION;
                $buyerAddress->citizenship_id = $job_status->profile->common_data->citizenship_id ?? null;
                $buyerAddress->save();

                //Partner id is present ? add to user created_by
                if($partner_id){
                    $partner = Partner::find($partner_id);
                    if(!$partner){
                        self::logData('Partner does not exist. ID: '.$partner_id);
                    }
                    if($partner){
                        $buyer->created_by = $partner->id;
                        $buyer->save();
                    }
                }
            }
            // добавляем в историю запись
            KycHistory::insertHistory($buyer->id, User::KYC_STATUS_MODIFY);
            $buyer->status = 1;
            $buyer->save();
            self::logData('Buyer ID='.$buyer->id .'status changed to 1');

            if(!$the_address || !isset($job_status->profile->address->permanent_registration->region_id_cbu) || !isset($job_status->profile->address->permanent_registration->district_id_cbu)){
                $the_address = 0;
            }

            $cardExists = Card::where('user_id', $buyer->id)->first();

            $changedStatus = $cardExists ? 12 : 1;
            User::changeStatus($buyer, $changedStatus);

            if(self::isUserBanned($buyer)){
                self::handleError([__('cabinet/cabinet.you_blocked')]);
            }
            if(self::isUserInBlackList($buyer)){
                self::handleError([__('panel/buyer.txt_pinfl_black_list')]);
            }

            // Mini scoring
            // Skip mini scoring if user is vendor
            if (!$is_vendor) {
                // Carry out additional checks if company_id is set
                if ($company_id) {
                    $company = Company::with('settings')->find($company_id);
                    if (!$company || !$company->settings) {
                        // Log company or settings not found and init mini scoring anyways
                        self::logData('Company or settings do not exist. ID: '.$company_id);
                        self::doMiniScoring($buyer);
                    } elseif ($company->settings->is_mini_scoring_enabled) {
                        self::doMiniScoring($buyer);
                    }
                } else {
                    // Init mini scoring if company_id not set
                    self::doMiniScoring($buyer);
                }
            }

            if(!$the_address){
                self::logData('Buyer ID='.$buyer->id .'address not found');
            }

            self::handleResponse(['address_is_received' => $the_address ? 1 : 0]);
        }
        $redis_hash = self::getRedisHashAttemptsCase($user->id);
        $response_data = [
            'hash' => $redis_hash,
            'manual_upload' => true
        ];
        $error_message = [__('api.internal_error')];
        if($response instanceof Response && $response->status() == 429) {
            $error_message =  [__('api.myid_result_too_many_requests')];
            $res_data = $response->json();
            if(isset($res_data['ttl'])){
                $error_message = [trans('api.myid_client_blocked_for_invalid_request',['ttl' => $res_data['ttl']])];
            }
        }
        if(self::getAttemptsCount($user->id) >= Config::get('test.myid_job_registration_limit')) {
            self::handleError($error_message,'error',$response instanceof Response ? $response->status() : 500,$response_data);
        }
        self::handleError($error_message,'error',$response instanceof Response ? $response->status() : 500,['manual_upload' => false]);
    }

    public static function jobStatus(Request $request)
    {
        return self::getJobStatus($request->job_id);
    }

    public static function getJobStatus($job_id,$attempt = 1)
    {
        $job = MyIDJob::where('job_id',$job_id)->first();
        if(!$job){
            self::handleError([__('app.err_not_found')]);
        }
        $response = self::makeJobStatusRequest($job_id);
        if($response instanceof Response && $response->ok()){
            //update DB
            $data = $response->json();
            if($data['result_code'] == 1){
                $job->result_code = $data['result_code'];
                $job->result_note = $data['result_note'];
                $job->profile = $data['profile'];
                $job->comparison_value = $data['comparison_value'];
                $job->save();
                //check for passport expire date
                if(isset($data['profile']['doc_data']['expiry_date'])){
                    $today = strtotime(date('d.m.Y'));
                    $expiry_date = strtotime($data['profile']['doc_data']['expiry_date']);
                    if($today >= $expiry_date){
                        self::logData('Buyer ID='.$job->user_id.' passport expired at->getJobStatus '.$data['profile']['doc_data']['expiry_date']);
                        self::handleError([__('api.myid_result_passport_expire')]);
                    }
                }
                //Check for passport type. Allowed only BIO:6 and ID:0
                if(!isset($data['profile']['doc_data']['doc_type_id_cbu']) || !in_array($data['profile']['doc_data']['doc_type_id_cbu'],[0,6])){
                    self::logData('Buyer ID='.$job->user_id.' passport type not found (getJobStatus)');
                    self::handleError([__('api.myid_result_passport_type')]);
                }
                //get permanent address
                if(!isset($data['profile']['address']['permanent_registration']['region_id_cbu'])){
                    self::getAddress($job);
                }
                return $job;
            }else{
                $job->result_code = $data['result_code'];
                $job->result_note = $data['result_note'];
                $job->save();
            }
            $message = __('api.myid_result_code_'.$data['result_code']);
            $response_data = [
                'hash' => self::getRedisHashAttemptsCase($job->user_id),
                'manual_upload' => true
            ];
            if(self::getAttemptsCount($job->user_id) >= Config::get('test.myid_job_registration_limit')) {
                self::handleError([$message],'error',400,$response_data);
            }
            self::handleError([$message],'error',400,['manual_upload' => false]);
        }
        if($response instanceof Response && $response->status() == 202 && $attempt < 5){
            $attempt ++;
            sleep(5);
            return self::getJobStatus($job_id,$attempt);
        }
        $response_data = [
            'hash' => self::getRedisHashAttemptsCase($job->user_id),
            'manual_upload' => true
        ];
        $error_message = [__('api.bad_request')];
        if($response instanceof Response && $response->status() == 429) {
            $error_message =  [__('api.myid_result_too_many_requests')];
            $res_data = $response->json();
            if(isset($res_data['ttl'])){
                $error_message = [trans('api.myid_client_blocked_for_invalid_request',['ttl' => $res_data['ttl']])];
            }
        }
        if(self::getAttemptsCount($job->user_id) >= Config::get('test.myid_job_registration_limit')) {
            self::handleError($error_message,'error',$response instanceof Response ? $response->status() : 500,$response_data);
        }
        self::handleError($error_message,'error',$response instanceof Response ? $response->status() : 500,['manual_upload' => false]);
    }

    public static function getAddress(MyIDJob $job,$attempts = 1)
    {
        $buyer = $job->buyer;
        $inputs = [
            'job_id' => $job->job_id,
            'client_id' => self::$client_id
        ];
        $response = self::makeAddressRequest($inputs);
        if($response instanceof Response && $response->ok()){
            $data = $response->json();
            if(isset($data['profile']['address']['permanent_registration']['region_id_cbu'])){
                $buyer->local_region = $data['profile']['address']['permanent_registration']['district_id_cbu'] ?? $buyer->local_region;
                $buyer->region = $data['profile']['address']['permanent_registration']['region_id_cbu'];
                //Update job address
                $job_profile = $job->profile;
                $job_profile['address'] = $data['profile']['address'];
                $job->profile = $job_profile;
                $job->save();
                return true;
            }
        }
        if($attempts < 5){
            $attempts ++;
            sleep(3);
            return self::getAddress($job,$attempts);
        }
        return false;
    }

    private static function logData($data) :void
    {
        Log::channel('myid')->info($data);
    }

    public static function isUserBanned(Buyer $user) : bool
    {
        $result = false;
        $personalData   = $user->personalData;
        if($personalData && $personalData->passport_number){
            $passport       = EncryptHelper::decryptData($personalData->passport_number);
            if(strlen($passport) == 9){
                $serial         = substr($passport, 0, 2);
                $number         = substr($passport, 2, 8);
                if (BuyerBlockingChecker::isUserBanned($serial, $number)) {
                    $result = true;
                    if($user->status != User::KYC_STATUS_BLOCKED){
                        $user->status = User::KYC_STATUS_BLOCKED;
                        $user->save();
                        KycHistory::insertHistory($user->id, User::KYC_STATUS_BLOCKED, User::KYC_STATUS_IN_BLACK_LIST);
                    }
                }
            }
        }
        self::logData('MYIDService->isUserBanned: BUYER='.$user->id.' RESULT='.$result);
        return $result;
    }

    public static function isUserInBlackList(Buyer $user) : bool
    {
        $result  = false;
        $personalData   = $user->personalData;
        if($personalData && $personalData->pinfl){
            $pinfl = EncryptHelper::decryptData($personalData->pinfl);
            if(strlen($pinfl) == 14){
                if (BuyerBlockingChecker::isUserInBlackList($pinfl)) {
                    $result = true;
                    $personalData->pinfl_status = 0;
                    $personalData->save();
                    if($user->status != User::KYC_STATUS_BLOCKED){
                        $user->status = User::KYC_STATUS_BLOCKED;
                        $user->save();
                        KycHistory::insertHistory($user->id, User::KYC_STATUS_BLOCKED, User::KYC_STATUS_SCORING_PINFL);
                    }
                }
            }
        }
        self::logData('MYIDService->isUserBanned: BUYER='.$user->id.' RESULT='.$result);
        return $result;
    }

    public static function getAddressFromKatm(Buyer $buyer) : string
    {
        try {
            self::logData('getAddressFromKatm start');
            $addressRegistration = $buyer->addressRegistration;
            if ($addressRegistration && $addressRegistration->address) {
                return $addressRegistration->address;
            }
            if($buyer->personals){
                $pinfl = EncryptHelper::decryptData($buyer->personals->pinfl);
                $katmRequestClientAddress = new KatmRequestClientAddress($pinfl);
                $katmRequestClientAddress->execute();
                if ($katmRequestClientAddress->isSuccessful()) {
                    $address = $katmRequestClientAddress->response()->address();
                    self::logData('getAddressFromKatm result: '.$address);
                    return $address;
                }
            }
            return '';
        }
        catch (\Exception $exception){
            self::logData('getAddressFromKatm failed: '.$exception->getMessage());
            return '';
        }
    }

    private static function doMiniScoring(Buyer $buyer) : void
    {
        if ( !($buyer->settings && $buyer->settings->mini_limit > 0) ) {

            $miniScoringResult = $buyer->scoringResultMini->last();
            if ($miniScoringResult && $miniScoringResult->attempts_limit_reached == 1) {
                $miniScoringResult->attempts_limit_reached = 0;
                $miniScoringResult->save();
            }
            $gradeScoringService = new GradeScoringService();
            $gradeScoringService->initMiniScoring($buyer->id);
            RestartMiniScoringIfBroken::dispatch($buyer->id)->delay(now()->addMinutes(Config::get('test.mini_scoring_check_status_period')));
        }
    }

    public static function getJobStatusWithoutLogic(MyIDJob $job,$attempts = 1) : Response
    {
        $response = self::makeJobStatusRequest($job->job_id);
        if($response instanceof Response && $response->status() == 202 && $attempts < 5){
            $attempts ++;
            sleep(5);
            return self::getJobStatusWithoutLogic($job,$attempts);
        }
        return $response;
    }

    public function checkBuyerToActivateContract(UploadedFile $file,Buyer $buyer,int $contract_id = null) : array
    {
        if(!$this->checkBuyerStatusToActivateContract($buyer)){
            return ['code' => 0,'message' => [__('auth.error_user_not_verified')]];
        }
        $buyerPersonals = $buyer->personals;
        $buyerAddress = $buyer->addressRegistration;
        if(!$buyerPersonals){
            return ['code' => 0,'message' => [__('api.buyer_not_verified')]];
        }
        if(!$buyerPersonals->pinfl){
            return ['code' => 0,'message' => [__('api.myid_result_pinfl_not_found')]];
        }
        $inputs = [];
        $inputs['birth_date'] = $buyer->birth_date;
        $inputs['pinfl'] = EncryptHelper::decryptData($buyerPersonals->pinfl);
        if(strlen($inputs['pinfl']) !== 14){
            return ['code' => 0,'message' => [__('api.myid_result_pinfl_not_found')]];
        }
        if(!strtotime($inputs['birth_date'])){
            return ['code' => 0,'message' => [__('api.myid_client_birth_date_not_found')]];
        }
        $extension = $file->getClientOriginalExtension();
        $image = base64_encode(file_get_contents($file));
        $image = "data:image/$extension;base64,$image";
        $inputs['client_id'] = self::$client_id;
        $inputs['external_id'] = Str::uuid();
        \request()->merge(['external_id' => $inputs['external_id']]);
        $inputs['photo_from_camera']['front'] = $image;
        $inputs['birth_date'] = date('Y-m-d',strtotime($inputs['birth_date']));
        $inputs['agreed_on_terms'] = true;
        //return $inputs;
        $response = self::makeJobRequest($inputs);
        if($response instanceof Response && $response->ok()) {
            $job_data = $response->json();
            $params = [
                'files' => [Buyer::PASSPORT_SELFIE_FOR_CONTRACT => $file],
                'element_id' => $buyerPersonals->id,
                'model' => 'buyer-personal',
                'user_id' => $buyer->id,
            ];
            $uploaded_file = FileHelper::uploadNew($params, true);
            $inputs['photo_from_camera'] = $uploaded_file ? $uploaded_file->id : null;
            $inputs['job_id'] = $job_data['job_id'];
            $inputs['user_id'] = $buyer->id;
            $inputs['type'] = MyIDJob::TYPE_CONTRACT_ACTIVATION;
            $inputs['contract_id'] = $contract_id;
            $job = MyIDJob::create($inputs);
            $job_status = self::getJobStatusWithoutLogic($job);
            if($job_status->status() == 429){
                $error_message =  [__('api.myid_result_too_many_requests')];
                $res_data = $response->json();
                if(isset($res_data['ttl'])){
                    $error_message = [trans('api.myid_client_blocked_for_invalid_request',['ttl' => $res_data['ttl']])];
                }
                return ['code' => 0,'error' => $error_message];
            }
            if($job_status->ok()){
                $data = $job_status->json();
                $job->result_code = $data['result_code'];
                $job->result_note = $data['result_note'];
                $job->profile = $data['profile'];
                $job->comparison_value = $data['comparison_value'];
                $job->save();
                if($data['result_code'] == 1){
                    if(EncryptHelper::decryptData($buyerPersonals->pinfl) !== $data['profile']['common_data']['pinfl']){
                        return ['code' => 0,'message' => 'invalid_data'];
                    }
                    //if address changed insert to kyc_history table
                    if(isset($data['profile']['address']['permanent_address'])){
                        if($buyerAddress){
                            if(trim($buyerAddress->address) !== trim($data['profile']['address']['permanent_address'])){
                                $buyerAddress->address = $data['profile']['address']['permanent_address'];
                                $buyerAddress->address_myid = $data['profile']['address']['permanent_address'];
                                $buyerAddress->save();
                                KycHistory::insertHistory($buyer->id,User::KYC_STATUS_UPDATE,null,'Адрес изменён',null,null,null,$buyerAddress->address);
                            }
                        }else{
                            $buyerAddress  = new BuyerAddress();
                            $buyerAddress->user_id = $buyer->id;
                            $buyerAddress->address_myid = $data['profile']['address']['permanent_address'];;
                            $buyerAddress->address = $data['profile']['address']['permanent_address'];;
                            $buyerAddress->type = BuyerAddress::TYPE_REGISTRATION;
                            $buyerAddress->citizenship_id = $data['profile']['common_data']['citizenship_id'] ?? null;
                            $buyerAddress->save();
                        }
                    }
                    //If passport data changed
                    if(trim($data['profile']['doc_data']['pass_data']) !== trim(EncryptHelper::decryptData($buyerPersonals->passport_number)) || (int)$data['profile']['doc_data']['doc_type_id_cbu'] !== $buyerPersonals->passport_type){
                        BuyerPersonalHistory::create([
                            'passport_number' => isset($buyerPersonals->passport_number) ? EncryptHelper::decryptData($buyerPersonals->passport_number) : '-',
                            'passport_date_issue' => EncryptHelper::decryptData($buyerPersonals->passport_date_issue),
                            'passport_issued_by' => EncryptHelper::decryptData($buyerPersonals->passport_issued_by),
                            'passport_expire_date' => EncryptHelper::decryptData($buyerPersonals->passport_expire_date),
                            'passport_type' => $buyerPersonals->passport_type ?? 6,
                        ]);
                        $buyerPersonals->passport_type = $data['profile']['doc_data']['doc_type_id_cbu'];
                        $buyerPersonals->passport_number = EncryptHelper::encryptData($data['profile']['doc_data']['pass_data']);
                        $buyerPersonals->passport_number_hash = md5($data['profile']['doc_data']['pass_data']);
                        $buyerPersonals->passport_issued_by = EncryptHelper::encryptData($data['profile']['doc_data']['issued_by']);
                        $buyerPersonals->passport_date_issue = EncryptHelper::encryptData($data['profile']['doc_data']['issued_date']);
                        $buyerPersonals->passport_expire_date = EncryptHelper::encryptData($data['profile']['doc_data']['expiry_date']);
                        $buyerPersonals->save();
                    }
                    //If name changed
                    $full_name_ltr = $buyer->surname.$buyer->name;
                    $full_name_rtl = $buyer->name.$buyer->surname;
                    $full_name_response = $data['profile']['common_data']['first_name'].$data['profile']['common_data']['last_name'];
                    if($full_name_ltr !== $full_name_response || $full_name_rtl !== $full_name_response ){
                        $buyer->surname = $data['profile']['common_data']['first_name'];
                        $buyer->name = $data['profile']['common_data']['last_name'];
                        $buyer->patronymic = $data['profile']['common_data']['middle_name'];
                        $buyer->save();
                        KycHistory::insertHistory($buyer->id,User::KYC_STATUS_UPDATE,null,null,$buyer->surname.' '.$buyer->name);
                    }
                    AccountService::generateNIBBDForBuyer($buyer);
                    return ['code' => 1,'message' => 'success','data' => $data];
                }
                return ['code' => 0,'message' => [__('api.myid_result_code_'.$data['result_code'])],'errorCode' => $data['result_code']];
            }
            return ['code' => 0,'message' => [__('api.myid_result_code_'.$job_data['result_code'])],'errorCode' => $job_data['result_code']];
        }
        $message = __('api.internal_error');
        if($response instanceof Response && $response->status() == 422){
            $job_data = $response->json();
            if(isset($job_data['detail'][0]['msg'])){
                $message = $job_data['detail'][0]['msg'];
            }
        }
        if($response instanceof Response && $response->status() == 429) {
            $message =  [__('api.myid_result_too_many_requests')];
            $res_data = $response->json();
            if(isset($res_data['ttl'])){
                $message = [trans('api.myid_client_blocked_for_invalid_request',['ttl' => $res_data['ttl']])];
            }
        }
        return ['code' => 0,'message' => [$message]];
    }

    public static function checkBuyerStatusToActivateContract(Buyer $buyer) : bool
    {
        if(in_array($buyer->status,MyID::MY_ID_ACTIVATE_CONTRACT_USER_STATUSES)) {
            return true;
        }
        return false;
    }

    public static function pinflExist(int $user_id, string $pinfl) : bool
    {
        return BuyerPersonal::query()->where('pinfl_hash','=',md5($pinfl))->where('user_id','!=',$user_id)->exists();
    }

    public static function passportExpirationIsValid(int $buyer_id,string $pass_data, string $birth_date,int $diff_in_months = 3) : bool
    {
        $job = MyIDJob::query()
            ->where('user_id',$buyer_id)
            ->where('pass_data',$pass_data)
            ->where('birth_date',$birth_date)
            ->where('result_code','=',1)
            ->whereNotNull('profile')
            ->first();
        if($job && isset($job->profile['doc_data']['expiry_date'])){
            $now = Carbon::now();
            $expiry_date = Carbon::createFromFormat('d.m.Y',$job->profile['doc_data']['expiry_date']);
            if($now->diffInMonths($expiry_date) < $diff_in_months){
                self::logData('Buyer ID='.$buyer_id.' passport expires in '.$now->diffInMonths($expiry_date).'. expire_date='.$expiry_date->format('Y-m-d'));
                return false;
            }
        }
        return true;
    }

    public static function isPassportExpired(int $buyer_id,string $pass_data, string $birth_date) : bool
    {
        $job = MyIDJob::query()
            ->where('user_id',$buyer_id)
            ->where('pass_data',$pass_data)
            ->where('birth_date',$birth_date)
            ->where('result_code','=',1)
            ->whereNotNull('profile')
            ->orderBy('id','DESC')
            ->first();
        if($job && isset($job->profile['doc_data']['expiry_date'])){
            $today = strtotime(date('d.m.Y'));
            $expiry_date = strtotime($job->profile['doc_data']['expiry_date']);
            if($today >= $expiry_date){
                self::logData('Buyer ID='.$buyer_id.' passport expired at->job '.$job->profile['doc_data']['expiry_date']);
                return true;
            }
        }
        return false;
    }

    private static function getAttemptsCount(int $user_id):int {
        return MyIDJob::where('user_id',$user_id)
            ->where('type','register')
            ->whereDate('created_at', Carbon::today())
            ->count();
    }

    private static function getRedisHashAttemptsCase(int $user_id):string {
        $redis_hash = Hash::make($user_id.time());
        try {
            Redis::set('myid_registration:' . $user_id,$redis_hash,'EX', '3600');
        }
        catch (\Exception $exception) {
            Log::error('could not to connect or save to Redis -> from myid job '.$exception->getMessage());
        }
        return $redis_hash;
    }

    private static function saveResponse(string $message,int $status_code = 200, array $data = [])
    {
        $body = \request()->all();
        $birth_date = isset($data['birth_date']) ?? date('Y-m-d');
        if(isset($data['birth_date'])){
            $birth_date = $data['birth_date'];
        }
        MyIDJob::query()->create([
            'user_id' => Auth::guard('api')->user()->id ?? 1,
            'job_id' => 0,
            'type' => isset($data['pinfl']) || $body['pinfl'] ? 'contract' : 'register',
            'pass_data' => $body['pass_data'] ?? null,
            'birth_date' => $birth_date,
            'external_id' => $body['external_id'] ?? 0,
            'contract_id' => $body['contract_id'] ?? null,
            'result_code' => null,
            'result_note' => substr($message,0,250),
            'status_code' => $status_code,
            'agreed_on_terms' => 1,
        ]);
    }
}
