<?php

namespace App\Rules;

use App\Models\Buyer;
use App\Models\Card;
use App\Models\User;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class HashedCardNumberCheck implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // Убираем пробелы
        $value = str_replace(' ', '', $value);
        if ($card = Card::where([['guid', '=', md5($value)], ['user_id', '=', request('buyer_id') ?? Auth::user()->id]])->first()){
            // Если такая карта уже есть, то выводим ошибку
            // buyer or vendor
            $user = Auth::user();
            // К кому привязана карта
            $cardHolder = User::find($card->user_id);
            if (isset($user) && $user->company_id != null && isset($cardHolder)) $this->error = __('cabinet/profile.card_already_linked_to_phone') . ' ' . $cardHolder->phone;
            else if (isset($user) && $user->company_id == null) $this->error = __('cabinet/profile.card_already_added');
            else $this->error = __('card exists but user_id is not set');

            return false;
        }else
            return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->error;
    }

}
