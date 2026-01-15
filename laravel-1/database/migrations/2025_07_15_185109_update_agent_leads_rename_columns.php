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
        Schema::table('agent_leads', function (Blueprint $table) {
            // Rename columns
            $table->renameColumn('agent_id', 'user_id');
            $table->renameColumn('contract_id', 'contract_date');
        });
    }
   

    /**
     * Reverse the migrations.
     */
       public function down(): void
    {
        Schema::table('agent_leads', function (Blueprint $table) {
            // Revert changes
            $table->renameColumn('user_id', 'agent_id');
            $table->renameColumn('contract_date', 'contract_id');
        });
    }
};
