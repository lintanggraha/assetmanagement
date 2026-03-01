<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('asset_code', 40)->unique();
            $table->string('name', 150);
            $table->string('asset_type', 50)->index();
            $table->string('environment', 50)->default('production')->index();
            $table->string('criticality', 20)->default('medium')->index();
            $table->string('status', 30)->default('active')->index();
            $table->string('lifecycle_stage', 30)->default('operational')->index();
            $table->string('owner_name', 120)->nullable();
            $table->string('owner_email', 120)->nullable();
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->string('ip_address', 100)->nullable()->index();
            $table->string('hostname', 120)->nullable()->index();
            $table->string('port', 50)->nullable();
            $table->string('source', 30)->default('manual')->index();
            $table->unsignedTinyInteger('discovery_confidence')->default(100);
            $table->unsignedTinyInteger('risk_score')->default(0)->index();
            $table->string('tags', 255)->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('bank_id')->references('id')->on('bank')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->index(['user_id', 'asset_type', 'status']);
            $table->index(['user_id', 'criticality', 'risk_score']);
            $table->index(['user_id', 'source']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assets');
    }
}

