<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NormalizeZpayPaymentNames extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('pays')) {
            return;
        }

        DB::table('pays')
            ->where('pay_check', 'alipay')
            ->update(['pay_name' => 'ZPAY 支付宝']);

        DB::table('pays')
            ->where('pay_check', 'wxpay')
            ->update(['pay_name' => 'ZPAY 微信']);
    }

    public function down()
    {
        if (!Schema::hasTable('pays')) {
            return;
        }

        DB::table('pays')
            ->where('pay_check', 'alipay')
            ->update(['pay_name' => '支付宝']);

        DB::table('pays')
            ->where('pay_check', 'wxpay')
            ->update(['pay_name' => '微信']);
    }
}
