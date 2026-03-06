<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCmdbFieldsToAssetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('host_type', 30)->nullable()->after('asset_type')->index();
            $table->string('server_role', 30)->nullable()->after('host_type')->index();
            $table->string('operating_system', 120)->nullable()->after('hostname');
            $table->string('os_version', 120)->nullable()->after('operating_system');
            $table->date('os_eol_date')->nullable()->after('os_version')->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex(['host_type']);
            $table->dropIndex(['server_role']);
            $table->dropIndex(['os_eol_date']);
            $table->dropColumn([
                'host_type',
                'server_role',
                'operating_system',
                'os_version',
                'os_eol_date',
            ]);
        });
    }
}

