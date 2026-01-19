<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('users', 'permissions')) {
            DB::statement('ALTER TABLE users RENAME COLUMN permissions TO backend_permissions');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'backend_permissions')) {
            DB::statement('ALTER TABLE users RENAME COLUMN backend_permissions TO permissions');
        }
    }
};
