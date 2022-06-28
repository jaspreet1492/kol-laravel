<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAddColumnInKolProfilesToKolProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kol_profiles', function (Blueprint $table) {
            $table->string('social_active')->after('banner')->nullable();
            $table->string('languages')->after('user_id')->nullable();
            $table->longText('bio')->after('languages');
            $table->text('avatar')->after('bio');
            $table->string('personal_email')->after('avatar')->nullable();
            $table->integer('kol_type')->after('personal_email');
            $table->string('achievement')->after('kol_type');
            $table->string('city')->after('zip_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kol_profiles', function (Blueprint $table) {
            //
        });
    }
}
