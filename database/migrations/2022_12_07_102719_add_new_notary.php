<?php

use App\Models\NotarySetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewNotary extends Migration
{
    public function up()
    {
        NotarySetting::create(['name'            => 'Судебный',
                               'surname'         => 'Приказ',
                               'patronymic'      => '',
                               'address'         => '',
                               'region'          => '',
                               'fee'             => 15000,
                               'template_number' => 'fourth',
                               'tax'             => '0',
                              ]);
    }

    public function down()
    {
        NotarySetting::where('name', 'Судебный')
            ->where('surname', 'Приказ')->delete();
    }
}
