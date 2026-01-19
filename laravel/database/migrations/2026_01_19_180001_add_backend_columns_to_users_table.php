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
            $table->string('full_name')->nullable()->after('internal_name');
            $table->string('password_hash')->nullable()->after('password');
            $table->string('role')->default('user')->after('password_hash');
            $table->unsignedBigInteger('group_id')->nullable()->after('role');
            $table->boolean('recording_enabled')->default(true)->after('group_id');
            $table->jsonb('permissions')->nullable()->after('recording_enabled');

            $table->foreign('group_id')->references('id')->on('groups')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn([
                'full_name',
                'password_hash',
                'role',
                'group_id',
                'recording_enabled',
                'permissions'
            ]);
        });
    }
};
