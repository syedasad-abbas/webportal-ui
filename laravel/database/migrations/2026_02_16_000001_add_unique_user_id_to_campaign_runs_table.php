<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('campaign_runs')) {
            return;
        }

        DB::statement(<<<'SQL'
            DELETE FROM campaign_runs t
             WHERE t.id IN (
                SELECT id
                  FROM (
                    SELECT id,
                           ROW_NUMBER() OVER (
                             PARTITION BY user_id
                             ORDER BY updated_at DESC NULLS LAST, created_at DESC NULLS LAST, id DESC
                           ) AS row_num
                      FROM campaign_runs
                  ) ranked
                 WHERE ranked.row_num > 1
             )
        SQL);

        DB::statement(<<<'SQL'
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1
                      FROM pg_constraint
                     WHERE conname = 'campaign_runs_user_id_unique'
                ) THEN
                    ALTER TABLE campaign_runs
                    ADD CONSTRAINT campaign_runs_user_id_unique UNIQUE (user_id);
                END IF;
            END
            $$;
        SQL);
    }

    public function down(): void
    {
        if (!Schema::hasTable('campaign_runs')) {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE campaign_runs
            DROP CONSTRAINT IF EXISTS campaign_runs_user_id_unique
        SQL);
    }
};
