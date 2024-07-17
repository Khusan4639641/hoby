<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Order;
use Faker\Generator as Faker;

$factory->define(Order::class, function (Faker $faker) {
    return [
        'user_id' => 0,
        'company_id' => 0,
        'partner_id' => 0,
        'total' => 0,
        'partner_total' => 0,
        'credit' => 0,
        'debit' => 0,
        'status' => 1,
    ];
});
