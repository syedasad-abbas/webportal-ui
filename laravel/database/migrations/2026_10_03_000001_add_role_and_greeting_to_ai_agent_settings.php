<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agent_settings', function (Blueprint $table): void {
            // The agent is no longer locked to appointment booking. `role` is
            // the duty it performs, `greeting` is the line it opens with, and
            // the existing `goal` column is now the conversational objective.
            // All three are free text; only their length is bounded.
            if (! Schema::hasColumn('ai_agent_settings', 'role')) {
                $table->text('role')->default('');
            }

            if (! Schema::hasColumn('ai_agent_settings', 'greeting')) {
                $table->text('greeting')->default('');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_agent_settings', function (Blueprint $table): void {
            $columns = array_values(array_filter(
                ['role', 'greeting'],
                static fn (string $column): bool => Schema::hasColumn('ai_agent_settings', $column)
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
