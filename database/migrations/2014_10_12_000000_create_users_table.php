<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('scim_id');
            $table->string('external_id');
            $table->string('tenant_id');
            $table->string('user_name')->nullable();
            $table->string('display_name')->nullable();
            $table->string('family_name')->nullable();
            $table->string('given_name')->nullable();
            $table->integer('group_id')->unsigned()->nullable();
            $table->string('email');
            $table->string('password');
            $table->rememberToken();
            $table->string('active')->nullable();
            $table->string('exist_externaldb')->nullable();
            $table->timestamps();
            
            $table->index('group_id');
            $table->foreign('group_id')->references('id')->on('groups');
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
