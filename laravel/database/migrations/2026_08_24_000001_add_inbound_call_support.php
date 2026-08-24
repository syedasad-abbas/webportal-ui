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
        Schema::table('call_logs', function (Blueprint $table) {
            $table->string('direction')->default('outbound')->after('user_id');
            $table->index(['direction', 'status']);
        });

        Schema::create('inbound_rr_state', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('last_user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbound_rr_state');

        Schema::table('call_logs', function (Blueprint $table) {
            $table->dropIndex(['direction', 'status']);
            $table->dropColumn('direction');
        });
    }
};
