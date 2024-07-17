<?php

namespace App\Services\API\V3\Interfaces;

use App\Models\Card;
use Illuminate\Http\Request;

interface CardInterface
{
    public static function balance(Request $request, Card $card, $flag = false): array;
}
