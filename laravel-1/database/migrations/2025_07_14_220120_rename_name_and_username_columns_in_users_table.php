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
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('Agent_id', 'user_id');
            $table->renameColumn('name', 'external_name');
            $table->renameColumn('username', 'internal_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('user_id', 'Agent_id');
            $table->renameColumn('external_name', 'name');
            $table->renameColumn('internal_name', 'username');
        });
    }
};
