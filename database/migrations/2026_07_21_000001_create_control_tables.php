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
        // 1. Products
        Schema::create('control_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Product Versions
        Schema::create('control_product_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('control_products')->cascadeOnDelete();
            $table->string('version');
            $table->text('release_notes')->nullable();
            $table->string('checksum')->nullable();
            $table->string('download_path')->nullable();
            $table->date('release_date');
            $table->timestamps();
        });

        // 3. Tariff Plans
        Schema::create('control_tariff_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('control_products')->cascadeOnDelete();
            $table->string('name');
            $table->string('code'); // e.g. trial, standard, premium
            $table->integer('duration_days')->nullable(); // null = lifetime
            $table->integer('price'); // price in cents
            $table->string('currency', 3)->default('USD');
            $table->integer('max_users')->default(10);
            $table->integer('max_objects')->default(5);
            $table->json('features')->nullable(); // e.g. {"mobile_api": true, "reports": true}
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Clients
        Schema::create('control_clients', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('contact_name');
            $table->string('phone')->nullable();
            $table->string('telegram')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 5. Licenses
        Schema::create('control_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('control_clients')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('control_products')->cascadeOnDelete();
            $table->foreignId('tariff_plan_id')->constrained('control_tariff_plans')->cascadeOnDelete();
            $table->string('license_key')->unique();
            $table->string('status')->default('awaiting_activation'); // awaiting_activation, active, expired, suspended
            $table->date('starts_at');
            $table->date('expires_at')->nullable();
            $table->integer('activation_limit')->default(1);
            $table->integer('installations_count')->default(0);
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamps();
        });

        // 6. Installations
        Schema::create('control_installations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained('control_licenses')->cascadeOnDelete();
            $table->string('hardware_uuid')->unique();
            $table->string('domain')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // 7. Payments
        Schema::create('control_license_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained('control_licenses')->cascadeOnDelete();
            $table->date('payment_date');
            $table->integer('amount');
            $table->string('currency', 3)->default('USD');
            $table->string('payment_method'); // cash, bank, card
            $table->string('attachment_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 8. Client Requests (Purchase requests)
        Schema::create('control_client_requests', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('contact_name');
            $table->string('phone');
            $table->string('email');
            $table->foreignId('product_id')->constrained('control_products')->cascadeOnDelete();
            $table->foreignId('tariff_plan_id')->constrained('control_tariff_plans')->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending, contacted, approved, rejected
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 9. Audit Logs
        Schema::create('control_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained('control_licenses')->cascadeOnDelete();
            $table->string('action');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('performed_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('control_audit_logs');
        Schema::dropIfExists('control_client_requests');
        Schema::dropIfExists('control_license_payments');
        Schema::dropIfExists('control_installations');
        Schema::dropIfExists('control_licenses');
        Schema::dropIfExists('control_clients');
        Schema::dropIfExists('control_tariff_plans');
        Schema::dropIfExists('control_product_versions');
        Schema::dropIfExists('control_products');
    }
};
