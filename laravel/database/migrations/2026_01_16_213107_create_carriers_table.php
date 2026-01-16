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
        Schema::create('carriers', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name')->unique();

            $table->string('default_caller_id')->nullable();
            $table->boolean('caller_id_required')->default(false);

            $table->string('sip_domain');
            $table->unsignedInteger('sip_port')->nullable();

            $table->string('transport')->default('udp'); // udp/tcp/tls

            // ✅ outbound proxy
            $table->string('outbound_proxy')->nullable();

            $table->boolean('registration_required')->default(false);
            $table->string('registration_username')->nullable();
            $table->string('registration_password')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carriers');
    }
};
