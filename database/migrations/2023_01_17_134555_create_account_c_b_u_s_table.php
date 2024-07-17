<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use \App\Models\AccountCBU;

class CreateAccountCBUSTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounts_cbu', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default(AccountCBU::STATUS_OPEN)->comment('0 – закрыт, 1 - открыт');
            $table->integer('user_id')->index()->nullable();
            $table->integer('contract_id')->index()->unsigned()->nullable();
            $table->string('name',255)->nullable();
            $table->string('number',20);
            $table->decimal('balance',16,2)->default(0);
            $table->string('mask',5);
            $table->string('currency',3);
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('accounts_cbu', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('contract_id')->references('id')->on('contracts');
        });

        $this->insertMainAccounts();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounts_cbu');
    }

    private function insertMainAccounts()
    {
        $data = [
            [
                'name' => 'Main account',
                'number' => '10513000905570410001',
                'mask' => '10513',
                'currency' => 'UZS',
            ],
            [
                'name' => 'Main account 2',
                'number' => '10509000605570410001',
                'mask' => '10509',
                'currency' => 'UZS',
            ],
            [
                'name' => 'Main account 3',
                'number' => '56802000505570410001',
                'mask' => '56802',
                'currency' => 'UZS',
            ],
        ];
        foreach ($data as $item) {
            AccountCBU::query()->create($item);
        }
    }
}
