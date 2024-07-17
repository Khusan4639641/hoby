<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InsertSettingsTableAddKeyCloakServiceTokenAndRefreshToken extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction( static function () {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('value', 1500)->nullable()->change();
            });
        }, 1);

        DB::transaction( static function () {
            Setting::updateOrCreate(
                [ "param" => "keycloak_service_refresh_token" ], // "expires_in": 86313600 (KeyCloak Documentation/Response)
                [ "value" => "" ]
            );
        }, 1);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::transaction( static function () {
            Setting::where('param', "keycloak_service_refresh_token")->delete();
        }, 1);

        DB::transaction( static function () {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('value', 1024)->nullable()->change();
            });
        }, 1);
    }
}
