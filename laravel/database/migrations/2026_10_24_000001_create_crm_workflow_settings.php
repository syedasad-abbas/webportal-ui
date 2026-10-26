<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('crm_workflow_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->json('fields')->nullable();
            $table->json('cards')->nullable();
            $table->json('demo_report')->nullable();
            $table->text('api_url')->nullable();
            $table->text('api_token')->nullable();
            $table->timestamps();
        });
        Schema::table('test_crm_customers', fn (Blueprint $table) => $table->json('intake')->nullable());
    }
    public function down(): void {
        Schema::table('test_crm_customers', fn (Blueprint $table) => $table->dropColumn('intake'));
        Schema::dropIfExists('crm_workflow_settings');
    }
};
