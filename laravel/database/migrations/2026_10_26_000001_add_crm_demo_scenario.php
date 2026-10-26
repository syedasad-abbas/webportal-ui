<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('crm_workflow_settings',function(Blueprint $t){$t->boolean('demo_enabled')->default(false);$t->json('demo_scenario')->nullable();});
        Schema::table('crm_call_customers',fn(Blueprint $t)=>$t->boolean('is_demo')->default(false));
    }
    public function down(): void {
        Schema::table('crm_workflow_settings',fn(Blueprint $t)=>$t->dropColumn(['demo_enabled','demo_scenario']));
        Schema::table('crm_call_customers',fn(Blueprint $t)=>$t->dropColumn('is_demo'));
    }
};
