<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 新增商品专属的小红书链接字段，并将历史存放在 video 字段中的小红书链接合并到该字段
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('products', 'xhs_url')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('xhs_url', 500)->default('')->after('video')->comment('小红书链接');
            });
        }

        // 历史数据中 video 字段被用于存放小红书链接，此处统一合并到 xhs_url，保证链接唯一
        DB::table('products')
            ->where(function ($query) {
                $query->where('video', 'like', '%xiaohongshu.com%')
                    ->orWhere('video', 'like', '%xhslink.com%');
            })
            ->where(function ($query) {
                $query->whereNull('xhs_url')->orWhere('xhs_url', '');
            })
            ->update(['xhs_url' => DB::raw('video')]);

        DB::table('products')
            ->where('xhs_url', '<>', '')
            ->whereColumn('video', 'xhs_url')
            ->update(['video' => '']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('products', 'xhs_url')) {
            DB::table('products')
                ->where('xhs_url', '<>', '')
                ->where(function ($query) {
                    $query->whereNull('video')->orWhere('video', '');
                })
                ->update(['video' => DB::raw('xhs_url')]);

            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('xhs_url');
            });
        }
    }
};
