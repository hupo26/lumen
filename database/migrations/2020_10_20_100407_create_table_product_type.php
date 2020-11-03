<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
class CreateTableProductType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_type', function (Blueprint $table) {
            $table->increments('id')->comment('类型编号');
            $table->char('content',20)->comment('产品类型');
            $table->tinyInteger('status')->comment('状态');
            $table->integer('product_type')->comment('产品类型');
            $table->integer('sort')->comment('排序');
            $table->timestamp('create_time')->comment('上传时间');
        });
        DB::statement("ALTER TABLE `product_type` comment '产品类型表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_type');
    }
}
