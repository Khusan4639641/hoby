<?php


namespace App\Services\API\Core;


use App\Http\Response\BaseResponse;
use App\Models\Buyer;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentHistoryService
{
    public $result = [];

//    protected $userPaymantTypes = [
//
//        'personal_account_bank_cards' => [
//                    'payment_system' => ['UZCARD', 'HUMO'],
//                    'status'         => 1,
//                    'type'           => ['user'],
//                    'description'    => "Пополнение лицевого счета через банковские карты",
//                    'searchBy'       => "user_id",
//        ],
//
//        'personal_account_payment_systems' => [
//                    'payment_system' => ['OCLICK', 'PAYME','PAYNET','UPAY','APELSIN', 'MYUZCARD', 'BANK'],
//                    'status'         => 1,
//                    'type'           => ['user'],
//                    'description'    => "Пополнение лицевого счета через платежные системы и через банк",
//                    'searchBy'       => "user_id",
//        ],
//
//        'personal_account_autopay' => [
//                    'payment_system' => ['Autopay'],
//                    'status'         => 1,
//                    'type'           => ['user'],
//                    'description'    => "Пополнение лицевого счета через сервис autopay",
//                    'searchBy'       => "user_id",
//        ],
//
//        'personal_account_mib' => [
//                    'payment_system' => ['MIB'],
//                    'status'         => 1,
//                    'type'           => ['user'],
//                    'description'    => "Пополнение лицевого счета через МИБ",
//                    'searchBy'       => "user_id",
//        ],
//
//        'bank_cards_auto_pay_with_pinfl' => [
//                    'payment_system' => ['UZCARD', 'HUMO', 'PNFL'],
//                    'status'         => 1,
//                    'type'           => ['auto'],
//                    'description'    => "Автоматическое списание с банковских карт и других карт клиента по ПИНФЛ",
//                    'searchBy'       => "user_id",
//        ],
//    ];
//
//    protected $contractPaymantTypes = [
//
//        'auto_pay_card_with_pnfl' => [
//                    'payment_system' => ['UZCARD', 'HUMO', 'PNFL'],
//                    'status'         => 1,
//                    'type'           => ['auto'],
//                    'description'    => "Автоматическое списание с банковских карт и других карт клиента по ПИНФЛ",
//                    'searchBy'       => "contract_id",
//        ],
//
//        'auto_pay_from_account' => [
//                    'payment_system' => ['ACCOUNT'],
//                    'status'         => 1,
//                    'type'           => ['auto'],
//                    'description'    => "Автоматическое списание с лицевого счета",
//                    'searchBy'       => "contract_id",
//        ],
//
//        'account_from_deposit' => [
//                    'payment_system' => ['DEPOSIT'],
//                    'status'         => 1,
//                    'type'           => ['auto'],
//                    'description'    => "Cписание Депозита с лицевого счета",
//                    'searchBy'       => "contract_id",
//        ],
//
//        'account_from_early_repayment' => [
//                    'payment_system' => ['ACCOUNT'],
//                    'status'         => 1,
//                    'type'           => ['user_auto'],
//                    'description'    => "Cписание с лицевого счета (Досрочное погашение)",
//                    'searchBy'       => "contract_id",
//        ],
//
//        'reimbursement_of_expenses_from_card_or_account' => [
//                    'payment_system' => ['UZCARD', 'HUMO', 'ACCOUNT'],
//                    'status'         => 1,
//                    'type'           => ['reimbursable', 'reimbursable_autopay'],
//                    'description'    => "Возмещение расходов (списание с карт или лицевого счета)",
//                    'searchBy'       => "contract_id",
//        ],
//    ];
    protected function getPaymentsByUserId($user_id){
        return Payment::where('user_id', $user_id)->where(function ($query) {
            $query->where(function ($query) {
                $query->where('type', 'user')->whereIn('payment_system', ['UZCARD', 'HUMO', 'CLICK', 'PAYME', 'PAYNET', 'PAY', 'APELSIN', 'BANK', 'Autopay','MIB']);
            })->orWhere(function ($query) {
//                'PINFL' и 'PNFL' на проде существует второй тип. На случай если исправят ошибку - оставил оба.
                $query->where('type', 'auto')->whereIn('payment_system', ['UZCARD', 'HUMO', 'PINFL', 'PNFL']);
            });
        })->get(['amount','payment_system', 'status', 'created_at','type']);
    }
    protected function getPaymentsByContractId($contract_id){
        return Payment::where('contract_id', $contract_id)->where(function ($query) {
            $query->where(function ($query) {
                $query->where('type', 'auto')->whereIn('payment_system', ['UZCARD', 'HUMO', 'PINFL', 'ACCOUNT', 'DEPOSIT']);
            })->orWhere(function ($query) {
                $query->where('type', 'user_auto')->where('payment_system', 'ACCOUNT');
            })->orWhere(function ($query) {
                $query->whereIn('type', ['reimbursable', 'reimbursable_autopay'])->whereIn('payment_system', ['UZCARD', 'HUMO', 'ACCOUNT']);
            });
        })->get(['amount','payment_system', 'status', 'created_at','type']);
    }

    public function getInfo(Request $request) {
        if (!$request->user_id && !$request->contract_id) {
            return BaseResponse::error('Invalid params',400);
        }

        if($request->user_id) {
            return $this->getPaymentsByUserId($request->user_id);
        } elseif ($request->contract_id) {
            return $this->getPaymentsByContractId($request->contract_id);
        }

        return $this->result;
    }
}
