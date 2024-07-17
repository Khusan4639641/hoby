<?php

namespace App\Services\API\V3\Partners;

use App\Helpers\CategoryHelper;
use App\Helpers\EncryptHelper;
use App\Models\Buyer;
use App\Models\CatalogCategory;
use App\Models\Contract;
use App\Models\ContractPaymentsSchedule;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Partner;
use App\Models\User;
use App\Services\API\V3\BaseService;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\FileHelper;
use Validator;


class BuyerService extends BaseService
{
    public static function validatePhone(Request $request)
    {
        $inputs = $request->only('phone');
        $validator = Validator::make($inputs, [
            'phone' => 'required|numeric|digits:12|regex:/(998)[0-9]{9}/',
        ]);
        if ($validator->fails()) {
            self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function validateBuyer(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'buyer_id' => 'required|exists:users,id'
        ]);
        if ($validator->fails()) {
            return self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function list(Request $request)
    {
        $user = Auth::user();
        $partner = Partner::with('company')->find($user->id);
        $phone = $request->input('phone');
        $buyer = Buyer::select('id', 'email', 'name', 'surname', 'patronymic', 'phone', 'status', 'doc_path', 'vip', 'created_by','black_list')
            ->with('settings:id,user_id,limit,personal_account,balance,zcoin,paycoin,mini_balance as mini_limit', 'personals:id,user_id,passport_type')
            ->where('phone', $phone)
            ->first();
        if (!$buyer) {
            return self::handleError([__('panel/buyer.err_buyer_not_found')], 'error', 404);

        }
        if(!$partner->can('detail', $buyer)){
            return self::handleError([__('app.err_access_denied')]);
        }
        $buyer->status_caption = __('user.statuslar_' . $buyer->status);
        // при оформлении договора показать (сумму?), что он в просрочке и не давать оформить
        $debts = ContractPaymentsSchedule::leftJoin('contracts','contract_payments_schedule.contract_id','=','contracts.id')
                                                ->where('contracts.user_id',$buyer->id)
                                                ->where('contract_payments_schedule.status',ContractPaymentsSchedule::STATUS_UNPAID)
                                                ->where('contract_payments_schedule.payment_date','<=',Carbon::now()->format('Y-m-d 23:59:59'))
                                                ->whereIn('contracts.status',[Contract::STATUS_ACTIVE,Contract::RECOVERY_TYPE_LETTER_WAIT,Contract::RECOVERY_TYPE_NOTARIUS])
                                                ->sum('contract_payments_schedule.balance');
        $vip_allowed = 1;
        if ($partner->company->vip == 1) {
            // если вендор сам платит за клиента, проверим его ли это клиент
            if ($buyer->vip) {
                // 1 - разрешено оформлять у этого вендора, 0 - не разрешено
                if ($user->id != $buyer->created_by) $vip_allowed = 0;
            }else{
                $vip_allowed = 0;
            }
        }else{
            // не вип вендор не может продавать вип клиентам
            if ($buyer->vip) {
                $vip_allowed = 0;
            }
        }
        // раскодирование паспорта
        if (isset($buyer->personals->passport_number)){
            $buyer->personals->passport_number = EncryptHelper::decryptData($buyer->personals->passport_number);
        }
        if($buyer->settings){
          $buyer->settings->limit = floatval($buyer->settings->limit);
          $buyer->settings->personal_account = floatval($buyer->settings->personal_account);
          $buyer->settings->balance = $partner->company->reverse_calc ? round($buyer->settings->balance * 1.42,2) : floatval($buyer->settings->balance);
          $buyer->settings->zcoin = floatval($buyer->settings->zcoin);
          $buyer->settings->paycoin = floatval($buyer->settings->paycoin);
          $buyer->settings->mini_limit = floatval($buyer->settings->mini_limit);
        }
        $result = $buyer;
        $result['debs'] = floatval($debts);// сумма просрочки
        $result['vip_allowed'] = $vip_allowed;   // vip - может купить только у вендора, кто его зарегистрировал
        $result['black_list'] = $buyer->black_list;   // черный список
        // test-395-Abdulaziz
        $result['address_is_received'] = ($buyer->addressRegistration && $buyer->addressRegistration->address !== null) ? 1 : 0;

        if ($buyer->personals->latest_id_card_or_passport_photo) {
            if ($buyer->personals->passport_type == 0) {
                $buyer->personals['id_passport_selfie'] = $buyer->personals->latest_id_card_or_passport_photo;
                $buyer->personals['id_passport_selfie']['full_path'] = FileHelper::url($buyer->personals->latest_id_card_or_passport_photo->path);
            } else {
                $buyer->personals['passport_selfie'] = $buyer->personals->latest_id_card_or_passport_photo;
                $buyer->personals['passport_selfie']['full_path'] = FileHelper::url($buyer->personals->latest_id_card_or_passport_photo->path);
            }
            unset($buyer->personals->latest_id_card_or_passport_photo);
        }

        // end

        return self::handleResponse($result);
    }

    public static function phonesCount(int $buyer_id, int $category_id = 0)
    {
        if ($category_id !== 0 &&
            !self::checkCategoryByParent($category_id, CategoryHelper::getPhoneCategoryIDs())) {
            $phones = 0;
        } else {
            $phones = self::getPhonesCount($buyer_id);
        }
        if ($phones > 1) {
            return self::handleError([__('billing/order.txt_phones_count')]);
        }
        $result['phones_count'] = $phones;
        return self::handleResponse($result);
    }

    private static function checkCategoryByParent(int $id, array $phoneCategories): bool
    {
        if (in_array($id, $phoneCategories)) {
            return true;
        }
        $category = CatalogCategory::find($id);
        if (!$category->parent_id) {
            return false;
        }
        return self::checkCategoryByParent($category->parent_id, $phoneCategories);
    }

    public static function getPhonesCount(int $buyerID): int
    {
        $products = OrderProduct::whereIn('order_id', Order::whereIn('id', Contract::where('user_id', $buyerID)->whereIn('status', [Contract::STATUS_ACTIVE, Contract::STATUS_OVERDUE_60_DAYS, Contract::STATUS_OVERDUE_30_DAYS])->select('order_id'))->select('id'))->orderBy('id')->get();

        return self::getPhonesCountByCategories($products->pluck('category_id')->all());
    }

    public static function getPhonesCountByCategories(array $categories): int
    {
        $count = 0;
        $phoneCategories = CategoryHelper::getPhoneCategoryIDs();
        foreach ($categories as $category) {
            if (!$category) {
                continue;
            }
            $count += (int)self::checkCategoryByParent($category, $phoneCategories);
        }
        return $count;
    }

    public function verify(string $phone) : HttpResponseException
    {
        $buyer = Buyer::where('phone',$phone)->first();
        if(!$buyer){
            self::handleError([__('panel/buyer.err_buyer_not_found')]);
        }
        $status = $buyer->black_list ? 8 : $buyer->status;
        $result = [
            'buyer_id' => $buyer->id,
            'message' => __('user.status_'.$status),
            'result' => $status,
        ];
        if ($status == User::KYC_STATUS_VERIFY) {
            $balance = $buyer->settings ? $buyer->settings->balance + $buyer->settings->personal_account : 0;
            $result['available_balance'] = number_format($balance, 2, ".", "");
        }
        self::handleResponse($result);
    }
}
