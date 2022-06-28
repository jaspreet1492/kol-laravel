<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
            Schema::create('users', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('last_name')->nullable();
                $table->string('email')->unique();
                $table->text('password')->nullable();
                $table->integer('role_id')->default(3);
                $table->longText('firebase_token')->nullable();
                $table->integer('is_varified')->default(0);
                $table->string('otp')->nullable();
                $table->text('avatar')->nullable();
                $table->enum('gender',['male','female','other'])->nullable();
                $table->string('phone')->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password_reset_code')->nullable();
                $table->integer('status')->default(1);
                $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
