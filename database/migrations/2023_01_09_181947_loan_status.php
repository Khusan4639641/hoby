<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\LoanStatus as Status;

class LoanStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('loan_status')) {
            Schema::create('loan_status', function (Blueprint $table) {
                $table->id();
                $table->integer('type')->comment("(1-активный, 2 досрочно прекращенный)");
                $table->timestamps();
            });
            Status::create(['type'=>"1"]);
            Status::create(['type'=>"2"]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('loan_status')) {
            Schema::dropIfExists('loan_status');
        }
    }
}
