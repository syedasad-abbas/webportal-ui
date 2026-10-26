<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('crm_demo_notes',fn(Blueprint $t)=>$t->json('continuation')->nullable()); }
    public function down(): void { Schema::table('crm_demo_notes',fn(Blueprint $t)=>$t->dropColumn('continuation')); }
};
