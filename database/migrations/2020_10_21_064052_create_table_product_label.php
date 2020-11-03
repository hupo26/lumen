<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
class CreateTableProductLabel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_label', function (Blueprint $table) {
            $table->increments('id')->comment('标签编号');
            $table->char('content',20)->comment('标签内容');
            $table->string('type')->comment('发布用户类型，1：平台，2：用户');
            $table->tinyInteger('status')->comment('状态，0：隐藏，1：显示');
            $table->timestamp('create_time')->comment('上传时间');
        });
        DB::statement("ALTER TABLE `product_label` comment '产品标签表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_label');
    }
}
