<?php

namespace App\Classes\Exceptions;

use Illuminate\Validation\ValidationException;

class MLValidationException extends ValidationException
{

    public function __construct($message, $validator)
    {
        parent::__construct($validator, null, $message);
    }

}
