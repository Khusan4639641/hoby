<?php

namespace App\Rules;

use App\Classes\CURL\test\CardRequest;
use App\Models\Buyer;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;

class CheckCardByPhone implements Rule
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

        $buyer = Buyer::find(request()->get('buyer_id') ?: Auth::id());

        $cardNumber = str_replace(' ', '', request()->get('card_number'));
        $expire = str_replace('/', '', request()->get('card_valid_date'));
        $expire = Str::substr($expire, 2, 2) . Str::substr($expire, 0, 2);

        if ($cardNumber === null) {
            $this->errorMessage = __('message.parameter_not_found', ['param' => 'card_number']);
            return false;
        }

        if ($expire === null) {
            $this->errorMessage = __('message.parameter_not_found', ['param' => 'card_valid_date']);
            return false;
        }

        $cardRegisterRequest = new CardRequest($cardNumber, $expire);
        $cardRegisterRequest->execute();

        if (!$cardRegisterRequest->isSuccessful()) {
            $this->errorMessage = __('message.service_temporarily_unavailable');
            return false;
        }

        $phone = null;
        try {
            $phone = $cardRegisterRequest->response()->phone();
        } catch (\Exception $e) {
            if ($phone === null) {
                $this->errorMessage = __('message.card_sms_notification_disabled');
                return false;
            }
            $this->errorMessage = __('message.response_is_not_satisfactory');
            return false;
        }
        if ($phone !== str_replace('+', '', $buyer->phone)) {
            $this->errorMessage = __('message.card_linked_to_another_phone');
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
