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
            $table->string('tenant_id'); // テナントID
            $table->string('user_name')->nullable(); // AADのユーザーネーム
            $table->string('display_name')->nullable(); // AADのユーザー表示名
            $table->string('family_name')->nullable(); // AADのユーザー名字
            $table->string('given_name')->nullable(); // AADのユーザー名前
            $table->integer('group_id')->unsigned()->nullable(); // GroupID
            $table->string('email');
            $table->string('password'); // 使用していないがLaravel上必要
            $table->rememberToken();
            $table->string('active')->nullable(); // AADのactiveステータス
            $table->string('exist_externaldb')->nullable(); // 貴社側DBに存在しない場合「false」
            $table->timestamps();
            $table->softDeletes();
            
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
