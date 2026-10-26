<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agent_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('ai_agent_settings', 'sales_script')) {
                $table->longText('sales_script')->nullable()->after('knowledge_instructions');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_agent_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('ai_agent_settings', 'sales_script')) {
                $table->dropColumn('sales_script');
            }
        });
    }
};
