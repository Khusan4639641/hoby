<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOtpEnterCodeAttemptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('otp_enter_code_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('phone',12)->index()->comment('in case if user not found we storage his phone number');
            $table->integer('user_id')->unique()->comment('relation with user table');
            $table->integer('attempts')->default(0)->comment('client attempts for checking his attempts');
            $table->string('code',4)->nullable(false)->comment('sms sent codes for checking client attempts, (not actual now, but may be required in future)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
       Schema::dropIfExists('otp_enter_code_attempts');
    }
}
