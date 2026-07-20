<?php

declare(strict_types=1);

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
        Schema::create('client_licenses', function (Blueprint $table) {
            $table->id();
            $table->string('license_key');
            $table->string('tariff_plan_code');
            $table->string('client_name');
            $table->date('starts_at');
            $table->date('expires_at')->nullable();
            $table->integer('max_users');
            $table->integer('max_objects');
            $table->json('features')->nullable();
            $table->string('installation_uuid');
            $table->string('status');
            $table->text('token_payload'); // Imzolangan litsenziya JSON payloadi
            $table->text('token_signature'); // Base64 imzo
            $table->timestamp('last_successful_heartbeat_at');
            $table->boolean('is_read_only_grace')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_licenses');
    }
};
