<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('dialer_provider')->default('freeswitch');
        });

        Schema::create('vicidial_credentials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('agent_user')->unique();
            $table->string('sip_username');
            $table->string('sip_password');
            $table->string('crm_agent_id')->nullable();
            $table->string('crm_team_id')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vicidial_credentials');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('dialer_provider');
        });
    }
};