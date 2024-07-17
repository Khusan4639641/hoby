<?php

use App\Models\CourtRegion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertIntoCourtRegionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        CourtRegion::create([
            'name' => 'Шайхонтоҳур',
            'is_visible' => 1,
        ]);
        CourtRegion::create([
            'name' => 'Миробод',
            'is_visible' => 1,
        ]);
        CourtRegion::create([
            'name' => 'Мирзо-Улуғбек',
            'is_visible' => 1,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        CourtRegion::where([
            'name' => 'Шайхонтоҳур',
            'is_visible' => 1,
        ])
            ->delete()
        ;

        CourtRegion::where([
            'name' => 'Миробод',
            'is_visible' => 1,
        ])
            ->delete()
        ;

        CourtRegion::where([
            'name' => 'Мирзо-Улуғбек',
            'is_visible' => 1,
        ])
            ->delete()
        ;
    }
}
