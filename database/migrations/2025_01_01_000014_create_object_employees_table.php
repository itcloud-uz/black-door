<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Obyekt xodimlari jadvalini yaratish.
     */
    public function up(): void
    {
        Schema::create('object_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('object_id')->constrained('objects');
            $table->foreignId('user_id')->constrained('users');
            $table->string('position');
            $table->string('daily_rate_currency', 3)->nullable(); // App\Enums\Currency
            $table->bigInteger('daily_rate')->nullable(); // sent/tiyinda
            $table->string('monthly_rate_currency', 3)->nullable();
            $table->bigInteger('monthly_rate')->nullable();
            $table->date('hired_at');
            $table->text('permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('object_id');
            $table->index('user_id');
            $table->index('is_active');
        });
    }

    /**
     * Migratsiyani bekor qilish.
     */
    public function down(): void
    {
        Schema::dropIfExists('object_employees');
    }
};
