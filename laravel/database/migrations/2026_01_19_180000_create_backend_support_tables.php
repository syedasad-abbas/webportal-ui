<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->jsonb('permissions')->nullable();
            $table->timestamps();
        });

        Schema::create('carrier_prefixes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('carrier_id');
            $table->string('prefix')->nullable();
            $table->string('caller_id')->nullable();
            $table->timestamps();

            $table->foreign('carrier_id')->references('id')->on('carriers')->cascadeOnDelete();
        });

        Schema::create('call_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('destination')->nullable();
            $table->string('caller_id')->nullable();
            $table->string('status')->default('queued');
            $table->string('recording_path')->nullable();
            $table->uuid('call_uuid')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->integer('sip_status')->nullable();
            $table->string('sip_reason')->nullable();
            $table->string('hangup_cause')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'call_uuid']);
        });

        Schema::create('password_reset_otps', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->unsignedInteger('attempt_count')->default(0);
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'consumed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_reset_otps');
        Schema::dropIfExists('call_logs');
        Schema::dropIfExists('carrier_prefixes');
        Schema::dropIfExists('groups');
    }
};
