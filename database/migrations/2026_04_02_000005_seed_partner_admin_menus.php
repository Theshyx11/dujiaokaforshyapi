<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedPartnerAdminMenus extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('admin_menu')) {
            return;
        }

        $now = now();
        $parent = DB::table('admin_menu')->where('uri', '/partners')->orWhere('title', 'Partner_Manage')->first();
        if ($parent) {
            return;
        }

        $maxId = (int) DB::table('admin_menu')->max('id');
        $rootId = $maxId + 1;

        DB::table('admin_menu')->insert([
            [
                'id' => $rootId,
                'parent_id' => 0,
                'order' => 23,
                'title' => 'Partner_Manage',
                'icon' => 'fa-users',
                'uri' => null,
                'extension' => '',
                'show' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $rootId + 1,
                'parent_id' => $rootId,
                'order' => 24,
                'title' => 'Partner',
                'icon' => 'fa-user-circle-o',
                'uri' => '/partners',
                'extension' => '',
                'show' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $rootId + 2,
                'parent_id' => $rootId,
                'order' => 25,
                'title' => 'Partner_Transactions',
                'icon' => 'fa-line-chart',
                'uri' => '/partner-transactions',
                'extension' => '',
                'show' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $rootId + 3,
                'parent_id' => $rootId,
                'order' => 26,
                'title' => 'Partner_Redemptions',
                'icon' => 'fa-ticket',
                'uri' => '/partner-redemptions',
                'extension' => '',
                'show' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down()
    {
        if (!Schema::hasTable('admin_menu')) {
            return;
        }

        DB::table('admin_menu')
            ->whereIn('title', [
                'Partner_Manage',
                'Partner',
                'Partner_Transactions',
                'Partner_Redemptions',
            ])
            ->delete();
    }
}
