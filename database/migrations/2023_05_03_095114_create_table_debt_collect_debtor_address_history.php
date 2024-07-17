<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\DB;

use \App\Models\Role;

class CreateTableDebtCollectDebtorAddressHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('debt_collect_debtor_address_history', static function (Blueprint $table) {
            $table->id();
            $table->integer('debtor_id')->comment('Должник.');
            $table->unsignedBigInteger('past_address')->comment("Прошлый адрес должника.\n(districts.cbu_id\n= regions.id\n= katm_regions.local_region)");
            $table->unsignedBigInteger('new_address')->comment("Новый адрес должника.\n(districts.cbu_id\n= regions.id\n= katm_regions.local_region)");
            $table->integer('changer_id')->comment('Тот кто изменил адрес.');
            $table->string('comment', 1024)->comment('Комментарий/Причина (Обязательное поле).');
            $table->string('file_path')->nullable()->comment('Фотография подтверждающее новый адрес');
            $table->timestamps();

            // Laravel foreign error: Id must be unsigned bigInt(20)
            // TODO: Change users.id from int(11) to bigInt(20)
            $table->foreign('debtor_id')->references('id')->on('users');
            $table->foreign('changer_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('debt_collect_debtor_address_history');
    }
}
