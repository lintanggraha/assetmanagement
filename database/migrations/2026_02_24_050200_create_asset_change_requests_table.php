<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetChangeRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asset_change_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('requester_user_id');
            $table->unsignedBigInteger('approver_user_id')->nullable();
            $table->string('change_type', 60)->index();
            $table->string('status', 30)->default('pending')->index();
            $table->text('reason')->nullable();
            $table->longText('current_snapshot')->nullable();
            $table->longText('requested_payload')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            $table->foreign('requester_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approver_user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['asset_id', 'status']);
            $table->index(['requester_user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('asset_change_requests');
    }
}

