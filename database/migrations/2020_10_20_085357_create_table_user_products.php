<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateTableUserProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_products', function (Blueprint $table) {
            $table->increments('id')->comment('产品编号');
            $table->integer('uid')->comment('用户id');
            $table->char('title',20)->comment('产品标题');
            $table->integer('product_type')->comment('产品类型');
            $table->text('product_content')->comment('产品内容');
            $table->text('product_img_vedio')->comment('产品视频或图片');
            $table->text('product_amount')->comment('产品购买数量');
            $table->Decimal('product_money')->comment('产品购买金额');
            $table->Decimal('product_allmoney')->comment('产品版权金额');
            $table->integer('zan')->comment('点赞数');
            $table->string('product_label',255)->comment('标签');
            $table->tinyInteger('uptype')->comment('上传类型');
            $table->tinyInteger('status')->comment('状态');
            $table->timestamp('create_time')->comment('上传时间');
        });
        DB::statement("ALTER TABLE `user_products` comment '用户产品表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_products');
    }
}
