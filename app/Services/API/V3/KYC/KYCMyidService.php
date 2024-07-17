<?php

namespace App\Services\API\V3\KYC;

use App\Helpers\EncryptHelper;
use App\Helpers\SmsHelper;
use App\Http\Requests\V3\Buyer\UploadPassportAndIDRequest;
use App\Models\Buyer;
use App\Models\Contract;
use App\Models\ContractStatus;
use App\Models\KYCMyidVerification;
use App\Models\Role;
use App\Services\API\V3\BaseService;
use App\Services\API\V3\BuyerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class KYCMyidService extends BaseService
{

    public function uploadDocuments(UploadPassportAndIDRequest $request): void
    {
        $user = Auth::user();

        if (!BuyerService::is_vendor($user->role_id) && $user->role_id !== Role::CLIENT_ROLE_ID) {
            self::handleError([__('app.err_access_denied_role')]);
        }
        if (BuyerService::is_vendor($user->role_id)) {
            $contract = Contract::where('id', $request->contract_id)->where('partner_id', $user->id)->first();
        } else {
            $contract = Contract::where('id', $request->contract_id)->where('user_id', $user->id)->first();
        }

        if (!isset($contract)) {
            self::handleError([__('api.contract_not_found')]);
        }

        $buyer = Buyer::where('id', $contract->user_id)->first();

        if (!isset($buyer)) {
            self::handleError([__('api.buyer_not_found')]);
        }

        $contract_status = ContractStatus::where('contract_id', $request->contract_id)->first();
        if (!isset($contract_status)) {
            self::handleError([__('api.contract_not_found')]);
        }

        $contract_status->status = ContractStatus::STATUS_ORDER_LIVELINESS_DETECTION_PENDING_APPLICATION;
        $contract_status->save();


        $KYCMyidVerification = new KYCMyidVerification;
        $KYCMyidVerification->buyer_id = $buyer->id;
        $KYCMyidVerification->contract_id = $contract_status->contract_id;
        $KYCMyidVerification->save();


        BuyerService::uploadPassportAndID($request, $buyer, $KYCMyidVerification->id);
    }

    public function getList()
    {

        $records = KYCMyidVerification::with('contract_status')->orderBy('created_at', 'desc')->paginate(10);

        return $records;

    }

    public function index(int $id)
    {

        $record = KYCMyidVerification::with('contract.company','files','contract_status','myIdSelfie','contract.order.products','buyer.personalData.files')->find($id);

        if (!isset($record)) {
            return BaseService::handleError([__('app.err_not_found')]);
        }

        $result = $record;

        $result->passport_number = EncryptHelper::decryptData($record->buyer->personalData->passport_number);
        $result->birthday = EncryptHelper::decryptData($record->buyer->personalData->birthday);
        $result->pinfl = EncryptHelper::decryptData($record->buyer->personalData->pinfl);

        return $result;

    }

    public function approveRequest(int $id)
    {

        $kycRequest = KYCMyidVerification::find($id);

        if (!isset($kycRequest)) {
            self::handleError([__('app.err_not_found')]);
        }

        $contract_status = ContractStatus::where('contract_id', $kycRequest->contract_id)->first();

        if (!isset($contract_status)) {
            self::handleError([__('app.err_not_found')]);
        }

        $buyer_phone = correct_phone($kycRequest->buyer->phone);

        $message = 'Vi uspeshno proshli proverku lichnosti dlya oformleniya dogovora rassrochki. Blagodarim, chto vibrali nas! Tel.: 78 7771515';

        [$result, $http_code] = SmsHelper::sendSms($buyer_phone, $message);
        if ($http_code === 200) {
            Log::info($result);

            $contract_status->status = ContractStatus::STATUS_ORDER_LIVELINESS_DETECTION_APPLICATION_APPROVE;
            $contract_status->save();

            $kycRequest->status = KYCMyidVerification::APPROVE_TYPE;
            $kycRequest->save();
            return true;
        }
        return self::handleError([__('panel/buyer.sms_not_sended')]);

    }

    public function rejectRequest(int $id, int $status = 0)
    {
        $kycRequest = KYCMyidVerification::find($id);

        if (!isset($kycRequest)) {
            self::handleError([__('app.err_not_found')]);
        }

        $contract_status = ContractStatus::where('contract_id', $kycRequest->contract_id)->first();

        if (!isset($contract_status)) {
            self::handleError([__('app.err_not_found')]);
        }


        $buyer_phone = correct_phone($kycRequest->buyer->phone);

        if ($status === 1) {
            $message = "Derjatel' pasporta na fotografii ne sootvetstvuet s lichnost'yu na pasporte. Pojaluysta, sdelayte i zagruzite selfi s pasportom i yego vladel'sem. Tel.: 78 7771515";

        } else {
            $message = 'Zagrujennoe foto klienta nekorrektnoe. Pojaluysta, sdelayte selfi-foto sebya s pasportom i zanovo zagruzite. Tel.: 78 7771515';
        }

        [$result, $http_code] = SmsHelper::sendSms($buyer_phone, $message);
        if ($http_code === 200) {
            Log::info($result);

            $contract_status->status = ContractStatus::STATUS_ORDER_LIVELINESS_DETECTION_APPLICATION_REJECT;
            $contract_status->save();

            $kycRequest->status = KYCMyidVerification::REJECTED_TYPE;
            $kycRequest->save();
            return true;
        }
        return self::handleError([__('panel/buyer.sms_not_sended')]);

    }


}
