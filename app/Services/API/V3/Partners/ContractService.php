<?php

namespace App\Services\API\V3\Partners;

use App\Helpers\FileHelper;
use App\Helpers\SellerBonusesHelper;
use App\Helpers\SmsHelper;
use App\Helpers\V3\OTPAttemptsHelper;
use App\Models\CancelContract;
use App\Models\Contract;
use App\Models\File;
use App\Models\GeneralCompany;
use App\Models\Partner;
use App\Models\PartnerContractAction;
use App\Models\User;
use App\Services\API\V3\BaseService;
use App\Services\MFO\MFOPaymentService;
use App\Traits\UzTaxTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class ContractService extends BaseService
{

    use UzTaxTrait;

    public static function validateCancelContract(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'contract_id' => 'required|exists:contracts,id'
        ]);
        if ($validator->fails()) {
            return self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function validateCheckCancelSms(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'contract_id' => 'required|exists:contracts,id',
            'code' => 'required|numeric|digits:6'
        ]);
        if ($validator->fails()) {
            return self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function validateUploadAct(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'act' => 'required|file|mimes:jpg,png,pdf',
            'id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function validateUploadImei(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'imei' => 'required|file|mimes:jpg,png,pdf',
            'id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function validateUploadClientPhoto(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'client_photo' => 'required|file|mimes:jpg,png,pdf',
            'id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }
    private function validateContractDetailRequest(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|integer|exists:contracts,id',
        ]);
        if ($validator->fails()) {
            return self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function cancelContract( Request $request)
    {
        $user = Auth::user();
        $partner = Partner::find($user->id);
        $contract = Contract::with('buyer')->where('status',Contract::STATUS_ACTIVE)->find($request->contract_id);
        if(!$contract){
            return self::handleError([__('api.contract_not_found')]);
        }
        if($contract->cancellation_status === 1) {// проверка на заявку от филлиала
            if ($user->company_id !== $contract->company->parent_id) {
                return self::handleError([__('app.err_access_denied_cancellation_request')]);
            }
        }
        elseif($contract->company->parent_id !== $partner->company->parent_id){
            return self::handleError([__('app.err_access_denied_incorrect_contract_id')]);
        }
        $code = OTPAttemptsHelper::generateCode(6);
        $contract_date = date('Y.m.d', strtotime($contract->created_at));

        $msg = 'Kod: ' . $code . '. '
            . $contract_date . ' da rasmiylashtirilgan ' . $contract->id
            . ' shartnomani bekor qilish kodi. Tel: ' . callCenterNumber(2);

        $hashedCode = Hash::make($contract->buyer->phone . $code);

        [$result, $http_code] = SmsHelper::sendSms($contract->buyer->phone, $msg);
        Log::info($result);

        Redis::set($contract->buyer->phone . '-' . $contract->id, $hashedCode);

        if (($http_code === 200)) {
            Log::channel('contracts')->info('Отправка смс кода клиенту ' . $contract->buyer->phone . ' об отмене контракта ' . $contract->id . ' Партнер ' . $contract->partner_id.' SMS '.$contract->buyer->phone . ': ' . $msg);
            return self::handleResponse();
        }
        Log::channel('contracts')->info("НЕ отправлен смс клиенту об отмене контракта $contract->id");

        return self::handleError([__('api.internal_error')]);
    }

    public static function checkCancelSms(Request $request)
    {
        $user = Auth::user();
        $partner = Partner::find($user->id);
        $contract = Contract::with('buyer')->where('status',Contract::STATUS_ACTIVE)->find($request->contract_id);
        if(!$contract){
            return self::handleError([__('api.contract_not_found')]);
        }
        if($contract->cancellation_status === 1) {// проверка на заявку от филлиала
            if ($user->company_id !== $contract->company->parent_id) {
                return self::handleError([__('app.err_access_denied_cancellation_request')]);
            }
        }
        elseif($contract->company->parent_id !== $partner->company->parent_id){
            return self::handleError([__('app.err_access_denied_incorrect_contract_id')]);
        }
        $hash = Redis::get($contract->buyer->phone . '-' . $request->contract_id);
        if(empty($hash)){
            return self::handleError([__('api.bad_request')]);
        }
        $resultCheck = UniversalService::checkSmsCode($contract->buyer->phone,$request->code,$hash);
        if ($resultCheck) {
            $contract->cancel_reason = $request->code;
            $contract->canceled_at = date('Y-m-d H:i:s');
            $contract->status = Contract::STATUS_CANCELED;
            $contract->cancellation_status = 3; // Отмена подтверждена
            $contract->order->status = 5;

            $limit = $contract->order->credit - $contract->deposit;
            if(isset($contract->price_plan) && $contract->price_plan->is_mini_loan) {
                //мини лимит
                $contract->buyer->settings->mini_balance += $limit;  // вернуть лимит
            }
            else {
                $contract->buyer->settings->balance += $limit;  // вернуть лимит
            }
            if ($contract->deposit > 0)  $contract->buyer->settings->personal_account += $contract->deposit; // вернуть депозит на ЛС, если он был

            $contract->save();
            $contract->order->save();
            $contract->buyer->settings->save();

            //MFO Отмена договора
            if($contract->general_company_id === GeneralCompany::MFO_COMPANY_ID) {
                $service = new MFOPaymentService();
                $service->cancelTransactionCheckSms($contract);
            }

            // создать минусовой договор с датой создания = дата отмены
            $cancel_contract = new CancelContract();
            $cancel_contract->contract_id = $contract->id;
            $cancel_contract->user_id = $contract->user_id;
            $cancel_contract->created_at = $contract->canceled_at;  // датой создания = дата отмены
            $cancel_contract->canceled_at = $contract->canceled_at;  // датой создания = дата отмены
            $cancel_contract->total = -1 * $contract->total;
            $cancel_contract->balance = -1 * $contract->balance;
            $cancel_contract->deposit = -1 * $contract->deposit;
            $cancel_contract->save();

            SellerBonusesHelper::refundByContract($contract->id);
            self::refundReturnProduct($contract->id);
            Redis::del($contract->buyer->phone . '-' . $contract->id);
            return self::handleResponse();
        }
        return self::handleError([__('auth.error_code_wrong')]);
    }

    public static function uploadAct($params = [])
    {
        $user = Auth::user();
        $contract = Contract::with('act')->where('partner_id',$user->id)->find($params['id']);
        if(!$contract){
            return self::handleError([__('app.err_not_found')]);
        }
        //Delete files
        $filesToDelete = $contract->act ? [$contract->act->id] : [];
        //Upload files
        $fileParams = [
            'files' => ['act' => $params['act']],
            'element_id' => $contract->id,
            'model' => 'contract'
        ];
        FileHelper::upload($fileParams, $filesToDelete, true);
        //Change contract act status
        $contract->act_status = $params['act_status'] ?? 1;
        $contract->save();
        $contract->load('act');

        $result['path'] = $contract->act->path;
        $result['message'] = __('billing/contract.txt_act_uploaded');
        return self::handleResponse($result);
    }

    public static function uploadImei($params = [])
    {
        $user = Auth::user();
        $contract = Contract::with('imei')->where('partner_id', $user->id)->find($params['id']);
        if(!$contract){
            return self::handleError([__('app.err_not_found')]);
        }
        //Delete files
        $filesToDelete = $contract->imei ? [$contract->imei->id] : [];
        //Upload files
        $fileParams = [
            'files' => ['imei' => $params['imei']],
            'element_id' => $contract->id,
            'model' => 'contract'
        ];
        FileHelper::upload($fileParams, $filesToDelete, true);
        //Change contract imai status
        $contract->imei_status = $params['imei_status'] ?? 3;
        $contract->save();
        $contract->load('imei');

        $result['path'] = $contract->imei->path;
        $result['message'] = __('billing/contract.imei_status_3');
        return self::handleResponse($result);
    }

    public static function uploadClientPhoto($params = [])
    {
        $user = Auth::user();
        $contract = Contract::with('clientPhoto')->where('partner_id', $user->id)->find($params['id']);
        if(!$contract){
            return self::handleError([__('app.err_not_found')]);
        }
        $filesToDelete = $contract->client_photo ? [$contract->client_photo->id] : [];
        $fileParams = [
            'files' => ['client_photo' => $params['client_photo']],
            'element_id' => $contract->id,
            'model' => 'contract'
        ];
        FileHelper::upload($fileParams, $filesToDelete, true);
        //Change contract client photo status
        $contract->client_status = isset($params['client_status']) ? $params['client_status'] : 3;
        $contract->save();
        $contract->load('clientPhoto');

        $result['path'] = $contract->clientPhoto->path;
        $result['message'] = __('billing/contract.client_photo_status_3');
        return self::handleResponse($result);
    }

    private function validateCancellationContractRequest(Request $request): array
    {
      $validator = Validator::make($request->all(), [
        'contract_id' => 'required|integer',
        'reason' => 'required|string|min:16|max:255',
      ]);
      if ($validator->fails()) {
        return self::handleError($validator->errors()->getMessages());
      }
      return $validator->validated();
    }

    public function validateRejectCancellationContractRequest(Request $request)
    {
      $validator = Validator::make($request->all(), [
        'contract_id' => 'required|integer',
      ]);
      if ($validator->fails()) {
        return self::handleError($validator->errors()->getMessages());
      }
      return $validator->validated();

    }

    public function cancellationContractRequest(Request $request)
    {
      $validated = $this->validateCancellationContractRequest($request);
      $partnerUser = Auth::user();

      $contract = Contract::where('id', $validated['contract_id'])->first();

      if (!isset($contract)) {
        return self::handleError([__('api.contract_not_found')]);
      }

      if ($contract->cancellation_status === 1) {
        return self::handleError([__('api.contract_is_already_sent')]);
      }

      if ($contract->cancellation_status === 3) {
        return self::handleError([__('api.contract_is_already_aborted')]);
      }

      if ($partnerUser->company_id !== $contract->company_id) {
        return self::handleError([__('api.contract_not_found')]);
      }

      $reason = $validated['reason'];

      $user = User::find($contract->user_id); // client
      $contract->cancellation_status = 1;
      $contract->contract_cancellation_reason = $reason;
      $contract->update();
      $clientSignatureFile = File::where('user_id', $user->id)->where('type', File::TYPE_SIGNATURE)->first();
      $clientSignaturePath = !empty($clientSignatureFile) ? Config::get('test.sftp_file_server_domain').'storage/'. $clientSignatureFile->path : null;


      $fileType = File::TYPE_CANCEL_ACT;
      $viewPath = 'contract/' . $contract->id . '/';

      $generalCompanyRecord = GeneralCompany::find($contract->general_company_id);
      $generalSignaturePath = $generalCompanyRecord->sign ?? '';

      FileHelper::generateAndUploadHtml($contract->id, 'contract', $fileType, '', $viewPath, 'mobile.partners.cancellation.act', compact(['contract','user','clientSignaturePath','generalSignaturePath']));
    }

    public function rejectCancellationContractRequest(Request $request)
    {
      $validated = $this->validateRejectCancellationContractRequest($request);

      $user = Auth::user();

      $contract = Contract::where('id', $validated['contract_id'])->first();

      if (!isset($contract)) {
        return self::handleError([__('api.contract_not_found')]);
      }

      if ($user->company_id !== $contract->company->parent_id) {
        return self::handleError([__('api.contract_not_found')]);
      }

      if ($contract->cancellation_status === 3 || $contract->staus === 5) {
        return self::handleError([__('api.contract_is_already_aborted')]);
      }

      if ($contract->cancellation_status === 2) {
        return self::handleError([__('api.contract_request_is_already_aborted')]);
      }

      $contract->cancellation_status = 2;
      $contract->update();
    }
    public function getContractDetail(Request $request) {
        $validated = $this->validateContractDetailRequest($request);
        $user = Auth::user();
        $contract_id = $validated['contract_id'];

        $contract = Contract::where('partner_id', $user->id)->where('id', $contract_id)->first();

        if(!isset($contract)) {
            return self::handleError([__('api.contract_not_found')]);
        }

        //special response for resus
        return (object)[
            'contract_id' => $contract->id,
            'contract_status' => $contract->status,
            'created_at' => $contract->created_at,
            'updated_at' => $contract->updated_at,
        ];

    }

    public function storeAction($request, $contract)
    {
        PartnerContractAction::create($request->merge([
            'company_id' => $contract->company_id,
            'contract_id' => $contract->id,
            'partner_id' => $contract->partner_id,
        ])->toArray());

        return response('', 201);
    }


}
