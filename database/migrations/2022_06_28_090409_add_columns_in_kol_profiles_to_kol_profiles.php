<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsInKolProfilesToKolProfiles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kol_profiles', function (Blueprint $table) {
            $table->longText('video_links')->after('social_active');
            $table->text('tags')->after('video_links');
            $table->text('status')->after('tags');
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
