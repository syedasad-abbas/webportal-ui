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
        Schema::create('agent_time_logs', function (Blueprint $table) {
            $table->id();
              $table->unsignedBigInteger('user_id');
            $table->date('log_date');
            $table->integer('login_minutes')->default(0);
            $table->integer('pause_minutes')->default(0);
            $table->integer('invoice_minutes')->default(0);
            $table->timestamps();

             $table->unique(['user_id', 'log_date']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_time_logs');
    }
};
