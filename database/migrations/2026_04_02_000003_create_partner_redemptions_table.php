<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartnerRedemptionsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('partner_redemptions')) {
            return;
        }

        Schema::create('partner_redemptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('redemption_no', 32)->unique();
            $table->unsignedBigInteger('partner_id')->index();
            $table->unsignedBigInteger('goods_id')->index();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->unsignedTinyInteger('status')->default(1)->index();
            $table->longText('codes')->nullable();
            $table->string('error_message', 255)->default('');
            $table->timestamps();
        });
    }

    public function down()
    {
        if (Schema::hasTable('partner_redemptions')) {
            Schema::dropIfExists('partner_redemptions');
        }
    }
}
