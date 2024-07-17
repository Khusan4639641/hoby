<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectionDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('collection_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('contract_id');
            $table->integer('user_id');
            $table->string('type');
            $table->string('file_link', 400);
            $table->timestamps();


            // Laravel foreign error: `contracts`.`id` is now int(10), it must be unsigned bigInt(20)
            // NO laravel foreign error if: $table->unsignedInteger('contract_id');
            // TODO: Change `contracts`.`id` from int(10) to bigInt(20)
            $table->foreign('contract_id')->references('id')->on('contracts');

            // Laravel foreign error: `users`.`id` is now int(11), it must be unsigned bigInt(20)
            // NO laravel foreign error if: $table->integer('user_id');
            // TODO: Change `users`.`id` from int(11) to bigInt(20)
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('collection_documents', function (Blueprint $table) {
            $table->dropForeign(['contract_id']);
            $table->dropForeign(['user_id']);
        });
        Schema::dropIfExists('collection_documents');
    }
}
