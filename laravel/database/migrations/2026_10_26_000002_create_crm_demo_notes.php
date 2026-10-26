<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('crm_demo_notes',function(Blueprint $t){
            $t->uuid('call_uuid')->primary();
            $t->unsignedBigInteger('customer_id');
            $t->text('pain_quote')->nullable();
            $t->json('credit_report')->nullable();
            $t->string('provenance')->default('model_transcribed_quote');
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('crm_demo_notes'); }
};
