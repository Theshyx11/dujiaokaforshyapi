<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartnersTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('partners')) {
            return;
        }

        Schema::create('partners', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 80);
            $table->string('email', 120)->unique();
            $table->string('password', 255);
            $table->string('referral_code', 20)->unique();
            $table->unsignedBigInteger('inviter_id')->nullable()->index();
            $table->tinyInteger('status')->default(1)->index();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        if (Schema::hasTable('partners')) {
            Schema::dropIfExists('partners');
        }
    }
}
