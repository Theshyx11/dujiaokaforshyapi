<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShyapiFieldsToGoodsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('goods')) {
            return;
        }

        Schema::table('goods', function (Blueprint $table) {
            if (!Schema::hasColumn('goods', 'delivery_source')) {
                $table->tinyInteger('delivery_source')->default(0)->comment('发货来源 0本地卡密 1ShyAPI兑换码')->after('type');
            }
            if (!Schema::hasColumn('goods', 'shyapi_name_prefix')) {
                $table->string('shyapi_name_prefix', 64)->default('')->comment('ShyAPI名称前缀筛选')->after('delivery_source');
            }
            if (!Schema::hasColumn('goods', 'shyapi_quota')) {
                $table->integer('shyapi_quota')->default(0)->comment('ShyAPI固定额度筛选')->after('shyapi_name_prefix');
            }
            if (!Schema::hasColumn('goods', 'shyapi_assigned_to')) {
                $table->string('shyapi_assigned_to', 64)->default('shop')->comment('ShyAPI分配渠道')->after('shyapi_quota');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('goods')) {
            return;
        }

        Schema::table('goods', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('goods', 'delivery_source') ? 'delivery_source' : null,
                Schema::hasColumn('goods', 'shyapi_name_prefix') ? 'shyapi_name_prefix' : null,
                Schema::hasColumn('goods', 'shyapi_quota') ? 'shyapi_quota' : null,
                Schema::hasColumn('goods', 'shyapi_assigned_to') ? 'shyapi_assigned_to' : null,
            ]));

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
}
