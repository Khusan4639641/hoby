<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('collectors', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->decimal('balance', 16, 2)
                ->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Laravel foreign error: Id must be unsigned bigInt(20)
            // TODO: Change users.id from int(11) to bigInt(20)
            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('collectors');
    }
}
