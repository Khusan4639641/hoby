<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBlockReasonFieldToCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('companies', 'block_reason')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('block_reason')->nullable()->comment('Причина блокировки');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if(Schema::hasColumn('companies', 'block_reason')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('block_reason');
            });
        }
    }
}
