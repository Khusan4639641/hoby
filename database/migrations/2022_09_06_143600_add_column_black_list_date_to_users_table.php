<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnBlackListDateToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('users')) {
            if ( !Schema::hasColumn('users', 'black_list_date') ) {
                Schema::table('users', function (Blueprint $table) {
                    $table->timestamp('black_list_date')->nullable();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('users')) {
            if ( Schema::hasColumn('users', 'black_list_date') ) {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropColumn('black_list_date');
                });
            }
        }
    }
}
