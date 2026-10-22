<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('test_crm_customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('phone', 16)->unique();
            $table->string('email')->nullable();
            $table->string('company', 200)->nullable();
            $table->string('status', 20)->default('new');
            $table->decimal('value', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('test_crm_customers'); }
};
