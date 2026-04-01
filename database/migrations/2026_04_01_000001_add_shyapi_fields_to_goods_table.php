<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShyapiFieldsToGoodsTable extends Migration
{
    public function up()
    {
        Schema::table('goods', function (Blueprint $table) {
            $table->tinyInteger('delivery_source')->default(0)->comment('发货来源 0本地卡密 1ShyAPI兑换码')->after('type');
            $table->string('shyapi_name_prefix', 64)->default('')->comment('ShyAPI名称前缀筛选')->after('delivery_source');
            $table->integer('shyapi_quota')->default(0)->comment('ShyAPI固定额度筛选')->after('shyapi_name_prefix');
            $table->string('shyapi_assigned_to', 64)->default('shop')->comment('ShyAPI分配渠道')->after('shyapi_quota');
        });
    }

    public function down()
    {
        Schema::table('goods', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_source',
                'shyapi_name_prefix',
                'shyapi_quota',
                'shyapi_assigned_to',
            ]);
        });
    }
}
