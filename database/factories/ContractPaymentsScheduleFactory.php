<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\ContractPaymentsSchedule;
use Faker\Generator as Faker;

$factory->define(ContractPaymentsSchedule::class, function (Faker $faker) {
    return [
        'price' => 0,
        'total' => 0,
        'balance' => 0,
        'payment_date' => now(),
        'status' => 0,
    ];
});
