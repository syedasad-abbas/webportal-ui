<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE users ALTER COLUMN external_name DROP NOT NULL');
        DB::statement('ALTER TABLE users ALTER COLUMN internal_name DROP NOT NULL');
        DB::statement('ALTER TABLE users ALTER COLUMN password DROP NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE users ALTER COLUMN external_name SET NOT NULL');
        DB::statement('ALTER TABLE users ALTER COLUMN internal_name SET NOT NULL');
        DB::statement('ALTER TABLE users ALTER COLUMN password SET NOT NULL');
    }
};
