<?php

namespace App\Http\Resources\Core\EmployeeBuyerController;

use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use Illuminate\Support\Facades\Log;
use App\Helpers\EncryptHelper;
use App\Libs\KycHistoryLibs;


class BuyerJsonResource extends JsonResource
{
    /**
     * Indicates if the resource's collection keys should be preserved.
     *
     * @var bool
     */
    public $preserveKeys = true;

    /**
     * The "data" wrapper that should be applied.
     *
     * @var string
     */
    public static $wrap = 'data';

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request) : array
    {
        // 1    TODO: Перенести в константы или ENUM'ы
        $icon = "/images/icons/icon_ok_circle_green.svg";    // "class" => "icon-status"
        if ( $this->status !== 4 ) {
            $icon = "/images/icons/icon_attention.svg";
        }

        // 2    TODO: Перенести в константы или ENUM'ы
        $statuses = [
            0 => 'Новый',
            1 => 'Новый',
            2 => 'Ожидание',
            3 => 'Отказ',
            4 => 'Верифицирован',
            5 => 'Паспорт',
            6 => 'Паспорт',
            7 => 'Паспорт',
            8 => 'Блокирован',
            9 => 'Блокирован',
            10 => 'Селфи',
            11 => 'Прописка',
            12 => 'Доверитель',
        ];
        $status = null;
        if ( !isset( $statuses[ $this->status ] ) ) {
            Log::channel("users")
                ->info("UserID: " . $this->id . " - status not found: " . $this->status)
            ;
        } else {
            $status = $statuses[ $this->status ];
        }

        // 3    TODO: Перенести в константы или ENUM'ы
        $reason = null;    // "class" => "passport", если есть
        $history = $this->whenLoaded("history");
        if (
            $history && $history->reason
        ) {
            $reason = KycHistoryLibs::getKycReason( $history->reason ); // получить причину
        }

        // 4
        $kyc_user = null;    // "class" => "kyc-user"
        if ($kyc = $this->whenLoaded("kyc")) {
            $kyc_user = $kyc->fi; // Имя и Фамилия
        }

        // 8
        $passport_number = null;    // "class" => "passport"
        if (
            ( $personals = $this->whenLoaded("personals") ) &&
            $personals->passport_number
        ) {
            $passport_number = EncryptHelper::decryptData($personals->passport_number);
        }

        // 9    TODO: Перенести в константы или ENUM'ы
        $gender = "-";    // "class" => "gender"
        if ( $this->gender === 2 ) {
            $gender = "Ж";
        }
        elseif ( $this->gender === 1 ) {
            $gender = "М";
        }

        // 12
        $limit = $this->whenLoaded("settings")->limit ?? 0;

        // 13 // Debt calculation
        $total_debt = 0;    // "class" => "red", если > 0

        $active_statuses = [
            Contract::STATUS_ACTIVE,            // contract->status = 1
            Contract::STATUS_OVERDUE_60_DAYS,   // contract->status = 3
            Contract::STATUS_OVERDUE_30_DAYS,   // contract->status = 4
        ];

        if ($this->whenLoaded("contracts")) {
            foreach ($this->whenLoaded("contracts") as $contract) {
                if ( in_array( (int) $contract->status, $active_statuses, true) ) {
                    // contract_payments_schedule + collect_cost + autopay_history
                    $total_debt += round( ( (float) $contract->debt_sum ), 2);
                }
            }
        }
        $total_debt = round($total_debt, 2);

        // 15
        $status_caption = __('user.status_' . $this->status);

        return [
            'icon'            => $icon,                         //  1;    // "class" => "icon-status"
            'status'          => $status,                       //  2;
            'reason'          => $reason,                       //  3;    // "class" => "passport"
            'kyc_user'        => $kyc_user,                     //  4;    // "class" => "kyc-user"
            'updated_data'    => $this->updated_data ?? null,   //  5;    // "class" => "updated"
            'id'              => $this->id ?? null,             //  6;    // "class" => "id"
            'fio'             => $this->fio ?? null,            //  7;    // "class" => "fio"
            'passport_number' => $passport_number,              //  8;    // "class" => "passport"
            'gender'          => $gender,                       //  9;    // "class" => "gender"
            'birth_date'      => $this->birth_date ?? null,     // 10;    // "class" => "birth_date"
            'phone'           => $this->phone ?? null,          // 11;    // "class" => "phone"
            'limit'           => $limit,                        // 12;
            'totalDebt'       => number_format($total_debt, 2, '.', ' '), // 13;    // "class" => "debt red", "red", если $total_debt > 0
            'black_list'      => $this->black_list,             // 14;
            'status_caption'  => $status_caption,               // 15;
        ];
    }

}
