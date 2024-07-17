<?php

namespace App\Rules;

use App\Classes\CURL\test\CardRequest;
use App\Models\Buyer;
use App\Services\testCardService;
use Illuminate\Contracts\Validation\Rule;

class CheckCardByService implements Rule
{

    private string $errorMessage = '';

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $buyer = Buyer::find($value);

        $card = $buyer->cardsActive->first();

        if (!$card) {
            $this->errorMessage = 'Нет активной карты';
            return false;
        }

        $cardNumber = $card->numberDecoded;
        $expire = $card->expireDecoded;

        $cardRegisterRequest = new testCardService();
        $getPhone = $cardRegisterRequest->getCardInfo($card->token_payment);
        $getBalance = $cardRegisterRequest->getCardBalance($card->token_payment);

        if (isset($getPhone['status']) and $getPhone['status'] === 'error'){
            $this->errorMessage = $getPhone['message'];
            return false;
        }

        if (isset($getBalance['status']) and $getBalance['status'] === 'error'){
            $this->errorMessage = $getBalance['message'];
            return false;
        }

        $phone = $getPhone['card']['phoneNumber'];
        $balance = $getBalance['balance'];

        if ($phone == null) {
            $this->errorMessage = __('У карты отключено СМС информирование');
            return false;
        }
//        if (!$cardRegisterRequest->isSuccessful()) {
//            $this->errorMessage = __('Сервис проверки платёжных карт временно не доступен');
//            return false;
//        }

        if ($phone != $buyer->phone) {
            $this->errorMessage = __('Карта привязана к другому номеру телефона');
            return false;
        }

        if ($balance < 1) {
            $this->errorMessage = __('На карте не достаточно средств. Минимальный порог 1 сум');
            return false;
        }
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->errorMessage;
    }
}
