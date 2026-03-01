<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetDiscoveryFindingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asset_discovery_findings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('run_id');
            $table->unsignedBigInteger('asset_id')->nullable();
            $table->string('fingerprint', 191)->index();
            $table->string('asset_name', 150);
            $table->string('asset_type', 50)->index();
            $table->string('ip_address', 100)->nullable();
            $table->string('hostname', 120)->nullable();
            $table->string('port', 50)->nullable();
            $table->string('environment', 50)->nullable();
            $table->string('finding_status', 30)->default('new')->index();
            $table->unsignedTinyInteger('confidence')->default(50);
            $table->longText('payload')->nullable();
            $table->timestamps();

            $table->foreign('run_id')->references('id')->on('asset_discovery_runs')->onDelete('cascade');
            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('set null');
            $table->index(['run_id', 'finding_status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('asset_discovery_findings');
    }
}

