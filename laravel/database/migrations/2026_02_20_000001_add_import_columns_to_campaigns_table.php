<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (! Schema::hasColumn('campaigns', 'import_status')) {
                $table->string('import_status')->default('pending')->after('file_path');
            }

            if (! Schema::hasColumn('campaigns', 'import_started_at')) {
                $table->timestamp('import_started_at')->nullable()->after('import_status');
            }

            if (! Schema::hasColumn('campaigns', 'import_completed_at')) {
                $table->timestamp('import_completed_at')->nullable()->after('import_started_at');
            }

            if (! Schema::hasColumn('campaigns', 'total_rows')) {
                $table->unsignedInteger('total_rows')->default(0)->after('import_completed_at');
            }

            if (! Schema::hasColumn('campaigns', 'imported_rows')) {
                $table->unsignedInteger('imported_rows')->default(0)->after('total_rows');
            }

            if (! Schema::hasColumn('campaigns', 'import_error')) {
                $table->text('import_error')->nullable()->after('imported_rows');
            }
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('campaigns', 'import_status')) {
                $table->dropColumn('import_status');
            }
            if (Schema::hasColumn('campaigns', 'import_started_at')) {
                $table->dropColumn('import_started_at');
            }
            if (Schema::hasColumn('campaigns', 'import_completed_at')) {
                $table->dropColumn('import_completed_at');
            }
            if (Schema::hasColumn('campaigns', 'total_rows')) {
                $table->dropColumn('total_rows');
            }
            if (Schema::hasColumn('campaigns', 'imported_rows')) {
                $table->dropColumn('imported_rows');
            }
            if (Schema::hasColumn('campaigns', 'import_error')) {
                $table->dropColumn('import_error');
            }
        });
    }
};
