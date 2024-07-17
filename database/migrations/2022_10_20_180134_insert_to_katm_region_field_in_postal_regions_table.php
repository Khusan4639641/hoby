<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\PostalRegion;

class InsertToKatmRegionFieldInPostalRegionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        PostalRegion::where('external_id', 1)->update(['katm_region' => 26]);
        PostalRegion::where('external_id', 2)->update(['katm_region' => 27]);
        PostalRegion::where('external_id', 3)->update(['katm_region' => 18]);
        PostalRegion::where('external_id', 4)->update(['katm_region' => 8]);
        PostalRegion::where('external_id', 5)->update(['katm_region' => 24]);
        PostalRegion::where('external_id', 6)->update(['katm_region' => 30]);
        PostalRegion::where('external_id', 7)->update(['katm_region' => 3]);
        PostalRegion::where('external_id', 8)->update(['katm_region' => 14]);
        PostalRegion::where('external_id', 9)->update(['katm_region' => 10]);
        PostalRegion::where('external_id', 10)->update(['katm_region' => 22]);
        PostalRegion::where('external_id', 11)->update(['katm_region' => 6]);
        PostalRegion::where('external_id', 12)->update(['katm_region' => 12]);
        PostalRegion::where('external_id', 13)->update(['katm_region' => 35]);
        PostalRegion::where('external_id', 14)->update(['katm_region' => 33]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
