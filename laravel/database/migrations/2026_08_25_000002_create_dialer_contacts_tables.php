<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dialer_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('phone', 40);
            $table->string('phone_normalized', 30);
            $table->string('email')->nullable();
            $table->boolean('is_flagged')->default(false);
            $table->json('labels')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'phone_normalized']);
            $table->index(['user_id', 'name']);
        });

        Schema::create('dialer_contact_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dialer_contact_id')->constrained('dialer_contacts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['dialer_contact_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dialer_contact_comments');
        Schema::dropIfExists('dialer_contacts');
    }
};
