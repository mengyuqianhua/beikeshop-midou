<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 商品增加第三方平台下单链接（淘宝 / 抖音）
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'taobao_url')) {
                $table->string('taobao_url', 500)->default('')->after('video')->comment('淘宝商品链接');
            }
            if (! Schema::hasColumn('products', 'douyin_url')) {
                $table->string('douyin_url', 500)->default('')->after('taobao_url')->comment('抖音商品链接');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'taobao_url')) {
                $table->dropColumn('taobao_url');
            }
            if (Schema::hasColumn('products', 'douyin_url')) {
                $table->dropColumn('douyin_url');
            }
        });
    }
};
