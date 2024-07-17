<?php

namespace App\Services\API\V3;

use App\Helpers\FileHelper;
use App\Helpers\ImageHelper;
use App\Helpers\OCRHelper;
use App\Http\Controllers\Core\CardController;
use App\Models\Buyer;
use App\Models\BuyerPersonal;
use App\Models\File;
use App\Models\KycHistory;
use App\Models\Ocr;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;
use Log;
use Validator;

class BuyerProfileService extends BaseService
{

    public static function validateVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'step' => ['required', Rule::in(2)],
            'card_number' => ['requiredIf:step,1', 'string', 'max:255'],
            'card_valid_date' => ['requiredIf:step,1', 'string', 'max:255'],

            'address_region' => ['sometimes:step,2', 'string'],
            'address_area' => ['sometimes:step,2', 'string'],
            'address_city' => ['sometimes', 'string'],
            'address' => ['sometimes:step,2', 'string'],
            // 'passport_selfie' => ['requiredIf:step,2', 'image'],
            // 'passport_first_page' => ['requiredIf:step,2', 'image'],
            // 'passport_with_address' => ['requiredIf:step,2', 'image'],
            'passport_type' => ['sometimes:step,2', Rule::in(6, 0)],
        ]);
        if ($validator->fails()) {
            self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function modifyVerification(Request $request)
    {
        $inputs = self::validateVerification($request);
        Log::info('BuyerProfile:modifyVerification - new');
        Log::info($request);
        $user = Auth::user();
        $buyer = Buyer::find($user->id);
        if (!$buyer) {
            return self::handleError([__('auth.error_user_not_found')]);
        }
        if($buyer->status == User::KYC_STATUS_VERIFY){
            return self::handleError([__('api.user_verified')]);
        }
        $config = Config::get('test');
        switch ($request->step) {
            case "1": // проверка карты
                $card = new CardController();
                $response = $card->add($request);
                if (isset($response['status']) && $response['status'] == 'success') {
                    return self::handleResponse([__('panel/buyer.txt_card_added')]);
                } else {
                    return self::handleError([__('auth.service_unavailable')]);
                }
                break;
             // проверка паспорта
            case "2":
                if ($buyer->personals) {
                    $step_rule2['passport_selfie'] = ['nullable', 'image'];
                    $step_rule2['passport_first_page'] = ['nullable', 'image'];
                    $step_rule2['passport_with_address'] = ['nullable', 'image'];
                } else {
                    $step_rule2 = [
                        'passport_selfie'       => ['nullable', 'image'],
                        'passport_first_page'   => ['nullable', 'image'],
                        'passport_with_address' => ['nullable', 'image']
                    ];
                }
                // return $step_rule2;
                $validator = Validator::make($request->all(), $step_rule2);
                if ($validator->fails()) {
                    self::handleError($validator->errors()->getMessages());
                }
                $buyerPersonals = $buyer->personals ?? new BuyerPersonal();
                $buyerPersonals->user_id = $buyer->id;
                $buyerPersonals->passport_type = $buyerPersonals->passport_type ?? $request->passport_type;
                $buyerPersonals->save();
                //Save files
                $filesToDelete = ($request->files_to_delete != '') ? explode(',', $request->files_to_delete) : [];
                if (count($request->file()) > 0) {
                    Log::info('buyer_id: ' . $buyer->id);
                    Log::info('save files' . __FILE__);
                    foreach ($request->file() as $file) {
                        $img = new ImageHelper($file);
                        $img->resize($config['documents_size']['width'], $config['documents_size']['height']);
                        $img->save($file->getRealPath(), 100, $file->extension());
                    }
                    $params = [
                        'files' => $request->file(),
                        'element_id' => $buyerPersonals->id,
                        'model' => 'buyer-personal'
                    ];
                    FileHelper::upload($params, $filesToDelete, true);
                    $file = File::where('element_id', $buyer->personals->id)->where(function ($file) {
                        $file->where('type', 'passport_first_page')->orWhere('type', 'id_first_page')->latest();
                    })->first();
                    if (isset($file)) {
                        $data = ['user_id' => $buyer->personals->id, 'file_name' => $file->name];
                        Log::info('OCR data new:');
                        // для паспорта отправка на распознование
                        // получаем PINFL, FIO ...
                        $ocrData = OCRHelper::send($data);
                        Log::info($ocrData);
                        Log::info('OCR user->status: ' . $buyer->status);
                        if ($ocrData['status'] == 'success' && (isset($ocrData['data'])  && is_array($ocrData['data']))) {
                            // результат распознования
                            $ocr = new Ocr();
                            $ocr->user_id = $buyer->id;
                            $ocr->response = json_encode($ocrData, JSON_UNESCAPED_UNICODE);
                            $ocr->save();
                            // сохранить покупателю Buyer полученную информацию от OCR
                            Buyer::saveOcrData($buyer, $ocrData['data']);
                        }
                        User::changeStatus($buyer, 11);
                        Log::info('OCR add-passport sucess. set buyer status 10, buyer_id: ' . $buyer->id);
                    }
                    if ($request->type == 2 && (isset($request->passport_selfie) || isset($request->id_selfie))) { // Селфи
                        User::changeStatus($buyer, 12); // отправляем на добавление доверенного лица
                        Log::info('add selfie buyer_id: ' . $buyer->id);
                    }
                    if ($request->type == 2 && (isset($request->passport_with_address) || isset($request->id_with_address))) { // прописка в паспорте
                        User::changeStatus($buyer, 10);
                        Log::info('add passport_with_address buyer_id: ' . $buyer->id);
                    }
                    // меняем kyc статус покупателя
                    $buyer->kyc_status = User::KYC_STATUS_MODIFY;
                    $buyer->kyc_id = null;
                    $buyer->save();
                    // добавляем в историю запись
                    KycHistory::insertHistory($buyer->id, User::KYC_STATUS_MODIFY);
                }
                return self::handleResponse($buyer);
                break;
        }
        return self::handleError([__('app.err_access_denied')]);
    }
}
