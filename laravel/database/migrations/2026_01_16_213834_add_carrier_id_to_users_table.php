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
            $table->unsignedBigInteger('carrier_id')->nullable()->after('is_active');

            // optional but recommended: add FK
            $table->foreign('carrier_id')->references('id')->on('carriers')->nullOnDelete();
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['carrier_id']);
            $table->dropColumn('carrier_id');
            //
        });
    }
};
