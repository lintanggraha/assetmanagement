<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetDiscoveryRunsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asset_discovery_runs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('run_uuid', 64)->unique();
            $table->string('scope', 150)->nullable();
            $table->string('source_mode', 30)->default('catalog_sync');
            $table->string('status', 30)->default('queued')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('total_found')->default(0);
            $table->unsignedInteger('total_new')->default(0);
            $table->unsignedInteger('total_updated')->default(0);
            $table->unsignedInteger('total_matched')->default(0);
            $table->text('summary')->nullable();
            $table->longText('input_payload')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'status', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('asset_discovery_runs');
    }
}

