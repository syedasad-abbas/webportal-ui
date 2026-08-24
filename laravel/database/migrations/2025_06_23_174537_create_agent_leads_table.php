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
        Schema::create('agent_leads', function (Blueprint $table) {
            $table->id();
             $table->foreignId('agent_id')->constrained('users')->onDelete('cascade');
    $table->string('lead_id');
    $table->string('contract_id');
    $table->date('client_payment_date')->nullable();
    $table->date('tpl_payment_date')->nullable();
    $table->date('agent_payment_date')->nullable();
    $table->enum('lead_status', ['Locked', 'Dropped', 'Confirmed', 'Received', 'Completed']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_leads');
    }
};
