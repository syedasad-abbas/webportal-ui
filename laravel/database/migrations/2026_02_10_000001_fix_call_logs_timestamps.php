<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE call_logs ALTER COLUMN created_at SET DEFAULT NOW()');
        DB::statement('ALTER TABLE call_logs ALTER COLUMN updated_at SET DEFAULT NOW()');

        DB::statement("
            UPDATE call_logs
               SET created_at = COALESCE(created_at, ended_at, connected_at, NOW()),
                   updated_at = COALESCE(updated_at, ended_at, connected_at, NOW())
             WHERE created_at IS NULL OR updated_at IS NULL
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE call_logs ALTER COLUMN created_at DROP DEFAULT');
        DB::statement('ALTER TABLE call_logs ALTER COLUMN updated_at DROP DEFAULT');
    }
};
