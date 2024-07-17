<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ScoringHelper
{
    public static function getUniversal(string $card_number, string $expiry_date)
    {
        $card_number = str_replace(' ', '', $card_number);
        $month = env('SCORING_MAX_MONTHS', 6);
        $id = 'test_' . uniqid(rand(), 1);
        return json_encode([
            'jsonrpc' => '2.0',
            'id'      => $id,
            'method'  => 'card.scoring',
            'params'  => [
                'card_number' => $card_number,
                'expire'      => $expiry_date,
                'start_date'  => Carbon::now()->subMonth($month)->format('Ym01'),
                'end_date'    => Carbon::now()->format('Ym25'),
            ],
        ]);
    }

    public static function gettest(string $card_number, string $expiry_date)
    {
        $card_number = str_replace(' ', '', $card_number);
        $month = env('SCORING_MAX_MONTHS', 6);
        return json_encode([
            'jsonrpc' => '2.0',
            'id'      => 'api_card_scoring_' . time() . uniqid(rand(), 10),
            'method'  => 'card.scoring',
            'params'  => [
                'card_number' => $card_number,
                'expiry'      => $expiry_date,
                'start_date'  => Carbon::now()->format('Ym25'),
                'end_date'    => Carbon::now()->subMonth($month)->format('Ym01'),
            ],
        ]);
    }

}
