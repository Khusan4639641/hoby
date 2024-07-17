<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Contract;
use Faker\Generator as Faker;

$factory->define(Contract::class, function (Faker $faker) {
    return [
        'user_id' => 0,
        'company_id' => 0,
        'partner_id' => 0,
        'order_id' => 0,
        'total' => 0,
        'balance' => 0,
        'period' => 0,
        'status' => 1,
        'expired_days' => 0,
    ];
});
