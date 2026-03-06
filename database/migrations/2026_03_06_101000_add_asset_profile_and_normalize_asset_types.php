<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddAssetProfileAndNormalizeAssetTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->json('asset_profile')->nullable()->after('notes');
        });

        DB::table('assets')
            ->where('asset_type', 'server')
            ->update([
                'asset_type' => 'application_server',
                'server_role' => 'application_server',
            ]);

        DB::table('assets')
            ->where('asset_type', 'database')
            ->update([
                'asset_type' => 'database_server',
                'server_role' => 'database_server',
            ]);

        DB::table('assets')
            ->where('asset_type', 'network')
            ->update(['asset_type' => 'network_peripheral']);

        DB::table('assets')
            ->whereIn('asset_type', ['endpoint', 'storage', 'other'])
            ->update(['asset_type' => 'etc']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('assets')
            ->where('asset_type', 'application_server')
            ->update(['asset_type' => 'server']);

        DB::table('assets')
            ->where('asset_type', 'database_server')
            ->update(['asset_type' => 'database']);

        DB::table('assets')
            ->where('asset_type', 'network_peripheral')
            ->update(['asset_type' => 'network']);

        DB::table('assets')
            ->where('asset_type', 'etc')
            ->update(['asset_type' => 'other']);

        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('asset_profile');
        });
    }
}

