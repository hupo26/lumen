<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
class CreateTableUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user', function (Blueprint $table) {
            $table->increments('id');
            $table->char('uid',28)->comment('openid');;
            $table->char('nickname',20)->comment('昵称');
            $table->string('avatar',255)->comment('头像');
            $table->string('gender',255)->comment('性别');
            $table->string('birthday',255)->comment('生日');
            $table->string('mobile',15)->comment('手机号');
            $table->string('city',20)->comment('城市');
            $table->string('province',20)->comment('省份');
            $table->string('country',20)->comment('国家');
            $table->string('amount',20)->comment('提现金额');
            $table->rememberToken()->comment('TOKEN');
            $table->timestamp('create_time')->comment('创建时间');
        });
        DB::statement("ALTER TABLE `user` comment '用户表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user');
    }
}
