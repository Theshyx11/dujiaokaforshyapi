<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartnerWalletTransactionsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('partner_wallet_transactions')) {
            return;
        }

        Schema::create('partner_wallet_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('partner_id')->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedBigInteger('source_partner_id')->nullable()->index();
            $table->unsignedBigInteger('goods_id')->nullable()->index();
            $table->unsignedBigInteger('redemption_id')->nullable()->index();
            $table->string('type', 32)->index();
            $table->unsignedTinyInteger('level')->nullable();
            $table->decimal('rate', 5, 2)->default(0);
            $table->decimal('amount', 10, 2)->default(0);
            $table->unsignedTinyInteger('status')->default(1)->index();
            $table->timestamp('available_at')->nullable()->index();
            $table->string('description', 255)->default('');
            $table->timestamps();

            $table->unique(['partner_id', 'order_id', 'type', 'level'], 'partner_wallet_order_level_unique');
        });
    }

    public function down()
    {
        if (Schema::hasTable('partner_wallet_transactions')) {
            Schema::dropIfExists('partner_wallet_transactions');
        }
    }
}
