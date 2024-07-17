<?php

namespace App\Http\Resources\Core\LetterController;

use App\Helpers\EncryptHelper;
use App\Helpers\FileHelper;
use App\Models\Letter;
use App\Models\NotarySetting;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LetterFillingDataResource extends JsonResource
{
    private function prepareResponse()
    {

        $this->contract->letters_expense_var = round($this->contract->balance / 100);

        if($this->notary_setting) {
            $this->notary_setting->fee_string = num2str( $this->notary_setting->fee, true, $this->contentLanguage === 'uz' );
            $this->notary_setting->half_fee = $this->notary_setting->fee / 2;
            $this->notary_setting->half_fee_string = num2str( $this->notary_setting->fee / 2, true, $this->contentLanguage === 'uz' );

            $total_expense = $this->contract->balance + $this->contract->letters_expense_var + $this->notary_setting->fee; // $this->contract->balance * 1.01 + $this->notary_setting->fee
            $this->contract->total_expense = number_format($total_expense, '2', ',', ' ');
            $this->contract->total_expense_string = num2str($total_expense, '', $this->contentLanguage === 'uz');
        }

        $debts_amount = 0;
        if ( $activePayments = $this->contract->activePayments ) {
            $today = Carbon::today()->endOfDay();
            foreach ( $activePayments as $schedule) {
                $payment_date = Carbon::parse($schedule->payment_date);
                $diffInDays = $payment_date->diffInDays($today, false);

                if ( $diffInDays > 0 ) {
                    $debts_amount += (float) $schedule->balance;
                }
            }
        }
        $this->contract->debts_amount = $debts_amount;

        if($this->contract->generalCompany) {
            $this->contract->generalCompany->stamp = FileHelper::url($this->contract->generalCompany->stamp);

            $letter_to_residency = $this->contract->letters()
                ->whereType(Letter::LETTER_TYPE_RESIDENCY)
                ->whereNotNull('amounts')
                ->orderByDesc("letters.id")
                ->orderByDesc("letters.created_at")
                ->first()
            ;
            if ( $letter_to_residency ) {
                $amounts = $letter_to_residency->amounts;
                if ( !empty($amounts) ) {
//                    $this->contract->total_max_amount = $amounts['total_max_amount'] ?? 0;
                    $this->contract->payments_sum_balance = $amounts["payments_sum_balance"] ?? 0;
                    $this->contract->total_max_autopay_post_cost = $amounts['total_max_autopay_post_cost'] ?? 0;
                    $this->contract->total_max_percent_fix_max   = $amounts['total_max_percent_fix_max']   ?? 0;

                    $this->contract->autopay   = $amounts["autopay"]   ?? 0;
                    $this->contract->post_cost = $amounts["post_cost"] ?? 0;
                    $this->contract->percent   = $amounts["percent"]   ?? 0;
                    $this->contract->fix_max   = $amounts["fix_max"]   ?? 0;

//                    if ($this->contract->generalCompany->is_tpp) {            // Путь суда
                        $this->contract->autopay = $amounts["autopay"] ?? 0;
                        $this->contract->post_cost = $amounts["post_cost"] ?? 0;
//                    }
//                    else {                                                    // Путь нотариуса
                        $this->contract->percent = $amounts["percent"] ?? 0;
                        $this->contract->fix_max = $amounts["fix_max"] ?? 0;
//                    }
                    $this->contract->payments_sum_autopay = $this->contract->payments_sum_balance + $this->contract->autopay;
                }
            }
            else {
                $this->contract->payments_sum_balance = (float) $this->contract->activePayments->sum('balance');
                $this->contract->autopay   = 0;             // Путь суда, 0 если generalCompany->is_tpp = 0
                $this->contract->payments_sum_autopay = 0;  // Путь суда, 0 если generalCompany->is_tpp = 0
                $this->contract->post_cost = 0;             // Путь суда, 0 если generalCompany->is_tpp = 0
                $this->contract->percent   = 0;  // Путь нотариуса, 0 если generalCompany->is_tpp = 1
                $this->contract->fix_max   = 0;  // Путь нотариуса, 0 если generalCompany->is_tpp = 1


                $this->contract->payments_sum_balance = $this->contract->activePayments->sum('balance');
//                if ($this->contract->generalCompany->is_tpp) {  // Путь суда
                    $this->contract->autopay   = ($this->contract->payments_sum_balance * 100) / 97 - $this->contract->payments_sum_balance;      // это равно: ($payments_sum_balance / 0,97) - $payments_sum_balance
                    $this->contract->post_cost = (float) NotarySetting::where("template_number", "fourth")->first()->fee; // Нам главное нужно чтобы тут было 15 000 сум (константа):
                    $this->contract->payments_sum_autopay = $this->contract->payments_sum_balance + $this->contract->autopay;
//                } else {                                        // Путь нотариуса
                    $this->contract->percent = (float) ($this->contract->payments_sum_balance / 100);
                    $this->contract->fix_max = (float) NotarySetting::max('fee');
//                }

//                $this->contract->total_max_amount = (
//                    $this->contract->payments_sum_balance
//                    + ($this->contract->autopay + $this->contract->post_cost) // Путь суда, 0 если generalCompany->is_tpp = 0
//                    + ($this->contract->percent + $this->contract->fix_max)   // Путь нотариуса, 0 если generalCompany->is_tpp = 1
//                );
                // Путь суда, 0 если generalCompany->is_tpp = 0
                $this->contract->total_max_autopay_post_cost = ( $this->contract->payments_sum_balance + ($this->contract->autopay + $this->contract->post_cost) );
                // Путь нотариуса, 0 если generalCompany->is_tpp = 1
                $this->contract->total_max_percent_fix_max   = ( $this->contract->payments_sum_balance + ($this->contract->percent + $this->contract->fix_max) );
            }
        }

        $this->contract->real_expired_days_minus_one = 0;

        if ( $this->contract->expired_at ) {
            $this->contract->real_expired_days_minus_one = Carbon::parse($this->contract->expired_at)->diffInDays() - 1;
        }

        $this->lettersResponse = [];
        foreach($this->contract->letters as $letter) {
            if($letter->response !== null) {
                $this->lettersResponse[] = $letter->response;
            }
        }

        if(isset($this->contract->buyer->addressRegistration)) {
            $this->addressRegistration = $this->contract->buyer->addressRegistration;
            $this->addressRegistration->postal_area = $this->addressRegistration->postalArea ?? '';
            $this->addressRegistration->postal_region = $this->addressRegistration->postalRegion ?? '';
        }
        if(isset($this->contract->buyer->addressResidential)) {
            $this->addressResidential = $this->contract->buyer->addressResidential;
            $this->addressResidential->postal_area = $this->addressResidential->postalArea ?? '';
            $this->addressResidential->postal_region = $this->addressResidential->postalRegion ?? '';
        }
        if(isset($this->contract->buyer->addressWorkplace)) {
            $this->addressWorkplace = $this->contract->buyer->addressWorkplace;
            $this->addressWorkplace->postal_area = $this->addressWorkplace->postalArea ?? '';
            $this->addressWorkplace->postal_region = $this->addressWorkplace->postalRegion ?? '';
        }
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $this->prepareResponse();

        return [
            'buyer' => [
                'id' => $this->contract->buyer->id,
                'name' => $this->contract->buyer->name,
                'surname' => $this->contract->buyer->surname,
                'patronymic' => $this->contract->buyer->patronymic,
                'fio' => $this->contract->buyer->fio,
                'phone' => $this->contract->buyer->phone,
                'birth_date' => $this->contract->buyer->birth_date,
                'contract' => [
                    'id' => $this->contract->id,
                    'delay_days' => $this->contract->delayDays,
                    'balance' => number_format($this->contract->balance, '2', ',', ' '),
                    'balance_int' => (int) $this->contract->balance,
                    'balance_string' => num2str( $this->contract->balance, '', $this->contentLanguage === 'uz' ),
                    'expense' => number_format($this->contract->letters_expense_var, 2, ',', ' '),
                    'expense_string' => num2str($this->contract->letters_expense_var, '', $this->contentLanguage === 'uz' ),
                    'expense_and_notary_fee' => number_format($this->contract->letters_expense_var + ($this->notary_setting->fee ?? 0), 2, ',', ' '),
                    'expense_and_notary_fee_string' => num2str($this->contract->letters_expense_var + ($this->notary_setting->fee ?? 0), '', $this->contentLanguage === 'uz'),
                    'total' => $this->contract->total,
                    'period' => $this->contract->period,
                    'total_expense' => $this->contract->total_expense,
                    'total_expense_string' => $this->contract->total_expense_string,

//                    'total_max_amount'     => number_format($this->contract->total_max_amount    , 2, ',', ' '),
                    'total_max_autopay_post_cost' => number_format($this->contract->total_max_autopay_post_cost, 2, ',', ' '),
                    'total_max_percent_fix_max'   => number_format($this->contract->total_max_percent_fix_max  , 2, ',', ' '),
                    'payments_sum_balance' => number_format($this->contract->payments_sum_balance, 2, ',', ' '),
                    'autopay'              => number_format($this->contract->autopay             , 2, ',', ' '),
                    'payments_sum_autopay' => number_format($this->contract->payments_sum_autopay, 2, ',', ' '),
                    'post_cost'            => number_format($this->contract->post_cost           , 2, ',', ' '),
                    'percent'              => number_format($this->contract->percent             , 2, ',', ' '),
                    'fix_max'              => number_format($this->contract->fix_max             , 2, ',', ' '),

                    'expired_days'                => $this->contract->expired_days,
                    'real_expired_days_minus_one' => $this->contract->real_expired_days_minus_one,

                    'created_at' => $this->contract->created_at,
                    'confirmed_at' => Carbon::parse($this->contract->confirmed_at)->format('d.m.Y'),
                    'debts_amount' => number_format($this->contract->debts_amount, 2, ',', ' '),
                    'debts_amount_string' => num2str( $this->contract->delaySum, '', $this->contentLanguage === 'uz' ),
                    'general_company' => $this->contract->generalCompany,
                    'letters' => $this->lettersResponse,
                    'recovery' => [
                        'id' => $this->contract->recover->id ?? '',
                    ],
                    'notary_setting' => $this->notary_setting,
                    'expiration_date' => Carbon::parse($this->contract->created_at)->addMonths($this->contract->period)->format('d.m.Y'),
                    'first_payment_date' => Carbon::parse($this->contract->schedule->first()->real_payment_date)->format('d.m.Y'),
                    'last_payment_date' => Carbon::parse($this->contract->schedule->last()->real_payment_date)->format('d.m.Y'),
                    'autopay_debit_history_balance' => (int) ($this->contract->autopay_history->first()->balance
                        ?? null),
                    'autopay_debit_history_total' => (int) ($this->contract->autopay_history->first()->total
                        ?? null) ],
                'addresses' => [
                    'registration_address' => $this->addressRegistration ?? '',
                    'residential_address' => $this->addressResidential ?? '',
                    'workplace_address' => $this->addressWorkplace ?? '',
                ],
                'personals' => [
                    'birthday' => EncryptHelper::decryptData($this->contract->buyer->personals->birthday ?? ''),
                    'passport_number' => EncryptHelper::decryptData($this->contract->buyer->personals->passport_number ?? ''),
                    'passport_date_issue' => EncryptHelper::decryptData($this->contract->buyer->personals->passport_date_issue ?? ''),
                    'pinfl' => EncryptHelper::decryptData($this->contract->buyer->personals->pinfl ?? ''),
                    'passport_issued_by' => EncryptHelper::decryptData($this->contract->buyer->personals->passport_issued_by ?? ''),
                    'passport_first_page' => [
                        'path' => isset($this->contract->buyer->personals->passport_first_page->path) ? $this->sftpFileServerDomain . $this->contract->buyer->personals->passport_first_page->path : '',
                    ],
                    'passport_second_page' => [
                        'path' => isset($this->contract->buyer->personals->passport_second_page->path) ? $this->sftpFileServerDomain . $this->contract->buyer->personals->passport_second_page->path : '',
                    ],
                    'passport_with_address' => [
                        'path' => isset($this->contract->buyer->personals->passport_with_address->path) ? $this->sftpFileServerDomain . $this->contract->buyer->personals->passport_with_address->path : '',
                    ],
                ],
            ],
            'court_regions' => $this->court_regions,
            'callcenter_number' => callCenterNumber(3),
            'current_date_string' => $this->contentLanguage === 'uz' ? uzbekDateNow(Carbon::now()) : russianDateNow(Carbon::now()),
        ];
    }
}
