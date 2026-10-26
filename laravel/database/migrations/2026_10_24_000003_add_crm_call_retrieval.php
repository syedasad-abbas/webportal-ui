<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('crm_workflow_settings',fn(Blueprint $t)=>$t->json('retrieval')->nullable());
        Schema::table('test_crm_customers',fn(Blueprint $t)=>$t->json('review')->nullable());
        Schema::create('crm_call_customers',function(Blueprint $t){
            $t->uuid('call_uuid')->primary(); $t->unsignedBigInteger('customer_id');
            $t->unsignedBigInteger('verified_by'); $t->timestamp('verified_at');
        });
    }
    public function down(): void {
        Schema::dropIfExists('crm_call_customers');
        Schema::table('test_crm_customers',fn(Blueprint $t)=>$t->dropColumn('review'));
        Schema::table('crm_workflow_settings',fn(Blueprint $t)=>$t->dropColumn('retrieval'));
    }
};
