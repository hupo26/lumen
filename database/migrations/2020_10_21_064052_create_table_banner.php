<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
class CreateTableBanner extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('banner', function (Blueprint $table) {
            $table->increments('id')->comment('编号');
            $table->string('banner_img',255)->comment('轮播图');
            $table->tinyInteger('status')->comment('状态，0：隐藏，1：显示');
            $table->timestamp('create_time')->comment('上传时间');
        });
        DB::statement("ALTER TABLE `banner` comment '轮播图表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('banner');
    }
}
