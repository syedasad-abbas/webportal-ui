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
    Schema::table('agent_time_logs', function (Blueprint $table) {
        // Rename Agent_id to user_id if not already renamed
        if (Schema::hasColumn('agent_time_logs', 'Agent_id')) {
            $table->renameColumn('Agent_id', 'user_id');
        }
    });

    Schema::table('agent_time_logs', function (Blueprint $table) {
        // Ensure user_id type matches users.id
        $table->unsignedBigInteger('user_id')->change();

        // Add foreign key with explicit name to avoid key conflict
        $table->foreign('user_id', 'fk_agent_time_logs_user_id')
              ->references('id')
              ->on('users')
              ->onDelete('cascade');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_time_logs', function (Blueprint $table) {
            
            //
        });
    }
};
