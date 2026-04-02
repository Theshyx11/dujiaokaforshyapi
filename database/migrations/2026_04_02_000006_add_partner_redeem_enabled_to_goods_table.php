<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPartnerRedeemEnabledToGoodsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('goods') || Schema::hasColumn('goods', 'partner_redeem_enabled')) {
            return;
        }

        Schema::table('goods', function (Blueprint $table) {
            $table->tinyInteger('partner_redeem_enabled')
                ->default(0)
                ->comment('是否允许合伙人佣金兑换')
                ->after('shyapi_assigned_to');
        });

        DB::table('goods')
            ->where('type', 1)
            ->where('delivery_source', 1)
            ->update(['partner_redeem_enabled' => 1]);
    }

    public function down()
    {
        if (!Schema::hasTable('goods') || !Schema::hasColumn('goods', 'partner_redeem_enabled')) {
            return;
        }

        Schema::table('goods', function (Blueprint $table) {
            $table->dropColumn('partner_redeem_enabled');
        });
    }
}
