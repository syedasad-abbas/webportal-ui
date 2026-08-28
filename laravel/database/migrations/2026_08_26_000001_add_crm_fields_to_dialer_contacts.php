<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dialer_contacts', function (Blueprint $table): void {
            $table->string('secondary_phone', 40)->nullable();
            $table->string('avatar_url', 2048)->nullable();
            $table->text('address')->nullable();
            $table->string('account_id', 80)->nullable()->index();
            $table->string('account_status', 80)->nullable();
            $table->date('customer_since')->nullable();
            $table->string('industry', 120)->nullable();
            $table->string('employees', 80)->nullable();
            $table->string('annual_revenue', 80)->nullable();
            $table->string('preferred_contact_time', 160)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('dialer_contacts', function (Blueprint $table): void {
            $table->dropIndex(['account_id']);
            $table->dropColumn([
                'secondary_phone',
                'avatar_url',
                'address',
                'account_id',
                'account_status',
                'customer_since',
                'industry',
                'employees',
                'annual_revenue',
                'preferred_contact_time',
            ]);
        });
    }
};
