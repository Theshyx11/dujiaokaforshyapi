<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPartnerFieldsToOrdersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'partner_id')) {
                $table->unsignedBigInteger('partner_id')->nullable()->index()->after('pay_id');
            }
            if (!Schema::hasColumn('orders', 'partner_referral_code')) {
                $table->string('partner_referral_code', 20)->nullable()->after('partner_id');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('orders', 'partner_id') ? 'partner_id' : null,
                Schema::hasColumn('orders', 'partner_referral_code') ? 'partner_referral_code' : null,
            ]));

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
}
