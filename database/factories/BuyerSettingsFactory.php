<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use Faker\Generator as Faker;
use App\Models\BuyerSetting;

$factory->define(BuyerSetting::class, function (Faker $faker) {
    return [
        'personal_account' => 0,
    ];
});
